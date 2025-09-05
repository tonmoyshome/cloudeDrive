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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $users = $user->getAllUsers();
    $currentUser = $user->getCurrentUser();
    
    // Remove current user from the list
    $users = array_filter($users, function($u) use ($currentUser) {
        return $u['id'] != $currentUser['id'];
    });
    
    echo json_encode(['success' => true, 'users' => array_values($users)]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
