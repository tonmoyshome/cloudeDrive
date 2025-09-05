<?php
header('Content-Type: application/json');
require_once '../classes/User.php';

session_start();

$user = new User();

if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!$user->isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $userId = $input['user_id'] ?? null;
    $storageLimit = $input['storage_limit'] ?? null;
    
    if (!$userId || !$storageLimit) {
        echo json_encode(['success' => false, 'message' => 'User ID and storage limit are required']);
        exit;
    }
    
    $result = $user->updateUserQuota($userId, $storageLimit);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
