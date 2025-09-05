<?php
require_once __DIR__ . '/../config/database.php';

class FolderManager {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function createFolder($name, $parentId = null, $userId = null) {
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

            // Check if user has permission to create folders in the target location
            if ($parentId && !$this->hasCreatePermission($parentId, $userId)) {
                return ['success' => false, 'message' => 'Permission denied: You cannot create folders in this location'];
            }

            // Validate folder name
            if (empty(trim($name))) {
                return ['success' => false, 'message' => 'Folder name cannot be empty'];
            }

            // Check if folder already exists in the same parent
            $stmt = $this->conn->prepare("SELECT id FROM folders WHERE name = ? AND parent_id = ?");
            $stmt->execute([$name, $parentId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Folder already exists in this location'];
            }

            // Create folder in database
            $stmt = $this->conn->prepare("INSERT INTO folders (name, parent_id, owner_id) VALUES (?, ?, ?)");
            $result = $stmt->execute([$name, $parentId, $userId]);

            if ($result) {
                $folderId = $this->conn->lastInsertId();
                
                // Create physical folder
                $folderPath = $this->getFolderPath($folderId);
                $fullPath = UPLOAD_PATH . $folderPath;
                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }

                $this->logActivity($userId, 'create', 'folder', $folderId, ['name' => $name]);
                return ['success' => true, 'message' => 'Folder created successfully', 'folder_id' => $folderId];
            }

            return ['success' => false, 'message' => 'Failed to create folder'];
        } catch (Exception $e) {
            error_log("Create folder error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create folder'];
        }
    }

