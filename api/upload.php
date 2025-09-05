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
    $folderId = $_POST['folder_id'] ?? null;
    $folderId = empty($folderId) ? null : intval($folderId);
    
    // Handle single file upload
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $result = $fileManager->uploadFile($file, $folderId);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload failed - please check your file and try again']);
        }
    }
    // Handle multiple files upload
    elseif (isset($_FILES['files'])) {
        $results = [];
        $totalFiles = count($_FILES['files']['name']);
        
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $fileData = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error' => $_FILES['files']['error'][$i],
                    'size' => $_FILES['files']['size'][$i]
                ];
                
                $result = $fileManager->uploadFile($fileData, $folderId);
                $results[] = [
                    'filename' => $fileData['name'],
                    'success' => $result['success'],
                    'message' => $result['message']
                ];
            }
        }
        
        $successCount = count(array_filter($results, function($r) { return $r['success']; }));
        
        echo json_encode([
            'success' => $successCount > 0,
            'message' => "Uploaded {$successCount} of {$totalFiles} files successfully",
            'results' => $results
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
