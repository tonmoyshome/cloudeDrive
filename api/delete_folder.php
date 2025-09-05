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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $folderId = $input['folder_id'] ?? $_POST['folder_id'] ?? $_GET['id'] ?? null;
    $force = $input['force'] ?? $_POST['force'] ?? false;
    
    if ($folderId) {
        $result = $folderManager->deleteFolder(intval($folderId), null, $force);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Folder ID required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
