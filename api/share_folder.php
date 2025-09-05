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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['folder_id']) && isset($data['user_ids']) && isset($data['permissions'])) {
        $result = $folderManager->shareFolder(
            $data['folder_id'],
            $data['user_ids'],
            $data['permissions']
        );
        
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
