 
<?php
require_once 'config.php';

// Redirect if logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Load PHPMailer classes properly
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if PHPMailer files exist and include them
$phpmailer_path = __DIR__ . '/phpmailer/PHPMailer.php';
$smtp_path = __DIR__ . '/phpmailer/SMTP.php';
$exception_path = __DIR__ . '/phpmailer/Exception.php';

if (!file_exists($phpmailer_path)) {
    error_log("PHPMailer not found at: " . $phpmailer_path);
    // Fallback - try alternative path
    $phpmailer_path = __DIR__ . '/PHPMailer/src/PHPMailer.php';
    $smtp_path = __DIR__ . '/PHPMailer/src/SMTP.php';
    $exception_path = __DIR__ . '/PHPMailer/src/Exception.php';
}

if (file_exists($phpmailer_path) && file_exists($smtp_path) && file_exists($exception_path)) {
    require_once $phpmailer_path;
    require_once $smtp_path;
    require_once $exception_path;
} else {
    error_log("PHPMailer files missing. Check installation.");
}

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    if (!$email) {
        $error = 'Please enter a valid email address';
    } else {
        $db = getDB();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id, email, username FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            $error = 'This email is not registered with us.';
        } else {
            // Check rate limit
            $stmt = $db->prepare("SELECT COUNT(*) FROM reset_requests 
                                  WHERE email = ? AND requested_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $stmt->execute([$email]);
            $emailCount = $stmt->fetchColumn();
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM reset_requests 
                                  WHERE ip_address = ? AND requested_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $stmt->execute([$ip]);
            $ipCount = $stmt->fetchColumn();
            
            if ($emailCount >= MAX_RESET_REQUESTS_PER_DAY || $ipCount >= MAX_RESET_REQUESTS_PER_DAY) {
                $error = 'Too many requests. Please try again after 24 hours.';
            } else {
                // Clean up expired tokens
                $stmt = $db->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
                $stmt->execute();
                
                // Generate token
                $rawToken = bin2hex(random_bytes(32));
                $hashedToken = password_hash($rawToken, PASSWORD_DEFAULT);
                $expires = date('Y-m-d H:i:s', time() + RESET_TOKEN_EXPIRY);
                
                $stmt = $db->prepare("INSERT INTO password_resets (email, hashed_token, expires_at, ip_address, user_agent) 
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$email, $hashedToken, $expires, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
                
                // Log request
                $stmt = $db->prepare("INSERT INTO reset_requests (email, ip_address) VALUES (?, ?)");
                $stmt->execute([$email, $ip]);
                
                // Send email using PHPMailer
                $resetLink = BASE_URL . '/reset_password.php?token=' . urlencode($rawToken) . '&email=' . urlencode($email);
                
                // Also create a backup simple mail function in case PHPMailer fails
                $emailSent = false;
                
                // Try PHPMailer first
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new PHPMailer(true);
                    
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = SMTP_HOST;
                        $mail->SMTPAuth = true;
                        $mail->Username = SMTP_USER;
                        $mail->Password = SMTP_PASS;
                        $mail->SMTPSecure = SMTP_SECURE;
                        $mail->Port = SMTP_PORT;
                        $mail->Timeout = 30;
                        $mail->SMTPDebug = 0; // Set to 2 for debugging
                        
                        // Recipients
                        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                        $mail->addAddress($email, $admin['username']);
                        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = "Password Reset Request - " . SITE_NAME;
                        $mail->Body = getPasswordResetEmailBody($resetLink, $admin['username']);
                        $mail->AltBody = "Password Reset Request\n\nHello " . $admin['username'] . ",\n\nWe received a request to reset your password for the " . SITE_NAME . " admin panel.\n\nClick this link to reset your password: " . $resetLink . "\n\nThis link will expire in 15 minutes.\n\nIf you didn't request this, please ignore this email.\n\nSecurity Notice: Never share this link with anyone.";
                        
                        $mail->send();
                        $emailSent = true;
                        $message = "Password reset link has been sent to your email. The link will expire in 15 minutes.";
                        error_log("Password reset email sent successfully to: " . $email);
                        
                    } catch (Exception $e) {
                        error_log("PHPMailer error: " . $mail->ErrorInfo);
                        $emailSent = false;
                    }
                }
                
                // Fallback: Use simple mail() function if PHPMailer fails
                if (!$emailSent) {
                    $subject = "Password Reset Request - " . SITE_NAME;
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
                    
                    $body = getPasswordResetEmailBody($resetLink, $admin['username']);
                    
                    if (mail($email, $subject, $body, $headers)) {
                        $message = "Password reset link has been sent to your email. Please check your inbox (including spam folder).";
                        error_log("Password reset email sent via mail() to: " . $email);
                    } else {
                        $error = "Failed to send reset email. Please try again later or contact support.";
                        error_log("Failed to send password reset email to: " . $email . " via mail()");
                    }
                }
            }
        }
    }
}

