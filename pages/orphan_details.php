<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/db.php';

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$orphanId = trim((string) ($_GET['id'] ?? ''));
if ($orphanId === '' || strlen($orphanId) > 10) {
    http_response_code(400);
    $pageError = 'A valid orphan record ID is required.';
    $orphan = null;
} else {
    $stmt = $pdo->prepare(
        'SELECT orphan_id, full_name, gender, date_of_birth, date_of_enrollment,
                has_disability, photo_path, birth_cert_path, record_status,
                status_updated_at, created_at
         FROM orphans
         WHERE orphan_id = :orphan_id
         LIMIT 1'
    );
    $stmt->execute([':orphan_id' => $orphanId]);
    $orphan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orphan) {
        http_response_code(404);
        $pageError = 'The requested orphan record could not be found.';
    }
}

function orphanDocumentUrl($storedPath, string $expectedFolder): ?string
{
    if (!is_string($storedPath) || $storedPath === '') {
        return null;
    }

    // Only serve files from the expected uploads folder, never arbitrary paths.
    $prefix = 'uploads/' . $expectedFolder . '/';
    if (strpos($storedPath, $prefix) !== 0) {
        return null;
    }

    $filename = basename($storedPath);
    if ($filename === '' || $filename === '.' || $filename === '..') {
        return null;
    }

    $relativePath = $prefix . $filename;
    $diskPath = dirname(__DIR__) . '/' . $relativePath;
    if (!is_file($diskPath)) {
        return null;
    }

    return '../' . $prefix . rawurlencode($filename);
}

include '../includes/header.php';
?>
<header class="topbar admin-topbar">
    <a class="icon-btn" href="orphan_records.php" aria-label="Back to orphan records">←</a>
    <strong>Orphan Details</strong>
    <a class="orange-btn" href="orphan_records.php">Back to Records</a>
</header>

<aside id="adminMenu" class="admin-menu" aria-label="Admin navigation">
    <button class="close-small" type="button" onclick="closeAdminMenu()" aria-label="Close navigation">×</button>
    <div class="menu-head"><div class="brand"><span class="logo">♡</span><div><strong>Abba House</strong><br><small>Admin page</small></div></div></div>
    <a href="dashboard.php">▥ Dashboard</a>
    <a href="orphan_records.php" aria-current="page">▤ Orphan Records</a>
    <a href="register_orphan.php">♙ Register Orphan</a>
    <a href="messages.php">✉ Visitor Messages</a>
    <div class="admin-profile"><span>AH</span><div><b><?= h($_SESSION['admin_name'] ?? 'Administrator') ?></b><br><small>Administrator</small></div><button type="button" onclick="showLogout()" aria-label="Log out">⇥</button></div>
</aside>
<div id="adminOverlay" onclick="closeAdminMenu()"></div>

<main class="page orphan-details-page">
    <?php if (isset($pageError)): ?>
        <section class="details-error" role="alert">
            <h1>Record unavailable</h1>
            <p><?= h($pageError) ?></p>
            <a class="orange-btn" href="orphan_records.php">Return to Orphan Records</a>
        </section>
    <?php else:
        $dob = new DateTimeImmutable($orphan['date_of_birth']);
        $age = $dob->diff(new DateTimeImmutable('today'))->y;
        $photoUrl = orphanDocumentUrl($orphan['photo_path'] ?? null, 'orphans');
        $certificateUrl = orphanDocumentUrl($orphan['birth_cert_path'] ?? null, 'certificates');
        $status = $orphan['record_status'] ?? 'Active';
        $statusClass = strtolower((string) $status);
    ?>
        <section class="details-heading">
            <p class="section-label">CHILD MANAGEMENT</p>
            <h1>Orphan Details</h1>
            <p>View the registered information and documents for this child.</p>
        </section>

        <section class="orphan-detail-card">
            <div class="orphan-detail-profile">
                <?php if ($photoUrl): ?>
                    <img class="orphan-detail-photo" src="<?= h($photoUrl) ?>" alt="Photo of <?= h($orphan['full_name']) ?>">
                <?php else: ?>
                    <div class="orphan-detail-photo orphan-photo-placeholder" aria-label="No photo available">♡</div>
                <?php endif; ?>
                <div class="orphan-detail-name">
                    <p class="orphan-detail-id">Record ID: <?= h($orphan['orphan_id']) ?></p>
                    <h2><?= h($orphan['full_name']) ?></h2>
                    <p><?= $age ?> years old</p>
                    <span class="record-status status-<?= h($statusClass) ?>"><?= h($status) ?></span>
                </div>
            </div>

            <div class="orphan-detail-section">
                <h2>Personal Information</h2>
                <dl class="orphan-detail-grid">
                    <div><dt>Full Name</dt><dd><?= h($orphan['full_name']) ?></dd></div>
                    <div><dt>Orphan ID</dt><dd><?= h($orphan['orphan_id']) ?></dd></div>
                    <div><dt>Gender</dt><dd><?= h($orphan['gender']) ?></dd></div>
                    <div><dt>Date of Birth</dt><dd><?= h(date('d F Y', strtotime($orphan['date_of_birth']))) ?></dd></div>
                    <div><dt>Age</dt><dd><?= $age ?> years</dd></div>
                    <div><dt>Date of Enrollment</dt><dd><?= h(date('d F Y', strtotime($orphan['date_of_enrollment']))) ?></dd></div>
                    <div><dt>Disability</dt><dd><?= h($orphan['has_disability']) ?></dd></div>
                    <div><dt>Record Status</dt><dd><span class="record-status status-<?= h($statusClass) ?>"><?= h($status) ?></span></dd></div>
                    <?php if (!empty($orphan['status_updated_at'])): ?>
                        <div><dt>Status Last Updated</dt><dd><?= h(date('d F Y, H:i', strtotime($orphan['status_updated_at']))) ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="orphan-detail-section">
                <h2>Documents</h2>
                <div class="orphan-documents">
                    <div class="orphan-document-item">
                        <span class="orphan-document-icon" aria-hidden="true">▧</span>
                        <div><strong>Child's Photograph</strong><p><?= $photoUrl ? 'A photograph is available for this record.' : 'No photograph has been uploaded.' ?></p></div>
                        <?php if ($photoUrl): ?><a class="black-small" href="<?= h($photoUrl) ?>" target="_blank" rel="noopener">View Photo</a><?php endif; ?>
                    </div>
                    <div class="orphan-document-item">
                        <span class="orphan-document-icon" aria-hidden="true">▤</span>
                        <div><strong>Birth Certificate</strong><p><?= $certificateUrl ? 'A birth certificate is available for this record.' : 'No birth certificate has been uploaded.' ?></p></div>
                        <?php if ($certificateUrl): ?><a class="black-small" href="<?= h($certificateUrl) ?>" target="_blank" rel="noopener">View Certificate</a><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="orphan-detail-actions">
                <a class="black-cancel" href="orphan_records.php">Back to Records</a>
                <a class="orange-btn" href="register_orphan.php">Register Another Child</a>
            </div>
        </section>
    <?php endif; ?>
</main>

<div id="logoutModal" class="modal"><div><h2>Are you sure you want to log out?</h2><a class="black-btn" href="logout.php">Yes</a><button class="orange-btn" onclick="hideLogout()">No</button></div></div>
<?php include '../includes/footer.php'; ?>
