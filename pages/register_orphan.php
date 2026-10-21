<?php
include '../includes/auth.php'; requireAdmin(); include '../includes/db.php';
$success = '';
function uploadFile($field, $folder) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
    $name = uniqid() . '.' . $ext;
    $target = '../uploads/' . $folder . '/' . $name;
    move_uploaded_file($_FILES[$field]['tmp_name'], $target);
    return 'uploads/' . $folder . '/' . $name;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $photo = uploadFile('photo', 'orphans');
    $cert = uploadFile('birth_cert', 'certificates');
    $isAzure = getenv('DB_CONNECTION') === 'sqlsrv';

    if ($isAzure) {
        $sql = "INSERT INTO orphans
            (orphan_id, full_name, gender, date_of_birth,
            date_of_enrollment, has_disability, photo_path,
            birth_cert_path, admin_id)
            VALUES (?, ?, ?, ?, CAST(GETDATE() AS DATE), ?, ?, ?, ?)";
    } else {
        $sql = "INSERT INTO orphans
            (orphan_id, full_name, gender, date_of_birth,
            date_of_enrollment, has_disability, photo_path,
            birth_cert_path, admin_id)
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['orphan_id'],
        trim($_POST['full_name']),
        $_POST['gender'],
        $_POST['date_of_birth'],
        $_POST['has_disability'],
        $photo,
        $cert,
        $_SESSION['admin_id']
    ]);

    $success = 'Orphan registered successfully.';
}
$next = 'ORP' . str_pad(($pdo->query('SELECT COUNT(*) FROM orphans')->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
include '../includes/header.php';
?>
<header class="topbar admin-topbar"><a class="icon-btn" href="dashboard.php">☰</a><strong>Register Orphan</strong><a class="black-cancel" href="dashboard.php">⊗ Cancel</a></header>
<main class="page register-page">
    <form method="post" enctype="multipart/form-data" class="register-card">
        <div class="form-title"><span class="square-icon">♙</span><div><h1>Register New Orphan</h1><p>Enter the details of the child to register them in the system</p></div></div>
        <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
        <label>Orphan ID</label><div class="id-row"><input id="orphanId" name="orphan_id" value="<?= $next ?>" required><button type="button" class="black-small" onclick="generateId()">Generate</button></div>
        <label>Full name</label><input name="full_name" placeholder="Enter child’s full name" required>
        <label>Gender</label><div class="radio-row"><label><input type="radio" name="gender" value="Male" required> Male</label><label><input type="radio" name="gender" value="Female"> Female</label></div>
        <label>Date of Birth</label><input type="date" name="date_of_birth" required>
        <label>Date of enrollment</label><input value="<?= date('d F Y') ?>" readonly><small>Enrollment date is automatically set to today’s date automatically</small>
        <label>Does the child have any disability?</label><div class="radio-row"><label><input type="radio" name="has_disability" value="Yes" required> yes</label><label><input type="radio" name="has_disability" value="No"> No</label></div>
        <label>Orphan Picture</label><label class="upload-box">▧<br><span>⇩ Upload Photo</span><input type="file" name="photo" accept="image/*"></label>
        <label>Birth Certificate</label><label class="upload-box">▧<br><span>⇩ Upload Photo</span><input type="file" name="birth_cert" accept="image/*,.pdf"></label>
        <button class="orange-btn full">♙ Register Orphan</button>
    </form>
</main>
<?php include '../includes/footer.php'; ?>
