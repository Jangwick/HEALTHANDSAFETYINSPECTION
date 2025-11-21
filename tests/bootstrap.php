<?php

// PHPUnit test bootstrap file

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define application root
define('APP_ROOT', dirname(__DIR__));

// Load Composer autoloader
require_once APP_ROOT . '/vendor/autoload.php';

// Load test environment variables
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_NAME'] = 'healthinspection_test';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = '';
$_ENV['JWT_SECRET'] = 'test-secret-key-for-phpunit-testing';

// Create test database if it doesn't exist
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$_ENV['DB_NAME']}");
    echo "Test database created/verified.\n";
} catch (PDOException $e) {
    echo "Failed to create test database: " . $e->getMessage() . "\n";
}

// Helper function to reset test database
function resetTestDatabase(): void
{
    $schemaFile = APP_ROOT . '/database/migrations/schema.sql';
    
    if (!file_exists($schemaFile)) {
        echo "Warning: Schema file not found at {$schemaFile}\n";
        return;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS']
        );
        
        // Drop all tables
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Import schema
        $sql = file_get_contents($schemaFile);
        $pdo->exec($sql);
        
        echo "Test database reset successfully.\n";
    } catch (PDOException $e) {
        echo "Failed to reset test database: " . $e->getMessage() . "\n";
    }
}

// Reset database before running tests
resetTestDatabase();

echo "PHPUnit bootstrap complete.\n";
