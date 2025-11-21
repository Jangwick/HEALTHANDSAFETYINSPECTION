<?php
require_once __DIR__ . '/config/database.php';

echo "Fixing user passwords...\n\n";

// Generate correct hash for 'Admin@123'
$correctPassword = 'Admin@123';
$correctHash = password_hash($correctPassword, PASSWORD_BCRYPT);

echo "New password hash for 'Admin@123': $correctHash\n\n";

try {
    $db = Database::getConnection();
    
    // Update all users with the correct password hash
    $stmt = $db->prepare("UPDATE users SET password_hash = ?");
    $stmt->execute([$correctHash]);
    
    echo "Updated " . $stmt->rowCount() . " users\n\n";
    
    // Verify the update
    echo "Verifying passwords...\n";
    $stmt = $db->query("SELECT user_id, username, email, password_hash FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $verified = password_verify($correctPassword, $user['password_hash']);
        echo "  {$user['username']} ({$user['email']}): " . ($verified ? "✓ WORKS" : "✗ FAILED") . "\n";
    }
    
    echo "\n✓ All passwords fixed!\n";
    echo "\nYou can now login with:\n";
    echo "  Email: admin@lgu.gov.ph\n";
    echo "  Password: Admin@123\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
