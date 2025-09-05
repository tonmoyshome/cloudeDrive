<?php
require_once 'config/database.php';
require_once 'classes/FolderManager.php';
require_once 'classes/FileManager.php';

try {
    $pdo = getDBConnection();
    
    // Get jayanto's user ID
    $stmt = $pdo->query('SELECT id FROM users WHERE username = "jayanto"');
    $jayanto = $stmt->fetch();
    if (!$jayanto) {
        echo 'User jayanto not found' . PHP_EOL;
        exit;
    }
    $jayantoId = $jayanto['id'];
    echo 'Jayanto User ID: ' . $jayantoId . PHP_EOL;
    
    // Get Product 1 folder ID
    $stmt = $pdo->query('SELECT id FROM folders WHERE name = "Product 1"');
    $folder = $stmt->fetch();
    if (!$folder) {
        echo 'Product 1 folder not found' . PHP_EOL;
        exit;
    }
    $folderId = $folder['id'];
    echo 'Product 1 Folder ID: ' . $folderId . PHP_EOL;
    
    // Check if jayanto is admin
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$jayantoId]);
    $user = $stmt->fetch();
    $isAdmin = $user && $user['role'] === 'admin';
    echo 'Jayanto is admin: ' . ($isAdmin ? 'YES' : 'NO') . PHP_EOL;
    
    // Manual query to test
    $sql = "
        SELECT f.*, u.username as uploaded_by_name,
               CASE 
                   WHEN f.uploaded_by = ? THEN 1
                   WHEN EXISTS (
                       SELECT 1 FROM file_permissions fp 
                       WHERE fp.file_id = f.id AND fp.user_id = ? AND fp.can_delete = 1
                   ) THEN 1
                   WHEN f.folder_id IS NOT NULL AND EXISTS (
                       SELECT 1 FROM folder_permissions folp 
                       WHERE folp.folder_id = f.folder_id AND folp.user_id = ? AND folp.can_delete = 1
                   ) THEN 1
                   ELSE 0
               END as can_delete_file
        FROM files f
        LEFT JOIN users u ON f.uploaded_by = u.id
        WHERE f.is_deleted = 0 AND f.folder_id = ?";
    
    $params = [$jayantoId, $jayantoId, $jayantoId, $folderId];
    
    if (!$isAdmin) {
        $sql .= " AND (f.uploaded_by = ? OR EXISTS (
            SELECT 1 FROM file_permissions fp 
            WHERE fp.file_id = f.id AND fp.user_id = ? AND fp.can_view = 1
        ) OR EXISTS (
            SELECT 1 FROM folder_permissions folp 
            WHERE folp.folder_id = ? AND folp.user_id = ? AND folp.can_view = 1
        ))";
        $params[] = $jayantoId;
        $params[] = $jayantoId;
        $params[] = $folderId;
        $params[] = $jayantoId;
    }
    
    echo 'Query: ' . $sql . PHP_EOL;
    echo 'Params: ' . implode(', ', $params) . PHP_EOL;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $files = $stmt->fetchAll();
    
    echo 'Files in Product 1 for jayanto:' . PHP_EOL;
    foreach ($files as $f) {
        echo 'File: ' . $f['original_name'] . ', can_delete: ' . ($f['can_delete_file'] ? 'YES' : 'NO') . ', uploaded_by: ' . $f['uploaded_by'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo 'Stack trace: ' . $e->getTraceAsString() . PHP_EOL;
}
?>
