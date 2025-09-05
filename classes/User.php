<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function register($username, $email, $password, $role = 'user', $storageLimit = null) {
        try {
            // Check if user already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Set default storage limit if not provided
            if ($storageLimit === null) {
                $storageLimit = ($role === 'admin') ? ADMIN_STORAGE_LIMIT : DEFAULT_USER_STORAGE_LIMIT;
            }

            // Insert user
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, role, storage_limit) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$username, $email, $hashedPassword, $role, $storageLimit]);

            if ($result) {
                $userId = $this->conn->lastInsertId();
                
                // Create user's home folder
                $this->createUserHomeFolder($userId, $username);
                
                return ['success' => true, 'message' => 'User registered successfully'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    public function login($username, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Start session
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }

    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function getAllUsers() {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, role, storage_limit, used_storage, created_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalUsers() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Get total users error: " . $e->getMessage());
            return 0;
        }
    }

    public function updateUserStorageLimit($userId, $storageLimit) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET storage_limit = ? WHERE id = ?");
            return $stmt->execute([$storageLimit, $userId]);
        } catch (Exception $e) {
            error_log("Update storage limit error: " . $e->getMessage());
            return false;
        }
    }

    public function updateUsedStorage($userId, $sizeChange) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET used_storage = used_storage + ? WHERE id = ?");
            return $stmt->execute([$sizeChange, $userId]);
        } catch (Exception $e) {
            error_log("Update used storage error: " . $e->getMessage());
            return false;
        }
    }

    public function getUserStorageInfo($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT storage_limit, used_storage FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get storage info error: " . $e->getMessage());
            return null;
        }
    }

    public function checkStorageLimit($userId, $fileSize) {
        try {
            $storageInfo = $this->getUserStorageInfo($userId);
            if (!$storageInfo) {
                return false;
            }
            
            return ($storageInfo['used_storage'] + $fileSize) <= $storageInfo['storage_limit'];
        } catch (Exception $e) {
            error_log("Check storage limit error: " . $e->getMessage());
            return false;
        }
    }

    public function updateUserRole($userId, $role) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            return $stmt->execute([$role, $userId]);
        } catch (Exception $e) {
            error_log("Update role error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($userId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ? AND id != ?");
            return $stmt->execute([$userId, $_SESSION['user_id']]); // Prevent self-deletion
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfile($userId, $data) {
        try {
            $updateFields = [];
            $params = [];

            // Check what fields to update
            if (isset($data['username']) && !empty($data['username'])) {
                // Check if username already exists (excluding current user)
                $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$data['username'], $userId]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Username already exists'];
                }
                $updateFields[] = "username = ?";
                $params[] = $data['username'];
            }

            if (isset($data['email']) && !empty($data['email'])) {
                // Check if email already exists (excluding current user)
                $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$data['email'], $userId]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Email already exists'];
                }
                $updateFields[] = "email = ?";
                $params[] = $data['email'];
            }

            if (isset($data['password']) && !empty($data['password'])) {
                $updateFields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // Only admins can update storage limit
            if (isset($data['storage_limit']) && $this->isAdmin()) {
                $storageLimit = intval($data['storage_limit']);
                if ($storageLimit > 0 && $storageLimit <= MAX_STORAGE_LIMIT) {
                    $updateFields[] = "storage_limit = ?";
                    $params[] = $storageLimit;
                }
            }

            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }

            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update profile'];
            }
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }

    public function updateUserQuota($userId, $storageLimit) {
        try {
            // Only admins can update user quotas
            if (!$this->isAdmin()) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            $storageLimit = intval($storageLimit);
            if ($storageLimit <= 0 || $storageLimit > MAX_STORAGE_LIMIT) {
                return ['success' => false, 'message' => 'Invalid storage limit'];
            }

            $stmt = $this->conn->prepare("UPDATE users SET storage_limit = ? WHERE id = ?");
            $result = $stmt->execute([$storageLimit, $userId]);

            if ($result) {
                return ['success' => true, 'message' => 'Storage quota updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update storage quota'];
            }
        } catch (Exception $e) {
            error_log("Update user quota error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update storage quota'];
        }
    }

    public function getUserById($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, role, storage_limit, used_storage, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return false;
        }
    }

    private function createUserHomeFolder($userId, $username) {
        try {
            require_once __DIR__ . '/FolderManager.php';
            $folderManager = new FolderManager();
            
            // Create the user's home folder in the database
            $result = $folderManager->createFolder($username, null, $userId);
            
            if ($result['success']) {
                error_log("Created home folder for user: $username (ID: $userId)");
            } else {
                error_log("Failed to create home folder for user: $username - " . $result['message']);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Create user home folder error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create home folder'];
        }
    }
}
?>
