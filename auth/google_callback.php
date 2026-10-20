<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function googleSignInFail(string $reason): void
{
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_started_at']);
    header('Cache-Control: no-store');
    header('Location: ../pages/signin.php?sso_error=' . rawurlencode($reason));
    exit();
}

$clientId = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');
$redirectUri = getenv('GOOGLE_REDIRECT_URI');

if (!$clientId || !$clientSecret || !$redirectUri) {
    error_log('Google SSO configuration is incomplete.');
    googleSignInFail('not_configured');
}

if (isset($_GET['error'])) {
    googleSignInFail('verification_failed');
}

$code = (string) ($_GET['code'] ?? '');
$returnedState = (string) ($_GET['state'] ?? '');
$expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
$startedAt = (int) ($_SESSION['google_oauth_started_at'] ?? 0);

if (
    $code === '' ||
    $returnedState === '' ||
    $expectedState === '' ||
    !hash_equals($expectedState, $returnedState) ||
    $startedAt < 1 ||
    (time() - $startedAt) > 600
) {
    googleSignInFail('verification_failed');
}

unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_started_at']);

function googleHttpRequest(string $url, string $method = 'GET', ?array $form = null, ?string $bearer = null): array
{
    $headers = ['Accept: application/json'];
    $content = '';

    if ($form !== null) {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $content = http_build_query($form, '', '&', PHP_QUERY_RFC3986);
    }
    if ($bearer !== null) {
        $headers[] = 'Authorization: Bearer ' . $bearer;
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $content,
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        throw new RuntimeException('Google endpoint request failed.');
    }

    $status = 0;
    foreach (($http_response_header ?? []) as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
            $status = (int) $matches[1];
        }
    }

    $decoded = json_decode($body, true);
    if ($status < 200 || $status >= 300 || !is_array($decoded)) {
        throw new RuntimeException('Google endpoint returned an unsuccessful response.');
    }

    return $decoded;
}

try {
    // Exchange the one-time authorization code on the server.
    $token = googleHttpRequest(
        'https://oauth2.googleapis.com/token',
        'POST',
        [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]
    );

    $accessToken = (string) ($token['access_token'] ?? '');
    if ($accessToken === '') {
        throw new RuntimeException('Google response did not contain an access token.');
    }

    // Retrieve the signed-in identity from Google's OIDC userinfo endpoint.
    $identity = googleHttpRequest('https://openidconnect.googleapis.com/v1/userinfo', 'GET', null, $accessToken);

    $email = strtolower(trim((string) ($identity['email'] ?? '')));
    $emailVerified = ($identity['email_verified'] ?? false) === true
        || ($identity['email_verified'] ?? null) === 'true';
    $subject = trim((string) ($identity['sub'] ?? ''));

    if ($email === '' || !$emailVerified || $subject === '') {
        throw new RuntimeException('Google identity is missing required verified claims.');
    }

    require_once __DIR__ . '/../includes/db.php';

    // Only an already-approved administrator can enter. SSO never creates admins.
    $stmt = $pdo->prepare('SELECT admin_id, email, full_name FROM admins WHERE LOWER(email) = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        error_log('Google SSO denied: verified email is not in the admin allowlist.');
        googleSignInFail('not_authorized');
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_name'] = $admin['full_name'];
    $_SESSION['auth_provider'] = 'google';

    header('Cache-Control: no-store');
    header('Location: ../pages/dashboard.php');
    exit();
} catch (Throwable $e) {
    // Keep provider tokens, secrets, and identity payloads out of logs.
    error_log('Google SSO failed: ' . get_class($e) . ': ' . $e->getMessage());
    googleSignInFail('temporarily_unavailable');
}
