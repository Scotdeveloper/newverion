<?php
// heartbeat.php - Extend session timeout on user activity
session_start();

// Security check
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    exit();
}

// Update last activity time
if (isset($_SESSION['admin_id'])) {
    $_SESSION['last_activity'] = time();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>