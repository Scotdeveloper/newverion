 <?php
// dinner.php - Heroes' Dinner Admin Management (Adults + Children)
require_once 'database.php';

// Security Headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// ============================================================
// PAGE CONFIG
// ============================================================
define('PAGE_SIZE', 50);
define('CURRENT_EVENT', 'heroes_dinner_2026');

$db = Database::getConnection();

// ============================================================
// SAFE HELPERS
// ============================================================
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function jsonOut($data, $code = 200) {
    // Clean any accidental output so JSON is pure
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function currentTab() {
    $t = $_GET['tab'] ?? 'adults';
    return in_array($t, ['adults','children'], true) ? $t : 'adults';
}

// Allowed statuses (whitelist — never concatenate into SQL)
$ALLOWED_STATUSES = ['unread','read','followed_up'];

// ============================================================
// AJAX ENDPOINTS (must run BEFORE any HTML output)
// ============================================================
/* ===== FIX: moved here from bottom of file ===== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'children_of_guardian') {
    // Don't strictly require isAjax() — some setups strip the header.
    // Just respond with JSON and exit.
    try {
        $gid = (int)($_GET['guardian_id'] ?? 0);
        if ($gid <= 0) {
            jsonOut(['success' => false, 'message' => 'Invalid guardian.'], 400);
        }

        $stmt = $db->prepare("SELECT id, full_name, gender, date_of_birth, status, created_at
                              FROM children
                              WHERE guardian_id = ? AND event_id = ?
                              ORDER BY
                                CASE status
                                    WHEN 'unread' THEN 0
                                    WHEN 'read' THEN 1
                                    WHEN 'followed_up' THEN 2
                                    ELSE 3
                                END,
                                full_name");
        $stmt->execute([$gid, CURRENT_EVENT]);
        $kids = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonOut(['success' => true, 'children' => $kids]);

    } catch (Throwable $e) {
        error_log("children_of_guardian AJAX error: " . $e->getMessage());
        jsonOut(['success' => false, 'message' => 'Failed to load children.'], 500);
    }
}
/* ===== end FIX ===== */

// ============================================================
// POST HANDLERS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------- Adult: mark followed up (one-way) ----------
    if (isset($_POST['mark_followed_up'], $_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE heroes_dinner_registrations
                              SET status='followed_up', updated_at=NOW()
                              WHERE id=? AND status<>'followed_up'");
        $stmt->execute([$id]);
        $_SESSION['success'] = $stmt->rowCount() > 0
            ? 'Registration marked as followed up successfully.'
            : 'This registration is already marked as followed up.';
        header('Location: dinner.php?tab=adults');
        exit;
    }

    // ---------- Child: mark followed up (one-way) ----------
    if (isset($_POST['mark_child_followed_up'], $_POST['child_id'])) {
        $cid = (int)$_POST['child_id'];
        $stmt = $db->prepare("UPDATE children
                              SET status='followed_up', updated_at=NOW()
                              WHERE id=? AND status<>'followed_up'");
        $stmt->execute([$cid]);
        $_SESSION['success'] = $stmt->rowCount() > 0
            ? 'Child marked as followed up successfully.'
            : 'This child is already marked as followed up.';
        header('Location: dinner.php?tab=children');
        exit;
    }

    // ---------- Adult: mark as read (AJAX) ----------
    if (isset($_POST['mark_as_read'], $_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE heroes_dinner_registrations
                              SET status='read', updated_at=NOW()
                              WHERE id=? AND status='unread'");
        $stmt->execute([$id]);
        if (isAjax()) jsonOut(['success' => true]);
        $_SESSION['success'] = 'Registration marked as read.';
        header('Location: dinner.php?tab=adults');
        exit;
    }

    // ---------- Child: mark as read (AJAX) ----------
    if (isset($_POST['mark_child_as_read'], $_POST['child_id'])) {
        $cid = (int)$_POST['child_id'];
        $stmt = $db->prepare("UPDATE children
                              SET status='read', updated_at=NOW()
                              WHERE id=? AND status='unread'");
        $stmt->execute([$cid]);
        if (isAjax()) jsonOut(['success' => true]);
        $_SESSION['success'] = 'Child marked as read.';
        header('Location: dinner.php?tab=children');
        exit;
    }

    // ---------- Delete adult (cascades to children via FK) ----------
    if (isset($_POST['delete_registration'], $_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM heroes_dinner_registrations WHERE id=?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Registration deleted successfully.';
        header('Location: dinner.php?tab=adults');
        exit;
    }

    // ---------- Delete child ----------
    if (isset($_POST['delete_child'], $_POST['child_id'])) {
        $cid = (int)$_POST['child_id'];
        $stmt = $db->prepare("DELETE FROM children WHERE id=?");
        $stmt->execute([$cid]);
        $_SESSION['success'] = 'Child deleted successfully.';
        header('Location: dinner.php?tab=children');
        exit;
    }

    // ---------- Export CSV (adults) ----------
    if (isset($_POST['export_csv'])) {
        $rows = $db->query("SELECT id, full_name, gender, phone, location, status,
                                   registered_at
                            FROM heroes_dinner_registrations
                            ORDER BY registered_at DESC")->fetchAll();
        if (!$rows) {
            $_SESSION['error'] = 'No registrations to export.';
            header('Location: dinner.php?tab=adults');
            exit;
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="adults_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Full Name','Gender','Phone','Location','Status','Registered At']);
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }

    // ---------- Export CSV (children, joined with guardian) ----------
    if (isset($_POST['export_children_csv'])) {
        $sql = "SELECT c.id, c.full_name AS child_name, c.gender, c.date_of_birth,
                       c.status AS child_status,
                       g.full_name AS guardian_name, g.phone AS guardian_phone,
                       g.location AS guardian_location, c.created_at
                FROM children c
                INNER JOIN heroes_dinner_registrations g ON g.id = c.guardian_id
                WHERE c.event_id = ?
                ORDER BY g.full_name, c.full_name";
        $stmt = $db->prepare($sql);
        $stmt->execute([CURRENT_EVENT]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            $_SESSION['error'] = 'No children to export.';
            header('Location: dinner.php?tab=children');
            exit;
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="children_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Child ID','Child Name','Gender','DOB','Child Status',
                       'Guardian Name','Guardian Phone','Guardian Location','Registered At']);
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }
}

// ============================================================
// FILTER / SEARCH / PAGINATION STATE
// ============================================================
$tab         = currentTab();
$search      = trim($_GET['search'] ?? '');
$statusFilt  = $_GET['status'] ?? '';
if (!in_array($statusFilt, $ALLOWED_STATUSES, true)) $statusFilt = '';

$page        = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($page - 1) * PAGE_SIZE;

// ============================================================
// STATS (adults + children)
// ============================================================
$stats = [
    'total'        => (int)$db->query("SELECT COUNT(*) FROM heroes_dinner_registrations")->fetchColumn(),
    'this_week'    => (int)$db->query("SELECT COUNT(*) FROM heroes_dinner_registrations
                                       WHERE WEEK(registered_at)=WEEK(CURDATE())")->fetchColumn(),
    'this_month'   => (int)$db->query("SELECT COUNT(*) FROM heroes_dinner_registrations
                                       WHERE MONTH(registered_at)=MONTH(CURDATE())
                                         AND YEAR(registered_at)=YEAR(CURDATE())")->fetchColumn(),
    'men'          => (int)$db->query("SELECT COUNT(*) FROM heroes_dinner_registrations WHERE gender='Male'")->fetchColumn(),
    'women'        => (int)$db->query("SELECT COUNT(*) FROM heroes_dinner_registrations WHERE gender='Female'")->fetchColumn(),
    'unread'       => (int)$db->query("SELECT COUNT(*) FROM heroes_dinner_registrations WHERE status='unread'")->fetchColumn(),
    'children_total' => (int)$db->query("SELECT COUNT(*) FROM children WHERE event_id='" . CURRENT_EVENT . "'")->fetchColumn(),
];

$totalAdults  = $stats['total'];
$totalChildren = $stats['children_total'];

// ============================================================
// ADULTS DATA (paginated)
// ============================================================
$adults       = [];
$adultsTotal  = 0;
$adultsPages  = 1;
$childCounts  = [];

if ($tab === 'adults') {
    $where  = "WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $where .= " AND (full_name LIKE ? OR phone LIKE ? OR location LIKE ?)";
        $term   = "%$search%";
        $params[] = $term; $params[] = $term; $params[] = $term;
    }
    if ($statusFilt !== '') {
        $where .= " AND status = ?";
        $params[] = $statusFilt;
    }

    $cntSql = "SELECT COUNT(*) FROM heroes_dinner_registrations $where";
    $cntStmt = $db->prepare($cntSql);
    $cntStmt->execute($params);
    $adultsTotal = (int)$cntStmt->fetchColumn();
    $adultsPages = max(1, (int)ceil($adultsTotal / PAGE_SIZE));
    if ($page > $adultsPages) { $page = $adultsPages; $offset = ($page-1)*PAGE_SIZE; }

    $dataSql = "SELECT id, full_name, gender, phone, location, status, registered_at
                FROM heroes_dinner_registrations
                $where
                ORDER BY
                    CASE status
                        WHEN 'unread' THEN 0
                        WHEN 'read' THEN 1
                        WHEN 'followed_up' THEN 2
                        ELSE 3
                    END,
                    registered_at DESC
                LIMIT " . (int)PAGE_SIZE . " OFFSET " . (int)$offset;

    $dataStmt = $db->prepare($dataSql);
    $dataStmt->execute($params);
    $adults = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($adults) {
        $ids = array_column($adults, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $ccStmt = $db->prepare("SELECT guardian_id, COUNT(*) AS cnt
                                FROM children
                                WHERE guardian_id IN ($placeholders)
                                GROUP BY guardian_id");
        $ccStmt->execute($ids);
        foreach ($ccStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $childCounts[(int)$r['guardian_id']] = (int)$r['cnt'];
        }
    }
}

// ============================================================
// CHILDREN DATA (paginated, joined with guardian)
// ============================================================
$children      = [];
$childrenTotal = 0;
$childrenPages = 1;

if ($tab === 'children') {
    $where  = "WHERE c.event_id = ?";
    $params = [CURRENT_EVENT];

    if ($search !== '') {
        $where .= " AND (c.full_name LIKE ? OR g.full_name LIKE ? OR g.phone LIKE ?)";
        $term   = "%$search%";
        $params[] = $term; $params[] = $term; $params[] = $term;
    }
    if ($statusFilt !== '') {
        $where .= " AND c.status = ?";
        $params[] = $statusFilt;
    }

    $cntSql = "SELECT COUNT(*)
               FROM children c
               INNER JOIN heroes_dinner_registrations g ON g.id = c.guardian_id
               $where";
    $cntStmt = $db->prepare($cntSql);
    $cntStmt->execute($params);
    $childrenTotal = (int)$cntStmt->fetchColumn();
    $childrenPages = max(1, (int)ceil($childrenTotal / PAGE_SIZE));
    if ($page > $childrenPages) { $page = $childrenPages; $offset = ($page-1)*PAGE_SIZE; }

    $dataSql = "SELECT c.id, c.full_name, c.gender, c.date_of_birth, c.status,
                       c.created_at,
                       g.id AS guardian_id, g.full_name AS guardian_name,
                       g.phone AS guardian_phone, g.location AS guardian_location,
                       g.status AS guardian_status
                FROM children c
                INNER JOIN heroes_dinner_registrations g ON g.id = c.guardian_id
                $where
                ORDER BY
                    CASE c.status
                        WHEN 'unread' THEN 0
                        WHEN 'read' THEN 1
                        WHEN 'followed_up' THEN 2
                        ELSE 3
                    END,
                    c.created_at DESC
                LIMIT " . (int)PAGE_SIZE . " OFFSET " . (int)$offset;

    $dataStmt = $db->prepare($dataSql);
    $dataStmt->execute($params);
    $children = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================================
// HELPER: build query string preserving state
// ============================================================
function qs(array $overrides = []) {
    $base = [
        'tab'    => $_GET['tab'] ?? 'adults',
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? '',
        'page'   => $_GET['page'] ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return '?' . http_build_query(array_filter($merged, fn($v) => $v !== '' && $v !== null));
}

$totalRecords = $tab === 'children' ? $totalChildren : $totalAdults;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heroes' Dinner — <?php echo defined('SITE_NAME') ? h(SITE_NAME) : 'EastGate Assembly'; ?></title>
    <link rel="icon" type="image/x-icon" href="favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .sidebar { transition: all 0.3s ease; position: fixed; top: 0; left: 0; height: 100vh; overflow-y: auto; z-index: 40; background: linear-gradient(180deg, #1e1b4b 0%, #2e1065 100%); width: 280px; }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
        .main-content { margin-left: 280px; width: calc(100% - 280px); min-height: 100vh; background: #f8fafc; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; width: 100%; } }
        .nav-link { transition: all 0.2s ease; border-radius: 12px; display: flex; align-items: center; padding: 12px 16px; color: rgba(255,255,255,0.7); text-decoration: none; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { background: rgba(255,255,255,0.15); color: white; border-left: 3px solid #818cf8; }
        .stat-card { transition: all 0.3s ease; border-radius: 20px; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -12px rgba(0,0,0,0.1); }
        .stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-icon.green { background: #d1fae5; color: #16a34a; }
        .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
        .stat-icon.pink { background: #fce7f3; color: #db2777; }
        .stat-icon.orange { background: #fed7aa; color: #ea580c; }
        .stat-icon.yellow { background: #fef3c7; color: #d97706; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 16px; max-width: 90%; max-height: 90vh; overflow-y: auto; }
        .data-table { border-collapse: separate; border-spacing: 0; width: 100%; }
        .data-table th { padding: 16px; text-align: left; font-weight: 600; background: #f1f5f9; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table tr:hover td { background: #f8fafc; }
        .action-btn { transition: all 0.2s ease; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: none; cursor: pointer; }
        .action-btn.view { background: #e0e7ff; color: #4f46e5; }
        .action-btn.view:hover { background: #4f46e5; color: white; transform: scale(1.05); }
        .action-btn.delete { background: #fee2e2; color: #dc2626; }
        .action-btn.delete:hover { background: #dc2626; color: white; transform: scale(1.05); }
        .action-btn.follow-up { background: #d1fae5; color: #065f46; }
        .action-btn.follow-up:hover:not(:disabled) { background: #065f46; color: white; transform: scale(1.05); }
        .action-btn.follow-up:disabled { opacity: 0.6; cursor: not-allowed; }
        .action-btn.children { background: #fef3c7; color: #b45309; }
        .action-btn.children:hover { background: #b45309; color: white; transform: scale(1.05); }
        .action-btn.followed-up-locked { background: #d1fae5; color: #065f46; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 16px; cursor: default; }
        .badge { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; }
        .status-unread { background: #fee2e2; color: #991b1b; }
        .status-read { background: #dbeafe; color: #1e40af; }
        .status-followed_up { background: #d1fae5; color: #065f46; }
        .gender-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        @media (max-width: 640px) { .gender-stats { grid-template-columns: 1fr; } }
        .action-buttons { display: flex; flex-direction: column; gap: 6px; align-items: center; }
        .action-icons { display: flex; gap: 4px; }
        #deleteModal { z-index: 1001; } #followUpModal { z-index: 1002; } #detailsModal { z-index: 1000; }
        .follow-up-icon { width: 56px; height: 56px; background: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #065f46; margin: 0 auto 16px; }
        .tab-btn { padding: 10px 18px; border-radius: 12px; font-weight: 600; font-size: 14px; transition: all 0.2s; }
        .tab-btn.active { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; box-shadow: 0 6px 20px -6px rgba(79,70,229,0.6); }
        .tab-btn:not(.active) { background: #eef2ff; color: #4338ca; }
        .tab-btn:not(.active):hover { background: #e0e7ff; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 12px; border-radius: 10px; font-size: 14px; font-weight: 600; }
        .pagination a { background: #f1f5f9; color: #334155; text-decoration: none; }
        .pagination a:hover { background: #e2e8f0; }
        .pagination .current { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; }
        .pagination .disabled { background: #f8fafc; color: #cbd5e1; pointer-events: none; }
        .child-guardian-line { font-size: 12px; color: #64748b; margin-top: 4px; }
        .guardian-pill { display: inline-flex; align-items: center; gap: 4px; background: #fff7ed; color: #c2410c; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-6">
            <div class="flex items-center space-x-3 mb-8 pb-4 border-b border-white/10">
                <div class="w-10 h-10 bg-gradient-to-r from-indigo-400 to-purple-400 rounded-xl flex items-center justify-center">
                    <i class="fas fa-church text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">EastGate Assembly</h2>
                    <p class="text-xs text-white/60">Admin Panel</p>
                </div>
            </div>
            <nav class="space-y-1">
                <a href="index.php" class="nav-link"><i class="fas fa-tachometer-alt w-5 mr-3"></i> Dashboard</a>
                <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt w-5 mr-3"></i> Events</a>
                <a href="messages.php" class="nav-link"><i class="fas fa-comments w-5 mr-3"></i> Messages</a>
                <a href="emergencies.php" class="nav-link"><i class="fas fa-exclamation-triangle w-5 mr-3"></i> Emergencies</a>
                <a href="contact_requests.php" class="nav-link"><i class="fas fa-envelope w-5 mr-3"></i> Contact Requests</a>
                <a href="connect_requests.php" class="nav-link"><i class="fas fa-users w-5 mr-3"></i> Connect Groups</a>
                <a href="members.php" class="nav-link"><i class="fas fa-address-card w-5 mr-3"></i> Members</a>
                <a href="dinner.php" class="nav-link active">
                    <i class="fas fa-utensils w-5 mr-3"></i> Heroes' Dinner
                    <?php if ($stats['unread'] > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1 animate-pulse"><?php echo $stats['unread']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="offerings.php" class="nav-link"><i class="fas fa-hand-holding-usd w-5 mr-3"></i> Offerings</a>
                <a href="profile.php" class="nav-link"><i class="fas fa-user-circle w-5 mr-3"></i> My Profile</a>
                <a href="logout.php" class="nav-link text-red-300 hover:text-red-200 mt-4 border-t border-white/10 pt-4"><i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout</a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="p-6 md:p-8">
            <div class="max-w-7xl mx-auto">

                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-xl">
                        <div class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <p class="text-green-700"><?php echo h($_SESSION['success']); unset($_SESSION['success']); ?></p></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-xl">
                        <div class="flex items-center"><i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <p class="text-red-700"><?php echo h($_SESSION['error']); unset($_SESSION['error']); ?></p></div>
                    </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Heroes' Dinner Management</h1>
                        <p class="text-gray-600">Manage adult/guardian registrations and their children</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button onclick="window.location.reload()" class="bg-blue-50 text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-100 transition flex items-center text-sm">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh
                        </button>
                        <form method="POST" class="inline-block">
                            <?php if ($tab === 'children'): ?>
                                <button type="submit" name="export_children_csv" class="bg-purple-50 text-purple-600 px-4 py-2 rounded-lg hover:bg-purple-100 transition flex items-center text-sm">
                                    <i class="fas fa-file-export mr-2"></i> Export Children CSV
                                </button>
                            <?php else: ?>
                                <button type="submit" name="export_csv" class="bg-purple-50 text-purple-600 px-4 py-2 rounded-lg hover:bg-purple-100 transition flex items-center text-sm">
                                    <i class="fas fa-file-export mr-2"></i> Export CSV
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-2 mb-6">
                    <a href="dinner.php?tab=adults" class="tab-btn <?php echo $tab === 'adults' ? 'active' : ''; ?>">
                        <i class="fas fa-user-friends mr-2"></i> Adults / Guardians
                        <span class="ml-2 text-xs opacity-80">(<?php echo number_format($totalAdults); ?>)</span>
                    </a>
                    <a href="dinner.php?tab=children" class="tab-btn <?php echo $tab === 'children' ? 'active' : ''; ?>">
                        <i class="fas fa-child mr-2"></i> Children
                        <span class="ml-2 text-xs opacity-80">(<?php echo number_format($totalChildren); ?>)</span>
                    </a>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
                    <div class="stat-card bg-white rounded-2xl shadow p-5 flex items-center gap-4 border-l-4 border-blue-500">
                        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                        <div><p class="text-xs text-gray-500 font-medium">Total Adults</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total']); ?></h3></div>
                    </div>
                    <div class="stat-card bg-white rounded-2xl shadow p-5 flex items-center gap-4 border-l-4 border-green-500">
                        <div class="stat-icon green"><i class="fas fa-calendar-week"></i></div>
                        <div><p class="text-xs text-gray-500 font-medium">This Week</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['this_week']); ?></h3></div>
                    </div>
                    <div class="stat-card bg-white rounded-2xl shadow p-5 flex items-center gap-4 border-l-4 border-purple-500">
                        <div class="stat-icon purple"><i class="fas fa-child"></i></div>
                        <div><p class="text-xs text-gray-500 font-medium">Total Children</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['children_total']); ?></h3></div>
                    </div>
                    <div class="stat-card bg-white rounded-2xl shadow p-5 flex items-center gap-4 border-l-4 border-red-500">
                        <div class="stat-icon yellow"><i class="fas fa-envelope"></i></div>
                        <div><p class="text-xs text-gray-500 font-medium">Unread Adults</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['unread']); ?></h3></div>
                    </div>
                </div>

                <!-- Gender Statistics -->
                <div class="gender-stats mb-6">
                    <div class="stat-card bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow p-4 border border-blue-100">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon blue" style="width:36px;height:36px;font-size:18px;"><i class="fas fa-male"></i></div>
                            <div><p class="text-xs text-gray-500 font-medium">Men</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['men']); ?></h3>
                            <p class="text-xs text-gray-400"><?php echo $stats['total'] ? round(($stats['men']/$stats['total'])*100).'%' : '0%'; ?></p></div>
                        </div>
                    </div>
                    <div class="stat-card bg-gradient-to-br from-pink-50 to-rose-50 rounded-2xl shadow p-4 border border-pink-100">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon pink" style="width:36px;height:36px;font-size:18px;"><i class="fas fa-female"></i></div>
                            <div><p class="text-xs text-gray-500 font-medium">Women</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['women']); ?></h3>
                            <p class="text-xs text-gray-400"><?php echo $stats['total'] ? round(($stats['women']/$stats['total'])*100).'%' : '0%'; ?></p></div>
                        </div>
                    </div>
                </div>

                <!-- Search + Filter -->
                <div class="bg-white rounded-xl shadow-lg mb-6 p-4">
                    <form method="GET" class="flex flex-wrap gap-4 items-end">
                        <input type="hidden" name="tab" value="<?php echo h($tab); ?>">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                <?php echo $tab === 'children' ? 'Search (child, guardian, phone)' : 'Search'; ?>
                            </label>
                            <input type="text" name="search" value="<?php echo h($search); ?>"
                                   placeholder="<?php echo $tab === 'children' ? 'Child name, guardian name, or phone...' : 'Name, phone, location...'; ?>"
                                   class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="w-40">
                            <label class="block text-gray-700 text-sm font-medium mb-2">Status</label>
                            <select name="status" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="unread" <?php echo $statusFilt === 'unread' ? 'selected' : ''; ?>>Unread</option>
                                <option value="read" <?php echo $statusFilt === 'read' ? 'selected' : ''; ?>>Read</option>
                                <option value="followed_up" <?php echo $statusFilt === 'followed_up' ? 'selected' : ''; ?>>Followed Up</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition">
                                <i class="fas fa-filter mr-2"></i> Filter
                            </button>
                            <a href="dinner.php?tab=<?php echo h($tab); ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg transition">
                                <i class="fas fa-times mr-2"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- ============================== ADULTS TAB ============================== -->
                <?php if ($tab === 'adults'): ?>
                    <?php if (empty($adults)): ?>
                        <div class="bg-white rounded-2xl shadow-lg p-16 text-center">
                            <i class="fas fa-utensils text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-2xl font-semibold text-gray-700">No Registrations Found</h3>
                            <p class="text-gray-500 mt-2">No adults/guardians match your filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Full Name</th>
                                            <th>Gender</th>
                                            <th>Phone</th>
                                            <th>Location</th>
                                            <th>Children</th>
                                            <th>Status</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($adults as $loopIndex => $reg):
                                        $statusClass = 'status-' . $reg['status'];
                                        $statusText  = ucfirst(str_replace('_', ' ', $reg['status']));
                                        $isFollowed  = $reg['status'] === 'followed_up';
                                        $cc          = $childCounts[(int)$reg['id']] ?? 0;
                                    ?>
                                        <tr id="row-<?php echo (int)$reg['id']; ?>">
                                            <td class="text-sm text-gray-500"><?php echo number_format($offset + $loopIndex + 1); ?></td>
                                            <td><div class="font-medium text-gray-900"><?php echo h($reg['full_name']); ?></div></td>
                                            <td class="text-sm text-gray-500"><?php echo h($reg['gender']); ?></td>
                                            <td class="text-sm"><?php echo h($reg['phone']); ?></td>
                                            <td class="text-sm text-gray-500"><?php echo h($reg['location']); ?></td>
                                            <td>
                                                <?php if ($cc > 0): ?>
                                                    <button onclick='openChildrenModal(<?php echo (int)$reg["id"]; ?>, <?php echo json_encode($reg["full_name"], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                                                            class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-semibold hover:bg-amber-200 transition">
                                                        <i class="fas fa-child"></i> <?php echo $cc; ?> child<?php echo $cc > 1 ? 'ren' : ''; ?>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-xs text-gray-400">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                            <td class="text-sm text-gray-500">
                                                <?php echo date('M d, Y', strtotime($reg['registered_at'])); ?>
                                                <div class="text-xs text-gray-400"><?php echo date('h:i A', strtotime($reg['registered_at'])); ?></div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <div id="followup-container-<?php echo (int)$reg['id']; ?>">
                                                        <?php if ($isFollowed): ?>
                                                            <div class="action-btn followed-up-locked" title="Followed Up (Locked)"><i class="fas fa-lock"></i></div>
                                                        <?php else: ?>
                                                            <button onclick='openFollowUpModal(<?php echo (int)$reg["id"]; ?>, <?php echo json_encode($reg["full_name"], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                                                                    class="action-btn follow-up" title="Mark as Followed Up">
                                                                <i class="fas fa-check-double text-sm"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="action-icons">
                                                        <button onclick='viewAndMarkRead(<?php echo json_encode($reg, JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                                                                class="action-btn view" title="View Details"><i class="fas fa-eye text-sm"></i></button>
                                                        <button onclick='openDeleteModal(<?php echo (int)$reg["id"]; ?>, <?php echo json_encode($reg["full_name"], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                                                                class="action-btn delete" title="Delete"><i class="fas fa-trash text-sm"></i></button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($adultsPages > 1): ?>
                            <div class="flex items-center justify-between mt-6 bg-white rounded-xl shadow p-4">
                                <p class="text-sm text-gray-600">
                                    Showing <strong><?php echo number_format($offset + 1); ?></strong>–<strong><?php echo number_format(min($offset + PAGE_SIZE, $adultsTotal)); ?></strong>
                                    of <strong><?php echo number_format($adultsTotal); ?></strong> adults
                                </p>
                                <div class="pagination flex gap-2 flex-wrap">
                                    <a class="<?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo $page <= 1 ? '#' : h(qs(['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end   = min($adultsPages, $page + 2);
                                    if ($start > 1) echo '<a href="' . h(qs(['page' => 1])) . '">1</a>' . ($start > 2 ? '<span class="disabled">…</span>' : '');
                                    for ($p = $start; $p <= $end; $p++):
                                    ?>
                                        <?php if ($p == $page): ?>
                                            <span class="current"><?php echo $p; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo h(qs(['page' => $p])); ?>"><?php echo $p; ?></a>
                                        <?php endif; ?>
                                    <?php endfor;
                                    if ($end < $adultsPages) echo ($end < $adultsPages - 1 ? '<span class="disabled">…</span>' : '') . '<a href="' . h(qs(['page' => $adultsPages])) . '">' . $adultsPages . '</a>';
                                    ?>
                                    <a class="<?php echo $page >= $adultsPages ? 'disabled' : ''; ?>" href="<?php echo $page >= $adultsPages ? '#' : h(qs(['page' => $page + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- ============================== CHILDREN TAB ============================== -->
                <?php if ($tab === 'children'): ?>
                    <?php if (empty($children)): ?>
                        <div class="bg-white rounded-2xl shadow-lg p-16 text-center">
                            <i class="fas fa-child text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-2xl font-semibold text-gray-700">No Children Found</h3>
                            <p class="text-gray-500 mt-2">No children match your filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Child</th>
                                            <th>Gender</th>
                                            <th>Date of Birth</th>
                                            <th>Guardian</th>
                                            <th>Guardian Phone</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($children as $loopIndex => $ch):
                                        $statusClass = 'status-' . $ch['status'];
                                        $statusText  = ucfirst(str_replace('_', ' ', $ch['status']));
                                        $isFollowed  = $ch['status'] === 'followed_up';
                                        $age = (new DateTime($ch['date_of_birth']))->diff(new DateTime('today'))->y;
                                    ?>
                                        <tr id="child-row-<?php echo (int)$ch['id']; ?>">
                                             <td><?php echo number_format($offset + $loopIndex + 1); ?></td>
                                            <td>
                                                <div class="font-medium text-gray-900"><?php echo h($ch['full_name']); ?></div>
                                                <div class="child-guardian-line"></div>
                                            </td>
                                            <td class="text-sm text-gray-500"><?php echo h($ch['gender']); ?></td>
                                            <td class="text-sm text-gray-500">
                                                <?php echo date('M d, Y', strtotime($ch['date_of_birth'])); ?>
                                                <div class="text-xs text-gray-400"><?php echo $age; ?> yrs</div>
                                            </td>
                                            <td class="text-sm text-gray-700 font-medium"><?php echo h($ch['guardian_name']); ?></td>
                                            <td class="text-sm text-gray-500"><?php echo h($ch['guardian_phone']); ?></td>
                                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($isFollowed): ?>
                                                        <div class="action-btn followed-up-locked" title="Followed Up (Locked)"><i class="fas fa-lock"></i></div>
                                                    <?php else: ?>
                                                        <button onclick='openChildFollowUpModal(<?php echo (int)$ch["id"]; ?>, <?php echo json_encode($ch["full_name"], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                                                                class="action-btn follow-up" title="Mark as Followed Up">
                                                            <i class="fas fa-check-double text-sm"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <div class="action-icons">
                                                        <button onclick='viewChildAndMarkRead(<?php echo json_encode($ch, JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                                                                class="action-btn view" title="View Details"><i class="fas fa-eye text-sm"></i></button>
                                                        <button onclick='openDeleteChildModal(<?php echo (int)$ch["id"]; ?>, <?php echo json_encode($ch["full_name"], JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                                                                class="action-btn delete" title="Delete"><i class="fas fa-trash text-sm"></i></button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($childrenPages > 1): ?>
                            <div class="flex items-center justify-between mt-6 bg-white rounded-xl shadow p-4">
                                <p class="text-sm text-gray-600">
                                    Showing <strong><?php echo number_format($offset + 1); ?></strong>–<strong><?php echo number_format(min($offset + PAGE_SIZE, $childrenTotal)); ?></strong>
                                    of <strong><?php echo number_format($childrenTotal); ?></strong> children
                                </p>
                                <div class="pagination flex gap-2 flex-wrap">
                                    <a class="<?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo $page <= 1 ? '#' : h(qs(['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end   = min($childrenPages, $page + 2);
                                    if ($start > 1) echo '<a href="' . h(qs(['page' => 1])) . '">1</a>' . ($start > 2 ? '<span class="disabled">…</span>' : '');
                                    for ($p = $start; $p <= $end; $p++):
                                    ?>
                                        <?php if ($p == $page): ?>
                                            <span class="current"><?php echo $p; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo h(qs(['page' => $p])); ?>"><?php echo $p; ?></a>
                                        <?php endif; ?>
                                    <?php endfor;
                                    if ($end < $childrenPages) echo ($end < $childrenPages - 1 ? '<span class="disabled">…</span>' : '') . '<a href="' . h(qs(['page' => $childrenPages])) . '">' . $childrenPages . '</a>';
                                    ?>
                                    <a class="<?php echo $page >= $childrenPages ? 'disabled' : ''; ?>" href="<?php echo $page >= $childrenPages ? '#' : h(qs(['page' => $page + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- ============ View Details Modal (Adults) ============ -->
    <div id="detailsModal" class="modal" onclick="if(event.target === this) closeDetailsModal()">
        <div class="modal-content p-6 max-w-2xl w-full mx-4" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Registration Details</h3>
                <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times text-2xl"></i></button>
            </div>
            <div id="modalContent" class="space-y-4"></div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeDetailsModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">Close</button>
            </div>
        </div>
    </div>

    <!-- ============ View Details Modal (Children) ============ -->
    <div id="childDetailsModal" class="modal" onclick="if(event.target === this) closeChildDetailsModal()">
        <div class="modal-content p-6 max-w-2xl w-full mx-4" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Child Details</h3>
                <button onclick="closeChildDetailsModal()" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times text-2xl"></i></button>
            </div>
            <div id="childModalContent" class="space-y-4"></div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeChildDetailsModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">Close</button>
            </div>
        </div>
    </div>

    <!-- ============ Children of Guardian Modal ============ -->
    <div id="childrenOfGuardianModal" class="modal" onclick="if(event.target === this) closeChildrenModal()">
        <div class="modal-content p-6 max-w-3xl w-full mx-4" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-2xl font-bold text-gray-800" id="cogTitle">Children</h3>
                    <p class="text-sm text-gray-500" id="cogSubtitle">Loading…</p>
                </div>
                <button onclick="closeChildrenModal()" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times text-2xl"></i></button>
            </div>
            <div id="cogContent" class="space-y-3">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-indigo-500 text-3xl"></i>
                    <p class="text-gray-500 mt-3">Loading children…</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ Delete Modal (Adult) ============ -->
    <div id="deleteModal" class="modal" onclick="if(event.target === this) closeDeleteModal()">
        <div class="modal-content p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div>
                <div><h3 class="text-xl font-bold text-gray-800">Confirm Delete</h3><p class="text-sm text-gray-500">This action cannot be undone</p></div>
            </div>
            <p class="text-gray-600 mb-6" id="deleteMessage">Are you sure?</p>
            <div class="flex gap-3 justify-end">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">Cancel</button>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="id" id="deleteId" value="">
                    <button type="submit" name="delete_registration" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"><i class="fas fa-trash mr-2"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ Delete Modal (Child) ============ -->
    <div id="deleteChildModal" class="modal" onclick="if(event.target === this) closeDeleteChildModal()">
        <div class="modal-content p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div>
                <div><h3 class="text-xl font-bold text-gray-800">Confirm Delete Child</h3><p class="text-sm text-gray-500">This action cannot be undone</p></div>
            </div>
            <p class="text-gray-600 mb-6" id="deleteChildMessage">Are you sure?</p>
            <div class="flex gap-3 justify-end">
                <button onclick="closeDeleteChildModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">Cancel</button>
                <form method="POST" id="deleteChildForm">
                    <input type="hidden" name="child_id" id="deleteChildId" value="">
                    <button type="submit" name="delete_child" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"><i class="fas fa-trash mr-2"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ Follow Up Modal (Adult) ============ -->
    <div id="followUpModal" class="modal" onclick="if(event.target === this) closeFollowUpModal()">
        <div class="modal-content p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
            <div class="text-center">
                <div class="follow-up-icon"><i class="fas fa-check-double"></i></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Confirm Follow Up</h3>
                <p class="text-gray-600 mb-2" id="followUpMessage">Are you sure?</p>
                <p class="text-sm text-red-500 mb-6">⚠️ This action cannot be undone</p>
                <div class="flex gap-3 justify-center">
                    <button onclick="closeFollowUpModal()" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">Cancel</button>
                    <form method="POST" id="followUpForm">
                        <input type="hidden" name="id" id="followUpId" value="">
                        <button type="submit" name="mark_followed_up" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition"><i class="fas fa-check mr-2"></i> Confirm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ Follow Up Modal (Child) ============ -->
    <div id="childFollowUpModal" class="modal" onclick="if(event.target === this) closeChildFollowUpModal()">
        <div class="modal-content p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
            <div class="text-center">
                <div class="follow-up-icon"><i class="fas fa-check-double"></i></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Confirm Child Follow Up</h3>
                <p class="text-gray-600 mb-2" id="childFollowUpMessage">Are you sure?</p>
                <p class="text-sm text-red-500 mb-6">⚠️ This action cannot be undone</p>
                <div class="flex gap-3 justify-center">
                    <button onclick="closeChildFollowUpModal()" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">Cancel</button>
                    <form method="POST" id="childFollowUpForm">
                        <input type="hidden" name="child_id" id="childFollowUpId" value="">
                        <button type="submit" name="mark_child_followed_up" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition"><i class="fas fa-check mr-2"></i> Confirm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============ Mobile menu ============
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.createElement('button');
            menuToggle.id = 'menuToggle';
            menuToggle.className = 'md:hidden fixed top-4 left-4 z-50 bg-indigo-600 text-white p-3 rounded-xl shadow-lg';
            menuToggle.innerHTML = '<i class="fas fa-bars text-xl"></i>';
            document.body.prepend(menuToggle);
            menuToggle.addEventListener('click', () => document.querySelector('.sidebar').classList.toggle('active'));
            document.querySelectorAll('.nav-link').forEach(l => l.addEventListener('click', () => {
                if (window.innerWidth < 768) document.querySelector('.sidebar').classList.remove('active');
            }));
            document.addEventListener('click', e => {
                if (window.innerWidth < 768) {
                    const sb = document.querySelector('.sidebar');
                    const tg = document.getElementById('menuToggle');
                    if (!sb.contains(e.target) && !tg.contains(e.target)) sb.classList.remove('active');
                }
            });
        });

        // ============ Helpers ============
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        }
        function lockScroll() { document.body.style.overflow = 'hidden'; }
        function unlockScroll() { document.body.style.overflow = 'auto'; }

        // ============ Adults: view + mark as read ============
        function viewAndMarkRead(reg) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');

            const statusColors = { unread:'text-red-600', read:'text-blue-600', followed_up:'text-green-600' };
            const statusDisplay = { unread:'Unread', read:'Read', followed_up:'Followed Up' };
            const registeredDate = new Date(reg.registered_at).toLocaleString();

            content.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Full Name</p>
                        <p class="font-semibold text-gray-800">${escapeHtml(reg.full_name)}</p></div>
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Gender</p>
                        <p class="font-semibold text-gray-800">${escapeHtml(reg.gender)}</p></div>
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Phone</p>
                        <p class="font-semibold text-gray-800">${escapeHtml(reg.phone)}</p></div>
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Location</p>
                        <p class="font-semibold text-gray-800">${escapeHtml(reg.location)}</p></div>
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold ${statusColors[reg.status]||'text-gray-600'}">${statusDisplay[reg.status]||reg.status}</p></div>
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Registered</p>
                        <p class="font-semibold text-gray-800">${registeredDate}</p></div>
                </div>`;

            modal.classList.add('active'); lockScroll();

            if (reg.status === 'unread') {
                fetch('dinner.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'mark_as_read=1&id=' + encodeURIComponent(reg.id)
                }).then(r => r.json()).then(d => {
                    if (d.success) {
                        const row = document.getElementById('row-' + reg.id);
                        if (row) {
                            const b = row.querySelector('.badge');
                            if (b) { b.className = 'badge status-read'; b.textContent = 'Read'; }
                        }
                    }
                }).catch(() => {});
            }
        }
        function closeDetailsModal() { document.getElementById('detailsModal').classList.remove('active'); unlockScroll(); }

        // ============ Children: view + mark as read ============
        function viewChildAndMarkRead(ch) {
            const modal = document.getElementById('childDetailsModal');
            const content = document.getElementById('childModalContent');
            const statusColors = { unread:'text-red-600', read:'text-blue-600', followed_up:'text-green-600' };
            const statusDisplay = { unread:'Unread', read:'Read', followed_up:'Followed Up' };
            const createdDate = new Date(ch.created_at).toLocaleString();
            const dob = new Date(ch.date_of_birth);
            const age = Math.floor((Date.now() - dob.getTime()) / (365.25 * 24 * 60 * 60 * 1000));

            content.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg col-span-2"><p class="text-sm text-gray-500">Child Name</p>
                        <p class="font-semibold text-gray-800">${escapeHtml(ch.full_name)}</p></div>
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Gender</p>
                        <p class="font-semibold text-gray-800">${escapeHtml(ch.gender)}</p></div>
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Date of Birth</p>
                        <p class="font-semibold text-gray-800">${escapeHtml(ch.date_of_birth)} <span class="text-xs text-gray-500">(${age} yrs)</span></p></div>
                    <div class="bg-gray-50 p-4 rounded-lg col-span-2 border-l-4 border-amber-400">
                        <p class="text-xs uppercase text-amber-700 font-bold mb-2">Guardian</p>
                        <p class="font-semibold text-gray-800">${escapeHtml(ch.guardian_name)}</p>
                        <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i> ${escapeHtml(ch.guardian_phone)}</p>
                        <p class="text-sm text-gray-600"><i class="fas fa-map-marker-alt mr-1"></i> ${escapeHtml(ch.guardian_location)}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold ${statusColors[ch.status]||'text-gray-600'}">${statusDisplay[ch.status]||ch.status}</p></div>
                    <div class="bg-gray-50 p-4 rounded-lg"><p class="text-sm text-gray-500">Registered</p>
                        <p class="font-semibold text-gray-800">${createdDate}</p></div>
                </div>`;
            modal.classList.add('active'); lockScroll();

            if (ch.status === 'unread') {
                fetch('dinner.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'mark_child_as_read=1&child_id=' + encodeURIComponent(ch.id)
                }).then(r => r.json()).then(d => {
                    if (d.success) {
                        const row = document.getElementById('child-row-' + ch.id);
                        if (row) { const b = row.querySelector('.badge'); if (b) { b.className = 'badge status-read'; b.textContent = 'Read'; } }
                    }
                }).catch(() => {});
            }
        }
        function closeChildDetailsModal() { document.getElementById('childDetailsModal').classList.remove('active'); unlockScroll(); }

        // ============ Children of Guardian modal ============
        function openChildrenModal(guardianId, guardianName) {
            const modal = document.getElementById('childrenOfGuardianModal');
            const title = document.getElementById('cogTitle');
            const subtitle = document.getElementById('cogSubtitle');
            const content = document.getElementById('cogContent');

            title.textContent = 'Children of ' + guardianName;
            subtitle.textContent = 'Loading…';
            content.innerHTML = `<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-indigo-500 text-3xl"></i><p class="text-gray-500 mt-3">Loading…</p></div>`;
            modal.classList.add('active'); lockScroll();

            fetch('dinner.php?ajax=children_of_guardian&guardian_id=' + encodeURIComponent(guardianId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(async r => {
                const raw = await r.text();
                try { return JSON.parse(raw); }
                catch (e) {
                    console.error('Non-JSON response from server:', raw);
                    throw new Error('Server returned invalid response (HTTP ' + r.status + ')');
                }
            })
            .then(d => {
                if (!d.success) {
                    content.innerHTML = `<p class="text-red-600 text-center py-6">${escapeHtml(d.message || 'Failed to load.')}</p>`;
                    return;
                }
                subtitle.textContent = d.children.length + ' child' + (d.children.length === 1 ? '' : 'ren');
                if (!d.children.length) {
                    content.innerHTML = `<p class="text-gray-500 text-center py-6">This guardian has no children registered.</p>`;
                    return;
                }
                content.innerHTML = d.children.map(c => {
                    const statusClass = 'status-' + c.status;
                    const statusText = c.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const age = Math.floor((Date.now() - new Date(c.date_of_birth).getTime()) / (365.25 * 24 * 60 * 60 * 1000));
                    return `
                        <div class="bg-gray-50 rounded-xl p-4 flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="font-semibold text-gray-800">${escapeHtml(c.full_name)}</p>
                                    <span class="badge ${statusClass}">${statusText}</span>
                                </div>
                                <p class="text-xs text-gray-500">
                                    ${escapeHtml(c.gender)} · DOB ${escapeHtml(c.date_of_birth)} (${age} yrs)
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button onclick='viewChildAndMarkRead(${JSON.stringify(c).replace(/'/g, "&#39;")})'
                                        class="action-btn view" title="View"><i class="fas fa-eye text-sm"></i></button>
                            </div>
                        </div>`;
                }).join('');
            })
            .catch(err => {
                console.error(err);
                content.innerHTML = `<p class="text-red-600 text-center py-6">${escapeHtml(err.message || 'Failed to load children.')}</p>`;
            });
        }
        function closeChildrenModal() { document.getElementById('childrenOfGuardianModal').classList.remove('active'); unlockScroll(); }

        // ============ Delete modals ============
        function openDeleteModal(id, name) {
            document.getElementById('deleteMessage').textContent = `Delete the registration for "${name}"? This also deletes all children under this guardian. Cannot be undone.`;
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').classList.add('active'); lockScroll();
        }
        function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('active'); unlockScroll(); }

        function openDeleteChildModal(id, name) {
            document.getElementById('deleteChildMessage').textContent = `Delete the child "${name}"? Cannot be undone.`;
            document.getElementById('deleteChildId').value = id;
            document.getElementById('deleteChildModal').classList.add('active'); lockScroll();
        }
        function closeDeleteChildModal() { document.getElementById('deleteChildModal').classList.remove('active'); unlockScroll(); }

        // ============ Follow up modals ============
        function openFollowUpModal(id, name) {
            document.getElementById('followUpMessage').textContent = `Mark "${name}" as followed up?`;
            document.getElementById('followUpId').value = id;
            document.getElementById('followUpModal').classList.add('active'); lockScroll();
        }
        function closeFollowUpModal() { document.getElementById('followUpModal').classList.remove('active'); unlockScroll(); }

        function openChildFollowUpModal(id, name) {
            document.getElementById('childFollowUpMessage').textContent = `Mark child "${name}" as followed up?`;
            document.getElementById('childFollowUpId').value = id;
            document.getElementById('childFollowUpModal').classList.add('active'); lockScroll();
        }
        function closeChildFollowUpModal() { document.getElementById('childFollowUpModal').classList.remove('active'); unlockScroll(); }

        // ============ Escape key closes everything ============
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDetailsModal(); closeChildDetailsModal(); closeChildrenModal();
                closeDeleteModal(); closeDeleteChildModal();
                closeFollowUpModal(); closeChildFollowUpModal();
            }
        });

        // ============ Auto-dismiss flash messages ============
        document.querySelectorAll('.bg-green-50, .bg-red-50').forEach(el => {
            setTimeout(() => { el.style.transition='opacity 0.5s'; el.style.opacity='0'; setTimeout(()=>el.remove(),500); }, 5000);
        });
    </script>
</body>
</html>