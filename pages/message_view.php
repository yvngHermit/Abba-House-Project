<?php
include '../includes/auth.php'; requireAdmin(); include '../includes/db.php';
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare('SELECT * FROM contacts WHERE contact_id = ?');
$stmt->execute([$id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>
<header class="topbar admin-topbar">
    <a class="black-small" href="messages.php">Back</a>
    <strong>Message Details</strong>
    <a class="orange-btn" href="dashboard.php">Dashboard</a>
</header>
<main class="page dashboard-page">
    <?php if (!$message): ?>
        <p class="empty-state">Message not found.</p>
    <?php else: ?>
        <section class="message-detail-card">
            <p class="section-label">FULL CONTACT INQUIRY</p>
            <h1><?= htmlspecialchars($message['subject']) ?></h1>
            <p><b>From:</b> <?= htmlspecialchars($message['full_name']) ?></p>
            <p><b>Email:</b> <?= htmlspecialchars($message['email']) ?></p>
            <p><b>Phone:</b> <?= $message['phone'] ? htmlspecialchars($message['phone']) : 'Not provided' ?></p>
            <p><b>Submitted:</b> <?= htmlspecialchars($message['submitted_at']) ?></p>
            <hr>
            <h2>Message</h2>
            <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
        </section>
    <?php endif; ?>
</main>
<?php include '../includes/footer.php'; ?>
