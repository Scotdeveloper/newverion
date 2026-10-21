<?php
require_once 'config.php';

// Redirect if logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

if (empty($token) || empty($email)) {
    header('Location: login.php');
    exit();
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($token) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $error = 'Password must contain uppercase, lowercase, number, and special character';
    } else {
        $db = getDB();
        
        // Get valid reset token
        $stmt = $db->prepare("SELECT * FROM password_resets 
                              WHERE email = ? AND expires_at > NOW() AND used = 0 
                              ORDER BY created_at DESC");
        $stmt->execute([$email]);
        $resets = $stmt->fetchAll();
        
        $validReset = null;
        foreach ($resets as $reset) {
            if (password_verify($token, $reset['hashed_token'])) {
                $validReset = $reset;
                break;
            }
        }
        
        if (!$validReset) {
            $error = 'Invalid or expired reset link';
        } else {
            // Update password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admins SET password_hash = ? WHERE email = ?");
            $stmt->execute([$hash, $email]);
            
            // Mark token as used
            $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt->execute([$validReset['id']]);
            
            $_SESSION['success'] = 'Password reset successfully. You can now login with your new password.';
            header('Location: login.php');
            exit();
        }
    }
}

$csrf_token = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Reset Password</title>
    <link rel="icon" type="image/x-icon" href="favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .auth-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="auth-bg min-h-screen">
    <!-- Background image with overlay -->
    <div class="absolute inset-0 bg-cover bg-center " style="background-image: url('profile.jpg');">   
    </div>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fas fa-lock text-white text-3xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">Set New Password</h1>
                    <p class="text-gray-500 mt-1">Enter your new password below</p>

                </div>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">New Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" name="password" id="password" required 
                                class="w-full pl-10 pr-12 py-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                placeholder="Enter new password">
                            <i class="fas fa-eye-slash absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer hover:text-indigo-600 transition"
                               onclick="togglePassword('password')"></i>
                        </div>
                        <!-- Password requirements -->
                         <div class="mt-2 text-xs text-gray-500 space-y-1">
                                <ul class="list-disc list-inside ml-2 space-y-0.5 hidden" id="passwordRequirements">
                                    <li class="<?php echo isset($_POST['password']) && strlen($_POST['password']) >= 8 ? 'text-green-600' : ''; ?>">At least 8 characters</li>
                                    <li class="<?php echo isset($_POST['password']) && preg_match('/[A-Z]/', $_POST['password']) ? 'text-green-600' : ''; ?>">At least one uppercase letter</li>
                                    <li class="<?php echo isset($_POST['password']) && preg_match('/[a-z]/', $_POST['password']) ? 'text-green-600' : ''; ?>">At least one lowercase letter</li>
                                    <li class="<?php echo isset($_POST['password']) && preg_match('/\d/', $_POST['password']) ? 'text-green-600' : ''; ?>">At least one number</li>
                                    <li class="<?php echo isset($_POST['password']) && preg_match('/[@$!%*?&]/', $_POST['password']) ? 'text-green-600' : ''; ?>">At least one special character (@$!%*?&)</li>
                                </ul>
                            </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Confirm Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" name="confirm_password" id="confirm_password" required 
                                class="w-full pl-10 pr-12 py-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                placeholder="Confirm new password">
                            <i class="fas fa-eye-slash absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer hover:text-indigo-600 transition"
                               onclick="togglePassword('confirm_password')"></i>
                        </div>
                        <!-- Password match error -->
                        <div id="passwordMatchError" class="text-red-600 text-sm hidden mt-1">Passwords do not match</div>
                    </div>
                    
                    <div id="passwordError" class="text-red-600 text-sm hidden"></div>
                    
                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold py-3 rounded-xl hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-save mr-2"></i> Reset Password
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = event.target;
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
        
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const errorDiv = document.getElementById('passwordError');
            
            if (password !== confirm) {
                e.preventDefault();
                errorDiv.textContent = 'Passwords do not match';
                errorDiv.classList.remove('hidden');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                errorDiv.textContent = 'Password must be at least 8 characters';
                errorDiv.classList.remove('hidden');
                return;
            }
            
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            if (!passwordRegex.test(password)) {
                e.preventDefault();
                errorDiv.textContent = 'Password must contain uppercase, lowercase, number, and special character (@$!%*?&)';
                errorDiv.classList.remove('hidden');
            }
        });
        // Show password requirements when focusing on the password field
        document.getElementById('password').addEventListener('focus', function() {
            document.getElementById('passwordRequirements').classList.remove('hidden');
        });
        document.getElementById('password').addEventListener('blur', function() {
            document.getElementById('passwordRequirements').classList.add('hidden');
        });
        // Check if password and confirm password match in real-time even if the form is not submitted yet
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const confirm = document.getElementById('confirm_password').value;
            const errorDiv = document.getElementById('passwordMatchError');
            
            if (password !== confirm && confirm !== '') {
                errorDiv.classList.remove('hidden');
            } else {
                errorDiv.classList.add('hidden');
            }
        });
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            const errorDiv = document.getElementById('passwordMatchError');
            
            if (password !== confirm && confirm !== '') {
                errorDiv.classList.remove('hidden');
            } else {
                errorDiv.classList.add('hidden');
            }
        });
    </script>
</body>
</html>