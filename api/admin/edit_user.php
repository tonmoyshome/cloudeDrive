<?php
session_start();
require_once __DIR__ . '/../../classes/User.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = $input['user_id'] ?? null;
    $username = $input['username'] ?? null;
    $email = $input['email'] ?? null;
    $role = $input['role'] ?? null;
    $storageLimit = $input['storage_limit'] ?? null;
    $password = $input['password'] ?? null;
    $confirm_password = $input['confirm_password'] ?? null;
    
    // Validate required fields
    if (!$userId || !$username || !$email || !$role || !$storageLimit) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Validate role
    if (!in_array($role, ['admin', 'user'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid role specified']);
        exit;
    }
    
    // Validate storage limit
    if ($storageLimit < 1073741824 || $storageLimit > 1099511627776) { // 1GB to 1TB
        echo json_encode(['success' => false, 'message' => 'Storage limit must be between 1GB and 1TB']);
        exit;
    }
    
    // Prevent admin from demoting themselves
    if ($userId == $_SESSION['user_id'] && $role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You cannot change your own role']);
        exit;
    }
    
    try {
        $user = new User();
        $pdo = getDBConnection();
        
        // Check if username/email already exists for other users
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        
        // Build update query
        $updateFields = [];
        $updateValues = [];
        
        $updateFields[] = "username = ?";
        $updateValues[] = $username;
        
        $updateFields[] = "email = ?";
        $updateValues[] = $email;
        
        $updateFields[] = "role = ?";
        $updateValues[] = $role;
        
        $updateFields[] = "storage_limit = ?";
        $updateValues[] = $storageLimit;
        
        // Only update password if provided
        if (!empty($password)) {
            // Check password confirmation
            if ($password !== $confirm_password) {
                echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match']);
                exit;
            }
            $updateFields[] = "password = ?";
            $updateValues[] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
        $updateValues[] = $userId; // for WHERE clause
        
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($updateValues);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user']);
        }
        
    } catch (Exception $e) {
        error_log("Edit user error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Unable to save user changes - please check your inputs and try again']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
