<?php
require_once 'config.php';
requireLogin();

$page_title = 'Dashboard';

$db = getDB();

// Get statistics
$eventCount = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$messageCount = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$emergencyCount = $db->query("SELECT COUNT(*) FROM emergencies WHERE is_active = 1")->fetchColumn();
$contactUnread = $db->query("SELECT COUNT(*) FROM contact_requests WHERE is_read = 0")->fetchColumn();
$contactTotal = $db->query("SELECT COUNT(*) FROM contact_requests")->fetchColumn();
$connectUnread = $db->query("SELECT COUNT(*) FROM connect_requests WHERE is_read = 0")->fetchColumn();
$connectTotal = $db->query("SELECT COUNT(*) FROM connect_requests")->fetchColumn();
$memberTotal = $db->query("SELECT COUNT(*) FROM members")->fetchColumn();
$memberUnread = $db->query("SELECT COUNT(*) FROM members WHERE is_read = 0")->fetchColumn();
$offeringTotal = $db->query("SELECT COALESCE(SUM(amount), 0) FROM offerings")->fetchColumn();
$offeringThisMonth = $db->query("SELECT COALESCE(SUM(amount), 0) FROM offerings WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// Get recent members
$recentMembers = $db->query("SELECT * FROM members ORDER BY registered_at DESC LIMIT 5")->fetchAll();

// Get recent offerings
$recentOfferings = $db->query("SELECT * FROM offerings ORDER BY submitted_at DESC LIMIT 5")->fetchAll();

ob_start();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-gray-500 mt-1">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>! Here's what's happening today.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-indigo-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Events</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $eventCount; ?></h3>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-calendar-alt text-indigo-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Messages</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $messageCount; ?></h3>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-comments text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-red-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Active Emergencies</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $emergencyCount; ?></h3>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Members</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $memberTotal; ?></h3>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-users text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Contact Stats -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-envelope text-indigo-600 mr-2"></i> Contact Requests
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-4 bg-yellow-50 rounded-xl">
                <p class="text-2xl font-bold text-yellow-600"><?php echo $contactUnread; ?></p>
                <p class="text-sm text-gray-600">Unread</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-xl">
                <p class="text-2xl font-bold text-gray-600"><?php echo $contactTotal; ?></p>
                <p class="text-sm text-gray-600">Total</p>
            </div>
        </div>
        <a href="contact_requests.php" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            View all requests <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    
    <!-- Connect Stats -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-users text-green-600 mr-2"></i> Connect Groups
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-4 bg-yellow-50 rounded-xl">
                <p class="text-2xl font-bold text-yellow-600"><?php echo $connectUnread; ?></p>
                <p class="text-sm text-gray-600">Unread</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-xl">
                <p class="text-2xl font-bold text-gray-600"><?php echo $connectTotal; ?></p>
                <p class="text-sm text-gray-600">Total</p>
            </div>
        </div>
        <a href="connect_requests.php" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            View all requests <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Members -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-user-plus text-blue-600 mr-2"></i> Recent Members
            </h3>
            <a href="members.php" class="text-sm text-indigo-600 hover:text-indigo-800">View all</a>
        </div>
        
        <?php if (empty($recentMembers)): ?>
            <p class="text-gray-500 text-center py-8">No members yet</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentMembers as $member): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($member['full_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($member['phone']); ?></p>
                        </div>
                        <span class="badge <?php echo $member['is_read'] ? 'badge-read' : 'badge-unread'; ?>">
                            <?php echo $member['is_read'] ? 'Read' : 'New'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Offerings -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-hand-holding-usd text-green-600 mr-2"></i> Recent Offerings
            </h3>
            <a href="offerings.php" class="text-sm text-indigo-600 hover:text-indigo-800">View all</a>
        </div>
        
        <?php if (empty($recentOfferings)): ?>
            <p class="text-gray-500 text-center py-8">No offerings yet</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentOfferings as $offering): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($offering['donor_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($offering['type']); ?></p>
                        </div>
                        <p class="font-bold text-green-600">Kshs <?php echo number_format($offering['amount'], 2); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">This Month</span>
                <span class="font-bold text-xl text-green-600">Kshs <?php echo number_format($offeringThisMonth, 2); ?></span>
            </div>
            <div class="flex justify-between items-center mt-2">
                <span class="text-gray-600">Total Received</span>
                <span class="font-bold text-xl text-indigo-600">Kshs <?php echo number_format($offeringTotal, 2); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
    <a href="events.php?action=create" class="bg-indigo-50 hover:bg-indigo-100 rounded-xl p-4 text-center transition">
        <i class="fas fa-plus-circle text-indigo-600 text-2xl mb-2"></i>
        <p class="font-medium text-indigo-800 text-sm">New Event</p>
    </a>
    <a href="messages.php?action=create" class="bg-green-50 hover:bg-green-100 rounded-xl p-4 text-center transition">
        <i class="fas fa-comment-medical text-green-600 text-2xl mb-2"></i>
        <p class="font-medium text-green-800 text-sm">New Message</p>
    </a>
    <a href="emergencies.php?action=create" class="bg-red-50 hover:bg-red-100 rounded-xl p-4 text-center transition">
        <i class="fas fa-bullhorn text-red-600 text-2xl mb-2"></i>
        <p class="font-medium text-red-800 text-sm">New Emergency</p>
    </a>
    <a href="members.php" class="bg-purple-50 hover:bg-purple-100 rounded-xl p-4 text-center transition">
        <i class="fas fa-address-card text-purple-600 text-2xl mb-2"></i>
        <p class="font-medium text-purple-800 text-sm">View Members</p>
    </a>
</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>