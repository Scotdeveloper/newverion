// connect_requests.php - Connect groups requests page
<?php
require_once 'config.php';
requireLogin();

$page_title = 'Connect Groups';
$db = getDB();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: connect_requests.php');
        exit();
    }
    
    // Delete connect request
    if (isset($_POST['delete_item']) && $_POST['type'] === 'connect') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM connect_requests WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Connect request deleted successfully';
        header('Location: connect_requests.php');
        exit();
    }
    
    // Mark as read (AJAX)
    if (isset($_POST['ajax_mark_connect_read'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE connect_requests SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit();
    }
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $db->prepare("UPDATE connect_requests SET is_read = 1 WHERE is_read = 0");
    $stmt->execute();
    $_SESSION['success'] = 'All connect requests marked as read';
    header('Location: connect_requests.php');
    exit();
}

// Get search parameter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$query = "SELECT * FROM connect_requests WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR location LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_fill(0, 4, $searchTerm);
}

if ($status === 'unread') {
    $query .= " AND is_read = 0";
} elseif ($status === 'read') {
    $query .= " AND is_read = 1";
}

$query .= " ORDER BY 
            CASE WHEN is_read = 0 THEN 0 ELSE 1 END,
            submitted_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$connectRequests = $stmt->fetchAll();

// Calculate statistics
$totalRequests = $db->query("SELECT COUNT(*) FROM connect_requests")->fetchColumn();
$unreadRequests = $db->query("SELECT COUNT(*) FROM connect_requests WHERE is_read = 0")->fetchColumn();
$readRequests = $db->query("SELECT COUNT(*) FROM connect_requests WHERE is_read = 1")->fetchColumn();

