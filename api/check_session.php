<?php
session_start();
require_once __DIR__ . '/../classes/FileManager.php';

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE
]);
?>
