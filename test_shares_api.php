<?php
require_once 'config/database.php';

// Test the get_folder_shares logic without session
$folderId = 6669; // Product 1

try {
    $pdo = getDBConnection();
    
    // Get current folder shares with user details
    $stmt = $pdo->prepare("
        SELECT 
            fp.id,
            fp.user_id,
            u.username,
            u.email,
            fp.can_view,
            fp.can_download,
            fp.can_upload,
            fp.can_delete,
            fp.granted_at
        FROM folder_permissions fp
        JOIN users u ON fp.user_id = u.id
        WHERE fp.folder_id = ?
        ORDER BY u.username
    ");
    
    $stmt->execute([$folderId]);
    $shares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available users (not already shared with)
    $stmt = $pdo->prepare("
        SELECT id, username, email 
        FROM users 
        WHERE id != ? 
        AND id NOT IN (
            SELECT user_id 
            FROM folder_permissions 
            WHERE folder_id = ?
        )
        ORDER BY username
    ");
    
    $stmt->execute([1, $folderId]); // Assuming user 1 is the current user
    $availableUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'success' => true,
        'current_shares' => $shares,
        'available_users' => $availableUsers
    ];
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
