<?php
require_once __DIR__ . '/config/database.php';

echo "Testing database connection via Database class...\n\n";

try {
    $db = Database::getConnection();
    echo "✓ SUCCESS! Database connection established.\n\n";
    
    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Users in database: {$result['count']}\n\n";
    
    // Show connection info
    $stmt = $db->query("SELECT DATABASE() as db, @@port as port");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Connected to database: {$result['db']}\n";
    echo "✓ Port: {$result['port']}\n\n";
    
    echo "Database is ready for login!\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}
