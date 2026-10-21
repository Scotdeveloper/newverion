<?php
require_once 'config.php';

// Redirect if logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

$adminCount = getAdminCount();
$maxAdmins = MAX_ADMINS_ALLOWED;
$registrationDisabled = isMaxAdminsReached();

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_admin']) && !$registrationDisabled) {
    $username = trim($_POST['username'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already exists';
        } else {
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO admins (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
                
                if ($stmt->execute([$username, $email, $hash])) {
                    $_SESSION['success'] = 'Admin account created successfully! You can now login.';
                    header('Location: login.php');
                    exit();
                } else {
                    $error = 'Failed to create admin account';
                }
            }
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
    <title><?php echo SITE_NAME; ?> - Admin Registration</title>
    <link rel="icon" type="image/x-icon" href="favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .auth-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
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
                        <i class="fas fa-user-plus text-white text-3xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">Admin Registration</h1>
                    <p class="text-gray-500 mt-1">Create administrator account</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($registrationDisabled): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 mb-6 rounded-xl text-center">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-ban text-yellow-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Registration Closed</h3>
                        <p class="text-gray-700">Number of administrators allowed has been reached.</p>
                        <p class="text-gray-600 text-sm mt-2">Please contact an existing administrator if you need access.</p>
                        <div class="mt-4">
                            <a href="login.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Login
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="register_admin" value="1">
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="username" required 
                                    pattern="[a-zA-Z0-9_]{3,}"
                                    title="Username must be at least 3 characters, letters, numbers, underscores only"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                    placeholder="Enter username">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
                            <div class="relative">
                                <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="email" name="email" required 
                                    class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                    placeholder="you@example.com">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                            <div class="relative">
                                <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="password" name="password" id="password" required 
                                    class="w-full pl-10 pr-12 py-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                    placeholder="Create password">
                                <i class="fas fa-eye-slash absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer hover:text-indigo-600 transition"
                                   onclick="togglePassword('password')"></i>
                            </div>
                                 <!-- Password requirements with a circle ticked when the requirement is met -->
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
                                    placeholder="Confirm password">
                                <i class="fas fa-eye-slash absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer hover:text-indigo-600 transition"
                                   onclick="togglePassword('confirm_password')">
                                </i>
                                <!-- Check if password and confirm password match even if the form is not submitted yet -->
                                <div id="passwordMatchError" class="text-red-600 text-sm hidden">
                                    Passwords do not match.
                                </div>
                            </div>
                        </div>
                        
                        <div id="passwordError" class="text-red-600 text-sm hidden"></div>
                        
                        <button type="submit" 
                            class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold py-3 rounded-xl hover:opacity-90 transition shadow-lg">
                            <i class="fas fa-user-plus mr-2"></i> Create Account
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <a href="login.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Login
                        </a>
                    </div>
                <?php endif; ?>
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