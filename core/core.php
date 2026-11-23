<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');   // never show errors on screen
ini_set('log_errors', '1');       // still write them to the log

ini_set('html_errors', '0');

// Visitors never see technical error details. Every error gets a short reference code that is
// written to the PHP error log together with the full details, so it can be looked up later.
function friendly_error_page($ref, $schemaProblem = false)
{
    if (!headers_sent()) http_response_code(500);
    $msg = "Sorry, something went wrong. Please try again in a moment. (Reference $ref)";
    foreach (headers_list() as $h) {
        if (stripos($h, 'application/json') !== false) { echo json_encode(['success' => false, 'message' => $msg]); return; }
    }
    $admin = (int)($_SESSION['user_role'] ?? 0) === 1;
    $base  = defined('BASE_URL') ? BASE_URL : '';
    echo '<div style="max-width:520px;margin:12vh auto;padding:32px;font-family:Arial,sans-serif;background:#FAF7F0;border:1px solid #D5CBBA;color:#1A1916;text-align:center">'
       . '<p style="font-family:Georgia,serif;font-size:26px;margin:0 0 12px">Something went wrong</p>'
       . '<p style="font-size:14px;color:#6B6050;line-height:1.6;margin:0 0 20px">Sorry about that. Please go back and try again in a moment. If it keeps happening, contact us and quote reference <strong>' . $ref . '</strong>.</p>'
       // A missing table/column means the database hasn't been updated yet: point admins (only) to the fix.
       . ($admin && $schemaProblem ? '<p style="font-size:14px;margin:0 0 20px;padding:12px;background:#E9D9A8">Admin: the database needs updating. <a href="' . htmlspecialchars($base) . '/views/admin/admin_setup.php" style="color:#B24008">Open Database update</a> and click <strong>Update database</strong>.</p>' : '')
       . '<a href="' . htmlspecialchars($base) . '/index.php" style="display:inline-block;background:#2B4A12;color:#F4EFE4;padding:12px 26px;text-decoration:none;font-size:14px">Back to the shop</a></div>';
}

set_exception_handler(function ($e) {
    $ref = strtoupper(bin2hex(random_bytes(3)));
    error_log("[error $ref] " . $e);
    friendly_error_page($ref, $e instanceof PDOException && in_array($e->getCode(), ['42S02', '42S22'], true));
});

// Warnings and notices: log them, never print them (even if the server's php.ini says display_errors=On).
set_error_handler(function ($no, $str, $file, $line) {
    if (!(error_reporting() & $no)) return true;   // silenced with @
    error_log("PHP warning: $str in $file:$line");
    return true;
});

// Fatal errors can't be caught by the handlers above: show the same friendly page instead of PHP's message.
register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR], true)) return;
    $ref = strtoupper(bin2hex(random_bytes(3)));
    error_log("[error $ref] Fatal: {$e['message']} in {$e['file']}:{$e['line']}");
    while (ob_get_level()) ob_end_clean();
    friendly_error_page($ref);
});

// ---------- Security: headers and session cookie (before anything is sent) ----------
const SESSION_IDLE_LIMIT = 7200;   // log out after 2 hours without activity
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');                       // no clickjacking: other sites can't frame our pages
    header("Content-Security-Policy: frame-ancestors 'self'");
    header('X-Content-Type-Options: nosniff');                   // browsers must not guess file types
    header('Referrer-Policy: strict-origin-when-cross-origin');  // don't leak full URLs (order refs) to other sites
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header_remove('X-Powered-By');                               // don't advertise the PHP version
}
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');    // ignore session IDs the server didn't create (session fixation)
    ini_set('session.use_only_cookies', '1');   // never accept a session ID from the URL
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $isHttps, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
// Idle timeout: sign the account out (the cart is kept) after SESSION_IDLE_LIMIT seconds of inactivity.
if (!empty($_SESSION['customer_id']) && isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > SESSION_IDLE_LIMIT) {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email'], $_SESSION['user_role'], $_SESSION['csrf']);
    session_regenerate_id(true);
}
$_SESSION['last_seen'] = time();
require_once __DIR__ . '/../settings/db_class.php';

// BASE_URL normally comes from settings/db_cred.php.
// Local:  define('BASE_URL', '/akuafo');
// Server: define('BASE_URL', '/~elisa.okyere/akuafo');
if (!defined('BASE_URL')) define('BASE_URL', '/akuafo');

// ---------- Security: every form posted to actions/ must carry this session's CSRF token ----------
// (Forms get the token automatically: views/layout/header.php and admin_menu.php add it to every POST form.)
// ---------- Security: login lockout ----------
const LOGIN_MAX_FAILS_EMAIL = 5;    // per email address
const LOGIN_MAX_FAILS_IP    = 20;   // per IP (higher: offices and mobile networks share IPs)
const LOGIN_LOCK_MINUTES    = 15;
// Pure decision, used by actions/login_action.php (and the unit tests).
function login_locked($emailFails, $ipFails)
{
    return (int)$emailFails >= LOGIN_MAX_FAILS_EMAIL || (int)$ipFails >= LOGIN_MAX_FAILS_IP;
}
function client_ip() { return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45); }

