<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$statusLabels = [
    'Active' => 'Active',
    'Adopted' => 'Adopted',
    'Deregistered' => 'Deregistered',
];
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        http_response_code(403);
        $error = 'Your session could not be verified. Refresh the page and try again.';
    } else {
        $orphanId = trim((string) ($_POST['orphan_id'] ?? ''));
        $newStatus = (string) ($_POST['new_status'] ?? '');

        if ($orphanId === '' || !in_array($newStatus, ['Active', 'Adopted', 'Deregistered'], true)) {
            $error = 'The requested status change is invalid.';
        } else {
            try {
                $update = $pdo->prepare(
                    'UPDATE orphans
                     SET record_status = :record_status,
                         status_updated_at = CURRENT_TIMESTAMP
                     WHERE orphan_id = :orphan_id'
                );
                $update->execute([
                    ':record_status' => $newStatus,
                    ':orphan_id' => $orphanId,
                ]);

                if ($update->rowCount() > 0) {
                    $notice = 'Orphan status updated to ' . $statusLabels[$newStatus] . '.';
                } else {
                    $check = $pdo->prepare('SELECT record_status FROM orphans WHERE orphan_id = ?');
                    $check->execute([$orphanId]);
                    $current = $check->fetchColumn();
                    if ($current === false) {
                        $error = 'The selected orphan could not be found.';
                    } else {
                        $notice = 'No status change was needed. This record is already ' . strtolower($statusLabels[$newStatus]) . '.';
                    }
                }
            } catch (PDOException $exception) {
                error_log('Orphan status update failed: ' . $exception->getMessage());
                $error = 'The status could not be updated. Please try again.';
            }
        }
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? 'Active');
$allowedFilters = ['Active', 'Adopted', 'Deregistered', 'All'];
if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = 'Active';
}

$sql = 'SELECT orphan_id, full_name, date_of_birth, record_status FROM orphans';
$conditions = [];
$params = [];
if ($search !== '') {
    $conditions[] = '(orphan_id LIKE :search_id OR full_name LIKE :search_name)';
    $params[':search_id'] = '%' . $search . '%';
    $params[':search_name'] = '%' . $search . '%';
}
if ($statusFilter !== 'All') {
    $conditions[] = 'record_status = :record_status';
    $params[':record_status'] = $statusFilter;
}
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY CASE record_status WHEN \'Active\' THEN 0 WHEN \'Adopted\' THEN 1 ELSE 2 END, full_name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalActive = (int) $pdo->query("SELECT COUNT(*) FROM orphans WHERE record_status = 'Active'")->fetchColumn();
$totalAdopted = (int) $pdo->query("SELECT COUNT(*) FROM orphans WHERE record_status = 'Adopted'")->fetchColumn();
$totalDeregistered = (int) $pdo->query("SELECT COUNT(*) FROM orphans WHERE record_status = 'Deregistered'")->fetchColumn();

include '../includes/header.php';
?>
<header class="topbar admin-topbar">
    <button class="icon-btn" type="button" onclick="openAdminMenu()" aria-label="Open admin navigation">☰</button>
    <strong>Orphan Records</strong>
    <a class="orange-btn" href="register_orphan.php">＋ Register</a>
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

