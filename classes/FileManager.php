<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/User.php';

class FileManager {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function uploadFile($fileData, $folderId = null, $userId = null) {
        try {
            if (!$userId) {
                session_start();
                $userId = $_SESSION['user_id'] ?? null;
            }

            if (!$userId) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }

            // Check upload permissions for the target folder
            if ($folderId && !$this->hasUploadPermission($folderId, $userId)) {
                return ['success' => false, 'message' => 'Permission denied: You do not have upload access to this folder'];
            }

            // Validate file
            $validation = $this->validateFile($fileData);
            if (!$validation['success']) {
                return $validation;
            }

            // Check storage limit
            $user = new User();
            if (!$user->checkStorageLimit($userId, $fileData['size'])) {
                return ['success' => false, 'message' => 'Storage limit exceeded'];
            }

            // Generate unique filename
            $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
            $fileName = $this->generateUniqueFileName($extension);
            $filePath = UPLOAD_PATH . $fileName;

            // Create folder structure if needed
            if ($folderId) {
                $folderPath = $this->getFolderPath($folderId);
                if ($folderPath) {
                    $fullPath = UPLOAD_PATH . $folderPath . '/';
                    if (!file_exists($fullPath)) {
                        mkdir($fullPath, 0755, true);
                    }
                    $filePath = $fullPath . $fileName;
                }
            }

            // Move uploaded file
            if (move_uploaded_file($fileData['tmp_name'], $filePath)) {
                // Save to database
                $stmt = $this->conn->prepare("
                    INSERT INTO files (original_name, file_name, file_path, file_size, file_type, mime_type, folder_id, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $fileData['name'],
                    $fileName,
                    $filePath,
                    $fileData['size'],
                    $extension,
                    $fileData['type'],
                    $folderId,
                    $userId
                ]);

                if ($result) {
                    $fileId = $this->conn->lastInsertId();
                    
                    // Update user storage usage
                    $user->updateUsedStorage($userId, $fileData['size']);
                    
                    $this->logActivity($userId, 'upload', 'file', $fileId, ['filename' => $fileData['name']]);
                    return ['success' => true, 'message' => 'File uploaded successfully', 'file_id' => $fileId];
                }
            }

