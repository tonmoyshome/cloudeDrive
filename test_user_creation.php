<?php
require_once 'config/database.php';
require_once 'classes/User.php';

try {
    $user = new User();
    
    // Create a test user
    $result = $user->register('testuser123', 'testuser123@example.com', 'password123', 'user');
    
    if ($result['success']) {
        echo 'User created successfully!' . PHP_EOL;
        
        // Check if home folder was created
        $pdo = getDBConnection();
        $stmt = $pdo->query('SELECT id, name, owner_id FROM folders WHERE name = "testuser123" ORDER BY created_at DESC LIMIT 1');
        $folder = $stmt->fetch();
        
        if ($folder) {
            echo 'Home folder created: ID=' . $folder['id'] . ', Name=' . $folder['name'] . ', Owner=' . $folder['owner_id'] . PHP_EOL;
            
            // Check physical folder
            $uploadPath = __DIR__ . '/uploads/testuser123';
            if (file_exists($uploadPath)) {
                echo 'Physical folder exists at: ' . $uploadPath . PHP_EOL;
            } else {
                echo 'Physical folder NOT found at: ' . $uploadPath . PHP_EOL;
            }
        } else {
            echo 'Home folder NOT created in database' . PHP_EOL;
        }
    } else {
        echo 'User creation failed: ' . $result['message'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
