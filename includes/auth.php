<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Administrator authentication helpers.
 */

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

function admin_logged_in(): bool
{
    auth_start_session();
    return !empty($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        redirect(base_url('admin/login.php'));
    }
}

function current_admin(): ?array
{
    if (!admin_logged_in()) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['admin_id'],
        'username' => (string) ($_SESSION['admin_username'] ?? ''),
    ];
}

function admin_login(string $username, string $password): bool
{
    auth_start_session();
    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $row['id'];
    $_SESSION['admin_username'] = (string) $row['username'];
    return true;
}

function admin_logout(): void
{
    auth_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}
