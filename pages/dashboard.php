<?php
include '../includes/auth.php'; requireAdmin(); include '../includes/db.php';
$search = $_GET['search'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    foreach ($_POST['attendance'] as $orphanId => $status) {
        $stmt = $pdo->prepare('INSERT INTO attendance (orphan_id, attendance_date, status) VALUES (?, CURDATE(), ?) ON DUPLICATE KEY UPDATE status = VALUES(status)');
        $stmt->execute([$orphanId, $status]);
    }
}
$total = $pdo->query('SELECT COUNT(*) FROM orphans')->fetchColumn();
$totalMessages = $pdo->query('SELECT COUNT(*) FROM contacts')->fetchColumn();
$todayPresent = $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'Present'")->fetchColumn();
$stmt = $pdo->prepare("SELECT o.*, COALESCE(a.status, 'Absent') AS status FROM orphans o LEFT JOIN attendance a ON o.orphan_id = a.orphan_id AND a.attendance_date = CURDATE() WHERE o.full_name LIKE ? ORDER BY o.full_name");
$stmt->execute(['%' . $search . '%']);
$orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>
<header class="topbar admin-topbar"><button class="icon-btn" onclick="openAdminMenu()">☰</button><strong>Admin Dashboard</strong><div><a class="orange-btn" href="register_orphan.php">♙ Register</a></div></header>
<aside id="adminMenu" class="admin-menu">
    <button class="close-small" onclick="closeAdminMenu()">×</button>
    <div class="menu-head"><div class="brand"><span class="logo">♡</span><div><strong>Abba House</strong><br><small>Admin page</small></div></div></div>
    <a href="dashboard.php">▥ Dashboard</a><a href="orphan_records.php">▤ Orphan Records</a><a href="register_orphan.php">♙ Register Orphan</a><a href="messages.php">✉️ Visitor Messages</a>
    <div class="admin-profile"><span>RB</span><div><b><?= $_SESSION['admin_name'] ?></b><br><small>admin@email.com</small></div><button onclick="showLogout()">⇥</button></div>
</aside>
<div id="adminOverlay" onclick="closeAdminMenu()"></div>
<main class="page dashboard-page">
    <p class="section-label">OVERVIEW STATISTICS</p>
    <div class="dashboard-grid">
        <div>
            <div class="horizontal-scroll stats-scroll">
                <div class="stat-card"><p>Children Registered</p><h2><?= $total ?></h2><img src="../assets/images/children_registered_icon.png" width="30" height="30"></div>
                <a class="stat-card" href="messages.php"><p>Visitor Messages</p><h2><?= $totalMessages ?></h2><span>✉️</span></a>
                <div class="stat-card"><p>Present Today</p><h2><?= $todayPresent ?></h2><span>✓</span></div>
            </div>
        </div>
        <div>
    <p class="section-label">ATTENDANCE & CHECK-IN</p>
    <section class="attendance-box">
        <h2>Attendance & Check-in</h2>
        <form method="get" class="search-row"><input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="⌕  Search by name"><button class="black-small">Search</button></form>
        <form method="post">
            <button type="button" class="black-small mark-all" onclick="markAllPresent()">ⓘ Mark All</button>
            <?php foreach ($orphans as $o):
                $age = date_diff(date_create($o['date_of_birth']), date_create('today'))->y;
                $initials = strtoupper(substr($o['full_name'],0,1) . substr(strrchr($o['full_name'],' ') ?: $o['full_name'],1,1));
            ?>
            <div class="child-row">
                <input type="hidden" name="attendance[<?= $o['orphan_id'] ?>]" value="Absent">
                <input class="present-check" type="checkbox" name="attendance[<?= $o['orphan_id'] ?>]" value="Present" <?= $o['status']=='Present'?'checked':'' ?>>
                <span class="avatar"><?= $initials ?></span>
                <div><h3><?= htmlspecialchars($o['full_name']) ?></h3><b>Age <?= $age ?></b></div>
                <span class="status-pill">◷ <?= $o['status'] ?></span>
            </div>
            <?php endforeach; ?>
            <button class="orange-btn save-attendance">Save Attendance</button>
        </form>
    </section>
        </div>
    </div>
</main>
<div id="logoutModal" class="modal"><div><h2>Are you sure you want to log out?</h2><a class="black-btn" href="logout.php">Yes</a><button class="orange-btn" onclick="hideLogout()">No</button></div></div>
<?php include '../includes/footer.php'; ?>