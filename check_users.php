<?php
require_once __DIR__ . '/config/database.php';

echo "Checking user accounts in database...\n\n";

try {
    $db = Database::getConnection();
    
    // Get all users
    $stmt = $db->query("
        SELECT u.user_id, u.username, u.email, u.password_hash, u.status,
               r.role_name
        FROM users u
        LEFT JOIN user_roles ur ON u.user_id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.role_id
        ORDER BY u.user_id
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total users: " . count($users) . "\n\n";
    
    foreach ($users as $user) {
        echo "-----------------------------------\n";
        echo "ID: {$user['user_id']}\n";
        echo "Username: {$user['username']}\n";
        echo "Email: {$user['email']}\n";
        echo "Role: " . ($user['role_name'] ?? 'No role') . "\n";
        echo "Status: {$user['status']}\n";
        echo "Password Hash: " . substr($user['password_hash'], 0, 60) . "...\n";
        
        // Test password verification
        $testPassword = 'Admin@123';
        $verified = password_verify($testPassword, $user['password_hash']);
        echo "Password 'Admin@123' works: " . ($verified ? "YES ✓" : "NO ✗") . "\n";
    }
    
    echo "\n-----------------------------------\n";
    echo "\nTest login with admin account...\n";
    
    $email = 'admin@lgu.gov.ph';
    $password = 'Admin@123';
    
    $stmt = $db->prepare("
        SELECT u.*, r.role_name, r.permissions 
        FROM users u
        LEFT JOIN user_roles ur ON u.user_id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.role_id
        WHERE u.email = ? OR u.username = ?
        LIMIT 1
    ");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User found: {$user['username']} ({$user['email']})\n";
        echo "Status: {$user['status']}\n";
        echo "Role: " . ($user['role_name'] ?? 'No role') . "\n";
        
        if (password_verify($password, $user['password_hash'])) {
            echo "Password verification: SUCCESS ✓\n";
            echo "\nLogin should work!\n";
        } else {
            echo "Password verification: FAILED ✗\n";
            echo "The password hash might be wrong.\n";
        }
    } else {
        echo "User not found!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
