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

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['share_id'])) {
        $shareId = intval($data['share_id']);
        
        try {
            $pdo = getDBConnection();
            
            // Verify the user owns the folder or is admin
            $stmt = $pdo->prepare("
                SELECT f.owner_id
                FROM folder_permissions fp
                JOIN folders f ON fp.folder_id = f.id
                WHERE fp.id = ?
            ");
            $stmt->execute([$shareId]);
            $folder = $stmt->fetch();
            
            if (!$folder) {
                echo json_encode(['success' => false, 'message' => 'Share not found']);
                exit;
            }
            
            // Check if user owns the folder or is admin
            if ($folder['owner_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }
            
            // Remove the share
            $stmt = $pdo->prepare("DELETE FROM folder_permissions WHERE id = ?");
            $result = $stmt->execute([$shareId]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Share removed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove share']);
            }
            
        } catch (Exception $e) {
            error_log("Remove folder share error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to remove share']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Share ID required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
