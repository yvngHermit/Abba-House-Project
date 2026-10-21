<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

$error = '';
$ssoError = trim((string) ($_GET['sso_error'] ?? ''));

$ssoMessages = [
    'not_configured' => 'Google sign-in is not configured yet. Please use your password or contact the administrator.',
    'not_authorized' => 'This Google account is not approved for Abba House administrator access.',
    'verification_failed' => 'Google sign-in could not be verified. Please try again.',
    'temporarily_unavailable' => 'Google sign-in is temporarily unavailable. Please use your password or try again later.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // Preserve the existing password login while SSO is being introduced.
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? AND password = ?');
    $stmt->execute([$email, $password]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        header('Location: dashboard.php');
        exit();
    }

    $error = 'Invalid email or password.';
}

include __DIR__ . '/../includes/header.php';
?>
<header class="brown-header"><span class="logo black-logo">♡</span><strong>Sign In</strong><a href="home.php" class="home-link">⌂</a></header>
<main class="page signin-page">
    <h1>Welcome back!</h1>
    <p>Sign into the admin dashboard to register new orphans and mark attendance</p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($ssoError !== ''): ?>
        <p class="error"><?= htmlspecialchars($ssoMessages[$ssoError] ?? 'Google sign-in failed. Please try again.', ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <a class="black-btn" href="../auth/google_login.php" style="display:block;text-align:center;text-decoration:none;margin:0 auto 18px;max-width:420px">
        Continue with Google
    </a>

    <p style="text-align:center">or sign in with your Abba House account</p>
    <form method="post" class="signin-card">
        <label>Email</label><input type="email" name="email" placeholder="Enter your email" autocomplete="username" required>
        <label>Password</label><input type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
        <button class="black-btn" type="submit">SIGN IN</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