function csrf_reject()
{
    http_response_code(400);
    $msg = 'Your session expired or the form was sent from somewhere else. Please go back, refresh the page and try again.';
    if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || preg_match('/(login|register)_action\.php$/', $_SERVER['SCRIPT_NAME'] ?? '')) {
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => $msg]));
    }
    exit('<div style="max-width:520px;margin:12vh auto;padding:32px;font-family:Arial,sans-serif;background:#FAF7F0;border:1px solid #D5CBBA;text-align:center">'
       . '<p style="font-family:Georgia,serif;font-size:24px;margin:0 0 12px">Please try that again</p><p style="font-size:14px;color:#6B6050;line-height:1.6">' . $msg . '</p>'
       . '<a href="javascript:history.back()" style="display:inline-block;margin-top:12px;background:#2B4A12;color:#F4EFE4;padding:12px 26px;text-decoration:none;font-size:14px">Go back</a></div>');
}
if (PHP_SAPI !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && strpos(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ''), '/actions/') !== false
    && !csrf_valid()) {
    csrf_reject();
}

const DELIVERY_FEE = 12;   // shown in the cart drawer until checkout knows the zone (replaced in D4)
const PASS_PRICE = 49;

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }
function money($n) { return 'GH₵ ' . number_format($n, floor($n) == $n ? 0 : 2); }
function redirect($path) { header('Location: ' . $path); exit; }

// Absolute URL for links inside emails, e.g. http://169.239.251.102:442/~elisa.okyere/akuafo/views/shop.php
// Only known hosts are trusted, so a forged Host header can't put a fake domain into an email.
function site_url($path = '')
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if ($host !== '169.239.251.102:442' && !preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host)) $host = 'localhost';
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https' : 'http') . '://' . $host . url($path);
}

// Only site-relative pages are allowed as a "go back here after login" target (no open redirects).
function safe_next($next)
{
    $next = (string)$next;
    return preg_match('#^(index\.php|views/[a-z_]+\.php)(\?[A-Za-z0-9_=&%.\-]*)?$#', $next) ? $next : '';
}

function customer_id() { return (int)($_SESSION['customer_id'] ?? 0); }

// Sends logged-out visitors to the login page, then back to $next after they log in.
function require_login($next = '')
{
    if (customer_id() > 0) return;
    $next = safe_next($next);
    redirect(url('views/login.php' . ($next ? '?next=' . urlencode($next) : '')));
}

// Sends the finished page to the browser and closes the connection, so slow work
// (like emailing) can carry on afterwards without the customer waiting for it.
// The page must have called ob_start() before any output.
function finish_response()
{
    ignore_user_abort(true);
    session_write_close();                       // don't block the customer's next click
    if (function_exists('fastcgi_finish_request')) { while (ob_get_level()) ob_end_flush(); fastcgi_finish_request(); return; }
    if (!headers_sent()) {
        header('Connection: close');
        if (!ini_get('zlib.output_compression')) header('Content-Length: ' . ob_get_length());
    }
    while (ob_get_level()) ob_end_flush();
    flush();
}

// True only when the order belongs to the logged-in customer (guest orders with no owner never match).
function owns_order($order) { return $order && customer_id() > 0 && (int)$order['user_id'] === customer_id(); }
function json_list($v) { return json_decode($v ?? '[]', true) ?: []; }

// Adds the shared-database prefix to a table name: tbl('products') => 'aku_products'
function tbl($name) { return 'aku_' . $name; }

function nav_items()
{
    return ['index.php' => 'Home', 'views/shop.php' => 'Shop', 'views/boxes.php' => 'Weekly Boxes',
            'views/preorder.php' => 'Entertaining', 'views/recipes.php' => 'Recipes', 'views/farms.php' => 'Our Farms',
            'views/membership.php' => 'Membership'];
}

function membership_tiers()
{
    return [
        ['name' => 'Silver',   'price' => 99,  'voucher' => 300,  'delivery' => 300,  'early' => '24 hours', 'discount' => 5,  'recipes' => 2, 'color' => '#8A8D91'],
        ['name' => 'Gold',     'price' => 199, 'voucher' => 600,  'delivery' => 600,  'early' => '48 hours', 'discount' => 10, 'recipes' => 4, 'color' => '#B8892B'],
        ['name' => 'Platinum', 'price' => 349, 'voucher' => 1000, 'delivery' => 1000, 'early' => '72 hours', 'discount' => 15, 'recipes' => 8, 'color' => '#4A5560'],
    ];
}

