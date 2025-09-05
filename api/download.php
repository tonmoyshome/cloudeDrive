<?php
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/FileManager.php';

session_start();

$user = new User();
$fileManager = new FileManager();

if (!$user->isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['id']) || isset($_GET['file_id']))) {
    $fileId = intval($_GET['id'] ?? $_GET['file_id']);
    
    if ($fileId <= 0) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
        exit;
    }
    
    $result = $fileManager->downloadFile($fileId);
    
    if ($result['success']) {
        // Check if file actually exists
        if (!file_exists($result['file_path'])) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'File not found on disk']);
            exit;
        }
        
        // Set headers for file download
        header('Content-Type: ' . ($result['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($result['original_name']) . '"');
        header('Content-Length: ' . filesize($result['file_path']));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Output file
        readfile($result['file_path']);
        exit;
    } else {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }
} else {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request - file ID required']);
}
?>
