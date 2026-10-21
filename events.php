<?php
require_once 'config.php';
requireLogin();

$page_title = 'Events';
$db = getDB();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: events.php');
        exit();
    }
    
    // Create event
    if (isset($_POST['create_event'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_date = $_POST['event_date'] ?? null;
        $location = trim($_POST['location'] ?? '');
        $event_time = $_POST['event_time'] ?? null;
        
        if (empty($title) || empty($description)) {
            $_SESSION['error'] = 'Title and description are required';
        } else {
            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($_FILES['image']['type'], $allowed)) {
                    $filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $target = UPLOAD_PATH . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                        $image_path = $target;
                    }
                }
            }
            
            $stmt = $db->prepare("INSERT INTO events (title, description, image_path, event_date, location, event_time) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $image_path, $event_date, $location, $event_time]);
            $_SESSION['success'] = 'Event created successfully';
        }
        header('Location: events.php');
        exit();
    }
    
    // Edit event
    if (isset($_POST['edit_event'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_date = $_POST['event_date'] ?? null;
        $location = trim($_POST['location'] ?? '');
        $event_time = $_POST['event_time'] ?? null;
        
        $stmt = $db->prepare("UPDATE events SET title=?, description=?, event_date=?, location=?, event_time=? WHERE id=?");
        $stmt->execute([$title, $description, $event_date, $location, $event_time, $id]);
        $_SESSION['success'] = 'Event updated successfully';
        header('Location: events.php');
        exit();
    }
    
    // Delete event
    if (isset($_POST['delete_item']) && $_POST['type'] === 'event') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("SELECT image_path FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $event = $stmt->fetch();
        if ($event && $event['image_path'] && file_exists($event['image_path'])) {
            unlink($event['image_path']);
        }
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Event deleted successfully';
        header('Location: events.php');
        exit();
    }
}

// Get all events
$events = $db->query("SELECT * FROM events ORDER BY event_date DESC, created_at DESC")->fetchAll();

// Calculate statistics
$totalEvents = count($events);
$today = date('Y-m-d');
$upcomingEvents = 0;
$pastEvents = 0;

foreach ($events as $event) {
    if ($event['event_date'] && $event['event_date'] >= $today) {
        $upcomingEvents++;
    } elseif ($event['event_date']) {
        $pastEvents++;
    }
}

$csrf_token = $_SESSION['csrf_token'];

ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Events Management</h1>
            <p class="text-gray-500 mt-1">Create, manage, and track church events</p>
        </div>
        <button onclick="openCreateModal()" 
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition shadow-sm flex items-center">
            <i class="fas fa-plus mr-2"></i> New Event
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Events</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $totalEvents; ?></h3>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Upcoming Events</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $upcomingEvents; ?></h3>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-calendar-week text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-6 stat-card border-l-4 border-gray-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Past Events</p>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $pastEvents; ?></h3>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-history text-gray-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<?php if (empty($events)): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <i class="fas fa-calendar-times text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700">No Events Yet</h3>
        <p class="text-gray-500 mt-2">Click the "New Event" button to create your first event.</p>
    </div>
<?php else: ?>
    <!-- All Events Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">All Events</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): 
                        $status = '';
                        $statusClass = '';
                        if ($event['event_date']) {
                            if ($event['event_date'] > $today) {
                                $status = 'Upcoming';
                                $statusClass = 'badge-green';
                            } elseif ($event['event_date'] < $today) {
                                $status = 'Past';
                                $statusClass = 'badge-gray';
                            } else {
                                $status = 'Today';
                                $statusClass = 'badge-yellow';
                            }
                        } else {
                            $status = 'TBD';
                            $statusClass = 'badge-gray';
                        }
                    ?>
                        <tr>
                            <td>
                                <div class="flex items-center">
                                    <?php if ($event['image_path']): ?>
                                        <img src="<?php echo $event['image_path']; ?>" class="w-10 h-10 rounded-lg object-cover mr-3">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-calendar-alt text-indigo-500"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($event['title']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo substr(htmlspecialchars($event['description']), 0, 50); ?>...</p>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $event['event_date'] ? date('M d, Y', strtotime($event['event_date'])) : 'TBD'; ?></td>
                            <td><?php echo $event['event_time'] ? date('g:i A', strtotime($event['event_time'])) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($event['location'] ?: 'N/A'); ?></td>
                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                            <td>
                                <div class="flex space-x-2">
                                    <button onclick="editEvent(<?php echo $event['id']; ?>)" class="action-btn view">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="confirmDelete('event', <?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')" class="action-btn delete">
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
    
    <!-- Past Events Section -->
    <?php 
    $pastEventsList = array_filter($events, function($e) use ($today) {
        return $e['event_date'] && $e['event_date'] < $today;
    });
    if (!empty($pastEventsList)): 
    ?>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-clock text-yellow-600 mr-2"></i> Past Events (Ready for cleanup)
                </h3>
                <p class="text-sm text-gray-500 mt-1">These events have already occurred. You may delete them to keep your list clean.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pastEventsList as $event): ?>
                            <tr class="bg-gray-50">
                                <td>
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-calendar-alt text-gray-500"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($event['title']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                <td>
                                    <button onclick="confirmDelete('event', <?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')" class="action-btn delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Create Event Modal -->
<div id="createModal" class="modal">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b">
                <h3 class="text-2xl font-bold text-gray-800">Create New Event</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="create_event" value="1">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Event Title *</label>
                    <input type="text" name="title" required 
                           class="w-full p-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Description *</label>
                    <textarea name="description" rows="4" required 
                              class="w-full p-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Event Date</label>
                        <input type="date" name="event_date" 
                               class="w-full p-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Event Time</label>
                        <input type="time" name="event_time" 
                               class="w-full p-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Location</label>
                        <input type="text" name="location" 
                               class="w-full p-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Event Image</label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full p-2 border border-gray-200 rounded-xl">
                    <p class="text-xs text-gray-400 mt-1">JPEG, PNG, GIF, WebP (max 5MB)</p>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition">
                        Create Event
                    </button>
                    <button type="button" onclick="closeCreateModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-xl transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div id="editModal" class="modal">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b">
                <h3 class="text-2xl font-bold text-gray-800">Edit Event</h3>
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
    
    function editEvent(id) {
        fetch(`get_event.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const formHtml = `
                        <form method="POST" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="edit_event" value="1">
                            <input type="hidden" name="id" value="${data.event.id}">
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Event Title *</label>
                                <input type="text" name="title" required value="${escapeHtml(data.event.title)}"
                                       class="w-full p-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Description *</label>
                                <textarea name="description" rows="4" required 
                                          class="w-full p-3 border border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">${escapeHtml(data.event.description)}</textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Event Date</label>
                                    <input type="date" name="event_date" value="${data.event.event_date || ''}"
                                           class="w-full p-3 border border-gray-200 rounded-xl">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Event Time</label>
                                    <input type="time" name="event_time" value="${data.event.event_time || ''}"
                                           class="w-full p-3 border border-gray-200 rounded-xl">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Location</label>
                                    <input type="text" name="location" value="${escapeHtml(data.event.location || '')}"
                                           class="w-full p-3 border border-gray-200 rounded-xl">
                                </div>
                            </div>
                            
                            <div class="flex space-x-3 pt-4">
                                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition">
                                    Update Event
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
                    alert('Failed to load event data');
                }
            })
            .catch(err => alert('Error loading event'));
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Close modal when clicking outside
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