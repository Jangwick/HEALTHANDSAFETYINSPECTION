<?php
mysqli_report(MYSQLI_REPORT_OFF);

echo "========================================================================\n";
echo "  AUTOMATIC DATABASE SETUP - Port 3307\n";
echo "========================================================================\n\n";

// Connect to XAMPP MySQL on port 3307 with no password
$mysqli = new mysqli('localhost', 'root', '', '', 3307);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "[OK] Connected to MySQL on port 3307\n\n";

// Create database
echo "Creating database 'healthinspection'...\n";
$mysqli->query("CREATE DATABASE IF NOT EXISTS healthinspection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "[OK] Database created\n\n";

// Select database
$mysqli->select_db('healthinspection');

// Import schema
echo "Importing tables (this takes 10-15 seconds)...\n";
$sql = file_get_contents(__DIR__ . '/database/migrations/001_create_tables.sql');
$mysqli->multi_query($sql);
do {
    if ($result = $mysqli->store_result()) $result->free();
} while ($mysqli->more_results() && $mysqli->next_result());
echo "[OK] Tables created\n\n";

// Import seed data
echo "Importing seed data...\n";
$sql = file_get_contents(__DIR__ . '/database/seeds/001_initial_data.sql');
$mysqli->multi_query($sql);
do {
    if ($result = $mysqli->store_result()) $result->free();
} while ($mysqli->more_results() && $mysqli->next_result());
echo "[OK] Seed data imported\n\n";

// Verify
$result = $mysqli->query("SELECT COUNT(*) as c FROM users");
$row = $result->fetch_assoc();

echo "========================================================================\n";
echo "  SETUP COMPLETE!\n";
echo "========================================================================\n";
echo "  Database: healthinspection\n";
echo "  Port: 3307\n";
echo "  Users created: {$row['c']}\n";
echo "========================================================================\n\n";
echo "Login at: http://localhost:8000/views/auth/login.php\n";
echo "  Email: admin@lgu.gov.ph\n";
echo "  Password: Admin@123\n\n";

$mysqli->close();
