<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'classes/User.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Define constants if not already defined
if (!defined('DEFAULT_USER_STORAGE_LIMIT')) {
    define('DEFAULT_USER_STORAGE_LIMIT', 10 * 1024 * 1024 * 1024); // 10GB
}

$pdo = getDBConnection();
$user = new User();
$currentUser = $user->getUserById($_SESSION['user_id']);

// Get system statistics
$totalUsers = $user->getTotalUsers();
$allUsers = $user->getAllUsers();

// Calculate total storage used
$totalStorageUsed = 0;
foreach ($allUsers as $userData) {
    $totalStorageUsed += $userData['used_storage'] ?? 0;
}

// Get recent activities
try {
    $stmt = $pdo->prepare("
        SELECT al.*, u.username 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $recentActivities = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CloudeDrive</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-layout">
        <!-- Navigation -->
        <nav class="navbar-professional">
            <div class="navbar-content">
                <a href="admin.php" class="navbar-brand">
                    <i class="fas fa-cloud"></i>
                    CloudeDrive Admin
                </a>
                
                <div class="navbar-actions">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                        <span class="admin-badge">Admin</span>
                    </div>
                    
                    <a href="index.php" class="btn-professional btn-outline btn-sm">
                        <i class="fas fa-arrow-left"></i>
                        Back to Files
                    </a>
                    
                    <a href="api/logout.php" class="btn-professional btn-outline btn-sm">
                        <i class="fas fa-sign-out-alt"></i>
                        Sign Out
                    </a>
                </div>
            </div>
        </nav>

        <!-- Admin Toolbar -->
        <div class="toolbar">
            <div class="toolbar-content">
                <div class="toolbar-left">
                    <h1 class="page-title">
                        <i class="fas fa-cogs"></i>
                        Admin Dashboard
                    </h1>
                </div>
                <div class="toolbar-right">
                    <div class="stats-summary">
                        <span class="stat-item">
                            <i class="fas fa-users"></i>
                            <?php echo $totalUsers; ?> Users
                        </span>
                        <span class="stat-item">
                            <i class="fas fa-hdd"></i>
                            <?php echo formatFileSize($totalStorageUsed); ?> Used
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-professional">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $totalUsers; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo formatFileSize($totalStorageUsed); ?></div>
                        <div class="stat-label">Storage Used</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?php
                            $uploadDir = 'uploads/';
                            $fileCount = 0;
                            if (is_dir($uploadDir)) {
                                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir));
                                foreach ($iterator as $file) {
                                    if ($file->isFile()) {
                                        $fileCount++;
                                    }
                                }
                            }
                            echo $fileCount;
                            ?>
                        </div>
                        <div class="stat-label">Total Files</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?php echo count(array_filter($allUsers, function($u) { return $u['role'] === 'admin'; })); ?>
                        </div>
                        <div class="stat-label">Admin Users</div>
                    </div>
                </div>
            </div>

            <!-- User Management and Recent Activities Section -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- User Management Section -->
                <div class="card-professional">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-users"></i>
                            User Management
                        </div>
                        <button class="btn-professional btn-success" onclick="showAddUserModal()">
                            <i class="fas fa-user-plus"></i>
                            Add User
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table-professional">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Storage Usage</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allUsers as $userData): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle" style="margin-right: 8px; color: var(--primary-color);"></i>
                                                <span><?php echo htmlspecialchars($userData['username']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($userData['email']); ?></td>
                                        <td>
                                            <span class="badge-professional <?php echo $userData['role'] === 'admin' ? 'badge-danger' : 'badge-primary'; ?>">
                                                <?php echo ucfirst($userData['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="storage-usage">
                                                <div class="storage-text">
                                                    <?php echo formatFileSize($userData['used_storage'] ?? 0); ?> / 
                                                    <?php echo formatFileSize($userData['storage_limit'] ?? DEFAULT_USER_STORAGE_LIMIT); ?>
                                                </div>
                                                <?php 
                                                $usedPercent = $userData['storage_limit'] > 0 
                                                    ? (($userData['used_storage'] ?? 0) / $userData['storage_limit']) * 100 
                                                    : 0; 
                                                ?>
                                                <div class="progress-bar-container">
                                                    <div class="progress-bar-fill <?php echo $usedPercent > 90 ? 'danger' : ($usedPercent > 70 ? 'warning' : 'success'); ?>" 
                                                         style="width: <?php echo min($usedPercent, 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($userData['created_at'])); ?></td>
                                        <td>
                                            <?php if ($userData['id'] != $currentUser['id']): ?>
                                            <div class="d-flex" style="gap: 8px;">
                                                <button class="btn-professional btn-primary btn-sm" onclick="editUser(<?php echo $userData['id']; ?>, '<?php echo htmlspecialchars($userData['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($userData['email'], ENT_QUOTES); ?>', '<?php echo $userData['role']; ?>', <?php echo $userData['storage_limit']; ?>)" title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-professional btn-warning btn-sm" onclick="toggleUserRole(<?php echo $userData['id']; ?>, '<?php echo $userData['role']; ?>')" title="Toggle Role">
                                                    <i class="fas fa-user-cog"></i>
                                                </button>
                                                <button class="btn-professional btn-danger btn-sm" onclick="deleteUser(<?php echo $userData['id']; ?>)" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities Section -->
                <div class="card-professional">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-history"></i>
                            Recent Activities
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if (!empty($recentActivities)): ?>
                            <div class="activity-list-compact">
                                <?php foreach (array_slice($recentActivities, 0, 10) as $activity): ?>
                                <div class="activity-item-compact">
                                    <div class="activity-icon-small">
                                        <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                    </div>
                                    <div class="activity-content-compact">
                                        <div class="activity-text-small">
                                            <strong><?php echo htmlspecialchars($activity['username'] ?? 'Unknown'); ?></strong>
                                            <?php echo getActivityText($activity['action'], $activity['resource_type']); ?>
                                        </div>
                                        <div class="activity-time-small">
                                            <?php echo timeAgo($activity['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state-small">
                                <i class="fas fa-history"></i>
                                <p>No recent activities</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            </div>
        </div>
        
        <!-- Add User Modal -->
        <div id="addUserModal" class="modal-professional d-none">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-user-plus"></i>
                        Add New User
                    </h3>
                    <button class="modal-close" onclick="closeAddUserModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="addUserForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="newUsername" class="form-label">Username</label>
                            <input type="text" id="newUsername" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newEmail" class="form-label">Email</label>
                            <input type="email" id="newEmail" name="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword" class="form-label">Password</label>
                            <input type="password" id="newPassword" name="password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newRole" class="form-label">Role</label>
                            <select id="newRole" name="role" class="form-control">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="newStorageLimit" class="form-label">Storage Limit</label>
                            <div class="input-group">
                                <input type="number" id="newStorageLimit" name="storage_limit" class="form-control" value="10" min="1" max="1024" step="0.1">
                                <span class="input-group-text">GB</span>
                            </div>
                            <small class="form-text">Maximum storage space for this user (1GB - 1TB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-professional btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                        <button type="submit" class="btn-professional btn-success">
                            <i class="fas fa-user-plus"></i>
                            Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="editUserModal" class="modal-professional d-none">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-user-edit"></i>
                        Edit User Details
                    </h3>
                    <button class="modal-close" onclick="closeEditUserModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="editUserForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" id="editUsername" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" id="editEmail" name="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editRole" class="form-label">Role</label>
                            <select id="editRole" name="role" class="form-control">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editStorageLimitUser" class="form-label">Storage Limit</label>
                            <div class="input-group">
                                <input type="number" id="editStorageLimitUser" name="storage_limit" class="form-control" min="1" max="1024" step="0.1" required>
                                <span class="input-group-text">GB</span>
                            </div>
                            <small class="form-text">Maximum storage space for this user (1GB - 1TB)</small>
                        </div>

                        <div class="form-group">
                            <label for="editPassword" class="form-label">New Password (Optional)</label>
                            <input type="password" id="editPassword" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            <small class="form-text">Only fill this if you want to change the user's password</small>
                        </div>

                        <div class="form-group">
                            <label for="confirmEditPassword" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirmEditPassword" name="confirm_password" class="form-control" placeholder="Confirm new password">
                        </div>

                        <input type="hidden" id="editUserIdFull">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-professional btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                        <button type="submit" class="btn-professional btn-primary">
                            <i class="fas fa-save"></i>
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer" class="alert-container"></div>

    <!-- Scripts -->
</body>
</html>

<?php
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function getActivityIcon($action) {
    $icons = [
        'upload' => 'upload',
        'download' => 'download',
        'delete' => 'trash',
        'create' => 'plus',
        'share' => 'share',
        'update_permissions' => 'user-cog',
        'remove_access' => 'user-times'
    ];
    return $icons[$action] ?? 'circle';
}

function getActivityText($action, $resourceType) {
    $texts = [
        'upload' => "uploaded a {$resourceType}",
        'download' => "downloaded a {$resourceType}",
        'delete' => "deleted a {$resourceType}",
        'create' => "created a {$resourceType}",
        'share' => "shared a {$resourceType}",
        'update_permissions' => "updated {$resourceType} permissions",
        'remove_access' => "removed {$resourceType} access"
    ];
    return $texts[$action] ?? "performed an action on {$resourceType}";
}
?>

    <script src="assets/js/admin.js"></script>
</body>
</html>

<?php
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>
