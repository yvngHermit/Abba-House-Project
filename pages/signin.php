<?php
include '../includes/db.php';
session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? AND password = ?');
    $stmt->execute([$_POST['email'], $_POST['password']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid email or password.';
    }
}
include '../includes/header.php';
?>
<header class="brown-header"><span class="logo black-logo">♡</span><strong>Sign In</strong><a href="home.php" class="home-link">⌂</a></header>
<main class="page signin-page">
    <h1>Welcome back!</h1>
    <p>Sign into the admin dashboard to register new orphans and mark attendance</p>
    <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
    <form method="post" class="signin-card">
        <label>email:</label><input type="email" name="email" placeholder="Enter your email" required>
        <label>Password</label><input type="password" name="password" placeholder="Enter email password" required>
        <button class="black-btn">SIGN IN</button>
    </form>
</main>
<?php include '../includes/footer.php'; ?>
