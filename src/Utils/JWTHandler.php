<?php

declare(strict_types=1);

namespace HealthSafety\Utils;

/**
 * JWT Handler Utility
 * Health & Safety Inspections System
 */
class JWTHandler
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $expirationTime;
    private string $issuer;
    private string $audience;
    
    public function __construct()
    {
        $config = require ROOT_PATH . '/config/jwt.php';
        
        $this->secretKey = $config['secret_key'];
        $this->algorithm = $config['algorithm'];
        $this->expirationTime = $config['expiration'];
        $this->issuer = $config['issuer'];
        $this->audience = $config['audience'];
    }
    
    /**
     * Generate JWT token
     */
    public function generateToken(array $data, ?int $expiration = null): string
    {
        $issuedAt = time();
        $expire = $issuedAt + ($expiration ?? $this->expirationTime);
        
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => $this->algorithm,
        ]));
        
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $data,
        ]));
        
        $signature = $this->sign("$header.$payload");
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Verify and decode JWT token
     */
    public function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        [$header, $payload, $signature] = $parts;
        
        // Verify signature
        $expectedSignature = $this->sign("$header.$payload");
        
        if (!hash_equals($expectedSignature, $signature)) {
            Logger::security('Invalid JWT signature', 'WARNING', ['token' => substr($token, 0, 20) . '...']);
            return null;
        }
        
        // Decode payload
        $payloadData = json_decode($this->base64UrlDecode($payload), true);
        
        if (!$payloadData) {
            return null;
        }
        
        // Check expiration
        if (isset($payloadData['exp']) && time() >= $payloadData['exp']) {
            return null;
        }
        
        // Check issuer
        if (isset($payloadData['iss']) && $payloadData['iss'] !== $this->issuer) {
            return null;
        }
        
        return $payloadData['data'] ?? null;
    }
    
    /**
     * Decode token without verification (for debugging)
     */
    public function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        
        return $payload;
    }
    
    /**
     * Get token expiration timestamp
     */
    public function getExpiration(string $token): ?int
    {
        $payload = $this->decodeToken($token);
        
        return $payload['exp'] ?? null;
    }
    
    /**
     * Check if token is expired
     */
    public function isExpired(string $token): bool
    {
        $expiration = $this->getExpiration($token);
        
        if ($expiration === null) {
            return true;
        }
        
        return time() >= $expiration;
    }
    
    /**
     * Refresh token (generate new with same data but new expiration)
     */
    public function refreshToken(string $token): ?string
    {
        $payload = $this->verifyToken($token);
        
        if ($payload === null) {
            return null;
        }
        
        return $this->generateToken($payload);
    }
    
    /**
     * Sign data
     */
    private function sign(string $data): string
    {
        $signature = hash_hmac('sha256', $data, $this->secretKey, true);
        
        return $this->base64UrlEncode($signature);
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Extract token from Authorization header
     */
    public static function getTokenFromHeader(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            // Try alternative header name
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        }
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
