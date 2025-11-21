<?php
mysqli_report(MYSQLI_REPORT_OFF);

echo "Testing MySQL connections...\n\n";

// Test port 3306 (System MySQL)
echo "Port 3306 (System MySQL):\n";
$m1 = @new mysqli('localhost', 'root', '', '', 3306);
if ($m1->connect_error) {
    echo "  FAILED: {$m1->connect_error}\n";
} else {
    echo "  SUCCESS!\n";
    $m1->close();
}

echo "\n";

// Test port 3307 (XAMPP MySQL)
echo "Port 3307 (XAMPP MySQL):\n";
$m2 = @new mysqli('localhost', 'root', '', '', 3307);
if ($m2->connect_error) {
    echo "  FAILED: {$m2->connect_error}\n";
} else {
    echo "  SUCCESS! This is the one to use!\n";
    $m2->close();
}
