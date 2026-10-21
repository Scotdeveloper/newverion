<?php
require_once 'config.php';
requireLogin();

$page_title = 'Messages';
$db = getDB();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: messages.php');
        exit();
    }
    
    // Create message
    if (isset($_POST['create_message'])) {
        $content = trim($_POST['content'] ?? '');
        $category = $_POST['category'] ?? 'general';
        
        if (empty($content)) {
            $_SESSION['error'] = 'Message content is required';
        } else {
            $stmt = $db->prepare("INSERT INTO messages (content, category) VALUES (?, ?)");
            $stmt->execute([$content, $category]);
            $_SESSION['success'] = 'Message created successfully';
        }
        header('Location: messages.php');
        exit();
    }
    
    // Edit message
    if (isset($_POST['edit_message'])) {
        $id = (int)$_POST['id'];
        $content = trim($_POST['content'] ?? '');
        $category = $_POST['category'] ?? 'general';
        
        $stmt = $db->prepare("UPDATE messages SET content=?, category=? WHERE id=?");
        $stmt->execute([$content, $category, $id]);
        $_SESSION['success'] = 'Message updated successfully';
        header('Location: messages.php');
        exit();
    }
    
    // Delete message
    if (isset($_POST['delete_item']) && $_POST['type'] === 'message') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Message deleted successfully';
        header('Location: messages.php');
        exit();
    }
}

// Get all messages
$messages = $db->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll();

// Calculate statistics
$totalMessages = count($messages);
$weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
$recentMessages = 0;
foreach ($messages as $msg) {
    if ($msg['created_at'] >= $weekAgo) $recentMessages++;
}

$csrf_token = $_SESSION['csrf_token'];
$hasCreateAction = isset($_GET['action']) && $_GET['action'] === 'create';

ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Messages Management</h1>
            <p class="text-gray-500 mt-1">Create and manage church announcements</p>
        </div>
        <button onclick="openCreateModal()" 
            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-xl transition shadow-sm flex items-center">
            <i class="fas fa-plus mr-2"></i> New Message
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Messages</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $totalMessages; ?></h3>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-envelope text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Last 7 Days</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $recentMessages; ?></h3>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-calendar-week text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<?php if ($hasCreateAction): ?>
<!-- Create Message Form -->
<div class="bg-white rounded-2xl shadow-sm p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Create New Message</h3>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="create_message" value="1">
        
        <div>
            <label class="block text-gray-700 font-medium mb-2">Category</label>
            <select name="category" class="w-full p-3 border border-gray-200 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200 transition">
                <option value="general">General</option>
                <option value="announcement">Announcement</option>
                <option value="update">Update</option>
                <option value="reminder">Reminder</option>
            </select>
        </div>
        
        <div>
            <label class="block text-gray-700 font-medium mb-2">Message Content *</label>
            <textarea name="content" rows="6" required 
                      class="w-full p-3 border border-gray-200 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200 transition"
                      placeholder="Enter your message here..."></textarea>
        </div>
        
        <div class="flex space-x-3">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-xl transition">
                Publish Message
            </button>
            <a href="messages.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-6 rounded-xl transition">
                Cancel
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (empty($messages)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <i class="fas fa-comment-slash text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700">No Messages Yet</h3>
        <p class="text-gray-500 mt-2">Click the "New Message" button to create your first message.</p>
    </div>
<?php else: ?>
    <!-- All Messages -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">All Messages</h3>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($messages as $message): ?>
                <div class="p-6 hover:bg-gray-50 transition">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center space-x-2">
                            <span class="badge bg-gray-100 text-gray-800">
                                <?php echo ucfirst($message['category']); ?>
                            </span>
                            <span class="text-xs text-gray-400">
                                <?php echo date('M d, Y h:i A', strtotime($message['created_at'])); ?>
                            </span>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="editMessage(<?php echo $message['id']; ?>)" class="action-btn view">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmDelete('message', <?php echo $message['id']; ?>)" class="action-btn delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <p class="text-gray-700 mt-2"><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Old Messages Section (older than 30 days) -->
    <?php
    $monthAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
    $oldMessages = array_filter($messages, function($m) use ($monthAgo) {
        return $m['created_at'] < $monthAgo;
    });
    if (!empty($oldMessages)):
    ?>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-archive text-yellow-600 mr-2"></i> Old Messages (30+ days)
                </h3>
                <p class="text-sm text-gray-500 mt-1">These messages are older than 30 days. Consider deleting them to keep your list organized.</p>
            </div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($oldMessages as $message): ?>
                    <div class="p-4 bg-yellow-50/30">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="badge bg-gray-100 text-gray-800 text-xs"><?php echo ucfirst($message['category']); ?></span>
                                <span class="text-xs text-gray-400 ml-2"><?php echo date('M d, Y', strtotime($message['created_at'])); ?></span>
                            </div>
                            <button onclick="confirmDelete('message', <?php echo $message['id']; ?>)" class="action-btn delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <p class="text-gray-600 text-sm mt-2 line-clamp-2"><?php echo htmlspecialchars(substr($message['content'], 0, 100)); ?>...</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Create Message Modal -->
<div id="createModal" class="modal">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b">
                <h3 class="text-2xl font-bold text-gray-800">Create New Message</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="create_message" value="1">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Category</label>
                    <select name="category" class="w-full p-3 border border-gray-200 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200">
                        <option value="general">General</option>
                        <option value="announcement">Announcement</option>
                        <option value="update">Update</option>
                        <option value="reminder">Reminder</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Message Content *</label>
                    <textarea name="content" rows="6" required 
                              class="w-full p-3 border border-gray-200 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200"
                              placeholder="Enter your message here..."></textarea>
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl transition">
                        Publish Message
                    </button>
                    <button type="button" onclick="closeCreateModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-xl transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Message Modal -->
<div id="editModal" class="modal">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b">
                <h3 class="text-2xl font-bold text-gray-800">Edit Message</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="editFormContainer"></div>
        </div>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.add('active');
    }
    
    function closeCreateModal() {
        document.getElementById('createModal').classList.remove('active');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }
    
    function editMessage(id) {
        fetch(`get_message.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const formHtml = `
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="edit_message" value="1">
                            <input type="hidden" name="id" value="${data.message.id}">
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Category</label>
                                <select name="category" class="w-full p-3 border border-gray-200 rounded-xl">
                                    <option value="general" ${data.message.category === 'general' ? 'selected' : ''}>General</option>
                                    <option value="announcement" ${data.message.category === 'announcement' ? 'selected' : ''}>Announcement</option>
                                    <option value="update" ${data.message.category === 'update' ? 'selected' : ''}>Update</option>
                                    <option value="reminder" ${data.message.category === 'reminder' ? 'selected' : ''}>Reminder</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Message Content *</label>
                                <textarea name="content" rows="6" required 
                                          class="w-full p-3 border border-gray-200 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200">${escapeHtml(data.message.content)}</textarea>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl transition">
                                    Update Message
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
                    alert('Failed to load message data');
                }
            });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    document.getElementById('createModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
    document.getElementById('editModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>