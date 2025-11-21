<?php

declare(strict_types=1);

namespace HealthSafety\Middleware;

use HealthSafety\Utils\JWTHandler;
use HealthSafety\Utils\Response;
use HealthSafety\Utils\Logger;

/**
 * Authentication Middleware
 * Health & Safety Inspections System
 */
class AuthMiddleware
{
    private JWTHandler $jwtHandler;
    private \PDO $pdo;
    
    public function __construct()
    {
        $this->jwtHandler = new JWTHandler();
        $this->pdo = \Database::getConnection();
    }
    
    /**
     * Verify authentication and load user context
     */
    public function handle(): void
    {
        // Get token from Authorization header
        $token = JWTHandler::getTokenFromHeader();
        
        if (!$token) {
            Response::unauthorized('Missing authorization token');
        }
        
        // Verify JWT token
        $payload = $this->jwtHandler->verifyToken($token);
        
        if (!$payload) {
            Logger::security('Invalid JWT token attempt', 'WARNING', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            Response::unauthorized('Invalid or expired token');
        }
        
        // Check if session is still active
        if (!$this->isSessionActive($token)) {
            Response::unauthorized('Session expired or revoked');
        }
        
        // Load user data
        $user = $this->getUserById($payload['user_id']);
        
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        // Check if user account is active
        if ($user['status'] !== 'active') {
            Response::forbidden('Account is not active');
        }
        
        // Set user data in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['permissions'] = $user['permissions'];
        
        // Update last activity
        $this->updateLastActivity($token);
    }
    
    /**
     * Check if session is active
     */
    private function isSessionActive(string $token): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT session_id 
            FROM user_sessions 
            WHERE jwt_token = :token 
            AND expires_at > NOW()
        ');
        
        $stmt->execute(['token' => hash('sha256', $token)]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get user by ID with role and permissions
     */
    private function getUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                u.user_id,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                u.status,
                r.role_name as role,
                GROUP_CONCAT(DISTINCT p.permission_key) as permissions
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
            LEFT JOIN roles r ON ur.role_id = r.role_id
            LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
            LEFT JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE u.user_id = :user_id
            GROUP BY u.user_id
        ');
        
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($user) {
            $user['permissions'] = $user['permissions'] ? explode(',', $user['permissions']) : [];
        }
        
        return $user ?: null;
    }
    
    /**
     * Update last activity timestamp
     */
    private function updateLastActivity(string $token): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE user_sessions 
            SET last_activity_at = NOW() 
            WHERE jwt_token = :token
        ');
        
        $stmt->execute(['token' => hash('sha256', $token)]);
    }
    
    /**
     * Get current authenticated user
     */
    public static function getCurrentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'full_name' => $_SESSION['full_name'],
            'permissions' => $_SESSION['permissions'] ?? [],
        ];
    }
}
