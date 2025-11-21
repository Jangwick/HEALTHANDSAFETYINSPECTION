<?php

declare(strict_types=1);

/**
 * Database Configuration and Connection Manager
 * Health & Safety Inspections System
 */

// Load environment variables if not already loaded
if (empty($_ENV['DB_HOST'])) {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Remove quotes if present
                $value = trim($value, '"\'');
                $_ENV[$key] = $value;
            }
        }
    }
}

class Database
{
    private static ?PDO $instance = null;
    
    /**
     * Get PDO database connection (Singleton)
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        
        return self::$instance;
    }
    
    /**
     * Create new PDO connection
     */
    private static function createConnection(): PDO
    {
        $config = self::getConfig();
        
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );
        
        try {
            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            
            return $pdo;
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new RuntimeException("Database connection failed. Please check configuration.");
        }
    }
    
    /**
     * Get database configuration from environment
     */
    private static function getConfig(): array
    {
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_NAME'] ?? 'health_safety_inspections',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
        ];
    }
    
    /**
     * Test database connection
     */
    public static function testConnection(): bool
    {
        try {
            $pdo = self::getConnection();
            $pdo->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Close connection
     */
    public static function closeConnection(): void
    {
        self::$instance = null;
    }
}
