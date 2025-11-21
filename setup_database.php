<?php
/**
 * Database Setup Script
 * Run this script once to create the database and import the schema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Health & Safety Inspection System - Database Setup ===\n\n";

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    echo "Loading .env file...\n";
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
    echo "✓ Environment variables loaded\n\n";
} else {
    echo "✗ .env file not found!\n\n";
    exit(1);
}

// Database credentials from environment
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? 3306;
$dbname = $_ENV['DB_NAME'] ?? 'healthinspection';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    // Step 1: Connect to MySQL server (without database) - Try with and without password
    echo "Step 1: Connecting to MySQL server...\n";
    echo "  Host: $host\n";
    echo "  Port: $port\n";
    echo "  User: $username\n";
    
    $passwords = ['', 'root', 'mysql', 'password', null];
    $connected = false;
    $pdo = null;
    $lastError = '';
    
    foreach ($passwords as $tryPassword) {
        try {
            echo "  Trying " . ($tryPassword === null ? "NULL" : ($tryPassword === '' ? "empty string" : "password: $tryPassword")) . "...\n";
            $pdo = new PDO(
                "mysql:host=$host;port=$port;charset=utf8mb4",
                $username,
                $tryPassword,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            $connected = true;
            $password = $tryPassword;
            echo "✓ Connected to MySQL server" . ($tryPassword ? " (with password: $tryPassword)" : " (no password)") . "\n\n";
            break;
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
            // Try next password
            continue;
        }
    }
    
    if (!$connected) {
        throw new Exception("Could not connect to MySQL. Last error: $lastError\n\nPlease check:\n1. MySQL service is running\n2. MySQL root password\n3. Try resetting password using: C:\\xampp\\mysql_reset_root.bat");
    }

    // Step 2: Create database
    echo "Step 2: Creating database '$dbname'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created/exists\n\n";

    // Step 3: Use the database
    echo "Step 3: Selecting database '$dbname'...\n";
    $pdo->exec("USE `$dbname`");
    echo "✓ Database selected\n\n";

    // Step 4: Load and execute migration file
    echo "Step 4: Loading migration file...\n";
    $migrationFile = __DIR__ . '/database/migrations/001_create_tables.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    echo "✓ Migration file loaded (" . strlen($sql) . " bytes)\n\n";

    echo "Step 5: Executing migration (creating tables)...\n";
    $pdo->exec($sql);
    echo "✓ Tables created successfully\n\n";

    // Step 6: Load and execute seed file
    echo "Step 6: Loading seed data file...\n";
    $seedFile = __DIR__ . '/database/seeds/001_initial_data.sql';
    
    if (!file_exists($seedFile)) {
        throw new Exception("Seed file not found: $seedFile");
    }
    
    $seedSql = file_get_contents($seedFile);
    echo "✓ Seed file loaded (" . strlen($seedSql) . " bytes)\n\n";

    echo "Step 7: Executing seed data...\n";
    $pdo->exec($seedSql);
    echo "✓ Seed data inserted successfully\n\n";

    // Step 8: Verify setup
    echo "Step 8: Verifying database setup...\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Total tables created: " . count($tables) . "\n";
    echo "  Tables: " . implode(', ', array_slice($tables, 0, 10)) . (count($tables) > 10 ? '...' : '') . "\n\n";

    // Check users
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "✓ Total users: $userCount\n";

    // List sample users
    $users = $pdo->query("SELECT username, email FROM users LIMIT 5")->fetchAll();
    echo "  Sample users:\n";
    foreach ($users as $user) {
        echo "    - {$user['username']} ({$user['email']})\n";
    }

    echo "\n=== DATABASE SETUP COMPLETE ===\n";
    echo "\nYou can now login with:\n";
    echo "  Email: admin@lgu.gov.ph\n";
    echo "  Password: Admin@123\n\n";
    echo "Server is running at: http://localhost:8000\n";
    echo "Login page: http://localhost:8000/views/auth/login.php\n\n";

} catch (PDOException $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
