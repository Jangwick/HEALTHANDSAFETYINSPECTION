<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;
use PDO;

class AuthServiceTest extends TestCase
{
    private PDO $db;
    private AuthService $authService;

    protected function setUp(): void
    {
        // Create test database connection
        $this->db = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS']
        );
        
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->authService = new AuthService($this->db);
    }

    public function testRegisterUser(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'full_name' => 'Test User',
            'role_id' => 4,
            'contact_number' => '+639171234567'
        ];

        $result = $this->authService->register($userData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    public function testRegisterDuplicateEmail(): void
    {
        $userData = [
            'email' => 'duplicate@example.com',
            'password' => 'SecurePass123!',
            'full_name' => 'Test User',
            'role_id' => 4
        ];

        // Register first time
        $this->authService->register($userData);

        // Try to register again with same email
        $result = $this->authService->register($userData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['error']);
    }

    public function testLoginWithValidCredentials(): void
    {
        // Register user first
        $this->authService->register([
            'email' => 'login@example.com',
            'password' => 'SecurePass123!',
            'full_name' => 'Login Test',
            'role_id' => 4
        ]);

        // Attempt login
        $result = $this->authService->login('login@example.com', 'SecurePass123!');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
    }

    public function testLoginWithInvalidPassword(): void
    {
        // Register user first
        $this->authService->register([
            'email' => 'wrongpass@example.com',
            'password' => 'SecurePass123!',
            'full_name' => 'Wrong Pass Test',
            'role_id' => 4
        ]);

        // Attempt login with wrong password
        $result = $this->authService->login('wrongpass@example.com', 'WrongPassword');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid', $result['error']);
    }

    public function testLoginWithNonExistentEmail(): void
    {
        $result = $this->authService->login('nonexistent@example.com', 'SomePassword');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid', $result['error']);
    }

    public function testAccountLockoutAfterFailedAttempts(): void
    {
        // Register user
        $this->authService->register([
            'email' => 'lockout@example.com',
            'password' => 'SecurePass123!',
            'full_name' => 'Lockout Test',
            'role_id' => 4
        ]);

        // Attempt login 5 times with wrong password
        for ($i = 0; $i < 5; $i++) {
            $this->authService->login('lockout@example.com', 'WrongPassword');
        }

        // 6th attempt should be locked
        $result = $this->authService->login('lockout@example.com', 'SecurePass123!');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('locked', strtolower($result['error']));
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->db->exec("DELETE FROM users WHERE email LIKE '%@example.com'");
        $this->db->exec("DELETE FROM login_attempts WHERE 1=1");
    }
}
