<?php
// profile.php - Admin Profile Management Page
require_once 'config.php';
requireLogin();

$page_title = 'My Profile';
$db = getDB();

// Get current admin profile
$adminId = $_SESSION['admin_id'];
$stmt = $db->prepare("SELECT id, username, email, role, created_at, updated_at FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin) {
    $_SESSION['error'] = 'Profile not found';
    header('Location: index.php');
    exit();
}

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token. Please refresh the page.';
        header('Location: profile.php');
        exit();
    }
    
    // Update Email
    if (isset($_POST['update_email'])) {
        $newEmail = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        
        if (!$newEmail) {
            $_SESSION['error'] = 'Please enter a valid email address';
        } elseif ($newEmail === $admin['email']) {
            $_SESSION['error'] = 'New email is the same as current email';
        } else {
            // Check if email already exists for another admin
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $adminId]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Email already in use by another administrator';
            } else {
                $stmt = $db->prepare("UPDATE admins SET email = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$newEmail, $adminId])) {
                    $_SESSION['admin_email'] = $newEmail;
                    $_SESSION['success'] = 'Email updated successfully';
                    // Refresh admin data
                    $admin['email'] = $newEmail;
                } else {
                    $_SESSION['error'] = 'Failed to update email';
                }
            }
        }
        header('Location: profile.php');
        exit();
    }
    
    // Change Password
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPassword)) {
            $errors[] = 'Password must contain uppercase, lowercase, number, and special character (@$!%*?&)';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }
        
        if (empty($errors)) {
            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ?");
            $stmt->execute([$adminId]);
            $adminData = $stmt->fetch();
            
            if (!password_verify($currentPassword, $adminData['password_hash'])) {
                $errors[] = 'Current password is incorrect';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admins SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$newHash, $adminId])) {
                    $_SESSION['success'] = 'Password changed successfully';
                    // Generate new CSRF token after password change
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $errors[] = 'Failed to update password';
                }
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
        }
        header('Location: profile.php');
        exit();
    }
    
    // Delete Account
    if (isset($_POST['delete_account'])) {
        $password = $_POST['delete_password'] ?? '';
        
        if (empty($password)) {
            $_SESSION['error'] = 'Password is required to delete your account';
            header('Location: profile.php');
            exit();
        }
        
        // Verify password
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $adminData = $stmt->fetch();
        
        if (!password_verify($password, $adminData['password_hash'])) {
            $_SESSION['error'] = 'Invalid password';
            header('Location: profile.php');
            exit();
        }
        
        // Check if this is super admin account and prevent deletion if it's the last admin
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM admins WHERE role = 'super_admin'");
        $stmt->execute();
        $superAdminCount = $stmt->fetch()['count'];
        
        if ($superAdminCount <= 1) {
            $_SESSION['error'] = 'Cannot delete the super administrator account';
            header('Location: profile.php');
            exit();
        }
        
        // Delete the admin account
        $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
        if ($stmt->execute([$adminId])) {
            // Destroy session and logout
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['success'] = 'Your account has been successfully deleted.';
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['error'] = 'Failed to delete account';
            header('Location: profile.php');
            exit();
        }
    }
}

