<?php
// includes/auth.php - sessions, access control and small helpers
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Escape output to prevent XSS. Use on every value printed into HTML. */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

/** Redirect visitors who are not logged in to the login page. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        header('Location: ' . BASE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
}

/** Only administrators may continue; everyone else gets 403. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Access denied. Administrator only.');
    }
}

/** Set a flash message (flash('success', 'Saved')) or read and clear it (flash()). */
function flash(?string $type = null, ?string $message = null)
{
    if ($type !== null) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function money($amount): string
{
    return CURRENCY . ' ' . number_format((float)$amount, 0);
}

/** CSRF protection: add csrf_field() inside every POST form and check csrf_valid() on submit. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

/** Only allow redirects to pages inside this site (prevents open-redirect attacks). */
function safe_redirect_target(?string $path, string $fallback): string
{
    if ($path && strpos($path, BASE_URL . '/') === 0 && strpos($path, '//') === false && strpos($path, '\\') === false && !preg_match('/[\r\n]/', $path)) {
        return $path;
    }
    return $fallback;
}
