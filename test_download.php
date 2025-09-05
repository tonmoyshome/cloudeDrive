<?php
session_start();

// Create a test session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

require_once 'classes/FileManager.php';

$fileManager = new FileManager();

// Test file ID 1409 (from our database query)
$fileId = 1409;

echo "Testing download for file ID: $fileId<br>";

$result = $fileManager->downloadFile($fileId);

echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['success']) {
    echo "File exists on disk: " . (file_exists($result['file_path']) ? 'YES' : 'NO') . "<br>";
    echo "File path: " . $result['file_path'] . "<br>";
    echo "File size: " . (file_exists($result['file_path']) ? filesize($result['file_path']) : 'N/A') . "<br>";
}
?>
