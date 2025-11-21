<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;
use HealthSafety\Utils\Logger;

class AuthService
{
    private PDO $pdo;
    private Logger $logger;
    private int $maxFailedAttempts = 5;
    private int $lockoutMinutes = 30;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Authenticate user with username/email and password
     */
    public function authenticate(string $username, string $password): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                u.user_id,
                u.username,
                u.email,
                u.password_hash,
                u.first_name,
                u.last_name,
                u.phone,
                u.profile_photo_url,
                u.status,
                r.role_name as role,
                r.role_id
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id 
                AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE (u.username = :username OR u.email = :username)
            AND u.status = "active"
            LIMIT 1
        ');
        
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Get user permissions
        $user['permissions'] = $this->getUserPermissions($user['user_id']);

        // Remove password hash from result
        unset($user['password_hash']);

        return $user;
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT p.permission_key
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE ur.user_id = :user_id
            AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
            ORDER BY p.permission_key
        ');
        
        $stmt->execute(['user_id' => $userId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $permissions;
    }

    /**
     * Create a new user session
     */
    public function createSession(int $userId, string $token, string $ipAddress, string $userAgent): string
    {
        $sessionId = 'sess_' . bin2hex(random_bytes(16));
        
        $stmt = $this->pdo->prepare('
            INSERT INTO user_sessions (
                session_id, user_id, session_token, jwt_token, 
                ip_address, user_agent, expires_at, last_activity_at
            ) VALUES (
                :session_id, :user_id, :session_token, :jwt_token,
                :ip_address, :user_agent, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW()
            )
        ');

        $stmt->execute([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'session_token' => hash('sha256', $sessionId),
            'jwt_token' => hash('sha256', $token),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);

        return $sessionId;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId, string $ipAddress): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE users 
            SET last_login_at = NOW(),
                last_login_ip = :ip_address,
                failed_login_attempts = 0,
                account_locked_until = NULL
            WHERE user_id = :user_id
        ');

        $stmt->execute([
            'user_id' => $userId,
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Check if account is locked due to failed attempts
     */
    public function isAccountLocked(string $username): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                failed_login_attempts,
                account_locked_until
            FROM users
            WHERE username = :username OR email = :username
            LIMIT 1
        ');

        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // Check if account is locked and lockout period hasn't expired
        if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
            return true;
        }

        return false;
    }

    /**
     * Record failed login attempt
     */
    public function recordFailedAttempt(string $username, string $ipAddress): void
    {
        // Log the attempt
        $stmt = $this->pdo->prepare('
            INSERT INTO login_attempts (
                username_or_email, ip_address, user_agent, 
                success, failure_reason, attempted_at
            ) VALUES (
                :username, :ip_address, :user_agent,
                0, "Invalid credentials", NOW()
            )
        ');

        $stmt->execute([
            'username' => $username,
            'ip_address' => $ipAddress,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);

        // Increment failed attempts
        $stmt = $this->pdo->prepare('
            UPDATE users 
            SET failed_login_attempts = failed_login_attempts + 1,
                account_locked_until = CASE 
                    WHEN failed_login_attempts + 1 >= :max_attempts 
                    THEN DATE_ADD(NOW(), INTERVAL :lockout_minutes MINUTE)
                    ELSE NULL
                END
            WHERE username = :username OR email = :username
        ');

        $stmt->execute([
            'max_attempts' => $this->maxFailedAttempts,
            'lockout_minutes' => $this->lockoutMinutes,
            'username' => $username
        ]);
    }

    /**
     * Log audit trail
     */
    public function logAudit(
        int $userId, 
        string $action, 
        string $module, 
        ?string $recordType = null, 
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO audit_logs (
                user_id, action, module, record_type, record_id,
                old_values, new_values, ip_address, user_agent, timestamp
            ) VALUES (
                :user_id, :action, :module, :record_type, :record_id,
                :old_values, :new_values, :ip_address, :user_agent, NOW()
            )
        ');

        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'record_type' => $recordType,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);

        $this->logger->info("Audit: User $userId performed $action on $module", [
            'record_type' => $recordType,
            'record_id' => $recordId
        ]);
    }

    /**
     * Revoke session (logout)
     */
    public function revokeSession(string $token): bool
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM user_sessions 
            WHERE jwt_token = :token
        ');

        $stmt->execute(['token' => hash('sha256', $token)]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Register new user
     */
    public function registerUser(array $data): array
    {
        // Check if username or email already exists
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM users 
            WHERE username = :username OR email = :email
        ');
        
        $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email']
        ]);

        if ($stmt->fetchColumn() > 0) {
            throw new \Exception('Username or email already exists');
        }

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        // Insert user
        $stmt = $this->pdo->prepare('
            INSERT INTO users (
                username, email, password_hash, first_name, last_name,
                phone, status, created_at
            ) VALUES (
                :username, :email, :password_hash, :first_name, :last_name,
                :phone, "pending_verification", NOW()
            )
        ');

        $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null
        ]);

        $userId = (int)$this->pdo->lastInsertId();

        // Assign default role (public or establishment_owner)
        $defaultRole = $data['role'] ?? 'public';
        $this->assignRole($userId, $defaultRole);

        // Get created user
        return $this->getUserById($userId);
    }

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, string $roleName, ?int $assignedBy = null): void
    {
        // Get role ID
        $stmt = $this->pdo->prepare('SELECT role_id FROM roles WHERE role_name = :role_name');
        $stmt->execute(['role_name' => $roleName]);
        $roleId = $stmt->fetchColumn();

        if (!$roleId) {
            throw new \Exception("Role '$roleName' not found");
        }

        // Assign role
        $stmt = $this->pdo->prepare('
            INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at)
            VALUES (:user_id, :role_id, :assigned_by, NOW())
            ON DUPLICATE KEY UPDATE assigned_at = NOW()
        ');

        $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId,
            'assigned_by' => $assignedBy
        ]);
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                u.user_id,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                u.phone,
                u.profile_photo_url,
                u.status,
                u.last_login_at,
                r.role_name as role
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE u.user_id = :user_id
            LIMIT 1
        ');

        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new \Exception('User not found');
        }

