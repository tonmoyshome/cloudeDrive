<?php
require_once __DIR__ . '/classes/FileManager.php';

$fileManager = new FileManager();

// Test canManageFile for file ID 6 with user ID 1 (admin)
$fileId = 6;
$userId = 1;

// Use reflection to call private method
$reflection = new ReflectionClass($fileManager);
$method = $reflection->getMethod('canManageFile');
$method->setAccessible(true);

$canManage = $method->invoke($fileManager, $fileId, $userId);
echo "Can user $userId manage file $fileId: " . ($canManage ? 'YES' : 'NO') . "\n";

// Test isAdmin method
$isAdminMethod = $reflection->getMethod('isAdmin');
$isAdminMethod->setAccessible(true);
$isAdmin = $isAdminMethod->invoke($fileManager, $userId);
echo "Is user $userId admin: " . ($isAdmin ? 'YES' : 'NO') . "\n";

// Check file details
echo "Checking file details...\n";
require_once __DIR__ . '/config/database.php';
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
$stmt->execute([$fileId]);
$file = $stmt->fetch();
echo "File data: " . json_encode($file) . "\n";

// Check user role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
echo "User role: " . $user['role'] . "\n";
?>
