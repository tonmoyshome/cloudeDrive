<?php
session_start();
require_once 'classes/User.php';

$user = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    echo "Attempting login with username: '$username' and password: '$password'\n";
    
    $result = $user->login($username, $password);
    echo "Login result: " . json_encode($result) . "\n";
    
    if ($result['success']) {
        echo "Session data after login: " . json_encode($_SESSION) . "\n";
        echo "Login successful! Should redirect to index.php\n";
    } else {
        echo "Login failed: " . $result['message'] . "\n";
    }
} else {
    echo "No POST data received\n";
}
?>
