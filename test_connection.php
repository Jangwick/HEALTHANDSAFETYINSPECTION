<?php
mysqli_report(MYSQLI_REPORT_OFF);
$m = @new mysqli('localhost', 'root', '');
if (!$m->connect_error) {
    echo "SUCCESS: Connected with empty password\n";
} else {
    echo "FAILED: {$m->connect_error}\n";
}