$csrf_token = $_SESSION['csrf_token'];

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
        <p class="text-gray-500 mt-1">Manage your account settings and preferences</p>
    </div>
    
    <!-- Profile Overview Card -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-6 mb-8 text-white">
        <div class="flex items-center space-x-4">
            <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center text-3xl font-bold backdrop-blur-sm">
                <i class="fas fa-user"></i>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($admin['username']); ?></h2>
                <p class="text-white/80"><?php echo htmlspecialchars($admin['email']); ?></p>
                <div class="flex flex-wrap gap-3 mt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20">
                         Role: <?php echo ucfirst($admin['role']); ?>
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20">
                         Member since: <?php echo date('F j, Y', strtotime($admin['created_at'])); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Update Email Section -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-envelope text-indigo-600 mr-2"></i> Update Email Address
                </h3>
                <p class="text-sm text-gray-500 mt-1">Change the email address associated with your account</p>
            </div>
            
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="update_email" value="1">
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Current Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" value="<?php echo htmlspecialchars($admin['email']); ?>" 
                               class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl bg-gray-100 text-gray-600 cursor-not-allowed" disabled>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">New Email Address <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="email" required 
                               placeholder="Enter new email address"
                               class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">You will need to use this email for future logins</p>
                </div>
                
                <button type="submit" 
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center space-x-2">
                    <i class="fas fa-save"></i>
                    <span>Update Email</span>
                </button>
            </form>
        </div>
        
        <!-- Change Password Section -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-key text-green-600 mr-2"></i> Change Password
                </h3>
                <p class="text-sm text-gray-500 mt-1">Update your password to keep your account secure</p>
            </div>
            
            <form method="POST" class="p-6 space-y-4" id="passwordForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="change_password" value="1">
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Current Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="current_password" id="currentPassword" required
                               class="w-full pl-10 pr-12 py-3 border border-gray-200 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200 transition">
                        <button type="button" onclick="togglePassword('currentPassword', this)" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">New Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="new_password" id="newPassword" required
                               class="w-full pl-10 pr-12 py-3 border border-gray-200 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200 transition">
                        <button type="button" onclick="togglePassword('newPassword', this)" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 space-y-1">
                        <ul class="list-disc list-inside ml-2 space-y-0.5 hidden" id="passwordRequirements">
                            <i class="fas fa-info-circle text-gray-400 mr-1"></i> Password must contain:
                            <li class="<?php echo isset($_POST['new_password']) && strlen($_POST['new_password']) >= 8 ? 'text-green-600' : ''; ?>">At least 8 characters</li>
                            <li class="<?php echo isset($_POST['new_password']) && preg_match('/[A-Z]/', $_POST['new_password']) ? 'text-green-600' : ''; ?>">At least one uppercase letter</li>
                            <li class="<?php echo isset($_POST['new_password']) && preg_match('/[a-z]/', $_POST['new_password']) ? 'text-green-600' : ''; ?>">At least one lowercase letter</li>
                            <li class="<?php echo isset($_POST['new_password']) && preg_match('/[0-9]/', $_POST['new_password']) ? 'text-green-600' : ''; ?>">At least one number</li>
                            <li class="<?php echo isset($_POST['new_password']) && preg_match('/[@$!%*?&]/', $_POST['new_password']) ? 'text-green-600' : ''; ?>">At least one special character (@$!%*?&)</li>
                        </ul>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Confirm New Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="confirm_password" id="confirmPassword" required
                               class="w-full pl-10 pr-12 py-3 border border-gray-200 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200 transition">
                        <button type="button" onclick="togglePassword('confirmPassword', this)" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>
                
                <div id="passwordMatchError" class="text-red-600 text-sm hidden"></div>
                
                <button type="submit" 
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center space-x-2">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                </button>
            </form>
        </div>
        
        <!-- Account Information (Read-only) -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-info-circle text-gray-600 mr-2"></i> Account Information
                </h3>
                <p class="text-sm text-gray-500 mt-1">Your account details and statistics</p>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">Username</span>
                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($admin['username']); ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">Role</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        <?php echo ucfirst($admin['role']); ?>
                    </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">Account Created</span>
                    <span class="text-gray-800"><?php echo date('F j, Y \a\t g:i A', strtotime($admin['created_at'])); ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-gray-600">Last Updated</span>
                    <span class="text-gray-800"><?php echo $admin['updated_at'] ? date('F j, Y \a\t g:i A', strtotime($admin['updated_at'])) : 'Never'; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Danger Zone - Delete Account -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border-2 border-red-200">
            <div class="px-6 py-4 border-b border-red-200 bg-gradient-to-r from-red-50 to-pink-50">
                <h3 class="text-lg font-semibold text-red-800 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i> Danger Zone
                </h3>
                <p class="text-sm text-red-600 mt-1">Permanently delete your account</p>
            </div>
            
            <div class="p-6">
                <div class="mb-4 p-4 bg-red-50 rounded-xl border border-red-200">
                    <div class="flex items-start">
                        <i class="fas fa-ban text-red-600 mt-0.5 mr-3"></i>
                        <div>
                            <p class="font-semibold text-red-800">Warning: This action cannot be undone</p>
                            <p class="text-sm text-red-700 mt-1">Deleting your account will permanently remove all your data and you will lose access to the admin panel.</p>
                            <p class="text-xs text-red-600 mt-2">⚠️ If this is the only admin account, you will not be able to delete it.</p>
                        </div>
                    </div>
                </div>
                
                <form method="POST" id="deleteAccountForm" onsubmit="return confirmDeleteAccount()">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="delete_account" value="1">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Confirm Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="delete_password" id="deletePassword" required
                                   placeholder="Enter your password to confirm deletion"
                                   class="w-full pl-10 pr-12 py-3 border border-red-300 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-200 transition">
                            <button type="button" onclick="togglePassword('deletePassword', this)" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full mt-4 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center space-x-2">
                        <i class="fas fa-trash-alt"></i>
                        <span>Delete My Account</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    