            return ['success' => false, 'message' => 'File upload failed'];
        } catch (Exception $e) {
            error_log("Upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    public function uploadChunk($sessionId, $chunkNumber, $chunkData) {
        try {
            // Get upload session
            $stmt = $this->conn->prepare("SELECT * FROM upload_sessions WHERE id = ? AND expires_at > NOW()");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();

            if (!$session) {
                return ['success' => false, 'message' => 'Invalid or expired upload session'];
            }

            // Save chunk to temporary location
            $chunkPath = UPLOAD_PATH . 'chunks/' . $sessionId . '/';
            if (!file_exists($chunkPath)) {
                mkdir($chunkPath, 0755, true);
            }

            $chunkFile = $chunkPath . 'chunk_' . $chunkNumber;
            if (file_put_contents($chunkFile, $chunkData) === false) {
                return ['success' => false, 'message' => 'Failed to save chunk'];
            }

            // Update uploaded chunks
            $uploadedChunks = json_decode($session['uploaded_chunks'] ?? '[]', true);
            if (!in_array($chunkNumber, $uploadedChunks)) {
                $uploadedChunks[] = $chunkNumber;
                sort($uploadedChunks);

                $stmt = $this->conn->prepare("UPDATE upload_sessions SET uploaded_chunks = ? WHERE id = ?");
                $stmt->execute([json_encode($uploadedChunks), $sessionId]);
            }

            // Check if all chunks are uploaded
            if (count($uploadedChunks) == $session['total_chunks']) {
                return $this->assembleChunks($sessionId, $session);
            }

            return ['success' => true, 'message' => 'Chunk uploaded', 'uploaded_chunks' => count($uploadedChunks), 'total_chunks' => $session['total_chunks']];
        } catch (Exception $e) {
            error_log("Chunk upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Chunk upload failed'];
        }
    }

    private function assembleChunks($sessionId, $session) {
        try {
            $chunkPath = UPLOAD_PATH . 'chunks/' . $sessionId . '/';
            $extension = pathinfo($session['original_filename'], PATHINFO_EXTENSION);
            $fileName = $this->generateUniqueFileName($extension);
            $finalPath = UPLOAD_PATH . $fileName;

            // Create final file
            $finalFile = fopen($finalPath, 'wb');
            if (!$finalFile) {
                return ['success' => false, 'message' => 'Failed to create final file'];
            }

            // Assemble chunks
            for ($i = 0; $i < $session['total_chunks']; $i++) {
                $chunkFile = $chunkPath . 'chunk_' . $i;
                if (file_exists($chunkFile)) {
                    $chunkData = file_get_contents($chunkFile);
                    fwrite($finalFile, $chunkData);
                    unlink($chunkFile); // Delete chunk after use
                }
            }
            fclose($finalFile);

            // Remove chunk directory
            rmdir($chunkPath);

            // Save to database
            $stmt = $this->conn->prepare("
                INSERT INTO files (original_name, file_name, file_path, file_size, file_type, mime_type, folder_id, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $mimeType = mime_content_type($finalPath);
                $result = $stmt->execute([
                    $session['original_filename'],
                    $fileName,
                    $finalPath,
                    $session['total_size'],
                    $extension,
                    $mimeType,
                    $session['folder_id'],
                    $session['user_id']
                ]);

                if ($result) {
                    $fileId = $this->conn->lastInsertId();
                    
                    // Update user storage usage
                    $user = new User();
                    $user->updateUsedStorage($session['user_id'], $session['total_size']);
                    
                    // Clean up upload session
                    $stmt = $this->conn->prepare("DELETE FROM upload_sessions WHERE id = ?");
                    $stmt->execute([$sessionId]);

                    $this->logActivity($session['user_id'], 'upload', 'file', $fileId, ['filename' => $session['original_filename']]);
                    return ['success' => true, 'message' => 'File uploaded successfully', 'file_id' => $fileId];
                }            return ['success' => false, 'message' => 'Failed to save file record'];
        } catch (Exception $e) {
            error_log("Assemble chunks error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to assemble file'];
        }
    }

    public function createUploadSession($filename, $totalSize, $chunkSize, $folderId = null, $userId = null) {
        try {
            if (!$userId) {
                session_start();
                $userId = $_SESSION['user_id'] ?? null;
            }

            // Check storage limit before creating session
            $user = new User();
            if (!$user->checkStorageLimit($userId, $totalSize)) {
                return ['success' => false, 'message' => 'Storage limit exceeded'];
            }

            $sessionId = uniqid('upload_', true);
            $totalChunks = ceil($totalSize / $chunkSize);

            $stmt = $this->conn->prepare("
                INSERT INTO upload_sessions (id, user_id, original_filename, total_size, chunk_size, total_chunks, folder_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$sessionId, $userId, $filename, $totalSize, $chunkSize, $totalChunks, $folderId]);

            if ($result) {
                return ['success' => true, 'session_id' => $sessionId, 'total_chunks' => $totalChunks];
            }

            return ['success' => false, 'message' => 'Failed to create upload session'];
        } catch (Exception $e) {
            error_log("Create upload session error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create upload session'];
        }
    }

    public function downloadFile($fileId, $userId = null) {
        try {
            if (!$userId) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $userId = $_SESSION['user_id'] ?? null;
            }

            if (!$userId) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }

            // Check file permissions
            if (!$this->hasFilePermission($fileId, $userId, 'download')) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            $stmt = $this->conn->prepare("SELECT * FROM files WHERE id = ? AND is_deleted = 0");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();

            if (!$file) {
                return ['success' => false, 'message' => 'File not found'];
            }

            if (!file_exists($file['file_path'])) {
                return ['success' => false, 'message' => 'File not found on disk'];
            }

            $this->logActivity($userId, 'download', 'file', $fileId, ['filename' => $file['original_name']]);

            return [
                'success' => true,
                'file_path' => $file['file_path'],
                'original_name' => $file['original_name'],
                'mime_type' => $file['mime_type'],
                'file_size' => $file['file_size']
            ];
        } catch (Exception $e) {
            error_log("Download error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Download failed'];
        }
    }

    public function deleteFile($fileId, $userId = null) {
        try {
            if (!$userId) {
                session_start();
                $userId = $_SESSION['user_id'] ?? null;
            }

            // Check file permissions
            if (!$this->hasFilePermission($fileId, $userId, 'delete')) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            $stmt = $this->conn->prepare("SELECT * FROM files WHERE id = ? AND is_deleted = 0");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();

            if (!$file) {
                return ['success' => false, 'message' => 'File not found'];
            }

            // Soft delete in database
            $stmt = $this->conn->prepare("UPDATE files SET is_deleted = 1 WHERE id = ?");
            $result = $stmt->execute([$fileId]);

            if ($result) {
                // Actually delete file from disk
                if (file_exists($file['file_path'])) {
                    unlink($file['file_path']);
                }

                // Update user storage usage
                $user = new User();
                $user->updateUsedStorage($file['uploaded_by'], -$file['file_size']);

                $this->logActivity($userId, 'delete', 'file', $fileId, ['filename' => $file['original_name']]);
                return ['success' => true, 'message' => 'File deleted successfully'];
            }

            return ['success' => false, 'message' => 'Failed to delete file'];
        } catch (Exception $e) {
            error_log("Delete error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }

    public function getFiles($folderId = null, $userId = null, $page = 1, $perPage = 50) {
        try {
            if (!$userId) {
                session_start();
                $userId = $_SESSION['user_id'] ?? null;
            }

            // Limit per page to max 200 and total to 200
            $perPage = min($perPage, 200);
            $maxResults = 200;
            $offset = ($page - 1) * $perPage;
            
            // If offset would exceed max results, return empty array
            if ($offset >= $maxResults) {
                return ['files' => [], 'total' => 0, 'hasMore' => false];
            }
            
            // Adjust limit if it would exceed max results
            $limit = min($perPage, $maxResults - $offset);

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
                WHERE f.is_deleted = 0
            ";
            $params = [$userId, $userId, $userId];
            if ($folderId !== null) {
                $sql .= " AND f.folder_id = ?";
                $params[] = $folderId;
            } else {
                $sql .= " AND f.folder_id IS NULL";
            }

            // Add permission check for non-admin users
            if (!$this->isAdmin($userId)) {
                $sql .= " AND (f.uploaded_by = ? OR EXISTS (
                    SELECT 1 FROM file_permissions fp 
                    WHERE fp.file_id = f.id AND fp.user_id = ? AND fp.can_view = 1
                )";
                $params[] = $userId;
                $params[] = $userId;
                
                // Also check folder permissions if files are in a folder
                if ($folderId !== null) {
                    $sql .= " OR EXISTS (
                        SELECT 1 FROM folder_permissions folp 
                        WHERE folp.folder_id = ? AND folp.user_id = ? AND folp.can_view = 1
                    )";
                    $params[] = $folderId;
                    $params[] = $userId;
                }
                
                $sql .= ")";
            }

            // Get total count with a separate simpler query
            $countSql = "
                SELECT COUNT(*) as total
                FROM files f
                WHERE f.is_deleted = 0
            ";
            $countParams = [];

            if ($folderId !== null) {
                $countSql .= " AND f.folder_id = ?";
                $countParams[] = $folderId;
            } else {
                $countSql .= " AND f.folder_id IS NULL";
            }

            // Add permission check for non-admin users
            if (!$this->isAdmin($userId)) {
                $countSql .= " AND (f.uploaded_by = ? OR EXISTS (
                    SELECT 1 FROM file_permissions fp 
                    WHERE fp.file_id = f.id AND fp.user_id = ? AND fp.can_view = 1
                )";
                $countParams[] = $userId;
                $countParams[] = $userId;
                
                // Also check folder permissions if files are in a folder
                if ($folderId !== null) {
                    $countSql .= " OR EXISTS (
                        SELECT 1 FROM folder_permissions folp 
                        WHERE folp.folder_id = ? AND folp.user_id = ? AND folp.can_view = 1
                    )";
                    $countParams[] = $folderId;
                    $countParams[] = $userId;
                }
                
                $countSql .= ")";
            }
            
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($countParams);
            $totalCount = min($countStmt->fetch()['total'], $maxResults);

            $sql .= " ORDER BY f.upload_date DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $files = $stmt->fetchAll();
            
            $hasMore = ($offset + count($files)) < $totalCount && ($offset + count($files)) < $maxResults;

            return [
                'files' => $files,
                'total' => $totalCount,
                'hasMore' => $hasMore,
                'currentPage' => $page,
                'perPage' => $perPage
            ];
        } catch (Exception $e) {
            error_log("Get files error: " . $e->getMessage());
            return ['files' => [], 'total' => 0, 'hasMore' => false];
        }
    }

    // Legacy method for backward compatibility - now uses pagination
    public function getFilesLegacy($folderId = null, $userId = null) {
        $result = $this->getFiles($folderId, $userId, 1, 20); // Default to 20 items
        return $result['files'];
    }

    private function validateFile($fileData) {
        // Check file size
        if ($fileData['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File too large'];
        }

        // Allow all file types - no extension restrictions
        // Only basic security checks
        $filename = $fileData['name'];
        
        // Block potentially dangerous files that could be executed on the server
        $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd', 'scr', 'vbs', 'js'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Only block if it's a dangerous server-side executable file
        if (in_array($extension, $dangerousExtensions)) {
            return ['success' => false, 'message' => 'Executable files are not allowed for security reasons'];
        }

        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error occurred'];
        }

        return ['success' => true];
    }

    private function generateUniqueFileName($extension) {
        return uniqid('file_', true) . '.' . $extension;
    }

    private function getFolderPath($folderId) {
        try {
            $stmt = $this->conn->prepare("SELECT name, parent_id FROM folders WHERE id = ?");
            $stmt->execute([$folderId]);
            $folder = $stmt->fetch();

            if (!$folder) {
                return null;
            }

            $path = $folder['name'];
            if ($folder['parent_id']) {
                $parentPath = $this->getFolderPath($folder['parent_id']);
                if ($parentPath) {
                    $path = $parentPath . '/' . $path;
                }
            }

            return $path;
        } catch (Exception $e) {
            error_log("Get folder path error: " . $e->getMessage());
            return null;
        }
    }

    private function hasFilePermission($fileId, $userId, $permission) {
        try {
            // Admin has all permissions
            if ($this->isAdmin($userId)) {
                return true;
            }

            // Check if user owns the file
            $stmt = $this->conn->prepare("SELECT uploaded_by, folder_id FROM files WHERE id = ?");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();

            if ($file && $file['uploaded_by'] == $userId) {
                return true;
            }

            // Check explicit file permissions
            $permissionColumn = 'can_' . $permission;
            $stmt = $this->conn->prepare("SELECT $permissionColumn FROM file_permissions WHERE file_id = ? AND user_id = ?");
            $stmt->execute([$fileId, $userId]);
            $perm = $stmt->fetch();

            if ($perm && $perm[$permissionColumn]) {
                return true;
            }

            // Check folder permissions if file is in a folder
            if ($file && $file['folder_id']) {
                $stmt = $this->conn->prepare("SELECT $permissionColumn FROM folder_permissions WHERE folder_id = ? AND user_id = ?");
                $stmt->execute([$file['folder_id'], $userId]);
                $folderPerm = $stmt->fetch();

                if ($folderPerm && $folderPerm[$permissionColumn]) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            error_log("Check file permission error: " . $e->getMessage());
            return false;
        }
    }

    private function hasUploadPermission($folderId, $userId) {
        try {
            // Admin has all permissions
            if ($this->isAdmin($userId)) {
                return true;
            }

            // If no folder specified, check if user can upload to root
            if (!$folderId) {
                return $this->isAdmin($userId);
            }

            // Check if it's user's home folder or subfolder
            if ($this->isUserHomeFolder($folderId, $userId)) {
                return true;
            }

            // Check if user owns the folder
            $stmt = $this->conn->prepare("SELECT owner_id FROM folders WHERE id = ?");
            $stmt->execute([$folderId]);
            $folder = $stmt->fetch();

            if ($folder && $folder['owner_id'] == $userId) {
                return true;
            }

            // Check explicit upload permissions
            $stmt = $this->conn->prepare("SELECT can_upload FROM folder_permissions WHERE folder_id = ? AND user_id = ?");
            $stmt->execute([$folderId, $userId]);
            $perm = $stmt->fetch();

            return $perm && $perm['can_upload'];
        } catch (Exception $e) {
            error_log("Check upload permission error: " . $e->getMessage());
            return false;
        }
    }

    private function isUserHomeFolder($folderId, $userId) {
        try {
            // Get user's username
            $stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return false;
            }

            // Check if the folder or any parent folder is the user's home folder
            $currentFolderId = $folderId;
            while ($currentFolderId) {
                $stmt = $this->conn->prepare("SELECT name, parent_id FROM folders WHERE id = ?");
                $stmt->execute([$currentFolderId]);
                $folder = $stmt->fetch();

                if (!$folder) {
                    break;
                }

                // If this is a root folder (parent_id is null) and matches username
                if ($folder['parent_id'] === null && $folder['name'] === $user['username']) {
                    return true;
                }

                $currentFolderId = $folder['parent_id'];
            }

            return false;
        } catch (Exception $e) {
            error_log("Check user home folder error: " . $e->getMessage());
            return false;
        }
    }

    public function renameFile($fileId, $newName, $userId) {
        try {
            // Check if user has permission to rename this file
            if (!$this->canManageFile($fileId, $userId)) {
                return ['success' => false, 'message' => 'Permission denied: You cannot rename this file'];
            }

            // Validate new name
            if (empty(trim($newName))) {
                return ['success' => false, 'message' => 'File name cannot be empty'];
            }

            // Get current file info
            $stmt = $this->conn->prepare("SELECT original_name, folder_id FROM files WHERE id = ? AND is_deleted = 0");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();

            if (!$file) {
                return ['success' => false, 'message' => 'File not found'];
            }

            // Check if new name already exists in the same folder
            $stmt = $this->conn->prepare("SELECT id FROM files WHERE original_name = ? AND folder_id = ? AND id != ? AND is_deleted = 0");
            $stmt->execute([$newName, $file['folder_id'], $fileId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'A file with this name already exists in this location'];
            }

            // Update file name
            $stmt = $this->conn->prepare("UPDATE files SET original_name = ? WHERE id = ?");
            $result = $stmt->execute([$newName, $fileId]);

            if ($result) {
                $this->logActivity($userId, 'rename', 'file', $fileId, [
                    'old_name' => $file['original_name'],
                    'new_name' => $newName
                ]);
                return ['success' => true, 'message' => 'File renamed successfully'];
            } else {
                return ['success' => false, 'message' => 'Unable to rename file - please try again'];
            }
        } catch (Exception $e) {
            error_log("Rename file error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to rename file - please try again'];
        }
    }

    public function deleteMultipleFiles($fileIds, $userId) {
        try {
            $deletedCount = 0;
            $failedFiles = [];

            foreach ($fileIds as $fileId) {
                if ($this->canManageFile($fileId, $userId)) {
                    $result = $this->deleteFile($fileId, $userId);
                    if ($result['success']) {
                        $deletedCount++;
                    } else {
                        $failedFiles[] = $fileId;
                    }
                } else {
                    $failedFiles[] = $fileId;
                }
            }

            $totalFiles = count($fileIds);
            if ($deletedCount === $totalFiles) {
                return ['success' => true, 'message' => "Successfully deleted $deletedCount files"];
            } else if ($deletedCount > 0) {
                $failedCount = count($failedFiles);
                return ['success' => true, 'message' => "Deleted $deletedCount of $totalFiles files - $failedCount files could not be deleted"];
            } else {
                return ['success' => false, 'message' => 'No files could be deleted - check your permissions'];
            }
        } catch (Exception $e) {
            error_log("Delete multiple files error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to delete files - please try again'];
        }
    }

    private function canManageFile($fileId, $userId) {
        try {
            // Admin can manage any file
            if ($this->isAdmin($userId)) {
                return true;
            }

            // Check if user owns the file
            $stmt = $this->conn->prepare("SELECT uploaded_by, folder_id FROM files WHERE id = ? AND is_deleted = 0");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();

            if (!$file) {
                return false;
            }

            // User can manage their own files
            if ($file['uploaded_by'] == $userId) {
                return true;
            }

            // Check if user has delete permission for the folder
            if ($file['folder_id']) {
                $stmt = $this->conn->prepare("
                    SELECT can_delete FROM folder_permissions 
                    WHERE folder_id = ? AND user_id = ?
                ");
                $stmt->execute([$file['folder_id'], $userId]);
                $permission = $stmt->fetch();
                return $permission && $permission['can_delete'];
            }

            return false;
        } catch (Exception $e) {
            error_log("Can manage file error: " . $e->getMessage());
            return false;
        }
    }

    private function isAdmin($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            return $user && $user['role'] === 'admin';
        } catch (Exception $e) {
            return false;
        }
    }

    private function logActivity($userId, $action, $resourceType, $resourceId, $details = []) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO activity_logs (user_id, action, resource_type, resource_id, details, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $action,
                $resourceType,
                $resourceId,
                json_encode($details),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Log activity error: " . $e->getMessage());
        }
    }
}
?>
