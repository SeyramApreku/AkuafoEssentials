<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');   // never show errors on screen
ini_set('log_errors', '1');       // still write them to the log

// TEMPORARY: set to true to see the real error message while testing on the server.
// Set it back to false before real users visit the site.
const DEBUG_MODE = true;

set_exception_handler(function ($e) {
    error_log($e);
    http_response_code(500);
    $msg = DEBUG_MODE ? 'DEBUG: ' . $e->getMessage() : 'Something went wrong. Please try again.';
    foreach (headers_list() as $h) {
        if (stripos($h, 'application/json') !== false) {
            echo json_encode(['success' => false, 'message' => $msg]);
            return;
        }
    }
    echo $msg;
});

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../settings/db_class.php';

// BASE_URL normally comes from settings/db_cred.php.
// Local:  define('BASE_URL', '/akuafo');
// Server: define('BASE_URL', '/~elisa.okyere/akuafo');
if (!defined('BASE_URL')) define('BASE_URL', '/akuafo');

const MIN_ORDER = 80;
const DELIVERY_FEE = 12;
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

function delivery_zones() { return (new Database())->fetchAll('SELECT id, name, fee FROM aku_delivery_zones ORDER BY name'); }

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
    $z = (new Database())->fetchOne('SELECT id, name, fee FROM aku_delivery_zones WHERE id = ?', [(int)$zoneId]);
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
        'delivery_zones' => ['label' => 'Delivery zones'],
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