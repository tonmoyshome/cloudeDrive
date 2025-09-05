<?php
header('Content-Type: application/json');
require_once '../classes/User.php';
require_once '../config/database.php';

session_start();

$user = new User();

if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['folder_id'])) {
    $folderId = intval($_GET['folder_id']);
    
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
        
        $stmt->execute([$_SESSION['user_id'], $folderId]);
        $availableUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'current_shares' => $shares,
            'available_users' => $availableUsers
        ]);
        
    } catch (Exception $e) {
        error_log("Get folder shares error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to load folder shares']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Folder ID required']);
}
?>
