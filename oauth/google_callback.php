<?php
require_once __DIR__ . '/../inc/connection.inc.php';

$config = [];
$configFile = __DIR__ . '/../inc/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
}

$clientId = $config['google_client_id'] ?? getenv('GOOGLE_CLIENT_ID');
$clientSecret = $config['google_client_secret'] ?? getenv('GOOGLE_CLIENT_SECRET');
$redirectUri = $config['google_redirect_uri'] ?? getenv('GOOGLE_REDIRECT_URI');

if (!$clientId || !$clientSecret) {
    $_SESSION['flash_error'] = 'Google OAuth is not configured properly.';
    header('Location: ../login.php');
    exit;
}

if (!$redirectUri) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $redirectUri = sprintf('%s://%s/oauth/google_callback.php', $scheme, $host);
}

if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || !hash_equals($_SESSION['oauth2state'], $_GET['state'])) {
    $_SESSION['flash_error'] = 'Invalid OAuth session state.';
    header('Location: ../login.php');
    exit;
}

unset($_SESSION['oauth2state']);
$_SESSION['oauth2provider'] = 'google';

if (!empty($_GET['error'])) {
    $_SESSION['flash_error'] = 'Google sign-in failed: ' . sanitize_text($_GET['error']);
    header('Location: ../login.php');
    exit;
}

$code = $_GET['code'] ?? null;
if (!$code) {
    $_SESSION['flash_error'] = 'Missing authorization code from Google.';
    header('Location: ../login.php');
    exit;
}

$tokenResponse = fetch_google_tokens($clientId, $clientSecret, $redirectUri, $code);
if (!$tokenResponse || empty($tokenResponse['id_token'])) {
    $_SESSION['flash_error'] = 'Unable to verify Google identity. Please try again.';
    header('Location: ../login.php');
    exit;
}

$payload = decode_google_id_token($tokenResponse['id_token']);
if (!$payload || ($payload['aud'] ?? '') !== $clientId) {
    $_SESSION['flash_error'] = 'Received invalid identity token from Google.';
    header('Location: ../login.php');
    exit;
}

$email = $payload['email'] ?? null;
$sub = $payload['sub'] ?? null;
$name = $payload['name'] ?? ($payload['email'] ?? 'Google User');

if (!$email || !$sub) {
    $_SESSION['flash_error'] = 'Your Google account did not provide the required details.';
    header('Location: ../login.php');
    exit;
}

$provider = 'google';

$statement = $con->prepare('SELECT id, email FROM users WHERE oauth_provider = ? AND oauth_identifier = ? LIMIT 1');
$statement->bind_param('ss', $provider, $sub);
$statement->execute();
$result = $statement->get_result();
$existingOauth = $result->fetch_assoc();
$statement->close();

if ($existingOauth) {
    $userId = (int)$existingOauth['id'];
} else {
    $statement = $con->prepare('SELECT id, password FROM users WHERE email = ? LIMIT 1');
    $statement->bind_param('s', $email);
    $statement->execute();
    $result = $statement->get_result();
    $existingUser = $result->fetch_assoc();
    $statement->close();

    if ($existingUser && !empty($existingUser['password'])) {
        $_SESSION['flash_error'] = 'An account already exists with this email address. Please sign in with your password.';
        header('Location: ../login.php');
        exit;
    }

    if ($existingUser) {
        $userId = (int)$existingUser['id'];
        $update = $con->prepare('UPDATE users SET oauth_provider = ?, oauth_identifier = ?, username = ?, password = NULL WHERE id = ?');
        $update->bind_param('sssi', $provider, $sub, $name, $userId);
        $update->execute();
        $update->close();
    } else {
        $phone = '';
        $insert = $con->prepare('INSERT INTO users (username, email, phone, password, oauth_provider, oauth_identifier) VALUES (?, ?, ?, NULL, ?, ?)');
        $insert->bind_param('sssss', $name, $email, $phone, $provider, $sub);
        $insert->execute();
        $userId = $insert->insert_id ?: $con->insert_id;
        $insert->close();
    }
}

session_regenerate_id(true);
$_SESSION['username'] = $email;
$_SESSION['user_id'] = $userId;
$_SESSION['display_name'] = $name;

header('Location: ../my_account.php');
exit;

function fetch_google_tokens(string $clientId, string $clientSecret, string $redirectUri, string $code): ?array
{
    $ch = curl_init('https://oauth2.googleapis.com/token');
    $postFields = http_build_query([
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
    ]);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        curl_close($ch);
        return null;
    }

    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        return null;
    }

    return json_decode($response, true);
}

function decode_google_id_token(string $idToken): ?array
{
    $parts = explode('.', $idToken);
    if (count($parts) !== 3) {
        return null;
    }

    $payload = base64url_decode($parts[1]);
    if ($payload === false) {
        return null;
    }

    return json_decode($payload, true);
}

function base64url_decode(string $data)
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padLen = 4 - $remainder;
        $data .= str_repeat('=', $padLen);
    }
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    return $decoded;
}
