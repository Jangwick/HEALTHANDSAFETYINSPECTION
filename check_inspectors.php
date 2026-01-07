<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getConnection();

echo "Inspectors Table:\n";
$stmt = $db->query("SELECT * FROM inspectors");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\nUsers with Inspector Roles:\n";
$stmt = $db->query("
    SELECT u.user_id, u.email, r.role_name 
    FROM users u 
    JOIN user_roles ur ON u.user_id = ur.user_id 
    JOIN roles r ON ur.role_id = r.role_id 
    WHERE r.role_name IN ('inspector', 'senior_inspector')
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
