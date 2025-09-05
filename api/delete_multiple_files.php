<?php
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
    $fileIds = $input['file_ids'] ?? [];

    if (empty($fileIds) || !is_array($fileIds)) {
        echo json_encode(['success' => false, 'message' => 'File IDs are required']);
        exit;
    }

    $fileManager = new FileManager();
    $result = $fileManager->deleteMultipleFiles($fileIds, $_SESSION['user_id']);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