    public function deleteFolder($folderId, $userId = null, $force = false) {
        try {
            if (!$userId) {
                if (session_status() === PHP_SESSION_NONE) { session_start(); }
                $userId = $_SESSION['user_id'] ?? null;
            }

            // Check folder permissions
            if (!$this->hasFolderPermission($folderId, $userId, 'delete')) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            $stmt = $this->conn->prepare("SELECT * FROM folders WHERE id = ?");
            $stmt->execute([$folderId]);
            $folder = $stmt->fetch();

            if (!$folder) {
                return ['success' => false, 'message' => 'Folder not found'];
            }

            // Check if folder has children
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM folders WHERE parent_id = ?");
            $stmt->execute([$folderId]);
            $childCount = $stmt->fetch()['count'];

            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM files WHERE folder_id = ? AND is_deleted = 0");
            $stmt->execute([$folderId]);
            $fileCount = $stmt->fetch()['count'];

            if (($childCount > 0 || $fileCount > 0) && !$force) {
                return ['success' => false, 'message' => 'Folder is not empty. Please delete all contents first.'];
            }

            // If force delete, recursively delete all contents
            if ($force && ($childCount > 0 || $fileCount > 0)) {
                // Delete all files in this folder
                if ($fileCount > 0) {
                    $stmt = $this->conn->prepare("SELECT id FROM files WHERE folder_id = ? AND is_deleted = 0");
                    $stmt->execute([$folderId]);
                    $files = $stmt->fetchAll();
                    
                    require_once __DIR__ . '/FileManager.php';
                    $fileManager = new FileManager();
                    
                    foreach ($files as $file) {
                        $fileManager->deleteFile($file['id'], $userId);
                    }
                }
                
                // Delete all subfolders
                if ($childCount > 0) {
                    $stmt = $this->conn->prepare("SELECT id FROM folders WHERE parent_id = ?");
                    $stmt->execute([$folderId]);
                    $subfolders = $stmt->fetchAll();
                    
                    foreach ($subfolders as $subfolder) {
                        $this->deleteFolder($subfolder['id'], $userId, true);
                    }
                }
            }

            // Delete folder from database
            $stmt = $this->conn->prepare("DELETE FROM folders WHERE id = ?");
            $result = $stmt->execute([$folderId]);

            if ($result) {
                // Delete physical folder and all its contents
                $folderPath = $this->getFolderPath($folderId);
                $fullPath = UPLOAD_PATH . $folderPath;
                if (file_exists($fullPath)) {
                    $this->deleteDirectory($fullPath);
                }

                $this->logActivity($userId, 'delete', 'folder', $folderId, ['name' => $folder['name'], 'force' => $force]);
                return ['success' => true, 'message' => 'Folder deleted successfully'];
            }

            return ['success' => false, 'message' => 'Failed to delete folder'];
        } catch (Exception $e) {
            error_log("Delete folder error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete folder'];
        }
    }

    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    public function shareFolder($folderId, $userIds, $permissions, $userId = null) {
        try {
            if (!$userId) {
                if (session_status() === PHP_SESSION_NONE) { session_start(); }
                $userId = $_SESSION['user_id'] ?? null;
            }

            // Check if user can share this folder (owner or admin)
            if (!$this->canShareFolder($folderId, $userId)) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            $stmt = $this->conn->prepare("UPDATE folders SET is_shared = 1 WHERE id = ?");
            $stmt->execute([$folderId]);

            // Add permissions for each user
            $successCount = 0;
            foreach ($userIds as $targetUserId) {
                if ($targetUserId == $userId) continue; // Skip self

                // Remove existing permissions
                $stmt = $this->conn->prepare("DELETE FROM folder_permissions WHERE folder_id = ? AND user_id = ?");
                $stmt->execute([$folderId, $targetUserId]);

                // Check if target user is admin
                $isTargetAdmin = $this->isAdmin($targetUserId);
                
                // Add new permissions (admin gets full permissions regardless of requested permissions)
                $stmt = $this->conn->prepare("
                    INSERT INTO folder_permissions (folder_id, user_id, can_view, can_download, can_upload, can_delete, granted_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $folderId,
                    $targetUserId,
                    $isTargetAdmin ? 1 : (($permissions['can_view'] ?? true) ? 1 : 0),
                    $isTargetAdmin ? 1 : (($permissions['can_download'] ?? true) ? 1 : 0),
                    $isTargetAdmin ? 1 : (($permissions['can_upload'] ?? false) ? 1 : 0),
                    $isTargetAdmin ? 1 : (($permissions['can_delete'] ?? false) ? 1 : 0),
                    $userId
                ]);

                if ($result) {
                    $successCount++;
                }
            }

            // Ensure all admins have full access to this folder
            $this->ensureAdminAccess($folderId, $userId);

            if ($successCount > 0) {
                $this->logActivity($userId, 'share', 'folder', $folderId, [
                    'shared_with' => count($userIds),
                    'permissions' => $permissions
                ]);
                return ['success' => true, 'message' => "Folder shared with {$successCount} user(s)"];
            }

            return ['success' => false, 'message' => 'Failed to share folder'];
        } catch (Exception $e) {
            error_log("Share folder error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to share folder'];
        }
    }

    public function updateFolderPermissions($folderId, $userId, $permissions, $granterUserId = null) {
        try {
            if (!$granterUserId) {
                if (session_status() === PHP_SESSION_NONE) { session_start(); }
                $granterUserId = $_SESSION['user_id'] ?? null;
            }

            // Check if granter can modify permissions
            if (!$this->canShareFolder($folderId, $granterUserId)) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            $stmt = $this->conn->prepare("
                UPDATE folder_permissions 
                SET can_view = ?, can_download = ?, can_upload = ?, can_delete = ? 
                WHERE folder_id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([
                ($permissions['can_view'] ?? true) ? 1 : 0,
                ($permissions['can_download'] ?? true) ? 1 : 0,
                ($permissions['can_upload'] ?? false) ? 1 : 0,
                ($permissions['can_delete'] ?? false) ? 1 : 0,
                $folderId,
                $userId
            ]);

            if ($result) {
                $this->logActivity($granterUserId, 'update_permissions', 'folder', $folderId, [
                    'target_user' => $userId,
                    'permissions' => $permissions
                ]);
                return ['success' => true, 'message' => 'Permissions updated'];
            }

            return ['success' => false, 'message' => 'Failed to update permissions'];
        } catch (Exception $e) {
            error_log("Update folder permissions error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update permissions'];
        }
    }

    public function removeFolderAccess($folderId, $userId, $granterUserId = null) {
        try {
            if (!$granterUserId) {
                if (session_status() === PHP_SESSION_NONE) { session_start(); }
                $granterUserId = $_SESSION['user_id'] ?? null;
            }

            // Check if granter can modify permissions
            if (!$this->canShareFolder($folderId, $granterUserId)) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            $stmt = $this->conn->prepare("DELETE FROM folder_permissions WHERE folder_id = ? AND user_id = ?");
            $result = $stmt->execute([$folderId, $userId]);

            if ($result) {
                $this->logActivity($granterUserId, 'remove_access', 'folder', $folderId, ['target_user' => $userId]);
                return ['success' => true, 'message' => 'Access removed'];
            }

            return ['success' => false, 'message' => 'Failed to remove access'];
        } catch (Exception $e) {
            error_log("Remove folder access error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to remove access'];
        }
    }

    public function getFolders($parentId = null, $userId = null, $page = 1, $perPage = 50) {
        try {
            if (!$userId) {
                if (session_status() === PHP_SESSION_NONE) { session_start(); }
                $userId = $_SESSION['user_id'] ?? null;
            }

            // Limit per page to max 200 and total to 200
            $perPage = min($perPage, 200);
            $maxResults = 200;
            $offset = ($page - 1) * $perPage;
            
            // If offset would exceed max results, return empty array
            if ($offset >= $maxResults) {
                return ['folders' => [], 'total' => 0, 'hasMore' => false];
            }
            
            // Adjust limit if it would exceed max results
            $limit = min($perPage, $maxResults - $offset);

            $sql = "
                SELECT f.*, u.username as owner_name,
                       CASE WHEN fp.folder_id IS NOT NULL THEN 1 ELSE 0 END as has_access,
                       CASE 
                           WHEN f.owner_id = ? THEN 1
                           WHEN fp.can_delete = 1 THEN 1
                           ELSE 0
                       END as can_delete_folder
                FROM folders f
                LEFT JOIN users u ON f.owner_id = u.id
                LEFT JOIN folder_permissions fp ON f.id = fp.folder_id AND fp.user_id = ?
                WHERE 1=1
            ";
            $params = [$userId, $userId];

            if ($parentId !== null) {
                $sql .= " AND f.parent_id = ?";
                $params[] = $parentId;
            } else {
                $sql .= " AND f.parent_id IS NULL";
            }

            // Add permission check for non-admin users
            if (!$this->isAdmin($userId)) {
                $sql .= " AND (f.owner_id = ? OR fp.folder_id IS NOT NULL)";
                $params[] = $userId;
            }

            // Get total count with a separate simpler query
            $countSql = "
                SELECT COUNT(*) as total
                FROM folders f
                LEFT JOIN folder_permissions fp ON f.id = fp.folder_id AND fp.user_id = ?
                WHERE 1=1
            ";
            $countParams = [$userId];
            
            if ($parentId !== null) {
                $countSql .= " AND f.parent_id = ?";
                $countParams[] = $parentId;
            } else {
                $countSql .= " AND f.parent_id IS NULL";
            }

            // Add permission check for non-admin users
            if (!$this->isAdmin($userId)) {
                $countSql .= " AND (f.owner_id = ? OR fp.folder_id IS NOT NULL)";
                $countParams[] = $userId;
            }
            
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($countParams);
            $totalCount = min($countStmt->fetch()['total'], $maxResults);

            $sql .= " ORDER BY f.name LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $folders = $stmt->fetchAll();
            
            $hasMore = ($offset + count($folders)) < $totalCount && ($offset + count($folders)) < $maxResults;

            return [
                'folders' => $folders,
                'total' => $totalCount,
                'hasMore' => $hasMore,
                'currentPage' => $page,
                'perPage' => $perPage
            ];
        } catch (Exception $e) {
            error_log("Get folders error: " . $e->getMessage());
            return ['folders' => [], 'total' => 0, 'hasMore' => false];
        }
    }

    // Legacy method for backward compatibility - now uses pagination
    public function getFoldersLegacy($parentId = null, $userId = null) {
        $result = $this->getFolders($parentId, $userId, 1, 20); // Default to 20 items
        return $result['folders'];
    }

    public function getFolderPermissions($folderId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT fp.*, u.username, u.email
                FROM folder_permissions fp
                LEFT JOIN users u ON fp.user_id = u.id
                WHERE fp.folder_id = ?
                ORDER BY u.username
            ");
            $stmt->execute([$folderId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get folder permissions error: " . $e->getMessage());
            return [];
        }
    }

    public function getFolderBreadcrumb($folderId) {
        try {
            $breadcrumb = [];
            $currentId = $folderId;

            while ($currentId) {
                $stmt = $this->conn->prepare("SELECT id, name, parent_id FROM folders WHERE id = ?");
                $stmt->execute([$currentId]);
                $folder = $stmt->fetch();

                if (!$folder) break;

                array_unshift($breadcrumb, $folder);
                $currentId = $folder['parent_id'];
            }

            return $breadcrumb;
        } catch (Exception $e) {
            error_log("Get folder breadcrumb error: " . $e->getMessage());
            return [];
        }
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

    private function hasFolderPermission($folderId, $userId, $permission) {
        try {
            // Admin has all permissions
            if ($this->isAdmin($userId)) {
                return true;
            }

            // Check if user owns the folder
            $stmt = $this->conn->prepare("SELECT owner_id FROM folders WHERE id = ?");
            $stmt->execute([$folderId]);
            $folder = $stmt->fetch();

            if ($folder && $folder['owner_id'] == $userId) {
                return true;
            }

            // Check explicit permissions
            $permissionColumn = 'can_' . $permission;
            $stmt = $this->conn->prepare("SELECT $permissionColumn FROM folder_permissions WHERE folder_id = ? AND user_id = ?");
            $stmt->execute([$folderId, $userId]);
            $perm = $stmt->fetch();

            return $perm && $perm[$permissionColumn];
        } catch (Exception $e) {
            error_log("Check folder permission error: " . $e->getMessage());
            return false;
        }
    }

    private function ensureAdminAccess($folderId, $granterUserId) {
        try {
            // Get all admin users
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE role = 'admin'");
            $stmt->execute();
            $admins = $stmt->fetchAll();

            foreach ($admins as $admin) {
                $adminId = $admin['id'];
                
                // Skip if admin is the one granting access (they already own the folder)
                if ($adminId == $granterUserId) continue;

                // Check if admin already has permissions
                $stmt = $this->conn->prepare("SELECT id FROM folder_permissions WHERE folder_id = ? AND user_id = ?");
                $stmt->execute([$folderId, $adminId]);
                
                if (!$stmt->fetch()) {
                    // Grant full permissions to admin
                    $stmt = $this->conn->prepare("
                        INSERT INTO folder_permissions (folder_id, user_id, can_view, can_download, can_upload, can_delete, granted_by) 
                        VALUES (?, ?, 1, 1, 1, 1, ?)
                    ");
                    $stmt->execute([$folderId, $adminId, $granterUserId]);
                } else {
                    // Update existing permissions to full access
                    $stmt = $this->conn->prepare("
                        UPDATE folder_permissions 
                        SET can_view = 1, can_download = 1, can_upload = 1, can_delete = 1 
                        WHERE folder_id = ? AND user_id = ?
                    ");
                    $stmt->execute([$folderId, $adminId]);
                }
            }
        } catch (Exception $e) {
            error_log("Ensure admin access error: " . $e->getMessage());
        }
    }

    private function canShareFolder($folderId, $userId) {
        try {
            // Admin can share any folder
            if ($this->isAdmin($userId)) {
                return true;
            }

            // Check if user owns the folder
            $stmt = $this->conn->prepare("SELECT owner_id FROM folders WHERE id = ?");
            $stmt->execute([$folderId]);
            $folder = $stmt->fetch();

            return $folder && $folder['owner_id'] == $userId;
        } catch (Exception $e) {
            error_log("Can share folder error: " . $e->getMessage());
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

    private function hasCreatePermission($parentId, $userId) {
        try {
            // Admin can create anywhere
            if ($this->isAdmin($userId)) {
                return true;
            }

            // If no parent (root level), only admin can create
            if (!$parentId) {
                return $this->isAdmin($userId);
            }

            // Check if it's user's home folder or subfolder
            if ($this->isUserHomeFolder($parentId, $userId)) {
                return true;
            }

            // Check if user owns the parent folder
            $stmt = $this->conn->prepare("SELECT owner_id FROM folders WHERE id = ?");
            $stmt->execute([$parentId]);
            $folder = $stmt->fetch();

            if ($folder && $folder['owner_id'] == $userId) {
                return true;
            }

            // Check explicit upload permissions (can upload implies can create folders)
            $stmt = $this->conn->prepare("SELECT can_upload FROM folder_permissions WHERE folder_id = ? AND user_id = ?");
            $stmt->execute([$parentId, $userId]);
            $perm = $stmt->fetch();

            return $perm && $perm['can_upload'];
        } catch (Exception $e) {
            error_log("Check create permission error: " . $e->getMessage());
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

    public function downloadFolder($folderId, $userId = null) {
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

            // Check folder permissions
            if (!$this->hasFolderPermission($folderId, $userId, 'download')) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            // Get folder info
            $stmt = $this->conn->prepare("SELECT * FROM folders WHERE id = ?");
            $stmt->execute([$folderId]);
            $folder = $stmt->fetch();

            if (!$folder) {
                return ['success' => false, 'message' => 'Folder not found'];
            }

            // Create temporary ZIP file
            $tempDir = sys_get_temp_dir();
            $zipFileName = 'folder_' . $folderId . '_' . time() . '.zip';
            $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipFileName;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                return ['success' => false, 'message' => 'Failed to create ZIP file'];
            }

            // Add folder contents to ZIP
            $this->addFolderToZip($zip, $folderId, $folder['name']);

            $zip->close();

            // Check if ZIP was created and has content
            if (!file_exists($zipPath) || filesize($zipPath) == 0) {
                return ['success' => false, 'message' => 'Failed to create ZIP file or folder is empty'];
            }

            $this->logActivity($userId, 'download', 'folder', $folderId, ['name' => $folder['name']]);

            return [
                'success' => true,
                'zip_path' => $zipPath,
                'folder_name' => $folder['name'],
                'file_size' => filesize($zipPath)
            ];
        } catch (Exception $e) {
            error_log("Download folder error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Download failed: ' . $e->getMessage()];
        }
    }

    public function renameFolder($folderId, $newName, $userId) {
        try {
            // Check if user has permission to rename this folder
            if (!$this->canManageFolder($folderId, $userId)) {
                return ['success' => false, 'message' => 'Permission denied: You cannot rename this folder'];
            }

            // Validate new name
            if (empty(trim($newName))) {
                return ['success' => false, 'message' => 'Folder name cannot be empty'];
            }

            // Get current folder info
            $stmt = $this->conn->prepare("SELECT name, parent_id FROM folders WHERE id = ?");
            $stmt->execute([$folderId]);
            $folder = $stmt->fetch();

            if (!$folder) {
                return ['success' => false, 'message' => 'Folder not found'];
            }

            // Check if new name already exists in the same parent
            $stmt = $this->conn->prepare("SELECT id FROM folders WHERE name = ? AND parent_id = ? AND id != ?");
            $stmt->execute([$newName, $folder['parent_id'], $folderId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'A folder with this name already exists in this location'];
            }

            // Update folder name
            $stmt = $this->conn->prepare("UPDATE folders SET name = ? WHERE id = ?");
            $result = $stmt->execute([$newName, $folderId]);

            if ($result) {
                $this->logActivity($userId, 'rename', 'folder', $folderId, [
                    'old_name' => $folder['name'],
                    'new_name' => $newName
                ]);
                return ['success' => true, 'message' => 'Folder renamed successfully'];
            } else {
                return ['success' => false, 'message' => 'Unable to rename folder - please try again'];
            }
        } catch (Exception $e) {
            error_log("Rename folder error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to rename folder - please try again'];
        }
    }

    public function deleteMultipleFolders($folderIds, $userId) {
        try {
            $deletedCount = 0;
            $failedFolders = [];

            foreach ($folderIds as $folderId) {
                if ($this->canManageFolder($folderId, $userId)) {
                    $result = $this->deleteFolder($folderId, $userId);
                    if ($result['success']) {
                        $deletedCount++;
                    } else {
                        $failedFolders[] = $folderId;
                    }
                } else {
                    $failedFolders[] = $folderId;
                }
            }

            $totalFolders = count($folderIds);
            if ($deletedCount === $totalFolders) {
                return ['success' => true, 'message' => "Successfully deleted $deletedCount folders"];
            } else if ($deletedCount > 0) {
                $failedCount = count($failedFolders);
                return ['success' => true, 'message' => "Deleted $deletedCount of $totalFolders folders - $failedCount folders could not be deleted"];
            } else {
                return ['success' => false, 'message' => 'No folders could be deleted - check your permissions'];
            }
        } catch (Exception $e) {
            error_log("Delete multiple folders error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to delete folders - please try again'];
        }
    }

    private function canManageFolder($folderId, $userId) {
        try {
            // Admin can manage any folder
            if ($this->isAdmin($userId)) {
                return true;
            }

            // Check if user owns the folder
            $stmt = $this->conn->prepare("SELECT owner_id FROM folders WHERE id = ?");
            $stmt->execute([$folderId]);
            $folder = $stmt->fetch();

            if (!$folder) {
                return false;
            }

            // User can manage their own folders
            if ($folder['owner_id'] == $userId) {
                return true;
            }

            // Check if user has delete permission for the folder
            $stmt = $this->conn->prepare("
                SELECT can_delete FROM folder_permissions 
                WHERE folder_id = ? AND user_id = ?
            ");
            $stmt->execute([$folderId, $userId]);
            $permission = $stmt->fetch();
            return $permission && $permission['can_delete'];
        } catch (Exception $e) {
            error_log("Can manage folder error: " . $e->getMessage());
            return false;
        }
    }

    private function addFolderToZip($zip, $folderId, $folderPath = '') {
        // Add files in this folder
        $stmt = $this->conn->prepare("SELECT * FROM files WHERE folder_id = ? AND is_deleted = 0");
        $stmt->execute([$folderId]);
        $files = $stmt->fetchAll();

        foreach ($files as $file) {
            if (file_exists($file['file_path'])) {
                $relativePath = $folderPath . '/' . $file['original_name'];
                $zip->addFile($file['file_path'], $relativePath);
            }
        }

        // Add subfolders recursively
        $stmt = $this->conn->prepare("SELECT * FROM folders WHERE parent_id = ?");
        $stmt->execute([$folderId]);
        $subfolders = $stmt->fetchAll();

        foreach ($subfolders as $subfolder) {
            $subfolderPath = $folderPath . '/' . $subfolder['name'];
            $zip->addEmptyDir($subfolderPath);
            $this->addFolderToZip($zip, $subfolder['id'], $subfolderPath);
        }
    }
}
?>
