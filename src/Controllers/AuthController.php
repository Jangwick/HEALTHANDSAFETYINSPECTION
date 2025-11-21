<?php

declare(strict_types=1);

namespace HealthSafety\Controllers;

use HealthSafety\Services\AuthService;
use HealthSafety\Utils\JWTHandler;
use HealthSafety\Utils\Validator;
use HealthSafety\Utils\Response;

class AuthController
{
    private AuthService $authService;
    private JWTHandler $jwtHandler;
    private Validator $validator;

    public function __construct(AuthService $authService, JWTHandler $jwtHandler, Validator $validator)
    {
        $this->authService = $authService;
        $this->jwtHandler = $jwtHandler;
        $this->validator = $validator;
    }

    /**
     * Login endpoint
     */
    public function login(array $data): void
    {
        // Validate input
        $rules = [
            'username' => ['required', 'string'],
            'password' => ['required', 'string']
        ];

        $errors = $this->validator->validate($data, $rules);
        
        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        // Check rate limiting
        if ($this->authService->isAccountLocked($data['username'])) {
            Response::error(
                'ACCOUNT_LOCKED', 
                'Too many failed attempts. Please try again later.',
                null,
                429
            );
            return;
        }

        // Authenticate user
        $user = $this->authService->authenticate($data['username'], $data['password']);

        if (!$user) {
            $this->authService->recordFailedAttempt(
                $data['username'], 
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            );
            
            Response::error('INVALID_CREDENTIALS', 'Invalid username or password', null, 401);
            return;
        }

        // Check if account is active
        if ($user['status'] !== 'active') {
            Response::error('ACCOUNT_INACTIVE', 'Account is not active', null, 403);
            return;
        }

        // Generate JWT token
        $token = $this->jwtHandler->generateToken([
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]);

        // Create session
        $sessionId = $this->authService->createSession(
            $user['user_id'],
            $token,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );

        // Update last login
        $this->authService->updateLastLogin(
            $user['user_id'], 
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        );

        // Log successful login
        $this->authService->logAudit($user['user_id'], 'login', 'auth');

        // Return response
        Response::success([
            'user' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
                'permissions' => $user['permissions']
            ],
            'token' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'expires_at' => date('c', time() + 3600)
            ],
            'session_id' => $sessionId
        ]);
    }

    /**
     * Register new user
     */
    public function register(array $data): void
    {
        // Validate input
        $rules = [
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'phone' => ['string']
        ];

        $errors = $this->validator->validate($data, $rules);
        
        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        try {
            $user = $this->authService->registerUser($data);
            
            Response::success([
                'user' => $user,
                'message' => 'Registration successful. Please wait for account activation.'
            ], 201);
        } catch (\Exception $e) {
            Response::error('REGISTRATION_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * Logout endpoint
     */
    public function logout(): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $this->authService->revokeSession($token);
            
            if (isset($_SESSION['user_id'])) {
                $this->authService->logAudit($_SESSION['user_id'], 'logout', 'auth');
            }
        }

        session_destroy();
        Response::success(['message' => 'Logged out successfully']);
    }

    /**
     * Get current user profile
     */
    public function me(): void
    {
        if (!isset($_SESSION['user_id'])) {
            Response::error('UNAUTHORIZED', 'Not authenticated', null, 401);
            return;
        }

        try {
            $user = $this->authService->getUserById($_SESSION['user_id']);
            Response::success(['user' => $user]);
        } catch (\Exception $e) {
            Response::error('USER_NOT_FOUND', $e->getMessage(), null, 404);
        }
    }

    /**
     * Forgot password - send reset token
     */
    public function forgotPassword(array $data): void
    {
        $rules = [
            'email' => ['required', 'email']
        ];

        $errors = $this->validator->validate($data, $rules);
        
        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid email address', $errors, 400);
            return;
        }

        $token = $this->authService->generatePasswordResetToken($data['email']);

        // Always return success to prevent email enumeration
        Response::success([
            'message' => 'If the email exists, a password reset link has been sent.',
            // In production, send email with token. For dev/testing:
            'reset_token' => $token // Remove this in production
        ]);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(array $data): void
    {
        $rules = [
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8']
        ];

        $errors = $this->validator->validate($data, $rules);
        
        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        $success = $this->authService->resetPassword($data['token'], $data['password']);

        if ($success) {
            Response::success(['message' => 'Password reset successful']);
        } else {
            Response::error('INVALID_TOKEN', 'Invalid or expired reset token', null, 400);
        }
    }

    /**
     * Change password (authenticated users)
     */
    public function changePassword(array $data): void
    {
        if (!isset($_SESSION['user_id'])) {
            Response::error('UNAUTHORIZED', 'Not authenticated', null, 401);
            return;
        }

        $rules = [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8']
        ];

        $errors = $this->validator->validate($data, $rules);
        
        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        // Verify current password
        $user = $this->authService->getUserById($_SESSION['user_id']);
        $authenticated = $this->authService->authenticate($user['username'], $data['current_password']);

        if (!$authenticated) {
            Response::error('INVALID_PASSWORD', 'Current password is incorrect', null, 401);
            return;
        }

        // Update password
        $passwordHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        
        global $pdo;
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id');
        $stmt->execute([
            'password_hash' => $passwordHash,
            'user_id' => $_SESSION['user_id']
        ]);

        $this->authService->logAudit($_SESSION['user_id'], 'change_password', 'auth');

        Response::success(['message' => 'Password changed successfully']);
    }
}
