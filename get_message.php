 // get_message.php - AJAX endpoint for message data
<?php
require_once 'config.php';

if (!isset($_GET['id']) || !isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit();
}

$id = (int)$_GET['id'];
$db = getDB();
$stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->execute([$id]);
$message = $stmt->fetch();

if ($message) {
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    echo json_encode(['success' => false]);
}
?>