// Function to generate email body
function getPasswordResetEmailBody($resetLink, $username) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background: #ffffff;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h2 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
            }
            .content {
                padding: 40px 30px;
                background: #ffffff;
            }
            .greeting {
                font-size: 18px;
                margin-bottom: 20px;
            }
            .message {
                color: #333;
                margin-bottom: 25px;
            }
            .button-container {
                text-align: center;
                margin: 30px 0;
            }
            .button {
                display: inline-block;
                padding: 14px 35px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white !important;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 600;
                font-size: 16px;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                transition: all 0.3s ease;
            }
            .button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
            }
            .alert {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 15px 20px;
                margin: 25px 0;
                border-radius: 8px;
            }
            .alert p {
                margin: 0;
                color: #856404;
                font-size: 14px;
            }
            .link-box {
                background: #f5f5f5;
                padding: 12px;
                border-radius: 8px;
                word-break: break-all;
                font-size: 12px;
                color: #666;
                margin: 15px 0;
                font-family: monospace;
            }
            .footer {
                text-align: center;
                padding: 20px;
                background: #f9f9f9;
                color: #666;
                font-size: 12px;
                border-top: 1px solid #eee;
            }
            .footer p {
                margin: 5px 0;
            }
            .security-tip {
                background: #e8f0fe;
                padding: 12px 15px;
                border-radius: 8px;
                margin: 20px 0;
                font-size: 13px;
            }
            .security-tip i {
                color: #4a5568;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>🔐 Password Reset Request</h2>
            </div>
            <div class="content">
                <div class="greeting">
                    Hello <strong>' . htmlspecialchars($username) . '</strong>,
                </div>
                
                <div class="message">
                    We received a request to reset your password for the <strong>' . SITE_NAME . '</strong> admin panel.
                </div>
                
                <div class="alert">
                    <p><strong>⚠️ Important Security Notice:</strong></p>
                    <p>• This link will expire in <strong>15 minutes</strong></p>
                    <p>• Never share this link with anyone</p>
                    <p>• Only one reset is allowed per request</p>
                </div>
                
                <div class="button-container">
                    <a href="' . $resetLink . '" class="button">Reset Password</a>
                </div>
                
                <p style="text-align: center; color: #666; font-size: 14px;">If the button doesn&apos;t work, copy and paste this link:</p>
                <div class="link-box">
                    ' . $resetLink . '
                </div>
                
                <div class="security-tip">
                    <i class="fas fa-shield-alt"></i> <strong>Security Tip:</strong> If you didn&apos;t request this password reset, please ignore this email. Your password will remain unchanged.
                </div>
                
                <p style="font-size: 14px; color: #666; margin-top: 20px;">
                    For security reasons, this link can only be used once. If you need to reset your password again, please submit a new request.
                </p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
                <p><small>This is an automated message, please do not reply to this email.</small></p>
            </div>
        </div>
    </body>
    </html>
    ';
}

$csrf_token = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Forgot Password</title>
    <link rel="icon" type="image/x-icon" href="kag favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .auth-bg { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }
        .bg-overlay {
            position: absolute;
            inset: 0;
            background-image: url('profile.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
        }
    </style>
</head>
<body class="auth-bg min-h-screen">
    <div class="bg-overlay"></div>
    <div class="min-h-screen flex items-center justify-center p-4 relative z-10">
        <div class="w-full max-w-md">
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fas fa-key text-white text-3xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">Reset Password</h1>
                    <p class="text-gray-500 mt-1">We'll send you a reset link</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <div>
                                <p class="text-green-700 text-sm font-medium">Success!</p>
                                <p class="text-green-600 text-sm"><?php echo htmlspecialchars($message); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <div>
                                <p class="text-red-700 text-sm font-medium">Error</p>
                                <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-5" id="forgotPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="forgot_password" value="1">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="email" name="email" id="email" required 
                                class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                placeholder="you@example.com">
                        </div>
                    </div>
                    
                    <button type="submit" id="submitBtn"
                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold py-3 rounded-xl hover:opacity-90 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-paper-plane mr-2"></i> Send Reset Link
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Prevent double form submission
        const form = document.getElementById('forgotPasswordForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                if (submitBtn.disabled) {
                    e.preventDefault();
                    return;
                }
                
                submitBtn.disabled = true;
                const originalHtml = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
                
                // Re-enable after 10 seconds (in case of network issues)
                setTimeout(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }, 10000);
            });
        }
        
        // Auto-focus on email field
        document.getElementById('email')?.focus();
    </script>
</body>
</html>