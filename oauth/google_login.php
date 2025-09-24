<?php
require_once __DIR__ . '/../inc/connection.inc.php';

$config = [];
$configFile = __DIR__ . '/../inc/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
}

$clientId = $config['google_client_id'] ?? getenv('GOOGLE_CLIENT_ID');
$redirectUri = $config['google_redirect_uri'] ?? getenv('GOOGLE_REDIRECT_URI');

if (!$clientId) {
    http_response_code(500);
    exit('Google OAuth client ID is not configured.');
}

if (!$redirectUri) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $redirectUri = sprintf('%s://%s/oauth/google_callback.php', $scheme, $host);
}

$state = bin2hex(random_bytes(32));
$_SESSION['oauth2state'] = $state;
$_SESSION['oauth2provider'] = 'google';

$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
