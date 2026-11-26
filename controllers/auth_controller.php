<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/user_class.php';
require_once __DIR__ . '/../classes/membership_class.php';

// ---------- Session / user ----------
function is_logged_in() { return current_user() !== null; }

function current_user()
{
    if (!array_key_exists('__auth_user', $GLOBALS)) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = (new UserClass())->findById((int)$_SESSION['user_id']);
            if (!$user) unset($_SESSION['user_id']);
        }
        $GLOBALS['__auth_user'] = $user;
    }
    return $GLOBALS['__auth_user'];
}

function login_user($user)
{
    session_regenerate_id(true);          // prevents session fixation; the cart is kept
    $_SESSION['user_id'] = (int)$user['id'];
    unset($GLOBALS['__auth_user']);
}

function logout_user()
{
    unset($_SESSION['user_id'], $_SESSION['pending_tier'], $_SESSION['after_login']);
    session_regenerate_id(true);
    unset($GLOBALS['__auth_user']);
}

function first_name($user) { return explode(' ', trim($user['name']))[0]; }

// Only server-set, whitelisted paths are used after login (no open redirects).
function after_login_path()
{
    $p = $_SESSION['after_login'] ?? '';
    unset($_SESSION['after_login']);
    return preg_match('#^(index\.php|views/[a-z_]+\.php)$#', $p) ? url($p) : url('index.php');
}

// ---------- CSRF ----------
// core/core.php already defines these; only define them here if it didn't (so loading both never crashes).
if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
}
if (!function_exists('csrf_valid')) {
    function csrf_valid()
    {
        $t = $_POST['csrf'] ?? '';
        return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
    }
}

// ---------- Flash messages (shown once, rendered by the header) ----------
function flash_set($type, $msg) { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function flash_get() { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }