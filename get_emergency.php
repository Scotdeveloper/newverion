// get_emergency.php - AJAX endpoint for emergency data
<?php
require_once 'config.php';

if (!isset($_GET['id']) || !isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit();
}

$id = (int)$_GET['id'];
$db = getDB();
$stmt = $db->prepare("SELECT * FROM emergencies WHERE id = ?");
$stmt->execute([$id]);
$emergency = $stmt->fetch();

if ($emergency) {
    echo json_encode(['success' => true, 'emergency' => $emergency]);
} else {
    echo json_encode(['success' => false]);
}
?>