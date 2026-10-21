  <?php
require_once 'config.php';
requireLogin();

$page_title = 'Offerings';
$db = getDB();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: offerings.php');
        exit();
    }
    
    // Delete transaction (only if 180 days old)
    if (isset($_POST['delete_item']) && $_POST['type'] === 'offering') {
        $id = (int)$_POST['id'];
        
        // Check if transaction is at least 180 days old
        $checkStmt = $db->prepare("SELECT submitted_at FROM transactions WHERE id = ?");
        $checkStmt->execute([$id]);
        $transaction = $checkStmt->fetch();
        
        if ($transaction) {
            $submittedDate = new DateTime($transaction['submitted_at']);
            $now = new DateTime();
            $diff = $submittedDate->diff($now);
            $daysOld = $diff->days;
            
            if ($daysOld >= 180) {
                $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Transaction record deleted successfully';
            } else {
                $_SESSION['error'] = 'Only transactions older than 180 days can be deleted';
            }
        } else {
            $_SESSION['error'] = 'Transaction not found';
        }
        
        header('Location: offerings.php');
        exit();
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build query - only fetch completed transactions
$query = "SELECT * FROM transactions WHERE status = 'Completed'";
$params = [];

if (!empty($search)) {
    $query .= " AND (phone LIKE ? OR amount LIKE ? OR offering_type LIKE ? OR mpesa_receipt LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($type)) {
    $query .= " AND offering_type = ?";
    $params[] = $type;
}

if (!empty($start_date)) {
    $query .= " AND submitted_at >= ?";
    $params[] = $start_date . ' 00:00:00';
}

if (!empty($end_date)) {
    $query .= " AND submitted_at <= ?";
    $params[] = $end_date . ' 23:59:59';
}

$query .= " ORDER BY submitted_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate statistics - only for completed transactions
$totalAmount = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'Completed'")->fetchColumn();
$thisMonth = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'Completed'")->fetchColumn();
$thisWeek = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'Completed'")->fetchColumn();
$today = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE DATE(submitted_at) = CURDATE() AND status = 'Completed'")->fetchColumn();

$totalCount = $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'Completed'")->fetchColumn();
$thisMonthCount = $db->query("SELECT COUNT(*) FROM transactions WHERE submitted_at > DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'Completed'")->fetchColumn();

// Type breakdown - only for completed transactions
$titheTotal = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE offering_type = 'tithe' AND status = 'Completed'")->fetchColumn();
$offeringTotal = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE offering_type = 'offering' AND status = 'Completed'")->fetchColumn();
$failedTotal = $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE offering_type = 'special' AND status = 'Completed'")->fetchColumn();

$csrf_token = $_SESSION['csrf_token'];

ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Offerings & Donations</h1>
            <p class="text-gray-500 mt-1">Track and manage church offerings</p>
        </div>
        <button onclick="window.location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl transition flex items-center">
            <i class="fas fa-sync-alt mr-2"></i> Refresh
        </button>
    </div>
</div>

<!-- Financial Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-2xl shadow-sm p-5 text-white">
        <p class="text-green-100 text-sm">Total Received</p>
        <h3 class="text-3xl font-bold">Kshs <?php echo number_format($totalAmount, 2); ?></h3>
        <p class="text-green-200 text-xs mt-1">All time</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-blue-500">
        <p class="text-gray-500 text-sm">This Month</p>
        <h3 class="text-2xl font-bold text-gray-800">Kshs <?php echo number_format($thisMonth, 2); ?></h3>
        <p class="text-xs text-gray-400 mt-1"><?php echo $thisMonthCount; ?> transactions</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-purple-500">
        <p class="text-gray-500 text-sm">This Week</p>
        <h3 class="text-2xl font-bold text-gray-800">Kshs <?php echo number_format($thisWeek, 2); ?></h3>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5 border-l-4 border-yellow-500">
        <p class="text-gray-500 text-sm">Today</p>
        <h3 class="text-2xl font-bold text-gray-800">Kshs <?php echo number_format($today, 2); ?></h3>
    </div>
</div>

<!-- Type Breakdown -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
        <p class="text-gray-500 text-sm">Tithe</p>
        <p class="text-2xl font-bold text-blue-600">Kshs <?php echo number_format($titheTotal, 2); ?></p>
        <?php 
        $titheCount = $db->query("SELECT COUNT(*) FROM transactions WHERE offering_type = 'tithe' AND status = 'Completed'")->fetchColumn();
        ?>
        <p class="text-xs text-gray-400 mt-1"><?php echo $titheCount; ?> transactions</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
        <p class="text-gray-500 text-sm">Offering</p>
        <p class="text-2xl font-bold text-green-600">Kshs <?php echo number_format($offeringTotal, 2); ?></p>
        <?php 
        $offeringCount = $db->query("SELECT COUNT(*) FROM transactions WHERE offering_type = 'offering' AND status = 'Completed'")->fetchColumn();
        ?>
        <p class="text-xs text-gray-400 mt-1"><?php echo $offeringCount; ?> transactions</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
        <p class="text-gray-500 text-sm">Failed</p>
        <?php 
        $failedCount = $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'Failed' OR status = 'Pending'")->fetchColumn();
        ?>
        <p class="text-base text-gray-400 mt-1"><?php echo $failedCount; ?> transactions</p>
    </div>
</div>

<!-- Search and Filter Bar -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[160px]">
            <label class="block text-gray-700 text-sm font-medium mb-2">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Phone, amount, offering type..."
                   class="w-full p-2.5 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
        </div>
        <div class="w-28">
            <label class="block text-gray-700 text-sm font-medium mb-2">Type</label>
            <select name="type" class="w-full p-2.5 border border-gray-200 rounded-xl">
                <option value="">All</option>
                <option value="tithe" <?php echo $type === 'tithe' ? 'selected' : ''; ?>>Tithe</option>
                <option value="offering" <?php echo $type === 'offering' ? 'selected' : ''; ?>>Offering</option>
                <option value="firstfruit" <?php echo $type === 'firstfruit' ? 'selected' : ''; ?>>First Fruits</option>
                <option value="thanksgiving" <?php echo $type === 'thanksgiving' ? 'selected' : ''; ?>>Thanksgiving</option>
                <option value="projects" <?php echo $type === 'projects' ? 'selected' : ''; ?>>Projects</option>
                <option value="missions" <?php echo $type === 'missions' ? 'selected' : ''; ?>>Missions</option>
            </select>
        </div>
        <div class="w-36">
            <label class="block text-gray-700 text-sm font-medium mb-2">Start Date</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" 
                   class="w-full p-2.5 border border-gray-200 rounded-xl">
        </div>
        <div class="w-36">
            <label class="block text-gray-700 text-sm font-medium mb-2">End Date</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" 
                   class="w-full p-2.5 border border-gray-200 rounded-xl">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl transition">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
            <a href="offerings.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2.5 rounded-xl transition">
                <i class="fas fa-times mr-2"></i> Clear
            </a>
        </div>
    </form>
</div>

<?php if (empty($transactions)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <i class="fas fa-hand-holding-usd text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700">No Completed Offerings Records Found</h3>
        <p class="text-gray-500 mt-2">Try adjusting your search or filter criteria.</p>
    </div>
<?php else: ?>
    <!-- Offerings Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Phone Number</th>
                        <th>Amount</th>
                        <th>Offering Type</th>
                        <th>Mpesa Receipt</th>
                        <th>Status</th>
                        <th>Transaction Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): 
                        // Check if transaction is 180 days old
                        $submittedDate = new DateTime($transaction['submitted_at']);
                        $now = new DateTime();
                        $diff = $submittedDate->diff($now);
                        $daysOld = $diff->days;
                        $canDelete = $daysOld >= 180;
                    ?>
                        <tr>
                            <td>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($transaction['phone']); ?></p>
                                </div>
                            </td>
                            <td>
                                <span class="font-bold text-green-600">Kshs <?php echo number_format($transaction['amount'], 2); ?></span>
                            </td>
                            <td>
                                <p class="uppercase"><?php echo ucfirst($transaction['offering_type']); ?></p>
                            </td>
                            <td><span class="text-lg font-mono"><?php echo htmlspecialchars($transaction['mpesa_receipt'] ?: 'N/A'); ?></span></td>
                            <td>
                                <span class="uppercase"><?php echo $transaction['status']; ?></span>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($transaction['submitted_at'])); ?></td>
                            <td>
                                <div class="flex space-x-2">
                                    <button onclick="viewTransaction(<?php echo htmlspecialchars(json_encode($transaction)); ?>)" 
                                            class="action-btn view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($canDelete): ?>
                                        <button onclick="openDeleteModal(<?php echo $transaction['id']; ?>, '<?php echo htmlspecialchars($transaction['phone']); ?>', <?php echo $daysOld; ?>)" 
                                                class="action-btn delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="action-btn delete-disabled" title="Can only delete transactions older than 180 days (<?php echo $daysOld; ?> days old)" disabled>
                                            <i class="fas fa-trash text-gray-400"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="2" class="text-right font-bold">Total:</td>
                        <td class="font-bold text-green-600">Kshs <?php echo number_format(array_sum(array_column($transactions, 'amount')), 2); ?></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="type" value="offering">
    <input type="hidden" name="delete_item" value="1">
