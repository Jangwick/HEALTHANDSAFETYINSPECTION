<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;

class AuthenticationFlowTest extends TestCase
{
    private PDO $db;
    private string $baseUrl;
    private string $token;

    protected function setUp(): void
    {
        $this->db = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS']
        );
        
        $this->baseUrl = 'http://localhost/HEALTHANDSAFETYINSPECTION/public/api/v1';
    }

    public function testCompleteAuthenticationFlow(): void
    {
        // Step 1: Register new user
        $registerData = [
            'email' => 'flowtest@example.com',
            'password' => 'SecurePass123!',
            'full_name' => 'Flow Test User',
            'role_id' => 4,
            'contact_number' => '+639171234567'
        ];

        $registerResponse = $this->makeRequest('POST', '/auth/register', $registerData);
        
        $this->assertEquals(201, $registerResponse['http_code']);
        $this->assertTrue($registerResponse['body']['success']);

        // Step 2: Login with registered credentials
        $loginData = [
            'email' => 'flowtest@example.com',
            'password' => 'SecurePass123!'
        ];

        $loginResponse = $this->makeRequest('POST', '/auth/login', $loginData);
        
        $this->assertEquals(200, $loginResponse['http_code']);
        $this->assertTrue($loginResponse['body']['success']);
        $this->assertArrayHasKey('token', $loginResponse['body']['data']);

        $this->token = $loginResponse['body']['data']['token'];

        // Step 3: Access protected endpoint with token
        $meResponse = $this->makeRequest('GET', '/auth/me', null, [
            'Authorization: Bearer ' . $this->token
        ]);

        $this->assertEquals(200, $meResponse['http_code']);
        $this->assertTrue($meResponse['body']['success']);
        $this->assertEquals('flowtest@example.com', $meResponse['body']['data']['email']);

        // Step 4: Logout
        $logoutResponse = $this->makeRequest('POST', '/auth/logout', null, [
            'Authorization: Bearer ' . $this->token
        ]);

        $this->assertEquals(200, $logoutResponse['http_code']);
        $this->assertTrue($logoutResponse['body']['success']);
    }

    public function testUnauthorizedAccessWithoutToken(): void
    {
        $response = $this->makeRequest('GET', '/auth/me');

        $this->assertEquals(401, $response['http_code']);
        $this->assertFalse($response['body']['success']);
    }

    private function makeRequest(string $method, string $endpoint, ?array $data = null, array $headers = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $defaultHeaders = ['Content-Type: application/json'];
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_CUSTOMREQUEST => $method
        ]);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'body' => json_decode($response, true)
        ];
    }

    protected function tearDown(): void
    {
        $this->db->exec("DELETE FROM users WHERE email = 'flowtest@example.com'");
    }
}