        $user['permissions'] = $this->getUserPermissions($userId);

        return $user;
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken(string $email): ?string
    {
        // Check if user exists
        $stmt = $this->pdo->prepare('SELECT user_id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            return null;
        }

        // Generate token
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        // Store reset token
        $stmt = $this->pdo->prepare('
            INSERT INTO password_resets (
                user_id, reset_token, expires_at, ip_address, created_at
            ) VALUES (
                :user_id, :reset_token, DATE_ADD(NOW(), INTERVAL 1 HOUR), 
                :ip_address, NOW()
            )
        ');

        $stmt->execute([
            'user_id' => $userId,
            'reset_token' => $hashedToken,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);

        return $token;
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $hashedToken = hash('sha256', $token);

        // Verify token
        $stmt = $this->pdo->prepare('
            SELECT pr.user_id 
            FROM password_resets pr
            WHERE pr.reset_token = :token
            AND pr.expires_at > NOW()
            AND pr.used_at IS NULL
            ORDER BY pr.created_at DESC
            LIMIT 1
        ');

        $stmt->execute(['token' => $hashedToken]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            return false;
        }

        // Update password
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $stmt = $this->pdo->prepare('
            UPDATE users 
            SET password_hash = :password_hash,
                updated_at = NOW()
            WHERE user_id = :user_id
        ');

        $stmt->execute([
            'password_hash' => $passwordHash,
            'user_id' => $userId
        ]);

        // Mark token as used
        $stmt = $this->pdo->prepare('
            UPDATE password_resets 
            SET used_at = NOW()
            WHERE reset_token = :token
        ');

        $stmt->execute(['token' => $hashedToken]);

        return true;
    }
}
