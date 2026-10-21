<?php
/**
 * Configuration file for Admin Panel
 */

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'admin_panel');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_NAME', 'EastGate Assembly');
define('SESSION_TIMEOUT', 900);
define('UPLOAD_PATH', 'uploads/');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 3600);
define('BASE_URL', 'https://assembly.gt.tc/assembly');
define('MAX_ADMINS_ALLOWED', 2);
define('MAX_RESET_REQUESTS_PER_DAY', 4);
define('RESET_TOKEN_EXPIRY', 900);

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'eastgateassembly@gmail.com');
define('SMTP_PASS', 'cyvmkdqsapnbmchr');
define('SMTP_FROM', 'noreply@eastgateassembly.org');
define('SMTP_FROM_NAME', 'EastGate Assembly Admin');

// Load PHPMailer
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Database connection
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    return $db;
}

// Initialize database tables
function initDatabase() {
    $db = getDB();
    
    $tables = [
        "CREATE TABLE IF NOT EXISTS admins (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'admin',
            failed_attempts INT DEFAULT 0,
            last_failed_attempt TIMESTAMP NULL,
            locked_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS events (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            image_path VARCHAR(500),
            event_date DATE,
            location VARCHAR(200),
            event_time TIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content TEXT NOT NULL,
            category VARCHAR(50) DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS emergencies (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content TEXT NOT NULL,
            priority VARCHAR(20) DEFAULT 'medium',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS contact_requests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            subject VARCHAR(200),
            message TEXT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            is_read BOOLEAN DEFAULT FALSE,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS connect_requests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20) NOT NULL,
            location VARCHAR(200),
            message TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            is_read BOOLEAN DEFAULT FALSE,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS members (
            id INT PRIMARY KEY AUTO_INCREMENT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(50) NOT NULL,
            location VARCHAR(200) NOT NULL,
            gender VARCHAR(20),
            age INT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            form_fingerprint VARCHAR(64),
            is_read BOOLEAN DEFAULT FALSE,
            registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS offerings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            donor_name VARCHAR(100) NOT NULL,
            donor_email VARCHAR(100),
            donor_phone VARCHAR(20),
            amount DECIMAL(10,2) NOT NULL,
            type VARCHAR(50) DEFAULT 'offering',
            payment_method VARCHAR(50),
            transaction_id VARCHAR(100),
            message TEXT,
            ip_address VARCHAR(45),
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            username VARCHAR(50) NOT NULL,
            success BOOLEAN DEFAULT FALSE,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS password_resets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(100) NOT NULL,
            hashed_token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS reset_requests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $sql) {
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            // Continue if table exists
        }
    }
    
    // Add gender and age columns if they don't exist
    try {
        $db->exec("ALTER TABLE members ADD COLUMN IF NOT EXISTS gender VARCHAR(20)");
        $db->exec("ALTER TABLE members ADD COLUMN IF NOT EXISTS age INT");
    } catch (PDOException $e) {
        // Columns might already exist
    }
    
    // Create offerings table if not exists
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS offerings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            donor_name VARCHAR(100) NOT NULL,
            donor_email VARCHAR(100),
            donor_phone VARCHAR(20),
            amount DECIMAL(10,2) NOT NULL,
            type VARCHAR(50) DEFAULT 'offering',
            payment_method VARCHAR(50),
            transaction_id VARCHAR(100),
            message TEXT,
            ip_address VARCHAR(45),
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        // Table might already exist
    }
    
    // Check if admin exists
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM admins");
    $stmt->execute();
    $adminCount = $stmt->fetch()['count'];
    
    if ($adminCount == 0) {
        $hash = password_hash('Eastgate@2026', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO admins (username, email, password_hash, role) VALUES (?, ?, ?, ?)")
           ->execute(['EastGate', 'eastgateassembly@gmail.com', $hash, 'super_admin']);
    }
}

// Authentication functions
function isLoggedIn() {
    if (!isset($_SESSION['admin_id'])) return false;
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getAdminProfile($adminId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, role, created_at, updated_at FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    return $stmt->fetch();
}

function updateAdminEmail($adminId, $newEmail) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    $stmt->execute([$newEmail, $adminId]);
    if ($stmt->fetch()) {
        return 'Email already in use by another administrator';
    }
    
    $stmt = $db->prepare("UPDATE admins SET email = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$newEmail, $adminId])) {
        $_SESSION['admin_email'] = $newEmail;
        return true;
    }
    return 'Failed to update email';
}

function updateAdminPassword($adminId, $currentPassword, $newPassword) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
        return 'Current password is incorrect';
    }
    
    if (strlen($newPassword) < 8) {
        return 'Password must be at least 8 characters';
    }
    
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
    
    if ($stmt->execute([$newHash, $adminId])) {
        return true;
    }
    return 'Failed to update password';
}

function deleteAdminAccount($adminId, $currentPassword) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
        return 'Invalid password';
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM admins");
    $stmt->execute();
    $adminCount = $stmt->fetch()['count'];
    
    if ($adminCount <= 1) {
        return 'Cannot delete the last administrator account';
    }
    
    $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
    if ($stmt->execute([$adminId])) {
        return true;
    }
    return 'Failed to delete account';
}

function getAdminCount() {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM admins");
    $stmt->execute();
    return $stmt->fetch()['count'];
}

