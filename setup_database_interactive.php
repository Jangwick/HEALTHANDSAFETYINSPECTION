<?php
/**
 * Interactive Database Setup Script
 * This script will guide you through setting up the database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=======================================================================\n";
echo "  Health & Safety Inspection System - Database Setup Wizard\n";
echo "=======================================================================\n\n";

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Get database credentials
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? 3306;
$dbname = $_ENV['DB_NAME'] ?? 'healthinspection';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

echo "Current database configuration (.env file):\n";
echo "  Host: $host\n";
echo "  Port: $port\n";
echo "  Database: $dbname\n";
echo "  Username: $username\n";
echo "  Password: " . ($password ? str_repeat('*', strlen($password)) : '(empty)') . "\n\n";

echo "If the password is incorrect, please:\n";
echo "1. Open the .env file in the project root\n";
echo "2. Update the DB_PASS= line with your MySQL root password\n";
echo "3. Run this script again\n\n";

echo "Common MySQL passwords:\n";
echo "  - (empty) - default XAMPP\n";
echo "  - root\n";
echo "  - password\n";
echo "  - mysql\n\n";

echo "Attempting to connect...\n";
echo "-----------------------------------------------------------------------\n\n";

try {
    // Try to connect
    $pdo = new PDO(
        "mysql:host=$host;port=$port;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✓ Connected to MySQL server successfully!\n\n";
    
    // Create database
    echo "Creating database '$dbname'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created\n\n";
    
    // Use database
    $pdo->exec("USE `$dbname`");
    
    // Load migration
    echo "Loading database schema...\n";
    $migrationFile = __DIR__ . '/database/migrations/001_create_tables.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    $sql = file_get_contents($migrationFile);
    echo "✓ Schema loaded (" . strlen($sql) . " bytes)\n\n";
    
    echo "Creating tables (this may take a moment)...\n";
    $pdo->exec($sql);
    echo "✓ Tables created\n\n";
    
    // Load seed data
    echo "Loading seed data...\n";
    $seedFile = __DIR__ . '/database/seeds/001_initial_data.sql';
    if (!file_exists($seedFile)) {
        throw new Exception("Seed file not found: $seedFile");
    }
    $seedSql = file_get_contents($seedFile);
    echo "✓ Seed data loaded (" . strlen($seedSql) . " bytes)\n\n";
    
    echo "Inserting seed data...\n";
    $pdo->exec($seedSql);
    echo "✓ Seed data inserted\n\n";
    
    // Verify
    echo "Verifying database setup...\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Total tables: " . count($tables) . "\n\n";
    
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "✓ Total users: $userCount\n\n";
    
    $users = $pdo->query("SELECT username, email FROM users LIMIT 5")->fetchAll();
    echo "Sample accounts:\n";
    foreach ($users as $user) {
        echo "  • {$user['username']} ({$user['email']})\n";
    }
    
    echo "\n=======================================================================\n";
    echo "  DATABASE SETUP COMPLETED SUCCESSFULLY!\n";
    echo "=======================================================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Start the development server (if not already running):\n";
    echo "   C:\\xampp\\php\\php.exe -S localhost:8000 -t public public/router.php\n\n";
    echo "2. Open your browser to:\n";
    echo "   http://localhost:8000/views/auth/login.php\n\n";
    echo "3. Login with:\n";
    echo "   Email: admin@lgu.gov.ph\n";
    echo "   Password: Admin@123\n\n";
    
} catch (PDOException $e) {
    echo "\n✗ DATABASE CONNECTION FAILED\n";
    echo "-----------------------------------------------------------------------\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n\n";
    
    if ($e->getCode() == 1045) {
        echo "SOLUTION:\n";
        echo "The MySQL root password is incorrect.\n\n";
        echo "To fix this:\n";
        echo "1. Find your MySQL root password\n";
        echo "   - Check your MySQL installation notes\n";
        echo "   - Or try resetting it using MySQL Workbench\n\n";
        echo "2. Open: c:\\xampp\\htdocs\\HEALTHANDSAFETYINSPECTION\\.env\n\n";
        echo "3. Update this line:\n";
        echo "   DB_PASS=\n";
        echo "   To:\n";
        echo "   DB_PASS=your_actual_password\n\n";
        echo "4. Run this script again:\n";
        echo "   C:\\xampp\\php\\php.exe setup_database.php\n\n";
    }
    
    exit(1);
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
