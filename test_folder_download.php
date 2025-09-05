<?php
session_start();

// Create a test session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

require_once 'classes/FolderManager.php';

$folderManager = new FolderManager();

// Test folder ID 6669 (Product 1)
$folderId = 6669;

echo "Testing folder download for folder ID: $folderId<br>";

$result = $folderManager->downloadFolder($folderId);

echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['success']) {
    echo "ZIP file exists: " . (file_exists($result['zip_path']) ? 'YES' : 'NO') . "<br>";
    echo "ZIP path: " . $result['zip_path'] . "<br>";
    echo "ZIP size: " . (file_exists($result['zip_path']) ? filesize($result['zip_path']) : 'N/A') . " bytes<br>";
    
    // Clean up test file
    if (file_exists($result['zip_path'])) {
        unlink($result['zip_path']);
        echo "Cleaned up test ZIP file<br>";
    }
}
?>