function isMaxAdminsReached() {
    return getAdminCount() >= MAX_ADMINS_ALLOWED;
}

// Logout function
function handleLogout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Initialize database on include
initDatabase();
if (!file_exists(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);

// Get admin profile if logged in
$current_admin = null;
if (isset($_SESSION['admin_id'])) {
    $current_admin = getAdminProfile($_SESSION['admin_id']);
}
// config.php - Add these functions after the existing code

// Generate unique session token
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

// Check if admin has active session on another device
function checkActiveSession($admin_id) {
    $db = getDB();
    
    // Get current session info
    $stmt = $db->prepare("SELECT session_token, session_created_at FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if ($admin && $admin['session_token']) {
        // Check if session is still valid (not expired)
        $stmt = $db->prepare("SELECT id FROM login_sessions 
                              WHERE admin_id = ? AND session_token = ? AND is_active = 1 
                              AND last_activity > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$admin_id, $admin['session_token'], SESSION_TIMEOUT]);
        $activeSession = $stmt->fetch();
        
        if ($activeSession) {
            return true; // Active session exists
        }
    }
    
    return false; // No active session
}

// Terminate all other sessions for an admin
function terminateOtherSessions($admin_id, $current_session_token) {
    $db = getDB();
    
    // Deactivate all other sessions
    $stmt = $db->prepare("UPDATE login_sessions 
                          SET is_active = 0 
                          WHERE admin_id = ? AND session_token != ?");
    $stmt->execute([$admin_id, $current_session_token]);
    
    // Update admin record with new session token
    $stmt = $db->prepare("UPDATE admins 
                          SET session_token = ?, session_created_at = NOW() 
                          WHERE id = ?");
    $stmt->execute([$current_session_token, $admin_id]);
}

// Register new session
function registerSession($admin_id, $session_token, $ip, $user_agent) {
    $db = getDB();
    
    // Get device info
    $device_info = getDeviceInfo($user_agent);
    
    // Insert session record
    $stmt = $db->prepare("INSERT INTO login_sessions 
                          (admin_id, session_id, session_token, ip_address, user_agent, device_info, last_activity) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$admin_id, session_id(), $session_token, $ip, $user_agent, $device_info]);
    
    // Update admin record
    $stmt = $db->prepare("UPDATE admins 
                          SET session_token = ?, session_created_at = NOW(), 
                              last_login_ip = ?, last_login_device = ?, 
                              login_count = login_count + 1 
                          WHERE id = ?");
    $stmt->execute([$session_token, $ip, $device_info, $admin_id]);
}

// Update session activity
function updateSessionActivity($admin_id, $session_token) {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE login_sessions 
                          SET last_activity = NOW() 
                          WHERE admin_id = ? AND session_token = ? AND is_active = 1");
    $stmt->execute([$admin_id, $session_token]);
}

// Validate current session
function validateSession() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    $db = getDB();
    $admin_id = $_SESSION['admin_id'];
    $session_token = $_SESSION['session_token'];
    
    // Check if session exists and is active
    $stmt = $db->prepare("SELECT id FROM login_sessions 
                          WHERE admin_id = ? AND session_token = ? AND is_active = 1 
                          AND last_activity > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$admin_id, $session_token, SESSION_TIMEOUT]);
    $session = $stmt->fetch();
    
    if ($session) {
        // Update last activity
        updateSessionActivity($admin_id, $session_token);
        return true;
    }
    
    return false;
}

// Get device info from user agent
function getDeviceInfo($user_agent) {
    $device = 'Unknown Device';
    
    if (strpos($user_agent, 'Windows') !== false) {
        $device = 'Windows PC';
    } elseif (strpos($user_agent, 'Mac') !== false) {
        $device = 'Mac';
    } elseif (strpos($user_agent, 'iPhone') !== false) {
        $device = 'iPhone';
    } elseif (strpos($user_agent, 'iPad') !== false) {
        $device = 'iPad';
    } elseif (strpos($user_agent, 'Android') !== false) {
        $device = 'Android Device';
    } elseif (strpos($user_agent, 'Linux') !== false) {
        $device = 'Linux';
    }
    
    // Add browser info
    if (strpos($user_agent, 'Chrome') !== false) {
        $device .= ' (Chrome)';
    } elseif (strpos($user_agent, 'Firefox') !== false) {
        $device .= ' (Firefox)';
    } elseif (strpos($user_agent, 'Safari') !== false) {
        $device .= ' (Safari)';
    } elseif (strpos($user_agent, 'Edge') !== false) {
        $device .= ' (Edge)';
    }
    
    return $device;
}

// Force logout from all devices
function logoutAllDevices($admin_id, $except_current = true) {
    $db = getDB();
    
    if ($except_current && isset($_SESSION['session_token'])) {
        $stmt = $db->prepare("UPDATE login_sessions 
                              SET is_active = 0 
                              WHERE admin_id = ? AND session_token != ?");
        $stmt->execute([$admin_id, $_SESSION['session_token']]);
    } else {
        $stmt = $db->prepare("UPDATE login_sessions SET is_active = 0 WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
    }
}

// Get active sessions for admin
function getActiveSessions($admin_id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id, device_info, ip_address, last_activity, created_at 
                          FROM login_sessions 
                          WHERE admin_id = ? AND is_active = 1 
                          ORDER BY last_activity DESC");
    $stmt->execute([$admin_id]);
    return $stmt->fetchAll();
}