<?php
/**
 * Database Setup API Endpoint
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

$password = $_POST['password'] ?? '';

try {
    // Try to connect on multiple ports (3306 for system MySQL, 3307 for XAMPP)
    $ports = [3307, 3306]; // Try XAMPP port first
    $connected = false;
    $mysqli = null;
    
    foreach ($ports as $port) {
        $mysqli = @new mysqli('localhost', 'root', $password, '', $port);
        if (!$mysqli->connect_error) {
            $connected = true;
            break;
        }
    }
    
    if (!$connected) {
        throw new Exception("Cannot connect to MySQL on ports 3306 or 3307: " . $mysqli->connect_error);
    }
    
    // Create database
    $result = $mysqli->query("CREATE DATABASE IF NOT EXISTS healthinspection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    if (!$result) {
        throw new Exception("Cannot create database: " . $mysqli->error);
    }
    
    // Select database
    $mysqli->select_db('healthinspection');
    
    // Import schema
    $schemaFile = __DIR__ . '/../database/migrations/001_create_tables.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    $mysqli->multi_query($sql);
    
    // Clear results
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    
    if ($mysqli->error) {
        throw new Exception("Schema import error: " . $mysqli->error);
    }
    
    // Import seed data
    $seedFile = __DIR__ . '/../database/seeds/001_initial_data.sql';
    if (!file_exists($seedFile)) {
        throw new Exception("Seed file not found: $seedFile");
    }
    
    $sql = file_get_contents($seedFile);
    $mysqli->multi_query($sql);
    
    // Clear results
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    
    if ($mysqli->error) {
        throw new Exception("Seed import error: " . $mysqli->error);
    }
    
    // Update .env file with correct port
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        $envContent = preg_replace('/^DB_PORT=.*$/m', 'DB_PORT=3307', $envContent);
        $envContent = preg_replace('/^DB_PASS=.*$/m', 'DB_PASS=' . $password, $envContent);
        file_put_contents($envFile, $envContent);
    }
    
    // Get user count
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    $userCount = $row['count'];
    
    $mysqli->close();
    
    echo json_encode([
        'success' => true,
        'message' => "Database created successfully!\nTables created\nSeed data imported\nTotal users: $userCount\n.env file updated"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database setup failed',
        'error' => $e->getMessage()
    ]);
}
