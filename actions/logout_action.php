<?php
// Log out. POST only (with the CSRF token, checked in core/core.php), so another website
// can't log customers out by hiding a link to this page.
require_once __DIR__ . '/../core/core.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('index.php'));

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'domain' => $p['domain'],
                                   'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => $p['samesite'] ?? 'Lax']);
}
session_destroy();
redirect(url('views/login.php'));
