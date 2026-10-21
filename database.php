<?php
// database.php - Complete version with all functions

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'admin_panel');
define('DB_USER', 'root');  // Change in production
define('DB_PASS', '');      // Change in production
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'EastGate Assembly');
define('BASE_URL', 'http://localhost/updates/assembly'); // Change to your domain
define('UPLOAD_PATH','/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Database connection class
class Database {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci"
                ];
                
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                die("Database connection failed. Please try again later.");
            }
        }
        return self::$connection;
    }
    
    public static function executeQuery($sql, $params = []) {
        try {
            $db = self::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getSingle($sql, $params = []) {
        $stmt = self::executeQuery($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    public static function getAll($sql, $params = []) {
        $stmt = self::executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
}


/**
 * Get all published events (both past and upcoming)
 */
function getAllEvents() {
    $sql = "SELECT id, title, description, image_path, 
                   event_date, location, event_time, created_at, status
            FROM events 
            WHERE status = 'published' 
            ORDER BY 
                CASE 
                    WHEN event_date >= CURDATE() OR event_date IS NULL THEN 0
                    ELSE 1 
                END,
                event_date ASC, 
                created_at DESC 
            LIMIT 50";
    
    return Database::getAll($sql);
}

/**
 * Get only upcoming events (for backward compatibility)
 */
function getUpcomingEvents() {
    $sql = "SELECT id, title, description, image_path, 
                   event_date, location, event_time, created_at, status
            FROM events 
            WHERE (event_date >= CURDATE() OR event_date IS NULL)
            AND status = 'published'
            ORDER BY event_date ASC, created_at DESC 
            LIMIT 20";
    
    return Database::getAll($sql);
}

/**
 * This is the function called in events.php to fetch all events, both past and upcoming, for the calendar view
 */
function getEvents() {
    return getAllEvents(); // Returns all events, both past and upcoming
}

// Get latest messages for the dashboard

function getMessages() {
    $sql = "SELECT id, content, category, created_at
            FROM messages
            ORDER BY created_at DESC 
            LIMIT 20";
    
    return Database::getAll($sql);
}

// Get active emergencies for the dashboard, ordered by priority and creation time

function getEmergencies() {
    $sql = "SELECT id, content, priority, created_at, is_active
            FROM emergencies 
            WHERE is_active = 1 
            ORDER BY 
                CASE priority 
                    WHEN 'critical' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                    ELSE 5 
                END ASC,
                created_at DESC 
            LIMIT 10";
    
    return Database::getAll($sql);
}

// Security function for fetching the data

// Rate limiting function
function checkRateLimit($key, $limit = 30, $timeout = 60) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $cacheKey = $key . '_' . $ip;
    
    if (!isset($_SESSION['rate_limit'][$cacheKey])) {
        $_SESSION['rate_limit'][$cacheKey] = [
            'count' => 1,
            'time' => time()
        ];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$cacheKey];
    
    if (time() - $data['time'] > $timeout) {
        $_SESSION['rate_limit'][$cacheKey] = [
            'count' => 1,
            'time' => time()
        ];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit'][$cacheKey]['count']++;
    return true;
}

// Input sanitization of the data coming from the user
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

// Generate form fingerprint for duplicate submission prevention
function generateFormFingerprint($data) {
    $fingerprint_data = [
        'name' => $data['name'] ?? '',
        'email' => $data['email'] ?? '',
        'phone' => $data['phone'] ?? '',
        'message' => $data['message'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    if (isset($data['subject'])) {
        $fingerprint_data['subject'] = $data['subject'];
    }
    if (isset($data['location'])) {
        $fingerprint_data['location'] = $data['location'];
    }
    
    return hash('sha256', json_encode($fingerprint_data));
}

// Rate Limiter Class for submissions
class RateLimiter {
    private static $maxAttempts = 2;
    
    public static function checkRateLimit($ip, $table) {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} 
                              WHERE phone = ? 
                              AND submitted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$ip]);
        $count = $stmt->fetchColumn();
        
        return $count < self::$maxAttempts;
    }
}
?>