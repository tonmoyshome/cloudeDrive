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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $currentUser = $user->getCurrentUser();
    $userId = $input['user_id'] ?? $currentUser['id'];
    
    // Only admins can update other users' profiles
    if ($userId != $currentUser['id'] && !$user->isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    $result = $user->updateProfile($userId, $input);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
