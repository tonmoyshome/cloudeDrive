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
    $sessionId = $_POST['session_id'] ?? '';
    $chunkNumber = intval($_POST['chunk_number'] ?? 0);
    
    if (empty($sessionId) || !isset($_FILES['chunk'])) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    
    $chunkData = file_get_contents($_FILES['chunk']['tmp_name']);
    $result = $fileManager->uploadChunk($sessionId, $chunkNumber, $chunkData);
    
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
