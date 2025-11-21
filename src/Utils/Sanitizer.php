<?php

declare(strict_types=1);

namespace HealthSafety\Utils;

/**
 * Input Sanitizer Utility
 * Health & Safety Inspections System
 */
class Sanitizer
{
    /**
     * Sanitize string (remove HTML/PHP tags)
     */
    public static function string(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        
        return strip_tags(trim((string)$value));
    }
    
    /**
     * Sanitize email
     */
    public static function email(string $value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';
    }
    
    /**
     * Sanitize integer
     */
    public static function int(mixed $value): int
    {
        return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     */
    public static function float(mixed $value): float
    {
        return (float)filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize URL
     */
    public static function url(string $value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_URL) ?: '';
    }
    
    /**
     * Sanitize HTML (allow safe tags)
     */
    public static function html(string $value, array $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'a']): string
    {
        $allowed = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($value, $allowed);
    }
    
    /**
     * Escape for HTML output (prevent XSS)
     */
    public static function escape(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map([self::class, 'escape'], $value));
        }
        
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize filename
     */
    public static function filename(string $value): string
    {
        // Remove path components
        $value = basename($value);
        
        // Remove special characters except dots, dashes, underscores
        $value = preg_replace('/[^a-zA-Z0-9._-]/', '_', $value);
        
        // Prevent directory traversal
        $value = str_replace('..', '', $value);
        
        return $value;
    }
    
    /**
     * Sanitize phone number (Philippine format)
     */
    public static function phone(string $value): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $value);
        
        // Convert to standard format
        if (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '9') {
            // Add leading 0
            $cleaned = '0' . $cleaned;
        } elseif (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '639') {
            // Convert +639 to 09
            $cleaned = '0' . substr($cleaned, 2);
        }
        
        return $cleaned;
    }
    
    /**
     * Sanitize array of values
     */
    public static function array(array $values, string $type = 'string'): array
    {
        $sanitized = [];
        
        foreach ($values as $key => $value) {
            $sanitizedKey = self::string($key);
            
            $sanitized[$sanitizedKey] = match($type) {
                'int' => self::int($value),
                'float' => self::float($value),
                'email' => self::email($value),
                'url' => self::url($value),
                default => self::string($value)
            };
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize JSON data
     */
    public static function json(string $value): ?array
    {
        $decoded = json_decode($value, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $decoded;
    }
    
    /**
     * Remove SQL injection attempts (additional layer, use PDO prepared statements!)
     */
    public static function sql(string $value): string
    {
        $dangerous = ['--', ';', '/*', '*/', 'xp_', 'sp_', 'DROP', 'DELETE', 'INSERT', 'UPDATE', 'UNION'];
        
        foreach ($dangerous as $pattern) {
            $value = str_ireplace($pattern, '', $value);
        }
        
        return trim($value);
    }
    
    /**
     * Sanitize boolean value
     */
    public static function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Trim whitespace from all string values in array
     */
    public static function trimAll(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        });
        
        return $data;
    }
}
