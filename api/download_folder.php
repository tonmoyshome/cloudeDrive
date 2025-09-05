<?php
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/FolderManager.php';

session_start();

$user = new User();
$folderManager = new FolderManager();

if (!$user->isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['id']) || isset($_GET['folder_id']))) {
    $folderId = intval($_GET['id'] ?? $_GET['folder_id']);
    
    if ($folderId <= 0) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid folder ID']);
        exit;
    }
    
    $result = $folderManager->downloadFolder($folderId);
    
    if ($result['success']) {
        // Check if ZIP file exists
        if (!file_exists($result['zip_path'])) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ZIP file not found']);
            exit;
        }
        
        // Set headers for ZIP download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $result['folder_name'] . '.zip"');
        header('Content-Length: ' . $result['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Output ZIP file
        readfile($result['zip_path']);
        
        // Clean up temporary file
        unlink($result['zip_path']);
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
    echo json_encode(['success' => false, 'message' => 'Invalid request - folder ID required']);
}
?>