<main class="page orphan-records-page">
    <section class="records-heading">
        <p class="section-label">CHILD MANAGEMENT</p>
        <h1>Orphan Records</h1>
        <p>Search and manage registered children. Adoption and deregistration update the record status without deleting the child's history or documents.</p>
    </section>

    <?php if ($notice !== ''): ?><p class="success" role="status"><?= h($notice) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>

    <section class="record-summary" aria-label="Orphan record totals">
        <div class="record-summary-card"><span>Active</span><strong><?= $totalActive ?></strong></div>
        <div class="record-summary-card"><span>Adopted</span><strong><?= $totalAdopted ?></strong></div>
        <div class="record-summary-card"><span>Deregistered</span><strong><?= $totalDeregistered ?></strong></div>
    </section>

    <section class="records-panel">
        <div class="records-panel-heading">
            <div><h2>Registered children</h2><p>Choose a record to view its details or update its status.</p></div>
            <a class="orange-btn" href="register_orphan.php">＋ Register Orphan</a>
        </div>
        <form method="get" class="records-filters">
            <label class="records-search-label" for="recordSearch">Search records</label>
            <input id="recordSearch" type="search" name="search" value="<?= h($search) ?>" placeholder="Search by ID or child's name">
            <label for="recordStatus">Status</label>
            <select id="recordStatus" name="status">
                <?php foreach ($allowedFilters as $filter): ?>
                    <option value="<?= h($filter) ?>" <?= $statusFilter === $filter ? 'selected' : '' ?>><?= h($filter === 'All' ? 'All statuses' : $filter) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="black-small" type="submit">Search</button>
            <a class="records-reset" href="orphan_records.php">Reset</a>
        </form>

        <?php if (!$orphans): ?>
            <div class="empty-state">No orphan records match your search and status filters.</div>
        <?php else: ?>
            <div class="records-table-wrap">
                <table class="records-table">
                    <thead><tr><th scope="col">ID</th><th scope="col">Name</th><th scope="col">Surname</th><th scope="col">Age</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($orphans as $orphan):
                        $nameParts = preg_split('/\s+/', trim($orphan['full_name'])) ?: [];
                        $firstName = array_shift($nameParts) ?? '';
                        $surname = implode(' ', $nameParts);
                        $dob = new DateTimeImmutable($orphan['date_of_birth']);
                        $age = $dob->diff(new DateTimeImmutable('today'))->y;
                        $statusClass = strtolower($orphan['record_status']);
                    ?>
                        <tr>
                            <td data-label="ID"><span class="orphan-id"><?= h($orphan['orphan_id']) ?></span></td>
                            <td data-label="Name"><?= h($firstName) ?></td>
                            <td data-label="Surname"><?= h($surname !== '' ? $surname : '—') ?></td>
                            <td data-label="Age"><?= $age ?></td>
                            <td data-label="Status"><span class="record-status status-<?= h($statusClass) ?>"><?= h($orphan['record_status']) ?></span></td>
                            <td data-label="Action">
                                <details class="record-action-menu">
                                    <summary aria-label="Actions for <?= h($orphan['full_name']) ?>">Actions <span aria-hidden="true">▾</span></summary>
                                    <div class="record-action-options">
                                        <a href="orphan_details.php?id=<?= rawurlencode($orphan['orphan_id']) ?>">View details</a>
                                        <?php if ($orphan['record_status'] !== 'Adopted'): ?>
                                            <form method="post" onsubmit="return confirm('Mark this child as adopted? The record and documents will be preserved.');">
                                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="orphan_id" value="<?= h($orphan['orphan_id']) ?>">
                                                <input type="hidden" name="new_status" value="Adopted">
                                                <button type="submit">Mark as adopted</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($orphan['record_status'] !== 'Deregistered'): ?>
                                            <form method="post" onsubmit="return confirm('Deregister this child? The record and documents will be preserved.');">
                                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="orphan_id" value="<?= h($orphan['orphan_id']) ?>">
                                                <input type="hidden" name="new_status" value="Deregistered">
                                                <button type="submit">Deregister</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($orphan['record_status'] !== 'Active'): ?>
                                            <form method="post" onsubmit="return confirm('Restore this child to Active status?');">
                                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="orphan_id" value="<?= h($orphan['orphan_id']) ?>">
                                                <input type="hidden" name="new_status" value="Active">
                                                <button type="submit">Restore to active</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="records-footnote">Status changes preserve the child's database record, attendance history and uploaded documents.</p>
    </section>
</main>
<div id="logoutModal" class="modal"><div><h2>Are you sure you want to log out?</h2><a class="black-btn" href="logout.php">Yes</a><button class="orange-btn" onclick="hideLogout()">No</button></div></div>
<?php include '../includes/footer.php'; ?>