function get_slots()
{
    $slots = [];
    for ($i = 1; $i <= 7; $i++) {
        $d = new DateTime("+$i day");
        $slots[] = ['date' => $d->format('D, j M'), 'value' => $d->format('Y-m-d')];
    }
    return $slots;
}
function time_windows() { return ['Morning (7am - 12pm)', 'Afternoon (12pm - 5pm)', 'Evening (5pm - 8pm)']; }

function frequencies()
{
    return ['weekly' => 'Every week', 'biweekly' => 'Every 2 weeks', 'monthly' => 'Once a month'];
}

function delivery_days() { return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; }

// Benefits for one tier by name ('gold' => the Gold row of membership_tiers()), or null.
function tier_info($name)
{
    foreach (membership_tiers() as $m) if (strtolower($m['name']) === strtolower((string)$name)) return $m;
    return null;
}

function member_tier()
{
    static $t = false;
    if ($t === false) {
        $t = null;
        if (!empty($_SESSION['customer_id'])) {
            $r = (new Database())->fetchOne(
                "SELECT tier FROM aku_memberships WHERE user_id = ? AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT 1",
                [$_SESSION['customer_id']]
            );
            $t = $r['tier'] ?? null;
        }
    }
    return $t;
}

// ---------- Site settings (aku_settings), editable in Admin > Delivery Settings ----------
function app_setting($name, $default)
{
    static $cache = null;
    if ($cache === null) {
        try { $cache = array_column((new Database())->fetchAll('SELECT name, value FROM aku_settings'), 'value', 'name'); }
        catch (Throwable $e) { $cache = []; }   // table not created yet: use the defaults
    }
    return $cache[$name] ?? $default;
}
function min_order()               { return (float)app_setting('min_order', 80); }
function free_delivery_threshold() { return (float)app_setting('free_delivery_threshold', 500); }   // 0 = no free delivery
function order_cutoff_time()       { return (string)app_setting('order_cutoff_time', '20:00'); }   // order by this time for next-day delivery
function zone_day_names()          { return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; }

// GhanaPostGPS digital address: 2 letters, then 3 or 4 digits, then 4 digits (GA-123-4567, GA-1234-5678).
// Returns the tidy uppercase form, or null if it isn't valid. Accepts spaces or missing dashes.
function normalize_gps($s)
{
    $s = strtoupper(preg_replace('/[\s.]+/', '', (string)$s));
    // Dashes optional: GA1234567 => GA-123-4567, GA12345678 => GA-1234-5678.
    return preg_match('/^([A-Z]{2})-?(\d{3,4})-?(\d{4})$/', $s, $m) ? "$m[1]-$m[2]-$m[3]" : null;
}

// "GA-123-4567, Osu · near Shell, Oxford St" for an order (empty for orders placed before saved addresses).
function order_address_line($o)
{
    if (empty($o['gps_address'])) return '';
    return $o['gps_address'] . ', ' . $o['area_name'] . (!empty($o['landmark']) ? ' · near ' . $o['landmark'] : '');
}

// Active zones only: an inactive zone disappears from checkout.
function delivery_zones() { return (new Database())->fetchAll('SELECT id, name, fee, delivery_days FROM aku_delivery_zones WHERE is_active = 1 ORDER BY name'); }

function delivery_credit_left()
{
    $tier = member_tier();
    if (!$tier) return 0;
    $allow = 0;
    foreach (membership_tiers() as $m) if (strtolower($m['name']) === strtolower($tier)) $allow = $m['delivery'];
    $r = (new Database())->fetchOne(
        "SELECT COALESCE(SUM(delivery_credit), 0) AS used FROM aku_orders WHERE user_id = ? AND status <> 'cancelled' AND payment_status = 'paid' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')",
        [$_SESSION['customer_id']]
    );
    return max(0, $allow - (float)$r['used']);
}

function delivery_quote($zoneId)
{
    $z = (new Database())->fetchOne('SELECT id, name, fee FROM aku_delivery_zones WHERE id = ? AND is_active = 1', [(int)$zoneId]);
    if (!$z) return null;
    $fee = (float)$z['fee'];
    $credit = min($fee, delivery_credit_left());
    return ['zone' => $z['name'], 'fee' => $fee, 'credit' => $credit, 'pay' => $fee - $credit];
}

function tier_star($tier, $size = 16)
{
    foreach (membership_tiers() as $m) {
        if (strtolower($m['name']) === strtolower((string)$tier)) {
            return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="' . $m['color'] . '" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        }
    }
    return '';
}

function partners() { return (new Database())->fetchAll('SELECT * FROM aku_partners ORDER BY sort_order, id'); }

