<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');   // never show errors on screen
ini_set('log_errors', '1');       // still write them to the log

set_exception_handler(function ($e) {
    error_log($e);
    http_response_code(500);
    foreach (headers_list() as $h) {
        if (stripos($h, 'application/json') !== false) {
            echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
            return;
        }
    }
    echo 'Something went wrong. Please try again.';
});



if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../settings/db_class.php';

const MIN_ORDER = 80;
const DELIVERY_FEE = 12;
const PASS_PRICE = 49;

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }
function money($n) { return 'GH₵ ' . number_format($n, floor($n) == $n ? 0 : 2); }
function redirect($path) { header('Location: ' . $path); exit; }
function json_list($v) { return json_decode($v ?? '[]', true) ?: []; }

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

function member_tier()
{
    static $t = false;
    if ($t === false) {
        $t = null;
        if (!empty($_SESSION['customer_id'])) {
            $r = (new Database())->fetchOne(
                "SELECT tier FROM memberships WHERE user_id = ? AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT 1",
                [$_SESSION['customer_id']]
            );
            $t = $r['tier'] ?? null;
        }
    }
    return $t;
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

function partners() { return (new Database())->fetchAll('SELECT * FROM partners ORDER BY sort_order, id'); }

function is_admin() { return (int)($_SESSION['user_role'] ?? 0) === 1; }
function require_admin() { if (!is_admin()) redirect(url('index.php')); }

function admin_tables()
{
    return [
        'products' => ['label' => 'Products', 'dir' => 'products'],
        'farmers'  => ['label' => 'Farmers',  'dir' => 'farmers',  'commas' => ['produce', 'certifications']],
        'recipes'  => ['label' => 'Recipes',  'dir' => 'recipes',  'commas' => ['produce', 'tags'], 'lines' => ['ingredients', 'method']],
        'partners' => ['label' => 'Partners', 'dir' => 'partners'],
        'categories' => ['label' => 'Categories'],
        'regions'    => ['label' => 'Regions'],
    ];
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
    if (preg_match('#^https?://#', $src)) return $src;
    if (strpos($src, 'images/') === 0) return url($src);
    return "https://images.unsplash.com/$src?w=$w&h=$h&fit=crop&auto=format";
}

$publicPages = ['login.php', 'register.php', 'login_action.php', 'register_action.php'];
if (empty($_SESSION['customer_id']) && !in_array(basename($_SERVER['SCRIPT_NAME']), $publicPages, true)) {
    redirect(url('views/login.php'));
}