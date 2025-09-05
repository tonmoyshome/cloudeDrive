<?php
header('Content-Type: application/json');
require_once '../classes/User.php';
require_once '../classes/FileManager.php';

session_start();

$user = new User();
$fileManager = new FileManager();

if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $fileId = $input['file_id'] ?? $_POST['file_id'] ?? $_GET['id'] ?? null;
    
    if ($fileId) {
        $result = $fileManager->deleteFile(intval($fileId));
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'File ID required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
