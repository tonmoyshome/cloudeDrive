<?php
// Debug version of delete_multiple_files.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    session_start();
    require_once __DIR__ . '/../classes/FileManager.php';

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Please log in to delete files']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
            exit;
        }
        
        $fileIds = $input['file_ids'] ?? [];

        if (empty($fileIds) || !is_array($fileIds)) {
            echo json_encode(['success' => false, 'message' => 'File IDs are required']);
            exit;
        }

        // Convert string IDs to integers
        $fileIds = array_map('intval', $fileIds);
        
        $fileManager = new FileManager();
        $result = $fileManager->deleteMultipleFiles($fileIds, $_SESSION['user_id']);
        echo json_encode($result);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