// This month
$thisMonth = $db->query("SELECT COUNT(*) FROM connect_requests WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// This week
$thisWeek = $db->query("SELECT COUNT(*) FROM connect_requests WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

$csrf_token = $_SESSION['csrf_token'];

ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Connect Group Requests</h1>
            <p class="text-gray-500 mt-1">People interested in joining connect groups</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl transition flex items-center">
                <i class="fas fa-sync-alt mr-2"></i> Refresh
            </button>
            <a href="?mark_all_read=1" onclick="return confirm('Mark all as read?')" 
               class="bg-yellow-100 hover:bg-yellow-200 text-yellow-700 px-4 py-2 rounded-xl transition flex items-center">
                <i class="fas fa-check-double mr-2"></i> Mark All Read
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center border-l-4 border-blue-500">
        <p class="text-gray-500 text-xs">Total</p>
        <h3 class="text-2xl font-bold text-gray-800"><?php echo $totalRequests; ?></h3>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center border-l-4 border-yellow-500">
        <p class="text-gray-500 text-xs">Unread</p>
        <h3 class="text-2xl font-bold text-yellow-600"><?php echo $unreadRequests; ?></h3>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center border-l-4 border-green-500">
        <p class="text-gray-500 text-xs">Read</p>
        <h3 class="text-2xl font-bold text-green-600"><?php echo $readRequests; ?></h3>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center border-l-4 border-purple-500">
        <p class="text-gray-500 text-xs">This Month</p>
        <h3 class="text-2xl font-bold text-purple-600"><?php echo $thisMonth; ?></h3>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center border-l-4 border-indigo-500">
        <p class="text-gray-500 text-xs">This Week</p>
        <h3 class="text-2xl font-bold text-indigo-600"><?php echo $thisWeek; ?></h3>
    </div>
</div>

<!-- Search Bar -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-gray-700 text-sm font-medium mb-2">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search by name, email, phone, location..."
                   class="w-full p-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
        </div>
        <div class="w-32">
            <label class="block text-gray-700 text-sm font-medium mb-2">Status</label>
            <select name="status" class="w-full p-3 border border-gray-200 rounded-xl focus:border-indigo-500">
                <option value="">All</option>
                <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl transition">
                <i class="fas fa-search mr-2"></i> Search
            </button>
            <a href="connect_requests.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-xl transition">
                <i class="fas fa-times mr-2"></i> Clear
            </a>
        </div>
    </form>
</div>

<?php if (empty($connectRequests)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <i class="fas fa-users-slash text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700">No Connect Requests Found</h3>
        <p class="text-gray-500 mt-2">Try adjusting your search or filter criteria.</p>
    </div>
<?php else: ?>
    <!-- Connect Requests Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact Info</th>
                        <th>Location</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($connectRequests as $request): ?>
                        <tr class="<?php echo !$request['is_read'] ? 'bg-green-50/30' : ''; ?>">
                            <td>
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white font-bold mr-3">
                                        <?php echo strtoupper(substr($request['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($request['name']); ?></p>
                                        <p class="text-xs text-gray-500">ID: #<?php echo $request['id']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($request['email']): ?>
                                    <p class="text-sm"><i class="fas fa-envelope text-gray-400 w-4"></i> <?php echo htmlspecialchars($request['email']); ?></p>
                                <?php endif; ?>
                                <p class="text-sm mt-1"><i class="fas fa-phone text-gray-400 w-4"></i> <?php echo htmlspecialchars($request['phone']); ?></p>
                            </td>
                            <td>
                                <?php if ($request['location']): ?>
                                    <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i> <?php echo htmlspecialchars($request['location']); ?>
                                <?php else: ?>
                                    <span class="text-gray-400">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($request['submitted_at'])); ?></td>
                            <td>
                                <?php if ($request['is_read']): ?>
                                    <span class="badge badge-read"><i class="fas fa-check-circle mr-1"></i> Read</span>
                                <?php else: ?>
                                    <span class="badge badge-unread"><i class="fas fa-envelope mr-1"></i> Unread</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex space-x-2">
                                    <button onclick="viewConnectRequest(<?php echo htmlspecialchars(json_encode($request)); ?>)" 
                                            class="action-btn view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (!$request['is_read']): ?>
                                        <button onclick="markAsRead(<?php echo $request['id']; ?>)" 
                                                class="action-btn view bg-blue-100 hover:bg-blue-500" title="Mark as Read">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <a href="tel:<?php echo $request['phone']; ?>" 
                                       class="action-btn view bg-green-100 hover:bg-green-500" title="Call">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                    <button onclick="confirmDelete('connect', <?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['name']); ?>')" 
                                            class="action-btn delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
    function viewConnectRequest(request) {
        const modal = document.getElementById('viewModal') || createViewModal();
        const data = typeof request === 'string' ? JSON.parse(request) : request;
        
        document.getElementById('viewTitle').innerHTML = `
            <i class="fas fa-users text-green-600 mr-2"></i> Connect Request from ${escapeHtml(data.name)}
        `;
        
        document.getElementById('viewContent').innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-xl">
                    <div><span class="text-gray-500 text-sm">Name:</span><br><span class="font-semibold">${escapeHtml(data.name)}</span></div>
                    <div><span class="text-gray-500 text-sm">Email:</span><br><span class="font-semibold">${escapeHtml(data.email || 'N/A')}</span></div>
                    <div><span class="text-gray-500 text-sm">Phone:</span><br><span class="font-semibold">${escapeHtml(data.phone)}</span></div>
                    <div><span class="text-gray-500 text-sm">Location:</span><br><span class="font-semibold">${escapeHtml(data.location || 'Not specified')}</span></div>
                    <div><span class="text-gray-500 text-sm">Submitted:</span><br><span class="font-semibold">${new Date(data.submitted_at).toLocaleString()}</span></div>
                    <div><span class="text-gray-500 text-sm">Status:</span><br><span class="badge ${data.is_read ? 'badge-read' : 'badge-unread'}">${data.is_read ? 'Read' : 'Unread'}</span></div>
                    ${data.ip_address ? `<div><span class="text-gray-500 text-sm">IP Address:</span><br><span class="font-mono text-sm">${escapeHtml(data.ip_address)}</span></div>` : ''}
                </div>
                <div class="bg-gray-50 p-4 rounded-xl">
                    <p class="text-gray-500 text-sm mb-2">Message:</p>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-gray-800 whitespace-pre-wrap">${escapeHtml(data.message || 'No message provided')}</p>
                    </div>
                </div>
            </div>
        `;
        
        const replyBtn = document.getElementById('replyButton');
        if (replyBtn) {
            if (data.email) {
                replyBtn.href = `mailto:${data.email}`;
                replyBtn.classList.remove('hidden');
            } else {
                replyBtn.classList.add('hidden');
            }
        }
        
        modal.classList.add('active');
        
        // Mark as read if unread
        if (!data.is_read) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax_mark_connect_read=1&id=${data.id}&csrf_token=<?php echo $csrf_token; ?>`
            }).then(() => {
                location.reload();
            });
        }
    }
    
    function markAsRead(id) {
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax_mark_connect_read=1&id=${id}&csrf_token=<?php echo $csrf_token; ?>`
        }).then(() => {
            location.reload();
        });
    }
    
    function createViewModal() {
        if (document.getElementById('viewModal')) return document.getElementById('viewModal');
        
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
                    <div class="mt-6 flex justify-end gap-2">
                        <a href="#" id="replyButton" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl transition hidden">Reply</a>
                        <a href="#" id="callButton" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl transition hidden">Call</a>
                        <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-200 rounded-xl hover:bg-gray-300 transition">Close</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalDiv);
        
        // Update call button dynamically
        const observer = new MutationObserver(() => {
            const callBtn = document.getElementById('callButton');
            const phoneMatch = document.getElementById('viewContent')?.innerHTML.match(/Phone:.*?(\d+)/);
            if (phoneMatch && callBtn) {
                callBtn.href = `tel:${phoneMatch[1]}`;
                callBtn.classList.remove('hidden');
            }
        });
        observer.observe(document.getElementById('viewContent'), { childList: true, subtree: true });
        
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
    
    document.getElementById('viewModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>