<?php
session_start();

// Create a test session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

// Simulate the download API call
$_GET['file_id'] = 1409;
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "Testing download API with file_id: " . $_GET['file_id'] . "<br>";

// Include the download API
ob_start();
include 'api/download.php';
$output = ob_get_contents();
ob_end_clean();

echo "API Output: " . htmlspecialchars($output) . "<br>";
echo "Headers sent: " . (headers_sent() ? 'YES' : 'NO') . "<br>";
?>