</div>

<script>
    // Toggle password visibility
    function togglePassword(fieldId, button) {
        const field = document.getElementById(fieldId);
        const icon = button.querySelector('i');
        
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
    
    // Password validation and match checking
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordForm = document.getElementById('passwordForm');
    const errorDiv = document.getElementById('passwordMatchError');
    
    function validatePassword() {
        if (newPassword && confirmPassword) {
            if (newPassword.value !== confirmPassword.value) {
                errorDiv.textContent = 'Passwords do not match';
                errorDiv.classList.remove('hidden');
                return false;
            } else {
                errorDiv.classList.add('hidden');
                return true;
            }
        }
        return true;
    }
    
    if (newPassword && confirmPassword) {
        newPassword.addEventListener('keyup', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
    }
    
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            if (!validatePassword()) {
                e.preventDefault();
            }
            
            // Additional password strength check
            const password = newPassword.value;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            
            if (password && !passwordRegex.test(password)) {
                e.preventDefault();
                errorDiv.textContent = 'Password does not meet the requirements';
                errorDiv.classList.remove('hidden');
            }
        });
    }

    // Show password requirements when user focuses on new password field
    if (newPassword) {
        newPassword.addEventListener('focus', function() {
            document.getElementById('passwordRequirements').classList.remove('hidden');
        });
        newPassword.addEventListener('blur', function() {
            document.getElementById('passwordRequirements').classList.add('hidden');
        });
    }
    
    // Delete account confirmation
    function confirmDeleteAccount() {
        return confirm('⚠️ WARNING: This will permanently delete your administrator account.\n\nYou will be logged out immediately and will lose access to the admin panel.\n\nAre you absolutely sure?');
    }
    
    // Live password strength indicator
    if (newPassword) {
        newPassword.addEventListener('keyup', function() {
            const password = this.value;
            const requirements = [
                { regex: /.{8,}/, element: 'length' },
                { regex: /[A-Z]/, element: 'uppercase' },
                { regex: /[a-z]/, element: 'lowercase' },
                { regex: /[0-9]/, element: 'number' },
                { regex: /[@$!%*?&]/, element: 'special' }
            ];
            
            requirements.forEach(req => {
                const element = document.querySelector(`.list-disc li:nth-child(${requirements.indexOf(req) + 1})`);
                if (element) {
                    if (req.regex.test(password)) {
                        element.classList.add('text-green-600');
                        element.classList.remove('text-gray-500');
                    } else {
                        element.classList.remove('text-green-600');
                        element.classList.add('text-gray-500');
                    }
                }
            });
        });
    }
</script>

<style>
    /* Additional styles for the profile page */
    .list-disc li {
        transition: color 0.2s ease;
    }
</style>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>