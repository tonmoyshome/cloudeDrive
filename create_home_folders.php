<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/FolderManager.php';

try {
    $pdo = getDBConnection();
    $folderManager = new FolderManager();
    
    // Get all users
    $stmt = $pdo->query('SELECT id, username FROM users ORDER BY id');
    $users = $stmt->fetchAll();
    
    echo 'Checking home folders for ' . count($users) . ' users...' . PHP_EOL;
    
    foreach ($users as $user) {
        $userId = $user['id'];
        $username = $user['username'];
        
        // Check if user already has a home folder (folder with their username owned by them)
        $stmt = $pdo->prepare('SELECT id FROM folders WHERE name = ? AND owner_id = ? AND parent_id IS NULL');
        $stmt->execute([$username, $userId]);
        $existingFolder = $stmt->fetch();
        
        if ($existingFolder) {
            echo "User $username already has home folder (ID: {$existingFolder['id']})" . PHP_EOL;
        } else {
            echo "Creating home folder for user: $username..." . PHP_EOL;
            
            // Create home folder
            $result = $folderManager->createFolder($username, null, $userId);
            
            if ($result['success']) {
                echo "✓ Created home folder for $username (Folder ID: {$result['folder_id']})" . PHP_EOL;
            } else {
                echo "✗ Failed to create home folder for $username: {$result['message']}" . PHP_EOL;
            }
        }
    }
    
    echo 'Home folder check complete!' . PHP_EOL;
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
