<?php
require_once 'config.php';
requireLogin();

$page_title = 'Emergencies';
$db = getDB();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: emergencies.php');
        exit();
    }
    
    // Create emergency
    if (isset($_POST['create_emergency'])) {
        $content = trim($_POST['content'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        
        if (empty($content)) {
            $_SESSION['error'] = 'Emergency content is required';
        } else {
            $stmt = $db->prepare("INSERT INTO emergencies (content, priority) VALUES (?, ?)");
            $stmt->execute([$content, $priority]);
            $_SESSION['success'] = 'Emergency alert created successfully';
        }
        header('Location: emergencies.php');
        exit();
    }
    
    // Edit emergency
    if (isset($_POST['edit_emergency'])) {
        $id = (int)$_POST['id'];
        $content = trim($_POST['content'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE emergencies SET content=?, priority=?, is_active=? WHERE id=?");
        $stmt->execute([$content, $priority, $is_active, $id]);
        $_SESSION['success'] = 'Emergency updated successfully';
        header('Location: emergencies.php');
        exit();
    }
    
    // Delete emergency
    if (isset($_POST['delete_item']) && $_POST['type'] === 'emergency') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM emergencies WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Emergency deleted successfully';
        header('Location: emergencies.php');
        exit();
    }
    
    // Toggle emergency status
    if (isset($_POST['toggle_emergency'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE emergencies SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Emergency status updated';
        header('Location: emergencies.php');
        exit();
    }
}

// Get all emergencies
$emergencies = $db->query("SELECT * FROM emergencies ORDER BY created_at DESC")->fetchAll();

// Calculate statistics
$totalEmergencies = count($emergencies);
$activeEmergencies = 0;
$inactiveEmergencies = 0;
$priorityCounts = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];

foreach ($emergencies as $emergency) {
    if ($emergency['is_active']) {
        $activeEmergencies++;
    } else {
        $inactiveEmergencies++;
    }
    $priorityCounts[$emergency['priority']]++;
}

$hasCreateAction = isset($_GET['action']) && $_GET['action'] === 'create';
$csrf_token = $_SESSION['csrf_token'];

ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Emergency Alerts</h1>
            <p class="text-gray-500 mt-1">Create and manage emergency notifications</p>
        </div>
        <a href="?action=create" 
            class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-xl transition shadow-sm flex items-center">
            <i class="fas fa-plus mr-2"></i> New Emergency
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-red-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Active Alerts</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $activeEmergencies; ?></h3>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-bell text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-gray-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Inactive</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $inactiveEmergencies; ?></h3>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-bell-slash text-gray-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">High Priority</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $priorityCounts['high'] + $priorityCounts['critical']; ?></h3>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-exclamation-circle text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $totalEmergencies; ?></h3>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-list text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<?php if ($hasCreateAction): ?>
<!-- Create Emergency Form -->
<div class="bg-white rounded-2xl shadow-sm p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Create New Emergency Alert</h3>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="create_emergency" value="1">
        
        <div>
            <label class="block text-gray-700 font-medium mb-2">Priority Level</label>
            <select name="priority" class="w-full p-3 border border-gray-200 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-200 transition">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2">Alert Content *</label>
            <textarea name="content" rows="6" required 
                      class="w-full p-3 border border-gray-200 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-200 transition"
                      placeholder="Enter emergency alert details here..."></textarea>
        </div>
        
        <div class="flex space-x-3">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-xl transition">
                Send Alert
            </button>
            <a href="emergencies.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-6 rounded-xl transition">
                Cancel
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (empty($emergencies)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <i class="fas fa-shield-alt text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700">No Emergency Alerts</h3>
        <p class="text-gray-500 mt-2">Click the "New Emergency" button to create your first alert.</p>
    </div>
<?php else: ?>
    <!-- Active Emergencies -->
    <?php
    $activeEmergenciesList = array_filter($emergencies, function($e) { return $e['is_active']; });
    if (!empty($activeEmergenciesList)):
    ?>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i> Active Alerts
                </h3>
            </div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($activeEmergenciesList as $emergency): 
                    $priorityColors = [
                        'low' => 'bg-blue-100 text-blue-800',
                        'medium' => 'bg-yellow-100 text-yellow-800',
                        'high' => 'bg-orange-100 text-orange-800',
                        'critical' => 'bg-red-100 text-red-800'
                    ];
                ?>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center space-x-2">
                                <span class="badge <?php echo $priorityColors[$emergency['priority']]; ?>">
                                    <?php echo ucfirst($emergency['priority']); ?> Priority
                                </span>
                                <span class="text-xs text-gray-400">
                                    <?php echo date('M d, Y h:i A', strtotime($emergency['created_at'])); ?>
                                </span>
                            </div>
                            <div class="flex space-x-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="toggle_emergency" value="1">
                                    <input type="hidden" name="id" value="<?php echo $emergency['id']; ?>">
                                    <button type="submit" class="action-btn view" title="Deactivate">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </form>
                                <button onclick="editEmergency(<?php echo $emergency['id']; ?>)" class="action-btn view">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="confirmDelete('emergency', <?php echo $emergency['id']; ?>)" class="action-btn delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($emergency['content'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Inactive Emergencies -->
    <?php
    $inactiveEmergenciesList = array_filter($emergencies, function($e) { return !$e['is_active']; });
    if (!empty($inactiveEmergenciesList)):
    ?>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-archive text-gray-600 mr-2"></i> Inactive/Resolved Alerts
                </h3>
                <p class="text-sm text-gray-500 mt-1">These alerts are no longer active. You can delete them to keep your list clean.</p>
            </div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($inactiveEmergenciesList as $emergency): ?>
                    <div class="p-4 bg-gray-50/50">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="badge bg-gray-200 text-gray-600 text-xs">Resolved</span>
                                <span class="text-xs text-gray-400 ml-2"><?php echo date('M d, Y', strtotime($emergency['created_at'])); ?></span>
                            </div>
                            <button onclick="confirmDelete('emergency', <?php echo $emergency['id']; ?>)" class="action-btn delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <p class="text-gray-600 text-sm mt-2 line-clamp-2"><?php echo htmlspecialchars(substr($emergency['content'], 0, 100)); ?>...</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Edit Emergency Modal -->
<div id="editModal" class="modal">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b">
                <h3 class="text-2xl font-bold text-gray-800">Edit Emergency Alert</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="editFormContainer"></div>
        </div>
    </div>
</div>

<script>
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }
    
    function editEmergency(id) {
        fetch(`get_emergency.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const formHtml = `
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="edit_emergency" value="1">
                            <input type="hidden" name="id" value="${data.emergency.id}">
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Priority Level</label>
                                <select name="priority" class="w-full p-3 border border-gray-200 rounded-xl">
                                    <option value="low" ${data.emergency.priority === 'low' ? 'selected' : ''}>Low</option>
                                    <option value="medium" ${data.emergency.priority === 'medium' ? 'selected' : ''}>Medium</option>
                                    <option value="high" ${data.emergency.priority === 'high' ? 'selected' : ''}>High</option>
                                    <option value="critical" ${data.emergency.priority === 'critical' ? 'selected' : ''}>Critical</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Alert Content *</label>
                                <textarea name="content" rows="6" required 
                                          class="w-full p-3 border border-gray-200 rounded-xl focus:border-red-500 focus:ring-2 focus:ring-red-200">${escapeHtml(data.emergency.content)}</textarea>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" ${data.emergency.is_active ? 'checked' : ''}
                                       class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <label for="is_active" class="ml-2 text-sm text-gray-700">Active (show alert)</label>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-xl transition">
                                    Update Alert
                                </button>
                                <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-xl transition">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    `;
                    document.getElementById('editFormContainer').innerHTML = formHtml;
                    document.getElementById('editModal').classList.add('active');
                } else {
                    alert('Failed to load emergency data');
                }
            });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    document.getElementById('editModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>