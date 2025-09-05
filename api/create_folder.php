<?php
header('Content-Type: application/json');
require_once '../classes/User.php';
require_once '../classes/FolderManager.php';

session_start();

$user = new User();
$folderManager = new FolderManager();

if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (isset($data['name']) && !empty(trim($data['name']))) {
        $result = $folderManager->createFolder(
            trim($data['name']),
            $data['parent_id'] ?? null
        );
        
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Folder name required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
