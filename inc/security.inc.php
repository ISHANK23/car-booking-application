<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
    $_SESSION['csrf_tokens'] = [];
}

/**
 * Generate a CSRF token for the given context.
 */
function generate_csrf_token(string $context): string
{
    if (!isset($_SESSION['csrf_tokens'][$context])) {
        $_SESSION['csrf_tokens'][$context] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_tokens'][$context];
}

/**
 * Validate a CSRF token for the given context.
 */
function validate_csrf_token(string $context, ?string $token): bool
{
    if (!isset($_SESSION['csrf_tokens'][$context])) {
        return false;
    }

    $expectedToken = $_SESSION['csrf_tokens'][$context];
    unset($_SESSION['csrf_tokens'][$context]);

    return is_string($token) && hash_equals($expectedToken, $token);
}

/**
 * Abort the request when the CSRF token is invalid.
 */
function require_valid_csrf_token(string $context, ?string $token): void
{
    if (!validate_csrf_token($context, $token)) {
        http_response_code(400);
        echo 'Invalid CSRF token. Please reload the page and try again.';
        exit;
    }
}

function sanitize_text(string $value): string
{
    return trim($value);
}

function sanitize_multiline(string $value): string
{
    return trim(filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW));
}

function sanitize_email(string $value): string
{
    return filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';
}

function sanitize_phone(string $value): string
{
    return preg_replace('/[^0-9+]/', '', $value);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
