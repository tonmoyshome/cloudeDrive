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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['filename']) && isset($data['total_size']) && isset($data['chunk_size'])) {
        $result = $fileManager->createUploadSession(
            $data['filename'],
            $data['total_size'],
            $data['chunk_size'],
            $data['folder_id'] ?? null
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
