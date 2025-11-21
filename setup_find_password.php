<?php
/**
 * MySQL Password Finder and Database Setup
 * Tries to find the correct MySQL password and setup database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);

echo "========================================================================\n";
echo "  MySQL Password Finder & Database Setup\n";
echo "========================================================================\n\n";

echo "Attempting to find your MySQL password...\n\n";

// Extended list of common passwords
$passwords = [
    '',           // Empty (default XAMPP)
    'root',       // Common
    'password',   // Common
    'mysql',      // Common  
    'admin',      // Common
    'toor',       // Root backwards
    '123456',     // Weak but common
    'P@ssw0rd',   // Common pattern
    'Passw0rd',   // Common pattern
    'Root123',    // Common pattern
    'Admin123',   // Common pattern
    'mysql123',   // Common pattern
];

$connected = false;
$workingPassword = null;
$mysqli = null;

foreach ($passwords as $pwd) {
    $displayPwd = $pwd === '' ? '(empty/blank)' : $pwd;
    echo "  Trying: $displayPwd...";
    
    $mysqli = @new mysqli('localhost', 'root', $pwd, '', 3306);
    
    if (!$mysqli->connect_error) {
        echo " SUCCESS!\n";
        $connected = true;
        $workingPassword = $pwd;
        break;
    } else {
        echo " failed\n";
    }
}

if (!$connected) {
    echo "\n";
    echo "========================================================================\n";
    echo "  PASSWORD NOT FOUND\n";
    echo "========================================================================\n\n";
    echo "None of the common passwords worked.\n\n";
    echo "Please try ONE of these options:\n\n";
    echo "OPTION 1: Use phpMyAdmin\n";
    echo "  1. Go to: http://localhost/phpmyadmin\n";
    echo "  2. Login with your MySQL credentials\n";
    echo "  3. Create database: healthinspection\n";
    echo "  4. Import: database/migrations/001_create_tables.sql\n";
    echo "  5. Import: database/seeds/001_initial_data.sql\n\n";
    echo "OPTION 2: Check your MySQL password\n";
    echo "  - Look in your MySQL installation notes\n";
    echo "  - Check C:\\ProgramData\\MySQL\\MySQL Server 8.0\\my.ini\n";
    echo "  - Or use MySQL Workbench to connect\n\n";
    echo "OPTION 3: Reset password using MySQL installer\n";
    echo "  - Run MySQL Installer\n";
    echo "  - Reconfigure server\n";
    echo "  - Set new password\n\n";
    exit(1);
}

echo "\n";
echo "========================================================================\n";
echo "  MYSQL PASSWORD FOUND!\n";
echo "========================================================================\n";
echo "  Password: " . ($workingPassword === '' ? '(empty/blank)' : $workingPassword) . "\n";
echo "========================================================================\n\n";

// Update .env file
echo "Updating .env file with correct password...\n";
$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);
$envContent = preg_replace('/^DB_PASS=.*$/m', 'DB_PASS=' . $workingPassword, $envContent);
file_put_contents($envFile, $envContent);
echo "  [OK] .env file updated\n\n";

// Create database
echo "Creating database 'healthinspection'...\n";
$result = $mysqli->query("CREATE DATABASE IF NOT EXISTS healthinspection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if ($result) {
    echo "  [OK] Database created\n\n";
} else {
    echo "  [ERROR] " . $mysqli->error . "\n\n";
    exit(1);
}

// Select database
$mysqli->select_db('healthinspection');

// Load and execute migration file
echo "Loading migration file...\n";
$migrationFile = __DIR__ . '/database/migrations/001_create_tables.sql';
if (!file_exists($migrationFile)) {
    echo "  [ERROR] Migration file not found: $migrationFile\n\n";
    exit(1);
}
$sql = file_get_contents($migrationFile);
echo "  [OK] Loaded " . number_format(strlen($sql)) . " bytes\n\n";

echo "Creating database tables...\n";
echo "  This may take 10-20 seconds...\n";

// Execute migration
$mysqli->multi_query($sql);

// Clear all results
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());

if ($mysqli->error) {
    echo "  [ERROR] " . $mysqli->error . "\n\n";
    exit(1);
}

echo "  [OK] All tables created successfully\n\n";

// Load and execute seed file  
echo "Loading seed data...\n";
$seedFile = __DIR__ . '/database/seeds/001_initial_data.sql';
if (!file_exists($seedFile)) {
    echo "  [ERROR] Seed file not found: $seedFile\n\n";
    exit(1);
}
$seedSql = file_get_contents($seedFile);
echo "  [OK] Loaded " . number_format(strlen($seedSql)) . " bytes\n\n";

echo "Inserting seed data...\n";
$mysqli->multi_query($seedSql);

// Clear all results
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());

if ($mysqli->error) {
    echo "  [ERROR] " . $mysqli->error . "\n\n";
    exit(1);
}

echo "  [OK] Seed data inserted successfully\n\n";

// Verify setup
echo "========================================================================\n";
echo "  VERIFICATION\n";
echo "========================================================================\n\n";

$result = $mysqli->query("SHOW TABLES");
$tableCount = $result->num_rows;
echo "  Total tables created: $tableCount\n\n";

$result = $mysqli->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
echo "  Total users: {$row['count']}\n\n";

echo "  Sample user accounts:\n";
$result = $mysqli->query("SELECT username, email FROM users LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "    - {$row['username']} ({$row['email']})\n";
}

echo "\n";
echo "========================================================================\n";
echo "  DATABASE SETUP COMPLETE!\n";
echo "========================================================================\n\n";
echo "  Server URL: http://localhost:8000\n";
echo "  Login Page: http://localhost:8000/views/auth/login.php\n\n";
echo "  Test Login Credentials:\n";
echo "    Email: admin@lgu.gov.ph\n";
echo "    Password: Admin@123\n\n";
echo "========================================================================\n";
echo "\n";
echo "You can now use the Health & Safety Inspection System!\n\n";

$mysqli->close();
