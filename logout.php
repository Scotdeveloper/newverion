 <?php
// logout.php - Clear session and deactivate
session_start();

if (isset($_SESSION['admin_id']) && isset($_SESSION['session_token'])) {
    require_once 'config.php';
    
    $db = getDB();
    
    // Deactivate the session
    $stmt = $db->prepare("UPDATE login_sessions SET is_active = 0 
                          WHERE admin_id = ? AND session_token = ?");
    $stmt->execute([$_SESSION['admin_id'], $_SESSION['session_token']]);
    
    // Clear admin session token
    $stmt = $db->prepare("UPDATE admins SET session_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>