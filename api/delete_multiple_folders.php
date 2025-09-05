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

    // Check if user is admin - admin can delete any folder
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

    if ($isAdmin) {
        // Admin bypass - allow deletion of any folders
        $folderManager = new FolderManager();
        $deletedCount = 0;
        $failedFolders = [];
        
        foreach ($folderIds as $folderId) {
            $result = $folderManager->deleteFolder($folderId, $_SESSION['user_id'], true);
            if ($result['success']) {
                $deletedCount++;
            } else {
                $failedFolders[] = $folderId;
            }
        }
        
        $totalFolders = count($folderIds);
        if ($deletedCount === $totalFolders) {
            echo json_encode(['success' => true, 'message' => "Successfully deleted $deletedCount folders"]);
        } else {
            $failedCount = count($failedFolders);
            echo json_encode(['success' => true, 'message' => "Deleted $deletedCount of $totalFolders folders - $failedCount folders could not be deleted"]);
        }
    } else {
        // For non-admin users, use normal permission checks
        $folderManager = new FolderManager();
        $result = $folderManager->deleteMultipleFolders($folderIds, $_SESSION['user_id']);
        echo json_encode($result);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
