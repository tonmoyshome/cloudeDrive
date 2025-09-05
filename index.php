<?php
session_start();
require_once 'classes/User.php';
require_once 'classes/FileManager.php';
require_once 'classes/FolderManager.php';
require_once 'config/database.php';

$user = new User();
$fileManager = new FileManager();
$folderManager = new FolderManager();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $user->getCurrentUser();
$folderId = $_GET['folder'] ?? null;

// Get pagination parameters
$folderPage = max(1, (int)($_GET['folder_page'] ?? 1));
$filePage = max(1, (int)($_GET['file_page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 20); // Default to 20
$perPage = in_array($perPage, [20, 50, 100, 200]) ? $perPage : 20; // Validate per page value

// Get folders and files with pagination
$foldersResult = $folderManager->getFolders($folderId, null, $folderPage, $perPage);
$filesResult = $fileManager->getFiles($folderId, null, $filePage, $perPage);

$folders = $foldersResult['folders'] ?? [];
$files = $filesResult['files'] ?? [];

$folderPagination = [
    'current' => $foldersResult['currentPage'] ?? 1,
    'total' => $foldersResult['total'] ?? 0,
    'hasMore' => $foldersResult['hasMore'] ?? false,
    'perPage' => $foldersResult['perPage'] ?? $perPage
];

$filePagination = [
    'current' => $filesResult['currentPage'] ?? 1,
    'total' => $filesResult['total'] ?? 0,
    'hasMore' => $filesResult['hasMore'] ?? false,
    'perPage' => $filesResult['perPage'] ?? $perPage
];

$breadcrumb = $folderId ? $folderManager->getFolderBreadcrumb($folderId) : [];

// Get user storage information
$storageInfo = $user->getUserStorageInfo($currentUser['id']);
$storageUsedPercent = $storageInfo ? ($storageInfo['used_storage'] / $storageInfo['storage_limit']) * 100 : 0;

// Check upload permissions for current folder
$canUpload = true; // Default to true for root folder
if ($folderId) {
    // Check if user has upload permission on this folder
    if ($currentUser['role'] === 'admin') {
        $canUpload = true;
    } else {
        $pdo = getDBConnection();
        
        // Check if user owns the folder
        $stmt = $pdo->prepare("SELECT owner_id FROM folders WHERE id = ?");
        $stmt->execute([$folderId]);
        $folder = $stmt->fetch();
        
        if ($folder && $folder['owner_id'] == $currentUser['id']) {
            $canUpload = true;
        } else {
            // Check explicit upload permissions
            $stmt = $pdo->prepare("SELECT can_upload FROM folder_permissions WHERE folder_id = ? AND user_id = ?");
            $stmt->execute([$folderId, $currentUser['id']]);
            $perm = $stmt->fetch();
            $canUpload = $perm && $perm['can_upload'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudeDrive - Professional File Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-layout">
        <!-- Professional Navbar -->
        <nav class="navbar-professional">
            <div class="navbar-content">
                <a href="index.php" class="navbar-brand">
                    <i class="fas fa-cloud"></i>
                    CloudeDrive
                </a>
                
                <div class="navbar-actions">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <span class="admin-badge">Admin</span>
                        <?php endif; ?>
                    </div>
                    
                    <button class="btn-professional btn-outline btn-sm" onclick="openProfileModal()">
                        <i class="fas fa-user-edit"></i>
                        Edit Profile
                    </button>
                    
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="admin.php" class="btn-professional btn-outline btn-sm">
                            <i class="fas fa-cogs"></i>
                            Admin Panel
                        </a>
                    <?php endif; ?>
                    
                    <a href="api/logout.php" class="btn-professional btn-outline btn-sm">
                        <i class="fas fa-sign-out-alt"></i>
                        Sign Out
                    </a>
                </div>
            </div>
        </nav>

        <!-- Professional Toolbar -->
        <div class="toolbar">
            <div class="toolbar-content">
                <div class="toolbar-left">
                    <!-- Breadcrumb -->
                    <nav class="breadcrumb-nav">
                        <div class="breadcrumb-item">
                            <a href="index.php">
                                <i class="fas fa-home"></i>
                                Home
                            </a>
                        </div>
                        <?php foreach ($breadcrumb as $crumb): ?>
                            <span class="breadcrumb-separator">›</span>
                            <div class="breadcrumb-item">
                                <a href="index.php?folder=<?php echo $crumb['id']; ?>">
                                    <?php echo htmlspecialchars($crumb['name']); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </nav>
                    
                    <!-- Stats -->
                    <div class="stats-info">
                        <span class="stat-badge">
                            <?php echo $folderPagination['total']; ?> folders<?php echo $folderPagination['hasMore'] ? ' (showing first 200 max)' : ''; ?>
                        </span>
                        <span class="stat-badge">
                            <?php echo $filePagination['total']; ?> files<?php echo $filePagination['hasMore'] ? ' (showing first 200 max)' : ''; ?>
                        </span>
                        <?php if ($storageInfo): ?>
                            <span class="stat-badge">
                                <?php echo formatFileSize($storageInfo['used_storage']); ?> / <?php echo formatFileSize($storageInfo['storage_limit']); ?>
                                (<?php echo number_format($storageUsedPercent, 1); ?>%)
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <!-- Search -->
                    <div class="search-container">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search files and folders...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    
                    <!-- View Toggle -->
                    <div class="view-toggle">
                        <input type="radio" id="listView" name="viewToggle" checked>
                        <label for="listView" title="List View">
                            <i class="fas fa-list"></i>
                        </label>
                        <input type="radio" id="gridView" name="viewToggle">
                        <label for="gridView" title="Grid View">
                            <i class="fas fa-th"></i>
                        </label>
                    </div>
                    
                    <!-- Actions -->
                    <?php if ($canUpload): ?>
                    <button class="btn-professional btn-primary" onclick="openUploadModal()">
                        <i class="fas fa-upload"></i>
                        Upload
                    </button>
                    <?php endif; ?>
                    
                    <button class="btn-professional btn-success" onclick="openFolderModal()">
                        <i class="fas fa-folder-plus"></i>
                        New Folder
                    </button>
                    
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <button class="btn-professional btn-warning" onclick="openShareModal()">
                            <i class="fas fa-share"></i>
                            Share
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if (!empty($folders) || !empty($files)): ?>
            
                <!-- Folders Section -->
                <?php if (!empty($folders)): ?>
                <div class="card-professional">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-folder"></i>
                            Folders
                            <span class="badge-professional badge-secondary"><?php echo $folderPagination['total']; ?></span>
                            <?php if ($folderPagination['total'] > 200): ?>
                                <span class="badge-professional badge-warning">Max 200 shown</span>
                            <?php endif; ?>
                        </div>
                        <div class="multi-select-controls" style="display: none;">
                            <button class="btn-professional btn-warning btn-sm" onclick="renameSelected('folder')" title="Rename Selected">
                                <i class="fas fa-edit"></i> Rename
                            </button>
                            <button class="btn-professional btn-danger btn-sm" onclick="deleteSelected('folder')" title="Delete Selected">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <button class="btn-professional btn-secondary btn-sm" onclick="clearSelection('folder')" title="Clear Selection">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- List View -->
                        <div id="foldersTableView">
                            <table class="table-professional">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAllFolders" onchange="toggleSelectAll('folder')" title="Select All">
                                        </th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($folders as $folder): ?>
                                    <tr onclick="location.href='index.php?folder=<?php echo $folder['id']; ?>'" style="cursor: pointer;">
                                        <td onclick="event.stopPropagation();">
                                            <input type="checkbox" class="folder-checkbox" value="<?php echo $folder['id']; ?>" onchange="updateMultiSelectControls('folder')">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-folder" style="color: #f59e0b; margin-right: 12px;"></i>
                                                <span><?php echo htmlspecialchars($folder['name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($folder['is_shared']): ?>
                                                <span class="badge-professional badge-info">
                                                    <i class="fas fa-share"></i>
                                                    Shared
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-professional badge-secondary">
                                                    <i class="fas fa-lock"></i>
                                                    Private
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($folder['created_at'])); ?></td>
                                        <td>
                                            <div class="d-flex" style="gap: 8px;">
                                                <button class="btn-professional btn-primary btn-sm" onclick="event.stopPropagation(); downloadFolder(<?php echo $folder['id']; ?>)" title="Download as ZIP">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <?php if ($currentUser['role'] === 'admin' || $folder['owner_id'] == $currentUser['id']): ?>
                                                    <button class="btn-professional btn-info btn-sm" onclick="event.stopPropagation(); renameFolder(<?php echo $folder['id']; ?>, '<?php echo htmlspecialchars($folder['name'], ENT_QUOTES); ?>')" title="Rename">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($folder['owner_id'] == $currentUser['id']): ?>
                                                    <button class="btn-professional btn-warning btn-sm" onclick="event.stopPropagation(); shareFolder(<?php echo $folder['id']; ?>)" title="Share">
                                                        <i class="fas fa-share"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($currentUser['role'] === 'admin' || $folder['can_delete_folder']): ?>
                                                <button class="btn-professional btn-danger btn-sm" onclick="event.stopPropagation(); showDeleteConfirmation('folder', <?php echo $folder['id']; ?>, '<?php echo htmlspecialchars($folder['name'], ENT_QUOTES); ?>')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Grid View -->
                        <div id="foldersGridView" class="grid-container d-none">
                            <?php foreach ($folders as $folder): ?>
                            <div class="grid-item" onclick="location.href='index.php?folder=<?php echo $folder['id']; ?>'">
                                <div class="grid-item-icon">
                                    <i class="fas fa-folder" style="color: #f59e0b;"></i>
                                </div>
                                <div class="grid-item-title"><?php echo htmlspecialchars($folder['name']); ?></div>
                                <div class="grid-item-meta">
                                    <?php if ($folder['is_shared']): ?>
                                        <span class="badge-professional badge-info">Shared</span>
                                    <?php else: ?>
                                        <span class="badge-professional badge-secondary">Private</span>
                                    <?php endif; ?>
                                </div>
                                <div class="grid-item-actions">
                                    <button class="btn-professional btn-primary btn-sm" onclick="event.stopPropagation(); downloadFolder(<?php echo $folder['id']; ?>)" title="Download as ZIP">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <?php if ($folder['owner_id'] == $currentUser['id']): ?>
                                        <button class="btn-professional btn-warning btn-sm" onclick="event.stopPropagation(); shareFolder(<?php echo $folder['id']; ?>)" title="Share">
                                            <i class="fas fa-share"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($currentUser['role'] === 'admin' || $folder['can_delete_folder']): ?>
                                    <button class="btn-professional btn-danger btn-sm" onclick="event.stopPropagation(); showDeleteConfirmation('folder', <?php echo $folder['id']; ?>, '<?php echo htmlspecialchars($folder['name'], ENT_QUOTES); ?>')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Folder Pagination -->
                        <?php if ($folderPagination['total'] > 20): ?>
                        <div class="pagination-container">
                            <div class="pagination-info">
                                Showing <?php echo min($folderPagination['perPage'], count($folders)); ?> of <?php echo min($folderPagination['total'], 200); ?> folders
                            </div>
                            <div class="pagination-controls">
                                <!-- Per Page Selector -->
                                <div class="per-page-selector">
                                    <label for="folderPerPage">Per page:</label>
                                    <select id="folderPerPage" onchange="changePerPage(this.value)">
                                        <option value="20" <?php echo $perPage == 20 ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
                                        <option value="200" <?php echo $perPage == 200 ? 'selected' : ''; ?>>200</option>
                                    </select>
                                </div>
                                
                                <?php if ($folderPagination['current'] > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['folder_page' => $folderPagination['current'] - 1])); ?>" 
                                       class="btn-professional btn-sm btn-outline">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <span class="pagination-current">
                                    Page <?php echo $folderPagination['current']; ?> of <?php echo ceil(min($folderPagination['total'], 200) / $folderPagination['perPage']); ?>
                                </span>
                                
                                <?php if ($folderPagination['hasMore'] && ($folderPagination['current'] * $folderPagination['perPage']) < 200): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['folder_page' => $folderPagination['current'] + 1])); ?>" 
                                       class="btn-professional btn-sm btn-outline">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Files Section -->
                <?php if (!empty($files)): ?>
                <div class="card-professional">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-file"></i>
                            Files
                            <span class="badge-professional badge-secondary"><?php echo $filePagination['total']; ?></span>
                            <?php if ($filePagination['total'] > 200): ?>
                                <span class="badge-professional badge-warning">Max 200 shown</span>
                            <?php endif; ?>
                        </div>
                        <div class="multi-select-controls" style="display: none;">
                            <button class="btn-professional btn-warning btn-sm" onclick="renameSelected('file')" title="Rename Selected">
                                <i class="fas fa-edit"></i> Rename
                            </button>
                            <button class="btn-professional btn-danger btn-sm" onclick="deleteSelected('file')" title="Delete Selected">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <button class="btn-professional btn-secondary btn-sm" onclick="clearSelection('file')" title="Clear Selection">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- List View -->
                        <div id="filesTableView">
                            <table class="table-professional">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAllFiles" onchange="toggleSelectAll('file')" title="Select All">
                                        </th>
                                        <th>Name</th>
                                        <th>Size</th>
                                        <th>Type</th>
                                        <th>Modified</th>
                                        <th>Owner</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($files as $file): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="file-checkbox" value="<?php echo $file['id']; ?>" onchange="updateMultiSelectControls('file')">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="<?php echo getFileIcon($file['file_type']); ?>" style="color: <?php echo getFileColor($file['file_type']); ?>; margin-right: 12px;"></i>
                                                <span><?php echo htmlspecialchars($file['original_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo formatFileSize($file['file_size']); ?></td>
                                        <td>
                                            <span class="badge-professional badge-secondary"><?php echo strtoupper($file['file_type']); ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($file['upload_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($file['uploaded_by_name']); ?></td>
                                        <td>
                                            <div class="d-flex" style="gap: 8px;">
                                                <button class="btn-professional btn-primary btn-sm" onclick="downloadFile(<?php echo $file['id']; ?>)" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <button class="btn-professional btn-secondary btn-sm" onclick="previewFile(<?php echo $file['id']; ?>)" title="Preview">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($currentUser['role'] === 'admin' || $file['uploaded_by'] == $currentUser['id']): ?>
                                                    <button class="btn-professional btn-info btn-sm" onclick="renameFile(<?php echo $file['id']; ?>, '<?php echo htmlspecialchars($file['original_name'], ENT_QUOTES); ?>')" title="Rename">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($currentUser['role'] === 'admin' || $file['uploaded_by'] == $currentUser['id'] || $file['can_delete_file']): ?>
                                                    <button class="btn-professional btn-danger btn-sm" onclick="showDeleteConfirmation('file', <?php echo $file['id']; ?>, '<?php echo htmlspecialchars($file['original_name'], ENT_QUOTES); ?>', 'Size: <?php echo formatFileSize($file['file_size']); ?>')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Grid View -->
                        <div id="filesGridView" class="grid-container d-none">
                            <?php foreach ($files as $file): ?>
                            <div class="grid-item">
                                <div class="grid-item-icon">
                                    <i class="<?php echo getFileIcon($file['file_type']); ?>" style="color: <?php echo getFileColor($file['file_type']); ?>;"></i>
                                </div>
                                <div class="grid-item-title" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                    <?php echo strlen($file['original_name']) > 20 ? substr($file['original_name'], 0, 20) . '...' : htmlspecialchars($file['original_name']); ?>
                                </div>
                                <div class="grid-item-meta">
                                    <?php echo formatFileSize($file['file_size']); ?>
                                    <br>
                                    <span class="badge-professional badge-secondary"><?php echo strtoupper($file['file_type']); ?></span>
                                </div>
                                <div class="grid-item-actions">
                                    <button class="btn-professional btn-primary btn-sm" onclick="downloadFile(<?php echo $file['id']; ?>)" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <?php if ($currentUser['role'] === 'admin' || $file['uploaded_by'] == $currentUser['id'] || $file['can_delete_file']): ?>
                                        <button class="btn-professional btn-danger btn-sm" onclick="showDeleteConfirmation('file', <?php echo $file['id']; ?>, '<?php echo htmlspecialchars($file['original_name'], ENT_QUOTES); ?>', 'Size: <?php echo formatFileSize($file['file_size']); ?>')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- File Pagination -->
                        <?php if ($filePagination['total'] > 20): ?>
                        <div class="pagination-container">
                            <div class="pagination-info">
                                Showing <?php echo min($filePagination['perPage'], count($files)); ?> of <?php echo min($filePagination['total'], 200); ?> files
                            </div>
                            <div class="pagination-controls">
                                <!-- Per Page Selector -->
                                <div class="per-page-selector">
                                    <label for="filePerPage">Per page:</label>
                                    <select id="filePerPage" onchange="changePerPage(this.value)">
                                        <option value="20" <?php echo $perPage == 20 ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100</option>
                                        <option value="200" <?php echo $perPage == 200 ? 'selected' : ''; ?>>200</option>
                                    </select>
                                </div>
                                
                                <?php if ($filePagination['current'] > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['file_page' => $filePagination['current'] - 1])); ?>" 
                                       class="btn-professional btn-sm btn-outline">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <span class="pagination-current">
                                    Page <?php echo $filePagination['current']; ?> of <?php echo ceil(min($filePagination['total'], 200) / $filePagination['perPage']); ?>
                                </span>
                                
                                <?php if ($filePagination['hasMore'] && ($filePagination['current'] * $filePagination['perPage']) < 200): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['file_page' => $filePagination['current'] + 1])); ?>" 
                                       class="btn-professional btn-sm btn-outline">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h2 class="empty-state-title">Your cloud storage is empty</h2>
                    <p class="empty-state-text">
                        Start by uploading your first file or creating a folder to organize your content. 
                        You can drag and drop files anywhere on this page.
                    </p>
                    <div class="empty-state-actions">
                        <?php if ($canUpload): ?>
                        <button class="btn-professional btn-primary" onclick="openUploadModal()">
                            <i class="fas fa-upload"></i>
                            Upload Files
                        </button>
                        <?php endif; ?>
                        <button class="btn-professional btn-success" onclick="openFolderModal()">
                            <i class="fas fa-folder-plus"></i>
                            Create Folder
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Professional Upload Modal -->
    <div id="uploadModal" class="modal-professional d-none">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Upload Files</h3>
                <button class="modal-close" onclick="closeUploadModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="dropZone" class="drop-zone">
                    <div class="drop-zone-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h4 class="drop-zone-title">Drag & Drop Files Here</h4>
                    <p class="drop-zone-text">Or click to browse and select files</p>
                    <small class="text-muted">All file types are supported (max 5GB per file)</small>
                    <input type="file" id="fileInput" multiple style="display: none;">
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="folderUpload" class="form-check-input">
                        <label for="folderUpload" class="form-check-label">Upload entire folder</label>
                    </div>
                </div>
                
                <input type="file" id="folderInput" webkitdirectory directory multiple style="display: none;">
                
                <div id="fileList" class="file-list d-none"></div>
                
                <div id="uploadProgress" class="d-none">
                    <div class="progress-container">
                        <div id="progressBar" class="progress-bar" style="width: 0%;"></div>
                    </div>
                    <div id="uploadStatus" class="text-center"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-professional btn-secondary" onclick="closeUploadModal()">Cancel</button>
                <button class="btn-professional btn-primary" id="uploadBtn">Upload Files</button>
            </div>
        </div>
    </div>

    <!-- Professional Folder Modal -->
    <div id="folderModal" class="modal-professional d-none">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Create New Folder</h3>
                <button class="modal-close" onclick="closeFolderModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="folderForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="folderName" class="form-label">Folder Name</label>
                        <input type="text" id="folderName" name="name" class="form-control" placeholder="Enter folder name" required>
                    </div>
                    <input type="hidden" name="parent_folder_id" value="<?php echo $folderId ?? ''; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-professional btn-secondary" onclick="closeFolderModal()">Cancel</button>
                    <button type="submit" class="btn-professional btn-success">Create Folder</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Professional Share Modal -->
    <?php if ($currentUser['role'] === 'admin'): ?>
    <div id="shareModal" class="modal-professional d-none">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Share Folder</h3>
                <button class="modal-close" onclick="closeShareModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="shareFolder" class="form-label">Select Folder</label>
                    <select id="shareFolder" class="form-control" onchange="loadCurrentShares()">
                        <option value="">Choose a folder to share...</option>
                        <?php foreach ($folders as $folder): ?>
                            <option value="<?php echo $folder['id']; ?>"><?php echo htmlspecialchars($folder['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Current Shares Section -->
                <div id="currentSharesSection" class="form-group d-none">
                    <label class="form-label">
                        <i class="fas fa-users"></i>
                        Currently Shared With
                    </label>
                    <div id="currentSharesList" class="current-shares-list">
                        <!-- Current shares will be loaded here -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="shareUsers" class="form-label">
                        <i class="fas fa-user-plus"></i>
                        Add New Users
                    </label>
                    <select id="shareUsers" class="form-control" multiple size="4">
                        <!-- Available users will be loaded dynamically -->
                    </select>
                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple users</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-key"></i>
                        Permissions for New Users
                    </label>
                    <div class="permissions-grid">
                        <div class="form-check">
                            <input type="checkbox" id="canView" class="form-check-input" checked>
                            <label for="canView" class="form-check-label">
                                <i class="fas fa-eye"></i>
                                Can View
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="canDownload" class="form-check-input" checked>
                            <label for="canDownload" class="form-check-label">
                                <i class="fas fa-download"></i>
                                Can Download
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="canUpload" class="form-check-input">
                            <label for="canUpload" class="form-check-label">
                                <i class="fas fa-upload"></i>
                                Can Upload
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="canDelete" class="form-check-input">
                            <label for="canDelete" class="form-check-label">
                                <i class="fas fa-trash"></i>
                                Can Delete
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-professional btn-secondary" onclick="closeShareModal()">Cancel</button>
                <button type="button" class="btn-professional btn-warning" onclick="handleShareFolder()">
                    <i class="fas fa-share"></i>
                    Share Folder
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert Container -->
    <div id="alertContainer" class="alert-container"></div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal-professional d-none">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i>
                    Confirm Deletion
                </h3>
                <button class="modal-close" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <div class="delete-icon">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div class="delete-content">
                        <h4 id="deleteTitle">Are you sure?</h4>
                        <p id="deleteMessage">This action cannot be undone. This will permanently delete the selected item.</p>
                        <div id="deleteDetails" class="delete-details"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-professional btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="btn-professional btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i>
                    Delete Permanently
                </button>
            </div>
        </div>
    </div>

    <!-- Profile Edit Modal -->
    <div id="profileModal" class="modal-professional d-none">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-edit"></i>
                    Edit Profile
                </h3>
                <button class="modal-close" onclick="closeProfileModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="profileForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="profileUsername" class="form-label">Username</label>
                        <input type="text" id="profileUsername" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="profileEmail" class="form-label">Email</label>
                        <input type="email" id="profileEmail" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="profilePassword" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" id="profilePassword" name="password" class="form-control" placeholder="Enter new password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control" placeholder="Confirm new password">
                    </div>
                    
                    <?php if ($currentUser['role'] === 'admin'): ?>
                    <div class="form-group">
                        <label for="profileStorageLimit" class="form-label">Storage Limit (GB)</label>
                        <select id="profileStorageLimit" name="storage_limit" class="form-control">
                            <option value="<?php echo 1 * 1024 * 1024 * 1024; ?>">1 GB</option>
                            <option value="<?php echo 5 * 1024 * 1024 * 1024; ?>">5 GB</option>
                            <option value="<?php echo 10 * 1024 * 1024 * 1024; ?>">10 GB</option>
                            <option value="<?php echo 25 * 1024 * 1024 * 1024; ?>">25 GB</option>
                            <option value="<?php echo 50 * 1024 * 1024 * 1024; ?>">50 GB</option>
                            <option value="<?php echo 100 * 1024 * 1024 * 1024; ?>">100 GB</option>
                            <option value="<?php echo 250 * 1024 * 1024 * 1024; ?>">250 GB</option>
                            <option value="<?php echo 500 * 1024 * 1024 * 1024; ?>">500 GB</option>
                            <option value="<?php echo 1024 * 1024 * 1024 * 1024; ?>">1 TB</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-professional btn-secondary" onclick="closeProfileModal()">Cancel</button>
                    <button type="submit" class="btn-professional btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/modals.js"></script>
    
    <script>
    // Pagination functions
    function changePerPage(perPage) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', perPage);
        url.searchParams.delete('folder_page'); // Reset to page 1
        url.searchParams.delete('file_page'); // Reset to page 1
        window.location.href = url.toString();
    }
    
    // Delete confirmation functions
    let deleteContext = {};
    
    function showDeleteConfirmation(type, id, name, details = '') {
        deleteContext = { type, id, name };
        
        const modal = document.getElementById('deleteModal');
        const title = document.getElementById('deleteTitle');
        const message = document.getElementById('deleteMessage');
        const detailsEl = document.getElementById('deleteDetails');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        if (type === 'file') {
            title.textContent = `Delete "${name}"?`;
            message.textContent = 'This file will be permanently deleted from the server. This action cannot be undone.';
        } else if (type === 'folder') {
            title.textContent = `Delete folder "${name}"?`;
            message.textContent = 'This folder and all its contents will be permanently deleted. This action cannot be undone.';
        }
        
        if (details) {
            detailsEl.textContent = details;
            detailsEl.style.display = 'block';
        } else {
            detailsEl.style.display = 'none';
        }
        
        // Remove existing event listeners and add new one
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
        
        newBtn.addEventListener('click', () => confirmDelete());
        
        modal.classList.remove('d-none');
    }
    
    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('d-none');
        deleteContext = {};
    }
    
    function confirmDelete() {
        const { type, id, name } = deleteContext;
        
        if (type === 'file') {
            deleteFile(id);
        } else if (type === 'folder') {
            deleteFolder(id);
        }
        
        closeDeleteModal();
    }
    
    // Update existing delete functions to use confirmation
    function deleteFile(fileId) {
        fetch('api/delete_file.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ file_id: fileId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'File deleted successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('error', data.message || 'Failed to delete file');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while deleting the file');
        });
    }
    
    function deleteFolder(folderId, force = false) {
        fetch('api/delete_folder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ folder_id: folderId, force: force })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Folder deleted successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                // If folder is not empty and we haven't forced it yet, ask for confirmation
                if (data.message.includes('not empty') && !force) {
                    if (confirm('This folder contains files or subfolders. Do you want to delete it and all its contents permanently? This action cannot be undone.')) {
                        deleteFolder(folderId, true); // Try again with force
                    }
                } else {
                    showAlert('error', data.message || 'Failed to delete folder');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while deleting the folder');
        });
    }
    
    // Add click event handlers to modal overlay to close on outside click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
    
    // Alert function
    function showAlert(type, message) {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert_' + Date.now();
        
        const alertElement = document.createElement('div');
        alertElement.id = alertId;
        alertElement.className = `alert alert-${type}`;
        alertElement.innerHTML = `
            <div class="alert-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="alert-close" onclick="removeAlert('${alertId}')">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        alertContainer.appendChild(alertElement);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            removeAlert(alertId);
        }, 5000);
    }
    
    function removeAlert(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.remove();
        }
    }
    
    // Profile modal functions
    function openProfileModal() {
        const modal = document.getElementById('profileModal');
        const form = document.getElementById('profileForm');
        
        // Populate form with current user data
        document.getElementById('profileUsername').value = '<?php echo htmlspecialchars($currentUser['username']); ?>';
        document.getElementById('profileEmail').value = '<?php echo htmlspecialchars($currentUser['email']); ?>';
        
        <?php if ($currentUser['role'] === 'admin' && isset($storageInfo)): ?>
        document.getElementById('profileStorageLimit').value = '<?php echo $storageInfo['storage_limit']; ?>';
        <?php endif; ?>
        
        modal.classList.remove('d-none');
    }
    
    function closeProfileModal() {
        const modal = document.getElementById('profileModal');
        modal.classList.add('d-none');
        
        // Reset form
        document.getElementById('profileForm').reset();
    }
    
    // Handle profile form submission
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        
        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                data[key] = value;
            }
        }
        
        // Check if passwords match
        if (data.password && data.confirm_password) {
            if (data.password !== data.confirm_password) {
                showAlert('error', 'Passwords do not match');
                return;
            }
            delete data.confirm_password; // Remove confirm_password from data
        }
        
        // Remove empty password field
        if (data.password === '') {
            delete data.password;
        }
        
        fetch('api/update_profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showAlert('success', result.message);
                closeProfileModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while updating profile');
        });
    });
    
    // Add click event handlers to modal overlay to close on outside click
    document.getElementById('profileModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeProfileModal();
        }
    });
    </script>
</body>
</html>

<?php
function getFileIcon($fileType) {
    $icons = [
        'pdf' => 'fas fa-file-pdf',
        'doc' => 'fas fa-file-word',
        'docx' => 'fas fa-file-word',
        'xls' => 'fas fa-file-excel',
        'xlsx' => 'fas fa-file-excel',
        'ppt' => 'fas fa-file-powerpoint',
        'pptx' => 'fas fa-file-powerpoint',
        'txt' => 'fas fa-file-alt',
        'jpg' => 'fas fa-file-image',
        'jpeg' => 'fas fa-file-image',
        'png' => 'fas fa-file-image',
        'gif' => 'fas fa-file-image',
        'mp4' => 'fas fa-file-video',
        'avi' => 'fas fa-file-video',
        'mov' => 'fas fa-file-video',
        'mp3' => 'fas fa-file-audio',
        'wav' => 'fas fa-file-audio',
        'zip' => 'fas fa-file-archive',
        'rar' => 'fas fa-file-archive',
        'html' => 'fas fa-file-code',
        'css' => 'fas fa-file-code',
        'js' => 'fas fa-file-code',
        'php' => 'fas fa-file-code',
        'py' => 'fas fa-file-code',
        'java' => 'fas fa-file-code'
    ];
    
    return $icons[$fileType] ?? 'fas fa-file';
}

function getFileColor($fileType) {
    $colors = [
        'pdf' => '#ef4444',
        'doc' => '#3b82f6',
        'docx' => '#3b82f6',
        'xls' => '#10b981',
        'xlsx' => '#10b981',
        'ppt' => '#f59e0b',
        'pptx' => '#f59e0b',
        'txt' => '#6b7280',
        'jpg' => '#06b6d4',
        'jpeg' => '#06b6d4',
        'png' => '#06b6d4',
        'gif' => '#06b6d4',
        'mp4' => '#8b5cf6',
        'avi' => '#8b5cf6',
        'mov' => '#8b5cf6',
        'mp3' => '#ec4899',
        'wav' => '#ec4899',
        'zip' => '#f59e0b',
        'rar' => '#f59e0b',
        'html' => '#ef4444',
        'css' => '#3b82f6',
        'js' => '#f59e0b',
        'php' => '#8b5cf6',
        'py' => '#10b981',
        'java' => '#f97316'
    ];
    
    return $colors[$fileType] ?? '#6b7280';
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
