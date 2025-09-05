<?php
session_start();

// Create admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Testing Edit User API...<br><br>";

// Test editing user ID 7 (tonmoy)
$testData = [
    'user_id' => 7,
    'username' => 'tonmoy_updated',
    'email' => 'tonmoy.updated@gmail.com', 
    'role' => 'user',
    'storage_limit' => 15 * 1024 * 1024 * 1024, // 15GB
    'password' => 'newpassword123'
];

echo "Test Data:<br>";
echo "<pre>" . print_r($testData, true) . "</pre>";

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [];

// Capture the API output
ob_start();
include 'api/admin/edit_user.php';
$apiOutput = ob_get_contents();
ob_end_clean();

echo "API Response:<br>";
echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";

// Check if user was actually updated
require_once 'config/database.php';
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT username, email, role, storage_limit FROM users WHERE id = ?");
$stmt->execute([7]);
$user = $stmt->fetch();

echo "User after update:<br>";
echo "<pre>" . print_r($user, true) . "</pre>";
?>
