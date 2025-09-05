<?php
// Test password verification
$stored_hash = '$2y$10$8z3hvArQvPnBqvyhWiOvkeYT9BSlMuGXZlvMqqo/R7Hn8jqs/q0I6';

$passwords_to_test = ['password', 'admin123', 'admin', '123456'];

foreach ($passwords_to_test as $password) {
    $verify = password_verify($password, $stored_hash);
    echo "Password: '$password' - " . ($verify ? "MATCHES" : "NO MATCH") . "\n";
}
?>
