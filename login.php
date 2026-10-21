  <?php
require_once 'config.php';

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $db = getDB();
    
    // Find the admin by username or email
    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
    } else {
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    }
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    
    // Check if account exists
    if (!$admin) {
        $error = "Invalid credentials";
    } else {
        // Check if account is locked
        $account_locked = false;
        $lock_message = '';
        
        if ($admin['locked_until']) {
            $locked_until = strtotime($admin['locked_until']);
            if (time() < $locked_until) {
                $account_locked = true;
                $minutes_left = ceil(($locked_until - time()) / 60);
                $lock_message = "Account is locked. Please try again in {$minutes_left} minute" . ($minutes_left != 1 ? 's' : '') . ".";
            }
        }
        
        if ($account_locked) {
            $error = $lock_message;
        } elseif (password_verify($password, $admin['password_hash'])) {
            
            // Check if already logged in on another device
            $active_session_exists = checkActiveSession($admin['id']);
            
            if ($active_session_exists) {
                // Get active session info for display
                $stmt = $db->prepare("SELECT device_info, ip_address, last_activity 
                                      FROM login_sessions 
                                      WHERE admin_id = ? AND is_active = 1 
                                      ORDER BY last_activity DESC LIMIT 1");
                $stmt->execute([$admin['id']]);
                $active_session = $stmt->fetch();
                
                // Store in session for display
                $_SESSION['device_info'] = $active_session['device_info'] ?? 'Unknown device';
                $_SESSION['ip_address'] = $active_session['ip_address'] ?? 'Unknown IP';
                
                $_SESSION['show_device_warning'] = true;
                $error = "You are already logged in from another device (" . ($active_session['device_info'] ?? 'Unknown') . "). 
                          Would you like to terminate that session and login here?";
                
                // Store admin ID for the force login option
                $_SESSION['pending_admin_id'] = $admin['id'];
                $_SESSION['pending_password'] = $password;
                
                header('Location: login.php?force_option=1');
                exit();
            }
            
            // No active session - proceed with normal login
            $session_token = generateSessionToken();
            
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['session_token'] = $session_token;
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Register new session
            registerSession($admin['id'], $session_token, $ip, $user_agent);
            
            // Reset failed attempts
            $stmt = $db->prepare("UPDATE admins SET failed_attempts = 0, locked_until = NULL WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            // Record successful login
            $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, 1)");
            $stmt->execute([$ip, $login]);
            
            header('Location: index.php');
            exit();
            
        } else {
            // Failed login - increment attempts
            $failed_attempts = $admin['failed_attempts'] + 1;
            
            $stmt = $db->prepare("UPDATE admins SET failed_attempts = ?, last_failed_attempt = NOW() WHERE id = ?");
            $stmt->execute([$failed_attempts, $admin['id']]);
            
            if ($failed_attempts >= MAX_LOGIN_ATTEMPTS) {
                $locked_until = date('Y-m-d H:i:s', time() + LOGIN_TIMEOUT);
                $stmt = $db->prepare("UPDATE admins SET locked_until = ? WHERE id = ?");
                $stmt->execute([$locked_until, $admin['id']]);
                $error = "Too many failed attempts. Account locked for 1 hour.";
            } else {
                $attempts_left = MAX_LOGIN_ATTEMPTS - $failed_attempts;
                $error = "Invalid credentials. You have {$attempts_left} attempt" . ($attempts_left != 1 ? 's' : '') . " remaining.";
            }
            
            $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, 0)");
            $stmt->execute([$ip, $login]);
        }
    }
}

// Handle force login (terminate other sessions)
if (isset($_GET['force_option']) && $_GET['force_option'] == '1' && isset($_POST['force_login'])) {
    if (isset($_SESSION['pending_admin_id']) && isset($_SESSION['pending_password'])) {
        $admin_id = $_SESSION['pending_admin_id'];
        $password = $_SESSION['pending_password'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $db = getDB();
        
        // Verify password again
        $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Terminate all existing sessions
            logoutAllDevices($admin_id, false);
            
            // Create new session
            $session_token = generateSessionToken();
            
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['session_token'] = $session_token;
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Register new session
            registerSession($admin['id'], $session_token, $ip, $user_agent);
            
            // Clear pending data
            unset($_SESSION['pending_admin_id']);
            unset($_SESSION['pending_password']);
            unset($_SESSION['show_device_warning']);
            
            header('Location: index.php');
            exit();
        }
    }
}

$csrf_token = $_SESSION['csrf_token'] ?? '';
$show_force_option = isset($_GET['force_option']) && $_GET['force_option'] == '1' && isset($_SESSION['show_device_warning']);
$device_info = $_SESSION['device_info'] ?? '';
$ip_address = $_SESSION['ip_address'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Admin Login</title>
    <link rel="icon" type="image/x-icon" href="favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .auth-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .warning-card {
            border-left: 4px solid #f59e0b;
        }
    </style>
</head>
<body class="auth-bg min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <!--profile logo image-->
                    <div class="mx-auto w-20 h-20 mb-4">
                        <i class="fas fa-user text-indigo-600 text-6xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo SITE_NAME; ?></h1>
                    <p class="text-gray-500 mt-1">Secure Administrator Login</p>
                </div>
                
                <?php if ($error && !$show_force_option): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg shake">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <p class="text-green-700 text-sm"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_force_option): ?>
                    <!-- Warning about existing session -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded-lg warning-card">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                            <div>
                                <p class="text-yellow-800 font-semibold mb-1">Active Session Detected</p>
                                <p class="text-yellow-700 text-sm">You are already logged in from other device.Please terminate the other session to login.</p>
                            </div>
                        </div>
                    </div>
                    
                    
                <?php else: ?>
                    <!-- Normal Login Form -->
                    <form method="POST" class="space-y-5" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="login" value="1">
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Username or Email</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="login" id="login" required 
                                    class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                    placeholder="Enter username or email"
                                    value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                            <div class="relative">
                                <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="password" name="password" id="password" required 
                                    class="w-full pl-10 pr-12 py-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                    placeholder="Enter password">
                                <i class="fas fa-eye-slash absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer hover:text-indigo-600 transition"
                                   onclick="togglePassword()"></i>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm">
                            <a href="forgot_password.php" class="text-indigo-600 hover:text-indigo-800 font-medium">Forgot password?</a>
                            <a href="register.php" class="text-indigo-600 hover:text-indigo-800 font-medium">Register new admin</a>
                        </div>
                        
                        <button type="submit" id="submitBtn"
                            class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold py-3 rounded-xl hover:opacity-90 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = event.target;
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
        
        // Prevent double form submission
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                if (submitBtn.disabled) {
                    e.preventDefault();
                    return;
                }
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Signing in...';
                submitBtn.classList.add('opacity-75');
            });
        }
        
        // Auto-focus on login field
        document.getElementById('login')?.focus();
        
        // Remove shake animation after it completes
        const errorMessage = document.querySelector('.shake');
        if (errorMessage) {
            setTimeout(function() {
                errorMessage.classList.remove('shake');
            }, 500);
        }
    </script>
</body>
</html>