function is_admin() { return (int)($_SESSION['user_role'] ?? 0) === 1; }

// Every admin form POST must carry the session's CSRF token (added by views/layout/admin_menu.php),
// so another website can't trick a logged-in admin's browser into changing data.
function require_admin()
{
    if (!is_admin()) redirect(url('index.php'));
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid()) {
        http_response_code(400);
        exit('Your session has expired. Go back, refresh the page and try again.');
    }
}

// ---------- CSRF ----------
function csrf_token()
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_valid()
{
    $t = $_POST['csrf'] ?? '';
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

// Order status flow (aku_orders.status). Payment itself is tracked in payment_status.
function order_statuses() { return ['pending', 'paid', 'shipped', 'delivered', 'cancelled']; }
// Entertaining orders have their own flow (payment confirmed => 'received').
function ent_order_statuses() { return ['pending', 'received', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled']; }

// Keys are the unprefixed names. Wherever the admin code puts a key into SQL,
// wrap it with tbl(), e.g. "SELECT * FROM " . tbl($table)
function admin_tables()
{
    return [
        'products' => ['label' => 'Products', 'dir' => 'products'],
        'farmers'  => ['label' => 'Farmers',  'dir' => 'farmers',  'commas' => ['produce', 'certifications']],
        'recipes'  => ['label' => 'Recipes',  'dir' => 'recipes',  'commas' => ['produce', 'tags'], 'lines' => ['ingredients', 'method']],
        // logo: show images uncropped. order: list in the same order as the home page.
        // choices: dropdown options (an existing value outside the list is still kept).
        'partners' => ['label' => 'Partners', 'dir' => 'partners', 'logo' => true, 'order' => 'sort_order, id',
                       'choices' => ['type' => ['Restaurant', 'Hotel', 'Catering', 'Cafe', 'Bar', 'Lodge']]],
        'categories' => ['label' => 'Categories'],
        'regions'    => ['label' => 'Regions'],
        // Entertaining section (items themselves have their own page: views/admin/admin_ent_items.php)
        'ent_occasions'  => ['label' => 'Occasions',        'dir' => 'entertaining', 'order' => 'sort_order, id'],
        'ent_categories' => ['label' => 'Event categories', 'dir' => 'entertaining', 'order' => 'sort_order, id'],
    ];
}

// Dietary tags for entertaining items (keys match the SET column aku_ent_items.dietary).
function ent_dietary_tags()
{
    return ['vegetarian' => 'Vegetarian', 'vegan' => 'Vegan', 'contains_nuts' => 'Contains nuts', 'spicy' => 'Spicy'];
}

// "48 hours" / "5 days" for a notice period stored in hours.
function notice_label($hours)
{
    $hours = (int)$hours;
    if ($hours > 48 && $hours % 24 === 0) return ($hours / 24) . ' days';   // 72 => "3 days", 48 stays "48 hours"
    return $hours . ' hours';
}

// Returns a path on success, null if no file was sent, false if the file is invalid
function save_image($file, $dir)
{
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 2 * 1024 * 1024) return false;
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][mime_content_type($file['tmp_name'])] ?? null;
    if (!$ext) return false;
    $folder = __DIR__ . '/../images/' . $dir;
    if (!is_dir($folder)) mkdir($folder, 0777, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], "$folder/$name") ? "images/$dir/$name" : false;
}

function delete_image($path)
{
    $path = (string)$path;
    // Shared placeholder images (images/*/placeholder.*) are used by many rows: never delete them.
    if (strpos(basename($path), 'placeholder.') === 0) return;
    if ($path && strpos($path, 'images/') === 0 && strpos($path, '..') === false) {
        $f = __DIR__ . '/../' . $path;
        if (is_file($f)) unlink($f);
    }
}

function testimonials()
{
    return [
        ['name' => 'Kwame Mensah',  'role' => 'Head Chef',      'business' => 'Adom Kitchen',    'quote' => 'Our kontomire arrives the morning it was picked, with the farm named on the crate. Guests taste the difference.'],
        ['name' => 'Efua Boateng',  'role' => 'Owner',          'business' => 'Sankofa Table',   'quote' => 'Weekly delivery slots let me plan the menu around what is in season, not what the market happens to have.'],
        ['name' => 'Yaw Darko',     'role' => 'Executive Chef', 'business' => 'Volta Bay Lodge', 'quote' => 'Consistent quality for a full hotel kitchen was the hard part. Akuafo made it routine.'],
    ];
}

function img($src, $w = 600, $h = 600)
{
    $src = (string)$src;   // avoids the strpos(null) deprecation when an image is missing
    if (preg_match('#^https?://#', $src)) return $src;
    if (strpos($src, 'images/') === 0) return url($src);
    return "https://images.unsplash.com/$src?w=$w&h=$h&fit=crop&auto=format";
}