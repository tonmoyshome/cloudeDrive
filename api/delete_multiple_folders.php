<?php
session_start();
require_once __DIR__ . '/../classes/FolderManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please log in to delete folders']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $folderIds = $input['folder_ids'] ?? [];

    if (empty($folderIds) || !is_array($folderIds)) {
        echo json_encode(['success' => false, 'message' => 'Folder IDs are required']);
        exit;
    }

    $folderManager = new FolderManager();
    $result = $folderManager->deleteMultipleFolders($folderIds, $_SESSION['user_id']);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
