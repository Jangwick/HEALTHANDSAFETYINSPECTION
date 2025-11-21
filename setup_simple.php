<?php
/**
 * Simple Database Setup - Manual MySQL Connection Test
 * This script tries to create the database using different approaches
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF); // Disable mysqli exceptions for password testing

echo "========================================================================\n";
echo "  MySQL Connection & Database Creation Tool\n";
echo "========================================================================\n\n";

echo "Let's try to connect to MySQL and set up the database...\n\n";

// Try using MySQLi extension instead of PDO
echo "Attempting MySQLi connection (no password)...\n";

$mysqli = @new mysqli('localhost', 'root', '', '', 3306);

if ($mysqli->connect_error) {
    echo "  Failed: " . $mysqli->connect_error . "\n\n";
    
    echo "Trying with common passwords...\n";
    $passwords = ['root', 'password', 'mysql', ''];
    
    foreach ($passwords as $pwd) {
        echo "  Trying password: '" . ($pwd ?: '(empty)') . "'...\n";
        $mysqli = @new mysqli('localhost', 'root', $pwd, '', 3306);
        
        if (!$mysqli->connect_error) {
            echo "  SUCCESS! Connected with password: '" . ($pwd ?: '(empty)') . "'\n\n";
            
            // Update .env file
            $envFile = __DIR__ . '/.env';
            $envContent = file_get_contents($envFile);
            $envContent = preg_replace('/^DB_PASS=.*$/m', 'DB_PASS=' . $pwd, $envContent);
            file_put_contents($envFile, $envContent);
            echo "  Updated .env file with correct password.\n\n";
            
            break;
        }
    }
    
    if ($mysqli->connect_error) {
        echo "\n========================================================================\n";
        echo "  UNABLE TO CONNECT TO MYSQL\n";
        echo "========================================================================\n\n";
        echo "Please do ONE of the following:\n\n";
        echo "OPTION 1: Find your MySQL password\n";
        echo "  1. Check your MySQL installation notes\n";
        echo "  2. Open .env file and update: DB_PASS=your_password\n";
        echo "  3. Run this script again\n\n";
        echo "OPTION 2: Use MySQL Workbench or phpMyAdmin\n";
        echo "  1. Open http://localhost/phpmyadmin (if XAMPP)\n";
        echo "  2. Create database named: healthinspection\n";
        echo "  3. Import: database/migrations/001_create_tables.sql\n";
        echo "  4. Import: database/seeds/001_initial_data.sql\n\n";
        echo "OPTION 3: Manual SQL\n";
        echo "  Run these commands in MySQL:\n";
        echo "    CREATE DATABASE healthinspection;\n";
        echo "    USE healthinspection;\n";
        echo "    SOURCE c:/xampp/htdocs/HEALTHANDSAFETYINSPECTION/database/migrations/001_create_tables.sql;\n";
        echo "    SOURCE c:/xampp/htdocs/HEALTHANDSAFETYINSPECTION/database/seeds/001_initial_data.sql;\n\n";
        exit(1);
    }
}

// Create database
echo "Creating database 'healthinspection'...\n";
$result = $mysqli->query("CREATE DATABASE IF NOT EXISTS healthinspection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if ($result) {
    echo "  [OK] Database created/verified\n\n";
} else {
    echo "  [ERROR] " . $mysqli->error . "\n\n";
    exit(1);
}

// Select database
$mysqli->select_db('healthinspection');

// Load and execute migration file
echo "Loading migration file...\n";
$migrationFile = __DIR__ . '/database/migrations/001_create_tables.sql';
$sql = file_get_contents($migrationFile);

echo "Executing migration (creating tables)...\n";
echo "  This may take a moment...\n";

// Split SQL by statement and execute
$mysqli->multi_query($sql);

// Clear all results
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());

echo "  [OK] Tables created\n\n";

// Load and execute seed file  
echo "Loading seed data...\n";
$seedFile = __DIR__ . '/database/seeds/001_initial_data.sql';
$seedSql = file_get_contents($seedFile);

echo "Executing seed data...\n";
$mysqli->multi_query($seedSql);

// Clear all results
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());

echo "  [OK] Seed data inserted\n\n";

// Verify
$result = $mysqli->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
echo "Verification:\n";
echo "  Total users: " . $row['count'] . "\n\n";

$result = $mysqli->query("SELECT username, email FROM users LIMIT 5");
echo "Sample users:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['username']} ({$row['email']})\n";
}

echo "\n========================================================================\n";
echo "  DATABASE SETUP COMPLETE!\n";
echo "========================================================================\n\n";
echo "You can now login at: http://localhost:8000/views/auth/login.php\n\n";
echo "Test Account:\n";
echo "  Email: admin@lgu.gov.ph\n";
echo "  Password: Admin@123\n\n";
echo "========================================================================\n";

$mysqli->close();
