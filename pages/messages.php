<?php
include '../includes/auth.php'; requireAdmin(); include '../includes/db.php';
$stmt = $pdo->query('SELECT * FROM contacts ORDER BY submitted_at DESC');
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>
<header class="topbar admin-topbar">
    <button class="icon-btn" onclick="openAdminMenu()">☰</button>
    <strong>Visitor Messages</strong>
    <a class="orange-btn" href="dashboard.php">Dashboard</a>
</header>
<aside id="adminMenu" class="admin-menu">
    <button class="close-small" onclick="closeAdminMenu()">×</button>
    <div class="menu-head"><div class="brand"><span class="logo">♡</span><div><strong>Abba House</strong><br><small>Admin page</small></div></div></div>
    <a href="dashboard.php">▥ Dashboard</a><a href="register_orphan.php">♙ Register Orphan</a><a href="messages.php">✉ Visitor Messages</a>
    <div class="admin-profile"><span>RB</span><div><b><?= htmlspecialchars($_SESSION['admin_name']) ?></b><br><small>admin@email.com</small></div><button onclick="showLogout()">⇥</button></div>
</aside>
<div id="adminOverlay" onclick="closeAdminMenu()"></div>
<main class="page dashboard-page">
    <div class="messages-header">
        <div>
            <p class="section-label">CONTACT INQUIRIES</p>
            <h1>Visitor Messages</h1>
        </div>
        <a class="black-small" href="dashboard.php">Back to dashboard</a>
    </div>

    <?php if (!$messages): ?>
        <p class="empty-state">No visitor messages have been submitted yet.</p>
    <?php else: ?>
        <div class="messages-list">
            <?php foreach ($messages as $m):
                $preview = mb_substr($m['message'], 0, 120);
                if (mb_strlen($m['message']) > 120) $preview .= '...';
            ?>
                <a class="message-card" href="message_view.php?id=<?= $m['contact_id'] ?>">
                    <h3><?= htmlspecialchars($m['subject']) ?></h3>
                    <p class="message-meta"><b><?= htmlspecialchars($m['full_name']) ?></b> • <?= htmlspecialchars($m['submitted_at']) ?></p>
                    <p class="message-meta"><?= htmlspecialchars($m['email']) ?><?= $m['phone'] ? ' • ' . htmlspecialchars($m['phone']) : '' ?></p>
                    <p class="message-preview"><?= htmlspecialchars($preview) ?></p>
                    <span class="orange-btn">View Details</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<div id="logoutModal" class="modal"><div><h2>Are you sure you want to log out?</h2><a class="black-btn" href="logout.php">Yes</a><button class="orange-btn" onclick="hideLogout()">No</button></div></div>
<?php include '../includes/footer.php'; ?>
