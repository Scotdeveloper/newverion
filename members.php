 <?php
// members.php - Members management page with demographic statistics
require_once 'config.php';
requireLogin();

$page_title = 'Members';
$db = getDB();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: members.php');
        exit();
    }
    
    // Delete member
    if (isset($_POST['delete_item']) && $_POST['type'] === 'member') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Member deleted successfully';
        header('Location: members.php');
        exit();
    }
    
    // Mark as read (AJAX)
    if (isset($_POST['ajax_mark_member_read'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE members SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit();
    }
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $db->prepare("UPDATE members SET is_read = 1 WHERE is_read = 0");
    $stmt->execute();
    $_SESSION['success'] = 'All members marked as read';
    header('Location: members.php');
    exit();
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$genderFilter = $_GET['gender'] ?? '';
$ageGroup = $_GET['age_group'] ?? '';

// Build query
$query = "SELECT * FROM members WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR email LIKE ? OR phone LIKE ? OR location LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($status === 'unread') {
    $query .= " AND is_read = 0";
} elseif ($status === 'read') {
    $query .= " AND is_read = 1";
}

if (!empty($genderFilter) && in_array($genderFilter, ['Male', 'Female'])) {
    $query .= " AND gender = ?";
    $params[] = $genderFilter;
}

if (!empty($ageGroup)) {
    switch ($ageGroup) {
        case 'children':
            $query .= " AND age BETWEEN 0 AND 12";
            break;
        case 'teens':
            $query .= " AND age BETWEEN 13 AND 18";
            break;
        case 'youth':
            $query .= " AND age BETWEEN 19 AND 35";
            break;
        case 'adults':
            $query .= " AND age > 35";
            break;
        case 'unknown':
            $query .= " AND age IS NULL";
            break;
    }
}

$query .= " ORDER BY 
            CASE WHEN is_read = 0 THEN 0 ELSE 1 END,
            registered_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Calculate demographic statistics with the correct logic:
// Men/Women are ONLY counted if age > 35
// Youth is counted for ages 19-35 (both Male and Female)

// Total members
$totalMembers = $db->query("SELECT COUNT(*) FROM members")->fetchColumn();
$unreadMembers = $db->query("SELECT COUNT(*) FROM members WHERE is_read = 0")->fetchColumn();
$readMembers = $db->query("SELECT COUNT(*) FROM members WHERE is_read = 1")->fetchColumn();

// Gender statistics - Men and Women ONLY if age > 35
$menCount = $db->query("SELECT COUNT(*) FROM members WHERE gender = 'Male' AND age > 35")->fetchColumn();
$womenCount = $db->query("SELECT COUNT(*) FROM members WHERE gender = 'Female' AND age > 35")->fetchColumn();

// Youth (ages 19-35) - includes both Male and Female, but NOT counted as Men/Women
$youthTotal = $db->query("SELECT COUNT(*) FROM members WHERE age BETWEEN 19 AND 35")->fetchColumn();

// Members with gender specified but age <= 35 (these are youth, not men/women)
$maleYouth = $db->query("SELECT COUNT(*) FROM members WHERE gender = 'Male' AND age BETWEEN 19 AND 35")->fetchColumn();
$femaleYouth = $db->query("SELECT COUNT(*) FROM members WHERE gender = 'Female' AND age BETWEEN 19 AND 35")->fetchColumn();

// Unknown gender (for all ages)
$unknownGender = $db->query("SELECT COUNT(*) FROM members WHERE gender IS NULL OR gender = ''")->fetchColumn();

// Age group statistics
$childrenCount = $db->query("SELECT COUNT(*) FROM members WHERE age BETWEEN 0 AND 12")->fetchColumn();
$teensCount = $db->query("SELECT COUNT(*) FROM members WHERE age BETWEEN 13 AND 18")->fetchColumn();
$adultsCount = $db->query("SELECT COUNT(*) FROM members WHERE age > 35")->fetchColumn();
$unknownAge = $db->query("SELECT COUNT(*) FROM members WHERE age IS NULL")->fetchColumn();

// This month
$thisMonth = $db->query("SELECT COUNT(*) FROM members WHERE registered_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

$csrf_token = $_SESSION['csrf_token'];

ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Members Management</h1>
            <p class="text-gray-500 mt-1">View and manage church members</p>
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

<!-- Main Statistics Row -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-4 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs">Total Members</p>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $totalMembers; ?></h3>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-users text-blue-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs">New (Unread)</p>
                <h3 class="text-2xl font-bold text-yellow-600"><?php echo $unreadMembers; ?></h3>
            </div>
            <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-plus text-yellow-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs">This Month</p>
                <h3 class="text-2xl font-bold text-green-600"><?php echo $thisMonth; ?></h3>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-calendar-alt text-green-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs">Read</p>
                <h3 class="text-2xl font-bold text-purple-600"><?php echo $readMembers; ?></h3>
            </div>
            <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-purple-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Demographic Statistics with Correct Logic -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Gender Distribution - Only counts members above 35 years -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            Gender Distribution
        </h3>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div class="p-3 bg-blue-50 rounded-xl">
                <p class="text-2xl font-bold text-blue-600"><?php echo $menCount; ?></p>
                <p class="text-sm text-gray-600">Men </p>
            </div>
            <div class="p-3 bg-pink-50 rounded-xl">
                <p class="text-2xl font-bold text-pink-600"><?php echo $womenCount; ?></p>
                <p class="text-sm text-gray-600">Women </p>
            </div>
            <div class="p-3 bg-gray-100 rounded-xl">
                <p class="text-2xl font-bold text-gray-600"><?php echo $unknownGender; ?></p>
                <p class="text-sm text-gray-600">Not specified</p>
            </div>
        </div>
    </div>
    
    <!-- Age Group Statistics -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
             Age Demographics
        </h3>
        <div class="grid grid-cols-5 gap-2 text-center">
            <div class="p-2 bg-green-50 rounded-xl">
                <p class="text-xl font-bold text-green-600"><?php echo $childrenCount; ?></p>
                <p class="text-xs text-gray-600">Children</p>
            </div>
            <div class="p-2 bg-blue-50 rounded-xl">
                <p class="text-xl font-bold text-blue-600"><?php echo $teensCount; ?></p>
                <p class="text-xs text-gray-600">Teens</p>
            </div>
            <div class="p-2 bg-purple-50 rounded-xl">
                <p class="text-xl font-bold text-purple-600"><?php echo $youthTotal; ?></p>
                <p class="text-xs text-gray-600">Youth</p>
            </div>
            <div class="p-2 bg-indigo-50 rounded-xl">
                <p class="text-xl font-bold text-indigo-600"><?php echo $adultsCount; ?></p>
                <p class="text-xs text-gray-600">Adults</p>
            </div>
            <div class="p-2 bg-gray-100 rounded-xl">
                <p class="text-xl font-bold text-gray-600"><?php echo $unknownAge; ?></p>
                <p class="text-xs text-gray-600">Unknown</p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter Bar -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-gray-700 text-sm font-medium mb-2">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search by name, email, phone, location..."
                   class="w-full p-2.5 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
        </div>
        <div class="w-28">
            <label class="block text-gray-700 text-sm font-medium mb-2">Status</label>
            <select name="status" class="w-full p-2.5 border border-gray-200 rounded-xl">
                <option value="">All</option>
                <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
            </select>
        </div>
        <div class="w-28">
            <label class="block text-gray-700 text-sm font-medium mb-2">Gender</label>
            <select name="gender" class="w-full p-2.5 border border-gray-200 rounded-xl">
                <option value="">All</option>
                <option value="Male" <?php echo $genderFilter === 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $genderFilter === 'Female' ? 'selected' : ''; ?>>Female</option>
            </select>
        </div>
        <div class="w-32">
            <label class="block text-gray-700 text-sm font-medium mb-2">Age Group</label>
            <select name="age_group" class="w-full p-2.5 border border-gray-200 rounded-xl">
                <option value="">All</option>
                <option value="children" <?php echo $ageGroup === 'children' ? 'selected' : ''; ?>>Children (0-12)</option>
                <option value="teens" <?php echo $ageGroup === 'teens' ? 'selected' : ''; ?>>Teens (13-18)</option>
                <option value="youth" <?php echo $ageGroup === 'youth' ? 'selected' : ''; ?>>Youth (19-35)</option>
                <option value="adults" <?php echo $ageGroup === 'adults' ? 'selected' : ''; ?>>Adults (35+)</option>
                <option value="unknown" <?php echo $ageGroup === 'unknown' ? 'selected' : ''; ?>>Unknown Age</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl transition">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
            <a href="members.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2.5 rounded-xl transition">
                <i class="fas fa-times mr-2"></i> Clear
            </a>
        </div>
    </form>
</div>

<?php if (empty($members)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <i class="fas fa-users-slash text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700">No Members Found</h3>
        <p class="text-gray-500 mt-2">Try adjusting your search or filter criteria.</p>
    </div>
<?php else: ?>
    <!-- Members Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Category</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): 
                        $fullName = $member['full_name'];
                        // Determine member category based on age
                        $memberCategory = '';
                        $categoryColor = '';
                        if ($member['age']) {
                            if ($member['age'] <= 12) {
                                $memberCategory = 'Child';
                                $categoryColor = 'bg-green-100 text-green-800';
                            } elseif ($member['age'] <= 18) {
                                $memberCategory = 'Teen';
                                $categoryColor = 'bg-blue-100 text-blue-800';
                            } elseif ($member['age'] <= 35) {
                                $memberCategory = 'Youth';
                                $categoryColor = 'bg-purple-100 text-purple-800';
                            } else {
                                $memberCategory = $member['gender'] === 'Male' ? 'Man' : ($member['gender'] === 'Female' ? 'Woman' : 'Adult');
                                $categoryColor = 'bg-indigo-100 text-indigo-800';
                            }
                        } else {
                            $memberCategory = 'Unknown';
                            $categoryColor = 'bg-gray-100 text-gray-800';
                        }
                    ?>
                        <tr class="<?php echo !$member['is_read'] ? 'bg-yellow-50/30' : ''; ?>">
                            <td>
                                <div class="flex items-center">
                                    <div>
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($member['full_name']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="text-sm"> <?php echo htmlspecialchars($member['phone']); ?></p>
                             </td>
                            <td>
                                <?php echo htmlspecialchars($member['location']); ?>
                             </td>
                            <td>
                                <?php if ($member['gender']): ?>
                                     <?php echo $member['gender']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">Not specified</span>
                                <?php endif; ?>
                             </td>
                            <td>
                                <?php if ($member['age']): ?>
                                      <?php echo $member['age']; ?>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">—</span>
                                <?php endif; ?>
                             </td>
                            <td>
                                    <?php echo $memberCategory; ?>
                             </td>
                            <td class="whitespace-nowrap">
                                <?php echo date('M d, Y', strtotime($member['registered_at'])); ?>
                             </td>
                            <td>
                                <?php if ($member['is_read']): ?>
                                    <span class="badge badge-read"><i class="fas fa-check-circle mr-1"></i> Read</span>
                                <?php else: ?>
                                    <span class="badge badge-unread"><i class="fas fa-envelope mr-1"></i> New</span>
                                <?php endif; ?>
                             </td>
                            <td>
                                <div class="flex space-x-2">
                                    <button onclick="viewMember(<?php echo htmlspecialchars(json_encode($member)); ?>)" 
                                            class="action-btn view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (!$member['is_read']): ?>
                                        <button onclick="markAsRead(<?php echo $member['id']; ?>)" 
                                                class="action-btn view bg-blue-100 hover:bg-blue-500" title="Mark as Read">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="openDeleteModal('member', <?php echo $member['id']; ?>, '<?php echo htmlspecialchars($fullName); ?>')" 
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

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="type" value="member">
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
                <p class="text-gray-700 mb-3">Are you sure you want to delete this member?</p>
                <div class="bg-gray-50 p-4 rounded-xl">
                    <p><strong>Member:</strong> <span id="deleteMemberName"></span></p>
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
    let deleteType = null;
    
    function viewMember(member) {
        const modal = document.getElementById('viewModal') || createViewModal();
        const data = typeof member === 'string' ? JSON.parse(member) : member;
        
        // Determine category for display
        let category = '';
        if (data.age) {
            if (data.age <= 12) category = 'Child';
            else if (data.age <= 18) category = 'Teen';
            else if (data.age <= 35) category = 'Youth';
            else category = data.gender === 'Male' ? 'Man' : (data.gender === 'Female' ? 'Woman' : 'Adult');
        } else {
            category = 'Unknown';
        }
        
        document.getElementById('viewTitle').innerHTML = `
            <i class="fas fa-address-card text-indigo-600 mr-2"></i> Member: ${escapeHtml(data.first_name)}
        `;
        
        document.getElementById('viewContent').innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-xl">
                    <div><span class="text-gray-500 text-sm">Full Name:</span><br><span class="font-semibold">${escapeHtml(data.full_name)}</span></div>
                    <div><span class="text-gray-500 text-sm">Member Category:</span><br><span class="font-semibold">${category}</span></div>
                    <div><span class="text-gray-500 text-sm">Phone:</span><br><span class="font-semibold">${escapeHtml(data.phone)}</span></div>
                    <div><span class="text-gray-500 text-sm">Location:</span><br><span class="font-semibold">${escapeHtml(data.location)}</span></div>
                    <div><span class="text-gray-500 text-sm">Email:</span><br><span class="font-semibold">${escapeHtml(data.email || 'Not provided')}</span></div>
                    <div><span class="text-gray-500 text-sm">Gender:</span><br><span class="font-semibold">${escapeHtml(data.gender || 'Not specified')}</span></div>
                    <div><span class="text-gray-500 text-sm">Age:</span><br><span class="font-semibold">${data.age || 'Not specified'}</span></div>
                    <div><span class="text-gray-500 text-sm">Registered:</span><br><span class="font-semibold">${new Date(data.registered_at).toLocaleString()}</span></div>
                </div>
            </div>
        `;
        
        modal.classList.add('active');
        
        // Mark as read if unread
        if (!data.is_read) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax_mark_member_read=1&id=${data.id}&csrf_token=<?php echo $csrf_token; ?>`
            }).then(() => {
                location.reload();
            });
        }
    }
    
    function markAsRead(id) {
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `ajax_mark_member_read=1&id=${id}&csrf_token=<?php echo $csrf_token; ?>`
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
    
    function openDeleteModal(type, id, name) {
        deleteId = id;
        deleteType = type;
        document.getElementById('deleteMemberName').textContent = name;
        document.getElementById('deleteModal').classList.add('active');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
        deleteId = null;
        deleteType = null;
    }
    
    function confirmDelete() {
        if (deleteId && deleteType) {
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
    
    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }
    
    .badge-read {
        background: #e0e7ff;
        color: #4f46e5;
    }
    
    .badge-unread {
        background: #fef3c7;
        color: #d97706;
    }
</style>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>