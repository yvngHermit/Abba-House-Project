<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function messageViewEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'contact_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
}

$message = null;
$replies = [];
$error = '';
$success = $_SESSION['message_reply_flash'] ?? '';
unset($_SESSION['message_reply_flash']);

if ($id) {
    $stmt = $pdo->prepare('SELECT contact_id, full_name, phone, email, subject, message, submitted_at FROM contacts WHERE contact_id = ?');
    $stmt->execute([$id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Your session token expired. Please refresh the page and try again.';
    } elseif (!$message) {
        $error = 'The contact message could not be found.';
    } else {
        $replyBody = $_POST['reply_body'] ?? '';
        if (!is_string($replyBody)) {
            $replyBody = '';
        }
        $replyBody = trim($replyBody);
        if ($replyBody === '') {
            $error = 'Enter a reply before sending.';
        } elseif (strlen($replyBody) > 20000) {
            $error = 'Your reply is too long. Please keep it under 20,000 bytes.';
        } elseif (!filter_var($message['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'The saved contact email address is invalid. No email was sent.';
        } else {
            $isAzure = getenv('DB_CONNECTION') === 'sqlsrv';

            if ($isAzure) {
                $insert = $pdo->prepare(
                    "INSERT INTO contact_replies
                        (contact_id, admin_id, reply_body, delivery_status)
                    OUTPUT INSERTED.reply_id
                    VALUES (?, ?, ?, 'Pending')"
                );

                $insert->execute([
                    (int) $message['contact_id'],
                    (int) $_SESSION['admin_id'],
                    $replyBody
                ]);

                $replyId = (int) $insert->fetchColumn();
            } else {
                $insert = $pdo->prepare(
                    "INSERT INTO contact_replies
                        (contact_id, admin_id, reply_body, delivery_status)
                    VALUES (?, ?, ?, 'Pending')"
                );

                $insert->execute([
                    (int) $message['contact_id'],
                    (int) $_SESSION['admin_id'],
                    $replyBody
                ]);

                $replyId = (int) $pdo->lastInsertId();
            }

            try {
                require_once '../includes/mailer.php';
                sendContactReply($message, $replyBody);
                $update = $pdo->prepare(
                    "UPDATE contact_replies
                    SET delivery_status = 'Sent',
                        sent_at = CURRENT_TIMESTAMP
                    WHERE reply_id = ?"
                );

                $update->execute([$replyId]);
                $_SESSION['message_reply_flash'] = 'Reply sent successfully to ' . $message['email'] . '.';
                header('Location: message_view.php?id=' . (int) $message['contact_id']);
                exit();
            } catch (Throwable $exception) {
                $update = $pdo->prepare("UPDATE contact_replies SET delivery_status = 'Failed' WHERE reply_id = ?");
                $update->execute([$replyId]);
                // Keep SMTP details in server logs, not in the page shown to the admin.
                error_log('Abba House contact reply failed (reply_id ' . $replyId . '): ' . $exception->getMessage());
                $error = 'The reply could not be sent. Check the local SMTP configuration and PHP error log, then try again.';
            }
        }
    }
}

if ($message) {
    $replyStmt = $pdo->prepare('SELECT r.reply_id, r.reply_body, r.delivery_status, r.created_at, r.sent_at, a.full_name AS admin_name FROM contact_replies r LEFT JOIN admins a ON a.admin_id = r.admin_id WHERE r.contact_id = ? ORDER BY r.created_at DESC, r.reply_id DESC');
    $replyStmt->execute([(int) $message['contact_id']]);
    $replies = $replyStmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>
<header class="topbar admin-topbar">
    <a class="black-small" href="messages.php">Back</a>
    <strong>Message Details</strong>
    <a class="orange-btn" href="dashboard.php">Dashboard</a>
</header>
<main class="page dashboard-page">
    <?php if ($success): ?><p class="success" role="status"><?= messageViewEscape($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error" role="alert"><?= messageViewEscape($error) ?></p><?php endif; ?>

    <?php if (!$message): ?>
        <p class="empty-state">Message not found.</p>
    <?php else: ?>
        <section class="message-detail-card">
            <p class="section-label">FULL CONTACT INQUIRY</p>
            <h1><?= messageViewEscape($message['subject']) ?></h1>
            <p><b>From:</b> <?= messageViewEscape($message['full_name']) ?></p>
            <p><b>Email:</b> <?= messageViewEscape($message['email']) ?></p>
            <p><b>Phone:</b> <?= $message['phone'] ? messageViewEscape($message['phone']) : 'Not provided' ?></p>
            <p><b>Submitted:</b> <?= messageViewEscape($message['submitted_at']) ?></p>
            <hr>
            <h2>Original message</h2>
            <p><?= nl2br(messageViewEscape($message['message'])) ?></p>
        </section>

        <section class="message-reply-card" aria-labelledby="reply-heading">
            <p class="section-label">ADMIN RESPONSE</p>
            <h2 id="reply-heading">Reply to this message</h2>
            <p class="reply-recipient">Recipient: <strong><?= messageViewEscape($message['email']) ?></strong></p>
            <form method="post" class="reply-form">
                <input type="hidden" name="contact_id" value="<?= (int) $message['contact_id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= messageViewEscape($_SESSION['csrf_token']) ?>">
                <label for="reply_body">Your reply</label>
                <textarea id="reply_body" name="reply_body" rows="8" maxlength="20000" required placeholder="Write a helpful response to this enquiry..."><?= isset($replyBody) && $error ? messageViewEscape($replyBody) : '' ?></textarea>
                <p class="reply-help">The email is sent from the Abba House SMTP account configured on this server.</p>
                <button type="submit" class="orange-btn">Send reply by email</button>
            </form>
        </section>

        <section class="message-reply-history" aria-labelledby="reply-history-heading">
            <h2 id="reply-history-heading">Reply history</h2>
            <?php if (!$replies): ?>
                <p class="empty-state">No replies have been recorded for this message.</p>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                    <article class="reply-history-item">
                        <div class="reply-history-heading">
                            <strong><?= messageViewEscape($reply['admin_name'] ?? 'Administrator') ?></strong>
                            <span class="reply-status status-<?= strtolower(messageViewEscape($reply['delivery_status'])) ?>"><?= messageViewEscape($reply['delivery_status']) ?></span>
                        </div>
                        <p class="message-meta">Created <?= messageViewEscape($reply['created_at']) ?><?= $reply['sent_at'] ? ' · Sent ' . messageViewEscape($reply['sent_at']) : '' ?></p>
                        <p><?= nl2br(messageViewEscape($reply['reply_body'])) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php include '../includes/footer.php'; ?>
