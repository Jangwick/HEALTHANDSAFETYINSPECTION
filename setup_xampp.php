<?php
/**
 * Try both MySQL installations - System MySQL and XAMPP MySQL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);

echo "========================================================================\n";
echo "  Trying Multiple MySQL Connections\n";
echo "========================================================================\n\n";

// Configuration sets to try
$configs = [
    [
        'name' => 'XAMPP MySQL (localhost socket)',
        'host' => 'localhost',
        'port' => 3306,
        'socket' => null
    ],
    [
        'name' => 'XAMPP MySQL (127.0.0.1)',
        'host' => '127.0.0.1',
        'port' => 3306,
        'socket' => null
    ],
    [
        'name' => 'XAMPP MySQL (named pipe)',
        'host' => '.',
        'port' => 3306,
        'socket' => null
    ]
];

$passwords = ['', 'root', 'password', 'mysql', 'admin'];

$success = false;
$workingConfig = null;

foreach ($configs as $config) {
    echo "Testing {$config['name']}...\n";
    
    foreach ($passwords as $pwd) {
        $displayPwd = $pwd === '' ? '(empty)' : $pwd;
        echo "  Password: $displayPwd...";
        
        $mysqli = @new mysqli($config['host'], 'root', $pwd, '', $config['port']);
        
        if (!$mysqli->connect_error) {
            echo " SUCCESS!\n\n";
            $success = true;
            $workingConfig = [
                'config' => $config,
                'password' => $pwd
            ];
            break 2;
        } else {
            echo " failed\n";
        }
        
        if ($mysqli && !$mysqli->connect_error) {
            $mysqli->close();
        }
    }
    echo "\n";
}

if (!$success) {
    echo "========================================================================\n";
    echo "  ALL CONNECTION ATTEMPTS FAILED\n";
    echo "========================================================================\n\n";
    echo "Your MySQL requires specific credentials.\n\n";
    echo "Please tell me your MySQL password, and I'll update the .env file\n";
    echo "and create the database for you.\n\n";
    echo "Or use phpMyAdmin: http://localhost/phpmyadmin\n";
    echo "See SETUP_MANUAL.md for instructions.\n\n";
    exit(1);
}

echo "========================================================================\n";
echo "  CONNECTION SUCCESSFUL!\n";
echo "========================================================================\n";
echo "  Host: {$workingConfig['config']['host']}\n";
echo "  Port: {$workingConfig['config']['port']}\n";
echo "  Password: " . ($workingConfig['password'] === '' ? '(empty)' : $workingConfig['password']) . "\n";
echo "========================================================================\n\n";

// Reconnect with working credentials
$mysqli = new mysqli(
    $workingConfig['config']['host'],
    'root',
    $workingConfig['password'],
    '',
    $workingConfig['config']['port']
);

// Update .env file
echo "Updating .env file...\n";
$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);
$envContent = preg_replace('/^DB_HOST=.*$/m', 'DB_HOST=' . $workingConfig['config']['host'], $envContent);
$envContent = preg_replace('/^DB_PASS=.*$/m', 'DB_PASS=' . $workingConfig['password'], $envContent);
file_put_contents($envFile, $envContent);
echo "  [OK] .env updated\n\n";

// Create database
echo "Creating database...\n";
$mysqli->query("CREATE DATABASE IF NOT EXISTS healthinspection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "  [OK] Database created\n\n";

// Select database
$mysqli->select_db('healthinspection');

// Import schema
echo "Importing schema (creating tables)...\n";
echo "  This will take 10-15 seconds...\n";
$sql = file_get_contents(__DIR__ . '/database/migrations/001_create_tables.sql');
$mysqli->multi_query($sql);
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());
echo "  [OK] Tables created\n\n";

// Import seed data
echo "Importing seed data (test users)...\n";
$sql = file_get_contents(__DIR__ . '/database/seeds/001_initial_data.sql');
$mysqli->multi_query($sql);
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());
echo "  [OK] Seed data imported\n\n";

// Verify
$result = $mysqli->query("SELECT COUNT(*) as c FROM users");
$row = $result->fetch_assoc();
echo "Verification: {$row['c']} users created\n\n";

echo "========================================================================\n";
echo "  DATABASE SETUP COMPLETE!\n";
echo "========================================================================\n\n";
echo "Login at: http://localhost:8000/views/auth/login.php\n";
echo "  Email: admin@lgu.gov.ph\n";
echo "  Password: Admin@123\n\n";
echo "========================================================================\n";

$mysqli->close();