</form>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i> Confirm Delete
                </h3>
                <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="deleteContent" class="mb-6">
                <p class="text-gray-700 mb-3">Are you sure you want to delete this transaction?</p>
                <div class="bg-gray-50 p-4 rounded-xl">
                    <p><strong>Phone:</strong> <span id="deletePhone"></span></p>
                    <p><strong>Transaction Age:</strong> <span id="deleteAge"></span> days</p>
                    <p class="text-red-600 text-sm mt-2"><i class="fas fa-info-circle"></i> This action cannot be undone.</p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 rounded-xl hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition">
                    <i class="fas fa-trash mr-2"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let deleteId = null;
    
    function viewTransaction(transaction) {
        const modal = document.getElementById('viewModal') || createViewModal();
        const data = typeof transaction === 'string' ? JSON.parse(transaction) : transaction;
        
        document.getElementById('viewTitle').innerHTML = `
            <i class="fas fa-hand-holding-usd text-green-600 mr-2"></i> Transaction Details
        `;
        
        document.getElementById('viewContent').innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-xl">
                    <div><span class="text-gray-500 text-sm">Phone Number:</span><br><span class="font-semibold">${escapeHtml(data.phone || 'N/A')}</span></div>
                    <div><span class="text-gray-500 text-sm">Offering Type:</span><br><span class="font-semibold">${escapeHtml(data.offering_type || 'N/A')}</span></div>
                    <div><span class="text-gray-500 text-sm">Amount:</span><br><span class="font-bold text-green-600 text-xl">Kshs ${parseFloat(data.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</span></div>
                    <div><span class="text-gray-500 text-sm">Mpesa Receipt:</span><br><span class="font-semibold">${escapeHtml(data.mpesa_receipt || 'N/A')}</span></div>
                    <div><span class="text-gray-500 text-sm">Status:</span><br><span class="font-semibold">${escapeHtml(data.status || 'N/A')}</span></div>
                    <div><span class="text-gray-500 text-sm">Date:</span><br><span class="font-semibold">${new Date(data.submitted_at).toLocaleString()}</span></div>
                </div>
                ${data.result_desc ? `
                <div class="bg-gray-50 p-3 rounded-xl">
                    <p class="text-gray-500 text-sm mb-1">Result Description:</p>
                    <p class="text-gray-700">${escapeHtml(data.result_desc)}</p>
                </div>
                ` : ''}
            </div>
        `;
        
        modal.classList.add('active');
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
    
    function openDeleteModal(id, phone, daysOld) {
        deleteId = id;
        document.getElementById('deletePhone').textContent = phone;
        document.getElementById('deleteAge').textContent = daysOld;
        document.getElementById('deleteModal').classList.add('active');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
        deleteId = null;
    }
    
    function confirmDelete() {
        if (deleteId) {
            document.getElementById('deleteId').value = deleteId;
            document.getElementById('deleteForm').submit();
        }
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
    
    document.getElementById('deleteModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
</script>

<style>
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
        z-index: 9999;
        padding: 20px;
        animation: fadeIn 0.3s ease;
    }
    
    .modal.active {
        display: flex;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .action-btn {
        padding: 6px 10px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .action-btn.view {
        background: #e0e7ff;
        color: #4f46e5;
    }
    
    .action-btn.view:hover {
        background: #c7d2fe;
    }
    
    .action-btn.delete {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .action-btn.delete:hover {
        background: #fecaca;
    }
    
    .action-btn.delete-disabled {
        padding: 6px 10px;
        border-radius: 8px;
        border: none;
        cursor: not-allowed;
        background: #f3f4f6;
        opacity: 0.5;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
</style>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>