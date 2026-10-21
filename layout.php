<?php
if (!isset($page_title)) $page_title = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * { font-family: 'Inter', sans-serif; }
        
        .sidebar {
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 40;
            background: linear-gradient(180deg, #1e1b4b 0%, #2e1065 100%);
        }
        
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
        
        .main-content { margin-left: 280px; width: calc(100% - 280px); min-height: 100vh; background: #f8fafc; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; width: 100%; }
        }
        
        .nav-link {
            transition: all 0.2s ease;
            border-radius: 12px;
        }
        .nav-link:hover { background: rgba(255,255,255,0.1); }
        .nav-link.active { background: rgba(255,255,255,0.15); border-left: 3px solid #818cf8; }
        
        .stat-card {
            transition: all 0.3s ease;
            border-radius: 20px;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -12px rgba(0,0,0,0.1); }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        
        .data-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .data-table th { 
            padding: 16px; 
            text-align: left; 
            font-weight: 600;
            background: #f1f5f9;
            border-bottom: 2px solid #e2e8f0;
        }
        .data-table td { 
            padding: 16px; 
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .data-table tr:hover td { background: #f8fafc; }
        
        .action-btn {
            transition: all 0.2s ease;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }
        .action-btn.view { background: #e0e7ff; color: #4f46e5; }
        .action-btn.view:hover { background: #4f46e5; color: white; transform: scale(1.05); }
        .action-btn.delete { background: #fee2e2; color: #dc2626; }
        .action-btn.delete:hover { background: #dc2626; color: white; transform: scale(1.05); }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-unread { background: #fef3c7; color: #92400e; }
        .badge-read { background: #d1fae5; color: #065f46; }
        .badge-high { background: #fee2e2; color: #dc2626; }
        .badge-medium { background: #fed7aa; color: #ea580c; }
        .badge-low { background: #e0e7ff; color: #4f46e5; }
        
        .profile-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #818cf8, #c084fc);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            color: white;
        }
        
        .danger-zone {
            border: 1px solid #fecaca;
            background: #fef2f2;
            border-radius: 20px;
        }
        
        .filter-btn.active {
            background: #4f46e5;
            color: white;
        }
        
        /* Session Timer Styles */
        .session-timer {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 12px;
            margin-top: 20px;
        }
        
        .timer-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 18px;
            letter-spacing: 2px;
        }
        
        .timer-warning {
            animation: pulse 1s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        /* Session timeout modal */
        .timeout-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .timeout-modal.active {
            display: flex;
        }
        
        .timeout-card {
            background: white;
            border-radius: 24px;
            padding: 32px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <button id="menuToggle" class="md:hidden fixed top-4 left-4 z-50 bg-indigo-600 text-white p-3 rounded-xl shadow-lg">
        <i class="fas fa-bars text-xl"></i>
    </button>
    
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar w-[280px] text-white">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-8 pb-4 border-b border-white/10">
                    <div class="w-10 h-10 bg-gradient-to-r from-indigo-400 to-purple-400 rounded-xl flex items-center justify-center">
                        <i class="fas fa-church text-white text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold"><?php echo SITE_NAME; ?></h2>
                        <p class="text-xs text-white/60">Admin Panel</p>
                    </div>
                </div>
                
                <nav class="space-y-1">
                    <a href="index.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="events.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt w-5"></i>
                        <span>Events</span>
                    </a>
                    <a href="messages.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : ''; ?>">
                        <i class="fas fa-comments w-5"></i>
                        <span>Messages</span>
                    </a>
                    <a href="emergencies.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'emergencies.php' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle w-5"></i>
                        <span>Emergencies</span>
                    </a>
                    <a href="contact_requests.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'contact_requests.php' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope w-5"></i>
                        <span>Contact Requests</span>
                    </a>
                    <a href="connect_requests.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'connect_requests.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users w-5"></i>
                        <span>Connect Groups</span>
                    </a>
                    <a href="members.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'members.php' ? 'active' : ''; ?>">
                        <i class="fas fa-address-card w-5"></i>
                        <span>Members</span>
                    </a>
                    <a href="dinner.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'dinner.php' ? 'active' : ''; ?>">
                        <i class="fas fa-address-card w-5"></i>
                        <span>Heroes' Dinner</span>
                    </a>
                    <a href="offerings.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'offerings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-hand-holding-usd w-5"></i>
                        <span>Offerings</span>
                    </a>
                    <a href="profile.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle w-5"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="logout.php" class="nav-link flex items-center space-x-3 px-4 py-3 text-sm text-red-300 hover:text-red-200 mt-1">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span>Logout</span>
                    </a>
                </nav>

                <!-- Session Timer Display -->
                <div class="session-timer mt-8 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-white">
                            <i class="fas fa-clock mr-1"></i> SESSION:
                        </span>
                        <span id="sessionTimerDisplay" class="text-sm font-mono font-bold text-white">15:00</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="p-6 md:p-8">
                <div class="max-w-7xl mx-auto">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-xl">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                <p class="text-green-700"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-xl">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                                <p class="text-red-700"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Session Timeout Modal -->
    <div id="timeoutModal" class="timeout-modal">
        <div class="timeout-card">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-hourglass-end text-red-600 text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Session Expired</h3>
            <p class="text-gray-600 mb-4">Your session has expired due to inactivity.</p>
            <p class="text-sm text-gray-500 mb-6">You will be redirected to the login page.</p>
            <div class="flex justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
        </div>
    </div>
    
    
    <script>
        // Session Timer Configuration (15 minutes = 900 seconds)
        const SESSION_DURATION = 15 * 60; // 900 seconds
        let remainingTime = SESSION_DURATION;
        let timerInterval;
        let warningShown = false;
        
        // Function to format time as MM:SS
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        
        // Function to update timer displays
        function updateTimerDisplay() {
            const formattedTime = formatTime(remainingTime);
            
            // Update sidebar timer
            const sessionTimerDisplay = document.getElementById('sessionTimerDisplay');
            if (sessionTimerDisplay) {
                sessionTimerDisplay.textContent = formattedTime;
            }
            
            // Update top bar timer
            const topTimer = document.getElementById('topTimer');
            if (topTimer) {
                topTimer.textContent = formattedTime;
            }
            
            // Update progress bar
            const progressPercent = (remainingTime / SESSION_DURATION) * 100;
            const timerBar = document.getElementById('sessionTimerBar');
            if (timerBar) {
                timerBar.style.width = `${progressPercent}%`;
                
                // Change color based on remaining time
                if (remainingTime <= 60) {
                    timerBar.classList.add('timer-warning');
                } else {
                    timerBar.classList.remove('timer-warning');
                }
            }
            
            // Add warning class to timer numbers when less than 2 minutes
            const timerElements = [sessionTimerDisplay, topTimer];
            timerElements.forEach(el => {
                if (el) {
                    if (remainingTime <= 120) {
                        el.classList.add('text-red-400', 'font-bold');
                    } else {
                        el.classList.remove('text-red-400', 'font-bold');
                    }
                }
            });
            
            // Show warning notification at 1 minute
            if (remainingTime === 60 && !warningShown) {
                warningShown = true;
                showWarningNotification();
            }
        }
        
        // Function to show warning notification
        function showWarningNotification() {
            const notification = document.createElement('div');
            notification.className = 'fixed top-20 right-6 z-50 bg-red-500 text-white px-6 py-4 rounded-xl shadow-lg animate-bounce';
            notification.innerHTML = `
                <div class="flex items-center space-x-3">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                    <div>
                        <p class="font-bold">Session Expiring Soon!</p>
                        <p class="text-sm">Your session will expire in 1 minute.</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Auto-remove after 10 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 10000);
        }
        
        // Function to perform logout
        function performLogout() {
            // Clear interval
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            
            // Show timeout modal
            const timeoutModal = document.getElementById('timeoutModal');
            if (timeoutModal) {
                timeoutModal.classList.add('active');
            }
            
            // Redirect to logout after 3 seconds
            setTimeout(() => {
                window.location.href = 'logout.php?timeout=1';
            }, 3000);
        }
        
        // Function to reset timer on user activity
        let activityTimeout;
        function resetTimer() {
            // Reset remaining time
            remainingTime = SESSION_DURATION;
            warningShown = false;
            updateTimerDisplay();
            
            // Reset activity timeout
            if (activityTimeout) {
                clearTimeout(activityTimeout);
            }
            
            // Send heartbeat to server to extend session
            fetch('heartbeat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'csrf_token=' + encodeURIComponent('<?php echo $_SESSION['csrf_token'] ?? ''; ?>')
            }).catch(err => console.log('Heartbeat failed'));
        }
        
        // Start timer countdown
        function startTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            
            timerInterval = setInterval(() => {
                if (remainingTime > 0) {
                    remainingTime--;
                    updateTimerDisplay();
                    
                    if (remainingTime <= 0) {
                        clearInterval(timerInterval);
                        performLogout();
                    }
                }
            }, 1000);
        }
        
        // Listen for user activity
        const activityEvents = ['mousedown', 'keydown', 'mousemove', 'scroll', 'click', 'touchstart'];
        activityEvents.forEach(event => {
            document.addEventListener(event, () => {
                resetTimer();
            });
        });
        
        // Initialize timer
        startTimer();
        
        // Mobile menu toggle
        document.getElementById('menuToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Close sidebar on link click (mobile)
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    document.querySelector('.sidebar').classList.remove('active');
                }
            });
        });
        
        // Profile modal functions
        function openProfileModal() {
            document.getElementById('profileModal').classList.add('active');
        }
        
        function closeProfileModal() {
            document.getElementById('profileModal').classList.remove('active');
        }
        
        // Toggle password visibility
        function togglePasswordField(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
        
        // Delete confirmation modal
        let deleteType = '', deleteId = 0;
        
        function confirmDelete(type, id, name = '') {
            deleteType = type;
            deleteId = id;
            const msg = `Delete ${name ? name + ' ' : ''}${type}? This action cannot be undone.`;
            if (confirm(msg)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_item" value="1">
                    <input type="hidden" name="type" value="${type}">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // View modal functions
        function viewContact(contact) {
            const modal = document.getElementById('viewModal') || createViewModal();
            const data = typeof contact === 'string' ? JSON.parse(contact) : contact;
            document.getElementById('viewTitle').textContent = `Message from ${escapeHtml(data.name)}`;
            document.getElementById('viewContent').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-sm text-gray-500">Name</p><p class="font-semibold">${escapeHtml(data.name)}</p></div>
                        <div><p class="text-sm text-gray-500">Email</p><p class="font-semibold">${escapeHtml(data.email || 'N/A')}</p></div>
                        <div><p class="text-sm text-gray-500">Phone</p><p class="font-semibold">${escapeHtml(data.phone || 'N/A')}</p></div>
                        <div><p class="text-sm text-gray-500">Subject</p><p class="font-semibold">${escapeHtml(data.subject || 'N/A')}</p></div>
                        <div><p class="text-sm text-gray-500">Submitted</p><p class="font-semibold">${new Date(data.submitted_at).toLocaleString()}</p></div>
                        <div><p class="text-sm text-gray-500">Status</p><span class="badge ${data.is_read ? 'badge-read' : 'badge-unread'}">${data.is_read ? 'Read' : 'Unread'}</span></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-sm text-gray-500 mb-2">Message</p>
                        <p class="text-gray-800 whitespace-pre-wrap">${escapeHtml(data.message)}</p>
                    </div>
                </div>
            `;
            modal.classList.add('active');
            
            // Mark as read if unread
            if (!data.is_read) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `ajax_mark_read=1&id=${data.id}&csrf_token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>`
                });
            }
        }
        
        function viewMember(member) {
            const modal = document.getElementById('viewModal') || createViewModal();
            const data = typeof member === 'string' ? JSON.parse(member) : member;
            document.getElementById('viewTitle').textContent = `Member: ${data.first_name}`;
            document.getElementById('viewContent').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-sm text-gray-500">Full Name</p><p class="font-semibold">${escapeHtml(data.first_name)}</p></div>
                        <div><p class="text-sm text-gray-500">Phone</p><p class="font-semibold">${escapeHtml(data.phone)}</p></div>
                        <div><p class="text-sm text-gray-500">Location</p><p class="font-semibold">${escapeHtml(data.location)}</p></div>
                        <div><p class="text-sm text-gray-500">Email</p><p class="font-semibold">${escapeHtml(data.email || 'N/A')}</p></div>
                        <div><p class="text-sm text-gray-500">Gender</p><p class="font-semibold">${escapeHtml(data.gender || 'Not specified')}</p></div>
                        <div><p class="text-sm text-gray-500">Age</p><p class="font-semibold">${data.age || 'N/A'}</p></div>
                        <div><p class="text-sm text-gray-500">Registered</p><p class="font-semibold">${new Date(data.registered_at).toLocaleString()}</p></div>
                    </div>
                </div>
            `;
            modal.classList.add('active');
        }
        
        function createViewModal() {
            const modalDiv = document.createElement('div');
            modalDiv.id = 'viewModal';
            modalDiv.className = 'modal';
            modalDiv.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4 pb-4 border-b">
                            <h3 id="viewTitle" class="text-xl font-bold text-gray-800">View Details</h3>
                            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div id="viewContent"></div>
                        <div class="mt-6 flex justify-end">
                            <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-200 rounded-xl hover:bg-gray-300 transition">Close</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modalDiv);
            return modalDiv;
        }
        
        function closeViewModal() {
            document.getElementById('viewModal')?.classList.remove('active');
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) this.classList.remove('active');
            });
        });
        
        // Escape key closes modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => modal.classList.remove('active'));
            }
        });
        
        // Page visibility API - pause timer when tab is inactive
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Tab is hidden - we don't pause the timer, but we could
                // This is just for reference
            } else {
                // Tab became visible again - reset timer on activity
                resetTimer();
            }
        });
    </script>
</body>
</html>