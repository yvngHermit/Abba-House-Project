<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$clientId = getenv('GOOGLE_CLIENT_ID');
$redirectUri = getenv('GOOGLE_REDIRECT_URI');

if (!$clientId || !$redirectUri) {
    header('Location: ../pages/signin.php?sso_error=not_configured');
    exit();
}

// A fresh unpredictable state value protects the OAuth callback against CSRF.
$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_started_at'] = time();

$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
];

header('Cache-Control: no-store');
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
exit();
