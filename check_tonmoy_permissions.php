<?php
require_once 'config/database.php';
require_once 'classes/FileManager.php';
try {
    $pdo = getDBConnection();
    
    // Get tonmoy's user ID
    $stmt = $pdo->query('SELECT id FROM users WHERE username = "tonmoy"');
    $tonmoy = $stmt->fetch();
    if (!$tonmoy) {
        echo 'User tonmoy not found' . PHP_EOL;
        exit;
    }
    $tonmoyId = $tonmoy['id'];
    echo 'Tonmoy User ID: ' . $tonmoyId . PHP_EOL;
    
    // Get Product 1 folder ID
    $stmt = $pdo->query('SELECT id FROM folders WHERE name = "Product 1"');
    $folder = $stmt->fetch();
    if (!$folder) {
        echo 'Product 1 folder not found' . PHP_EOL;
        exit;
    }
    $folderId = $folder['id'];
    echo 'Product 1 Folder ID: ' . $folderId . PHP_EOL;
    
    // Test upload permission using FileManager
    $fileManager = new FileManager();
    
    // Use reflection to access the private method for testing
    $reflection = new ReflectionClass($fileManager);
    $method = $reflection->getMethod('hasUploadPermission');
    $method->setAccessible(true);
    
    $hasUploadPermission = $method->invoke($fileManager, $folderId, $tonmoyId);
    echo 'Tonmoy has upload permission on Product 1: ' . ($hasUploadPermission ? 'YES' : 'NO') . PHP_EOL;
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
