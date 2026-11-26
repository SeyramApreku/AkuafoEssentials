<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');   // never show errors on screen
ini_set('log_errors', '1');       // still write them to the log

ini_set('html_errors', '0');

// Visitors never see technical error details. Every error gets a short reference code that is
// written to the PHP error log together with the full details, so it can be looked up later.
// $detail: the technical reason. Shown ONLY to logged-in admins (customers never see it).
function friendly_error_page($ref, $schemaProblem = false, $detail = '')
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
       . ($admin && $detail !== '' ? '<p style="font-size:12px;margin:0 0 20px;padding:10px;background:#EDE6D6;text-align:left;font-family:monospace;word-break:break-word"><strong>For admins only:</strong> ' . htmlspecialchars($detail) . '</p>' : '')
       // A missing table/column means the database hasn't been updated yet: point admins (only) to the fix.
       . ($admin && $schemaProblem ? '<p style="font-size:14px;margin:0 0 20px;padding:12px;background:#E9D9A8">Admin: the database needs updating. <a href="' . htmlspecialchars($base) . '/views/admin/admin_setup.php" style="color:#B24008">Open Database update</a> and click <strong>Update database</strong>.</p>' : '')
       . '<a href="' . htmlspecialchars($base) . '/index.php" style="display:inline-block;background:#2B4A12;color:#F4EFE4;padding:12px 26px;text-decoration:none;font-size:14px">Back to the shop</a></div>';
}

set_exception_handler(function ($e) {
    $ref = strtoupper(bin2hex(random_bytes(3)));
    error_log("[error $ref] " . $e);
    friendly_error_page($ref, $e instanceof PDOException && in_array($e->getCode(), ['42S02', '42S22'], true),
                        $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
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
    friendly_error_page($ref, false, $e['message'] . ' (' . basename($e['file']) . ':' . $e['line'] . ')');
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
// 3 failed tries in a row for the same email + IP locks it for 10 minutes (db/login_lockout.sql).
const LOGIN_MAX_FAILS    = 3;
const LOGIN_LOCK_MINUTES = 10;
// Messages, used by actions/login_action.php (and the unit tests).
function login_lock_message()
{
    return "You've been locked out for " . LOGIN_LOCK_MINUTES . ' minutes due to too many failed login attempts. Please try again after ' . LOGIN_LOCK_MINUTES . ' minutes.';
}
function login_fail_message($failCount)
{
    $left = LOGIN_MAX_FAILS - (int)$failCount;
    if ($left <= 0) return login_lock_message();
    return 'Incorrect email or password. You have ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.';
}
// Kept only so an older actions/login_action.php still on the server keeps working (never crashes login).
function login_locked($emailFails, $ipFails = 0) { return (int)$emailFails >= LOGIN_MAX_FAILS; }
// "Try again in 7 minutes" (rounded up, so 30 seconds left still says 1 minute).
function login_wait_text($seconds)
{
    $m = max(1, (int)ceil($seconds / 60));
    return 'Try again in ' . $m . ' minute' . ($m === 1 ? '' : 's') . '.';
}
// ---------- Input validation: one rule per kind of field (unit tested in tests/validation_test.php) ----------
// validate_field($rule, $value, $label) returns [true, cleaned value] or [false, message for the customer].
// Letters include accents (\p{L}), so names like "Kome-Mensah" or "Ébène" are fine.
function validate_field($rule, $value, $label)
{
    $v = trim(preg_replace('/\s+/u', ' ', (string)$value));                 // tidy spaces
    $letters = preg_match_all('/\p{L}/u', $v);
    $len = mb_strlen($v, 'UTF-8');
    $bad = fn($msg) => [false, $msg];
    switch ($rule) {
        case 'person_name':      // e.g. Maame Afua Kome-Mensah, Kofi O'Neil, J. Asante
            if ($len < 2 || $len > 100) return $bad("$label must be between 2 and 100 characters.");
            if (!preg_match("/^[\p{L}][\p{L}\p{M} .'’-]*$/u", $v) || $letters < 2) return $bad("$label can only contain letters, spaces, hyphens and apostrophes (no numbers).");
            return [true, $v];
        case 'place':            // city, country, area, zone: e.g. Accra, Tema Community 25
            if ($len < 2 || $len > 100) return $bad("$label must be between 2 and 100 characters.");
            if (!preg_match("/^[\p{L}\p{M}\p{N} .,'’&()\/-]+$/u", $v) || $letters < 2) return $bad("$label must be a real place name (letters, not just numbers).");
            return [true, $v];
        case 'landmark':         // e.g. Opposite Shell, Oxford Street. Numbers allowed, but not only numbers.
            if ($len < 3 || $len > 150) return $bad("$label must be between 3 and 150 characters.");
            if (!preg_match("/^[\p{L}\p{M}\p{N} .,'’&()\/#:;+-]+$/u", $v) || $letters < 3) return $bad("$label should describe a real place you can see, e.g. \"Opposite Shell, Oxford Street\" (not just numbers).");
            return [true, $v];
        case 'address_text':     // event venue / address
            if ($len < 5 || $len > 255) return $bad("$label must be between 5 and 255 characters.");
            if ($letters < 3) return $bad("$label should describe the place in words, e.g. \"Trinity Hall, Osu\".");
            return [true, $v];
        case 'note':             // delivery notes, quotes: free text, but must say something
            if ($len > 600) return $bad("$label can be at most 600 characters.");
            if ($letters < 2) return $bad("$label should be written in words.");
            return [true, $v];
        case 'message':          // enquiry
            if ($len < 10 || $len > 2000) return $bad("$label must be between 10 and 2,000 characters.");
            if ($letters < 5) return $bad("$label should be written in words.");
            return [true, $v];
        case 'label':            // "Home", "Office 2"
            if ($len > 40) return $bad("$label can be at most 40 characters.");
            if (!preg_match("/^[\p{L}\p{M}\p{N} .'’&-]+$/u", $v) || $letters < 1) return $bad("$label should be a short name like \"Home\" or \"Office\".");
            return [true, $v];
        case 'title':            // job title: Head Chef
            if ($len < 2 || $len > 80) return $bad("$label must be between 2 and 80 characters.");
            if (!preg_match("/^[\p{L}\p{M} .,'’&\/-]+$/u", $v) || $letters < 2) return $bad("$label can only contain letters (no numbers).");
            return [true, $v];
        case 'business_name':    // Adom Kitchen, 12 Degrees Bistro
            if ($len < 2 || $len > 120) return $bad("$label must be between 2 and 120 characters.");
            if ($letters < 2) return $bad("$label must contain letters, not just numbers.");
            return [true, $v];
        case 'email':
            $v = strtolower($v);
            if ($len > 150 || !filter_var($v, FILTER_VALIDATE_EMAIL) || !preg_match('/@[^@]+\.[a-z]{2,}$/i', $v)) return $bad("Enter a valid email address, like name@example.com.");
            return [true, $v];
        /* ---- admin catalogue fields ---- */
        case 'money':            // price: more than 0, at most 2 decimals, e.g. 22 or 22.50
            $n = str_replace([',', 'GH₵', 'GHS', '₵'], '', $v);
            if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', trim($n)) || (float)$n <= 0) return $bad("$label must be more than 0, e.g. 22 or 22.50 (in GH₵).");
            if ((float)$n > 100000) return $bad("$label looks too high. Please check it (maximum GH₵ 100,000).");
            return [true, number_format((float)$n, 2, '.', '')];
        case 'whole_positive':   // serves, quantities: 1 or more
            if (!ctype_digit($v) || (int)$v < 1 || (int)$v > 100000) return $bad("$label must be a whole number of 1 or more.");
            return [true, (string)(int)$v];
        case 'whole':            // stock, sort order: 0 or more
            if (!ctype_digit($v) || (int)$v > 100000) return $bad("$label must be a whole number (0 or more).");
            return [true, (string)(int)$v];
        case 'decimal_positive': // hectares
            if (!is_numeric($v) || (float)$v <= 0 || (float)$v > 100000) return $bad("$label must be a number more than 0, e.g. 2.5.");
            return [true, (string)(float)$v];
        case 'product_name':     // White Yam, Kontomire (Cocoyam Leaves), Party Jollof Tray
            if ($len < 2 || $len > 120) return $bad("$label must be between 2 and 120 characters.");
            if (!preg_match("/^[\p{L}\p{M}\p{N} .,'’&()\/+-]+$/u", $v) || $letters < 2) return $bad("$label must be a real name in words (not just numbers or symbols).");
            return [true, $v];
        case 'unit':             // per kilo, per tuber (~1.5kg), per 500g pack
            if ($len < 3 || $len > 80) return $bad("$label must be between 3 and 80 characters, e.g. \"per kilo\".");
            if (!preg_match("/^[\p{L}\p{M}\p{N} .,~()\/+-]+$/u", $v) || $letters < 2) return $bad("$label should say how it's sold, e.g. \"per kilo\" or \"per tuber (~1.5kg)\".");
            return [true, $v];
        case 'duration':         // 45 min, 1 hr 20 min, 2 hours
            if (!preg_match('/^\d{1,3}\s*(min|mins|minutes|hr|hrs|hour|hours)(\s+\d{1,2}\s*(min|mins|minutes))?$/i', $v)) return $bad("$label should look like \"45 min\" or \"1 hr 20 min\".");
            return [true, $v];
        case 'difficulty':
            $d = ucfirst(strtolower($v));
            if (!in_array($d, ['Easy', 'Medium', 'Hard'], true)) return $bad("$label must be Easy, Medium or Hard.");
            return [true, $d];
        case 'description':      // product/farmer/recipe text
            if ($len < 10 || $len > 3000) return $bad("$label must be between 10 and 3,000 characters.");
            if ($letters < 5) return $bad("$label should be written in words.");
            return [true, trim(preg_replace('/[ \t]+/u', ' ', (string)$value))];   // keep line breaks (one step per line)
        case 'list':             // comma-separated or one-per-line words: White Yam, Fresh Tomatoes
            foreach (preg_split('/[,\n]+/', (string)$value) as $item) {
                $item = trim($item);
                if ($item !== '' && preg_match_all('/\p{L}/u', $item) < 2) return $bad("$label: \"$item\" should be a word, not just numbers.");
            }
            return [true, trim((string)$value)];
        case 'choice':           // a dropdown that must be picked
            if ($v === '' || $v === '0') return $bad("Please choose a $label.");
            return [true, $v];
        case 'percent':          // commission / premium rates: 0 to 100, e.g. 15 or 12.5
            $n = str_replace('%', '', $v);
            if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', trim($n)) || (float)$n > 100) return $bad("$label must be a percentage from 0 to 100, e.g. 15 or 12.5.");
            return [true, number_format((float)$n, 2, '.', '')];
        case 'phone_gh':         // 024 123 4567, +233 24 123 4567 => 0241234567
            $d = preg_replace('/[\s().-]/', '', $v);
            if (preg_match('/^(?:\+?233|0)([235]\d{8})$/', $d, $m)) return [true, '0' . $m[1]];
            return $bad("Enter a Ghana phone number like 024 123 4567 (10 digits starting with 02, 03 or 05).");
    }
    return [true, $v];
}

// Which form fields use which rule, per action. 'optional' fields may be left empty.
// Checked in one place below for every POST to actions/, before the action's own code runs.
function input_rules()
{
    $customer = ['customer_name' => ['person_name', 'Full name'], 'customer_email' => ['email', 'Email'],
                 'customer_country' => ['place', 'Country'], 'customer_city' => ['place', 'City'], 'customer_contact' => ['phone_gh', 'Phone number']];
    return [
        'register_action.php'       => ['fields' => $customer, 'reply' => 'json'],
        'admin_customer_action.php' => ['fields' => $customer, 'when' => ['act' => 'save'], 'reply' => ['admin_msg', 'text']],
        'address_action.php'        => ['when' => ['act' => 'save'], 'reply' => ['address_msg', 'pair'], 'old' => 'address_old', 'fields' => [
            'landmark' => ['landmark', 'Nearest landmark'], 'phone' => ['phone_gh', 'Phone for delivery'],
            'notes' => ['note', 'Delivery notes', 'optional'], 'label' => ['label', 'Address name', 'optional']]],
        'address_action.php#waitlist' => ['when' => ['act' => 'waitlist'], 'reply' => ['address_msg', 'pair'], 'fields' => ['area_name' => ['place', 'Your area']]],
        'checkout_action.php'       => ['reply' => ['checkout_errors', 'list'], 'fields' => ['event_address' => ['address_text', 'Event address', 'optional']]],
        'ent_order_action.php'      => ['when' => ['act' => 'change'], 'reply' => ['ent_order_msg', 'pair'], 'fields' => ['event_address' => ['address_text', 'Event address', 'optional']]],
        'enquiry_action.php'        => ['reply' => ['enquiry_msg', 'pair'], 'fields' => [
            'name' => ['person_name', 'Your name'], 'email' => ['email', 'Email'], 'message' => ['message', 'Your message']]],
        'admin_partner_action.php'  => ['when' => ['act' => 'save'], 'reply' => ['admin_msg', 'text'], 'fields' => [
            'name' => ['business_name', 'Name'], 'role' => ['title', 'Role', 'optional'],
            'restaurant_name' => ['business_name', 'Restaurant', 'optional'], 'quote' => ['note', 'Quote', 'optional']]],
        'admin_delivery_action.php' => ['when' => ['act' => ['save_zone', 'save_area']], 'reply' => ['admin_msg', 'text'], 'fields' => ['name' => ['place', 'Name']]],
        'admin_delivery_action.php#zone' => ['when' => ['act' => 'save_zone'], 'reply' => ['admin_msg', 'text'], 'fields' => ['fee' => ['money', 'Delivery fee']]],
        'admin_ent_items_action.php' => ['when' => ['act' => 'save'], 'reply' => ['admin_msg', 'text'], 'restore' => true, 'fields' => [
            'name' => ['product_name', 'Item name'], 'price' => ['money', 'Price'], 'serves' => ['whole_positive', 'Serves', 'optional'],
            'notice_hours' => ['whole', 'Minimum notice (hours)'], 'badge' => ['label', 'Badge', 'optional'],
            'description' => ['description', 'Description', 'optional'], 'contents' => ['list', "What's included", 'optional']]],
        'admin_box_save_action.php' => ['reply' => ['admin_msg', 'text'], 'restore' => true, 'fields' => [
            'name' => ['product_name', 'Box name'], 'tagline' => ['note', 'Tagline', 'optional'], 'note' => ['note', 'Note', 'optional'],
            'contents' => ['list', 'Contents', 'optional']]],
    ] + admin_table_rules();
}

// Rules for the generic catalogue editor (views/admin/admin_manage.php -> actions/admin_save_action.php),
// one set per table. Only the columns the form actually sends are checked ('present' => true).
function admin_table_rules()
{
    $tables = [
        'products' => ['name' => ['product_name', 'Product name'], 'twi' => ['product_name', 'Twi name', 'optional'], 'price' => ['money', 'Price'],
                       'unit' => ['unit', 'Unit'], 'category' => ['choice', 'category'], 'region' => ['choice', 'region'], 'farmer' => ['choice', 'farmer'],
                       'description' => ['description', 'Description', 'optional'], 'badge' => ['label', 'Badge', 'optional'],
                       'stock_qty' => ['whole', 'Stock quantity', 'optional'], 'low_stock_at' => ['whole', 'Warn when stock is at', 'optional']],
        'farmers'  => ['name' => ['person_name', "Farmer's name"], 'region' => ['choice', 'region'], 'location' => ['place', 'Location'],
                       'produce' => ['list', 'Produce', 'optional'], 'story' => ['description', 'Story', 'optional'],
                       'certifications' => ['list', 'Certifications', 'optional'], 'hectares' => ['decimal_positive', 'Hectares', 'optional'],
                       'season' => ['note', 'Season', 'optional'],
                       'plan' => ['choice', 'plan', 'optional'], 'supply_channel' => ['choice', 'supply channel', 'optional'],
                       'commission_rate' => ['percent', 'Commission rate', 'optional'], 'box_premium_rate' => ['percent', 'Box premium rate', 'optional'],
                       'payout_momo_number' => ['phone_gh', 'MoMo number', 'optional'], 'phone' => ['phone_gh', 'Phone', 'optional'],
                       'email' => ['email', 'Email', 'optional']],
        'recipes'  => ['name' => ['product_name', 'Recipe name'], 'subtitle' => ['product_name', 'Subtitle', 'optional'], 'time' => ['duration', 'Cooking time'],
                       'difficulty' => ['difficulty', 'Difficulty'], 'serves' => ['whole_positive', 'Serves'], 'ingredients' => ['list', 'Ingredients'],
                       'method' => ['description', 'Method'], 'produce' => ['list', 'Produce used', 'optional'], 'tags' => ['list', 'Tags', 'optional'], 'partner' => ['choice', 'partner restaurant', 'optional']],
        'categories'     => ['name' => ['product_name', 'Category name']],
        'regions'        => ['name' => ['place', 'Region name']],
        'ent_occasions'  => ['name' => ['product_name', 'Occasion name'], 'sort_order' => ['whole', 'Display order', 'optional']],
        'ent_categories' => ['name' => ['product_name', 'Category name'], 'sort_order' => ['whole', 'Display order', 'optional']],
    ];
    $out = [];
    foreach ($tables as $t => $fields) {
        $out["admin_save_action.php#$t"] = ['when' => ['t' => $t], 'present' => true, 'restore' => true, 'reply' => ['admin_msg', 'text'], 'fields' => $fields];
    }
    return $out;
}

// Typing help for admin forms: [example text, hint under the box, browser limits].
// Keys: 't:<table>' for the generic catalogue editor, or the action file name for other forms.
// Fields ending in [price] etc. (e.g. sizes[3][price]) match '[price]'. Applied by views/layout/admin_menu.php.
function admin_field_help()
{
    $money = ['min' => '0.01', 'step' => '0.01', 'required' => true, 'inputmode' => 'decimal'];
    $sort  = ['e.g. 1', 'Lower numbers show first.', ['min' => '0', 'step' => '1']];
    return [
        't:products' => [
            'name'  => ['e.g. White Yam', 'The name customers see. Words, not just numbers.', ['minlength' => '2', 'maxlength' => '120', 'required' => true]],
            'twi'   => ['e.g. Bayere', 'Optional: the Twi name.', ['maxlength' => '120']],
            'price' => ['e.g. 22.50', 'Price in GH₵ for one unit. Must be more than 0.', $money],
            'unit'  => ['e.g. per kilo, per tuber (~1.5kg)', 'How one unit is sold.', ['minlength' => '3', 'maxlength' => '80', 'required' => true]],
            'description' => ['e.g. Harvested in the dry season from the rich soils of Techiman…', 'One or two sentences about taste, origin or use.', ['maxlength' => '3000']],
            'badge' => ['e.g. Bestseller, In Season', 'Optional short label shown on the picture.', ['maxlength' => '40']],
            'stock_qty' => ['e.g. 50', 'How many are in stock. Leave empty to not track stock.', ['min' => '0', 'step' => '1']],
            'low_stock_at' => ['e.g. 5', 'Show "Only a few left" at or below this number.', ['min' => '0', 'step' => '1']],
        ],
        't:farmers' => [
            'name' => ['e.g. Kofi Asante', "The farmer's full name.", ['minlength' => '2', 'maxlength' => '100', 'required' => true]],
            'location' => ['e.g. Techiman', 'Town or village of the farm.', ['minlength' => '2', 'maxlength' => '100', 'required' => true]],
            'produce' => ['e.g. White Yam, Fresh Tomatoes', 'What they grow for us, separated by commas.', []],
            'story' => ['e.g. Kofi has farmed the same plot outside Techiman for 22 years…', 'A short story for the Our Farms page.', ['maxlength' => '3000']],
            'certifications' => ['e.g. Pesticide-free, Soil-tested annually', 'Separated by commas.', []],
            'hectares' => ['e.g. 4.5', 'Farm size in hectares (more than 0).', ['min' => '0.1', 'step' => '0.1']],
            'season' => ['e.g. Oct - Feb (Yam)', 'When their produce is in season.', ['maxlength' => '80']],
            'plan' => ['', farmer_plan_help(), []],
            'supply_channel' => ['', 'How they earn with us. both = sells in the Farm Shop AND supplies our boxes. shop = Farm Shop only. box = supplies boxes/hampers only.', []],
            'commission_rate' => ['e.g. 15', "% Akuafo keeps from each Farm Shop sale. Leave empty to use the plan's rate (it updates when you change the plan).", ['min' => '0', 'max' => '100', 'step' => '0.01', 'inputmode' => 'decimal']],
            'box_premium_rate' => ['e.g. 10', '% Akuafo pays ON TOP of the farmgate price when buying their produce for boxes. Leave empty for 10%.', ['min' => '0', 'max' => '100', 'step' => '0.01', 'inputmode' => 'decimal']],
            'payout_momo_number' => ['e.g. 024 123 4567', 'Mobile money number their weekly payout (every Friday) is sent to.', ['inputmode' => 'tel']],
            'phone' => ['e.g. 024 123 4567', 'To call or WhatsApp the farmer.', ['inputmode' => 'tel']],
            'email' => ['e.g. kofi@example.com', 'Optional. For payout statements.', ['maxlength' => '150']],
        ],
        't:recipes' => [
            'members_only' => ['0', 'Type 1 to make this an exclusive members-only recipe, or 0 for everyone.', ['min' => '0', 'max' => '1', 'step' => '1']],
            'partner' => ['', 'The partner restaurant or hotel whose chef created this recipe. Members-only recipes should have one.', []],
            'name' => ['e.g. Kontomire Stew', 'Recipe name.', ['minlength' => '2', 'maxlength' => '120', 'required' => true]],
            'subtitle' => ['e.g. Palava Sauce', 'Optional second name.', ['maxlength' => '120']],
            'time' => ['e.g. 45 min, 1 hr 20 min', 'Total cooking time.', ['required' => true, 'maxlength' => '20']],
            'difficulty' => ['Easy, Medium or Hard', '', ['required' => true]],
            'serves' => ['e.g. 4', 'Number of people (1 or more).', ['min' => '1', 'step' => '1', 'required' => true]],
            'ingredients' => ["e.g.\n2 large bunches kontomire\n500g smoked fish", 'One ingredient per line.', ['required' => true]],
            'method' => ["e.g.\nWash and chop the kontomire.\nBoil for 10 minutes.", 'One step per line.', ['required' => true]],
            'produce' => ['e.g. Kontomire, Garden Eggs', 'Our products used in this recipe, separated by commas.', []],
            'tags' => ['e.g. Quick, Vegan, Traditional', 'Separated by commas.', []],
        ],
        't:categories'     => ['name' => ['e.g. Leafy Greens', 'Category name.', ['minlength' => '2', 'required' => true]]],
        't:regions'        => ['name' => ['e.g. Ashanti', 'Region name.', ['minlength' => '2', 'required' => true]]],
        't:ent_occasions'  => ['name' => ['e.g. Christmas', 'Occasion name.', ['minlength' => '2', 'required' => true]], 'sort_order' => $sort],
        't:ent_categories' => ['name' => ['e.g. Small chops', 'Category name.', ['minlength' => '2', 'required' => true]], 'sort_order' => $sort],
        'admin_ent_items_action.php' => [
            'price' => ['e.g. 420', 'Price in GH₵. Must be more than 0.', $money],
            'serves' => ['e.g. 15', 'People one unit feeds (1 or more). Leave empty for bulk crates.', ['min' => '1', 'step' => '1']],
        ],
        'admin_box_sizes_action.php' => [
            '[label]' => ['e.g. Small', '', ['required' => true, 'minlength' => '2']],
            '[description]' => ['e.g. 1-2 people', '', []],
            '[price]' => ['e.g. 68', 'Weekly price in GH₵ (more than 0).', $money],
            '[items]' => ['e.g. 5-6 items', '', []],
        ],
        'admin_box_save_action.php' => [
            'name' => ['e.g. The Veg Box', '', ['minlength' => '2', 'required' => true]],
            'tagline' => ['e.g. Seasonal Ghanaian vegetables, every week', '', ['maxlength' => '160']],
            'color' => ['e.g. #2B4A12', 'Colour of the card, as a hex code.', ['pattern' => '#[0-9A-Fa-f]{6}']],
        ],
        'admin_delivery_action.php' => ['fee' => ['e.g. 15', 'Delivery fee in GH₵ (more than 0).', $money]],
        'admin_customer_action.php' => ['customer_contact' => ['e.g. 024 123 4567', 'Ghana phone number.', ['inputmode' => 'tel']]],
    ];
}

// Checks the fields of one action and cleans $post in place.
// Returns [messages, the matching rule set] ([] and null when nothing applies or all is fine).
function validate_input($script, array &$post)
{
    $errors = []; $matched = null;
    foreach (input_rules() as $key => $spec) {
        if (explode('#', $key)[0] !== $script) continue;
        $ok = true;
        foreach ($spec['when'] ?? [] as $f => $want) $ok = $ok && in_array($post[$f] ?? '', (array)$want, true);
        if (!$ok) continue;
        $matched = $spec;
        foreach ($spec['fields'] as $field => $r) {
            if (!empty($spec['present']) && !array_key_exists($field, $post)) continue;   // column not on this form
            $value = $post[$field] ?? '';
            if (is_array($value)) { $errors[] = $r[1] . ' is not valid.'; continue; }
            if (trim((string)$value) === '' && ($r[2] ?? '') === 'optional') { $post[$field] = ''; continue; }
            if (trim((string)$value) === '') { $errors[] = $r[1] . ' is required.'; continue; }
            [$good, $out] = validate_field($r[0], $value, $r[1]);
            if ($good) $post[$field] = $out; else $errors[] = $out;
        }
    }
    return [$errors, $matched];
}

// Refuses a form that failed validate_input(): the message goes where that page normally shows its
// messages, the customer's typing is kept where the page supports it, and they go back to the form.
function reject_input(array $errors, array $spec, array $raw)
{
    $reply = $spec['reply'];
    if ($reply === 'json') {
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => $errors[0]]));
    }
    [$key, $shape] = $reply;
    $_SESSION[$key] = $shape === 'list' ? $errors : ($shape === 'pair' ? [false, implode(' ', $errors)] : implode(' ', $errors));
    if (!empty($spec['old'])) $_SESSION[$spec['old']] = array_map(fn($v) => is_string($v) ? $v : '', $raw);
    if (!empty($spec['restore'])) $_SESSION['admin_restore'] = 1;   // admin forms refill what was typed (admin_menu.php)
    // Back to the page the form was on (only if it's this site), otherwise home.
    $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $p = parse_url($ref);
    $same = $ref !== '' && ($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : '') === ($_SERVER['HTTP_HOST'] ?? '')
            && strpos($p['path'] ?? '', BASE_URL . '/') === 0;
    redirect($same ? $ref : url('index.php'));
}

// ---------- Password rule (registration) ----------
// 8-72 characters with an uppercase letter, a lowercase letter, a number and a symbol.
// ANY symbol counts (- _ . # @ ! space ...) and any characters are allowed.
// Returns '' when fine, otherwise a plain sentence saying what's missing. js/customer.js mirrors this.
function password_problem($pass, $confirm = null)
{
    $pass = (string)$pass;
    $len  = mb_strlen($pass, 'UTF-8');
    if ($len < 8)  return 'Your password needs at least 8 characters.';
    if ($len > 72) return 'Your password can be at most 72 characters.';   // bcrypt only uses the first 72
    $missing = [];
    if (!preg_match('/\p{Lu}/u', $pass))        $missing[] = 'an uppercase letter';
    if (!preg_match('/\p{Ll}/u', $pass))        $missing[] = 'a lowercase letter';
    if (!preg_match('/\p{N}/u', $pass))         $missing[] = 'a number';
    if (!preg_match('/[^\p{L}\p{N}]/u', $pass)) $missing[] = 'a symbol (for example - _ ! @ #)';
    if ($missing) {
        $last = array_pop($missing);
        return 'Your password needs ' . ($missing ? implode(', ', $missing) . ' and ' : '') . $last . '.';
    }
    if ($confirm !== null && $confirm !== $pass) return 'The two passwords do not match.';
    return '';
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
// ---------- Input validation for every form posted to actions/ (rules: input_rules() / validate_field()) ----------
if (PHP_SAPI !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && strpos(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ''), '/actions/') !== false) {
    $rawPost = $_POST;
    [$inputErrors, $inputSpec] = validate_input(basename($_SERVER['SCRIPT_FILENAME']), $_POST);
    if ($inputErrors) reject_input($inputErrors, $inputSpec, $rawPost);
    unset($rawPost, $inputErrors, $inputSpec);
}

const DELIVERY_FEE = 12;   // shown in the cart drawer until checkout knows the zone (replaced in D4)
const PASS_PRICE = 49;

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
// '%word%' for SQL LIKE, with % and _ in the search typed by the customer treated as plain characters.
function like_pattern($word) { return '%' . addcslashes((string)$word, '%_\\') . '%'; }

function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }
// URL for a CSS/JS file with its last-change time attached (css/style.css?v=1732540810), so browsers
// fetch the new copy as soon as the file is updated instead of reusing an old cached one.
function asset($path)
{
    $file = __DIR__ . '/../' . ltrim($path, '/');
    return url($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}
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

// ---------- Breadcrumbs ("Home › The Produce › White Yam") ----------
// Where each customer page sits: page => [label, parent page]. Shown by views/layout/header.php.
function breadcrumb_map()
{
    return [
        'views/shop.php'               => ['The Produce', 'index.php'],
        'views/product.php'            => [null, 'views/shop.php'],
        'views/boxes.php'              => ['Weekly Boxes', 'index.php'],
        'views/custom_box.php'         => ['Build your own box', 'views/boxes.php'],
        'views/preorder.php'           => ['Entertaining', 'index.php'],
        'views/entertaining_item.php'  => [null, 'views/preorder.php'],
        'views/recipes.php'            => ['Recipes', 'index.php'],
        'views/member_recipes.php'     => ['My recipes', 'views/recipes.php'],
        'views/farms.php'              => ['Our Farms', 'index.php'],
        'views/testimonials.php'       => [null, 'index.php'],
        'views/membership.php'         => ['Membership', 'index.php'],
        'views/membership_checkout.php'=> ['Checkout', 'views/membership.php'],
        'views/checkout.php'           => ['Checkout', 'index.php'],
        'views/order_summary.php'      => ['Order summary', 'views/checkout.php'],
        'views/orders.php'             => ['My orders', 'index.php'],
        'views/invoice.php'            => ['Invoice', 'views/orders.php'],
        'views/order_success.php'      => ['Order confirmed', 'views/orders.php'],
        'views/payment_failed.php'     => ['Payment not completed', 'views/orders.php'],
        'views/ent_order.php'          => ['Event order', 'views/orders.php'],
        'views/rate.php'               => ['Rate your order', 'views/orders.php'],
        'views/addresses.php'          => ['My addresses', 'index.php'],
        'views/subscriptions.php'      => ['My subscriptions', 'index.php'],
    ];
}
// The trail for a page: [[label, url or null for the current page], ...]. [] on the home page or unknown pages.
// $title is the page's own name (from $pageTitle), used for detail pages like a product or a farmer.
function breadcrumbs($page, $title = '', $isDetail = false)
{
    $map = breadcrumb_map();
    if (!isset($map[$page])) return [];
    $title = trim(preg_replace('/\s*\|.*$/u', '', (string)$title));   // "White Yam | Akuafo & Essentials" => "White Yam"
    [$label, $parent] = $map[$page];
    $trail = [];
    if ($isDetail && $label !== null) {          // e.g. one farmer on farms.php?id=3: Our Farms › Kofi Asante
        $trail[] = [$title !== '' ? $title : $label, null];
        $trail[] = [$label, url($page)];
    } else {
        $trail[] = [$label ?? ($title !== '' ? $title : 'This page'), null];
    }
    for ($p = $parent, $guard = 0; $p && $p !== 'index.php' && isset($map[$p]) && $guard < 5; $guard++) {
        $trail[] = [$map[$p][0] ?? 'Back', url($p)];
        $p = $map[$p][1];
    }
    $trail[] = ['Home', url('index.php')];
    return array_reverse($trail);
}

function membership_tiers()
{
    return [
        ['name' => 'Silver',   'price' => 99,  'voucher' => 300,  'delivery' => 300,  'early' => '24 hours', 'discount' => 5,  'recipes' => 1, 'color' => '#8A8D91'],
        ['name' => 'Gold',     'price' => 199, 'voucher' => 600,  'delivery' => 600,  'early' => '48 hours', 'discount' => 10, 'recipes' => 2, 'color' => '#B8892B'],
        ['name' => 'Platinum', 'price' => 349, 'voucher' => 1000, 'delivery' => 1000, 'early' => '72 hours', 'discount' => 15, 'recipes' => 4, 'color' => '#4A5560'],
    ];
}

// Upgrading mid-month: pay the difference, keep the same end date. Returns the price, or null if it isn't an upgrade.
function membership_upgrade_price($fromTier, $toTier)
{
    $names = array_map(fn($t) => strtolower($t['name']), membership_tiers());
    $from = array_search(strtolower((string)$fromTier), $names, true);
    $to = array_search(strtolower((string)$toTier), $names, true);
    if ($from === false || $to === false || $to <= $from) return null;
    return membership_tiers()[$to]['price'] - membership_tiers()[$from]['price'];
}
// Higher tiers a member can move up to: [tier info + 'upgrade_price', ...]
function membership_upgrades($currentTier)
{
    $out = [];
    foreach (membership_tiers() as $t) {
        $p = membership_upgrade_price($currentTier, $t['name']);
        if ($p !== null) $out[] = $t + ['upgrade_price' => $p];
    }
    return $out;
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

// ---------- Weekly box subscriptions ----------
// A box is paid upfront for a number of weeks; the number of boxes depends on how often it comes.
const BOX_REMIND_DAYS = 3;   // reminder email this many days before the last delivery
function box_durations() { return [4 => '1 month', 8 => '2 months', 12 => '3 months']; }   // 4, 8, 12 weeks
function box_step_weeks($freq) { return ['weekly' => 1, 'biweekly' => 2, 'monthly' => 4][$freq] ?? 1; }
function box_delivery_count($freq, $weeks) { return max(1, intdiv((int)$weeks, box_step_weeks($freq))); }
// "4 deliveries over 4 weeks, paid upfront"
function box_plan_label($freq, $weeks)
{
    $n = box_delivery_count($freq, $weeks);
    return $n . ' deliver' . ($n === 1 ? 'y' : 'ies') . ' over ' . (int)$weeks . ' weeks, paid upfront';
}
// The plan is kept in the cart item's key (…-w8-biweekly-…). Boxes bought before plans existed count as one week.
function box_plan_from_key($key)
{
    if (preg_match('/-w(\d+)-(weekly|biweekly|monthly)(-|$)/', (string)$key, $m)) return [$m[2], (int)$m[1]];
    return ['weekly', 1];
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

// The admin's monthly choice of exclusive recipes per tier (Admin > Member Recipes): ['silver' => [ids], ...].
function member_recipe_picks()
{
    $out = [];
    foreach (membership_tiers() as $t) {
        $key = strtolower($t['name']);
        $ids = json_decode((string)app_setting('member_recipes_' . $key, ''), true);
        $out[$key] = is_array($ids) ? array_map('intval', $ids) : null;   // null = not chosen yet
    }
    return $out;
}
// Exclusive recipes: each tier opens the recipes the admin picked for it this month (Silver 1, Gold 2, Platinum 4).
// Until the admin has picked, the newest members-only recipes are used.
// Returns [all members-only ids, ids this visitor may open]. Admins can open every recipe.
function member_recipe_access()
{
    static $out = null;
    if ($out !== null) return $out;
    require_once __DIR__ . '/../classes/catalog_class.php';
    $all = (new CatalogClass())->memberRecipeIds();
    if (is_admin()) return $out = [$all, $all];
    $tier = member_tier() ? tier_info(member_tier()) : null;
    if (!$tier) return $out = [$all, []];
    $picked = member_recipe_picks()[strtolower($tier['name'])] ?? null;
    $open = $picked !== null ? array_values(array_intersect($picked, $all)) : array_slice($all, 0, (int)$tier['recipes']);
    return $out = [$all, array_slice($open, 0, (int)$tier['recipes'])];
}

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

// Partner restaurant/hotel names (the business, not the chef), A to Z. Used for recipe credits and vouchers.
// Farmer plans (aku_farmer_plans): ['basic' => ['name' => 'Basic', 'monthly_fee' => '0.00', 'commission_rate' => '15.00', ...], ...]
function farmer_plans()
{
    static $plans = null;
    if ($plans !== null) return $plans;
    try {
        $plans = [];
        foreach ((new Database())->fetchAll('SELECT * FROM aku_farmer_plans ORDER BY sort_order, id') as $p) $plans[$p['code']] = $p;
    } catch (Throwable $e) { $plans = []; }
    if (!$plans) $plans = ['basic' => ['name' => 'Basic', 'monthly_fee' => 0, 'commission_rate' => 15],
                           'grower' => ['name' => 'Grower', 'monthly_fee' => 100, 'commission_rate' => 10],
                           'partner' => ['name' => 'Partner Farm', 'monthly_fee' => 250, 'commission_rate' => 7]];
    return $plans;
}
// ---------- Farmer payouts (weekly, Saturday to Friday, paid on the Friday) ----------
// One farmer's week. Nothing is rounded until the very end.
//   $sales     Farm Shop lines from orders DELIVERED that week: [['amount' => price x qty, 'rate' => commission %], ...]
//   $box       what Akuafo owes for produce bought for boxes that week
//   $pickups, $qc, $planFee   deductions; $carriedIn = what the farmer still owed from last week
// If deductions are bigger than earnings the farmer is paid 0 and the rest is carried to next week.
function farmer_payout_calc(array $sales, $box = 0, $pickups = 0, $qc = 0, $planFee = 0, $carriedIn = 0)
{
    $gross = 0.0; $commission = 0.0;
    foreach ($sales as $s) {
        $gross += (float)$s['amount'];
        $commission += (float)$s['amount'] * (float)$s['rate'] / 100;
    }
    $shop = $gross - $commission;
    $net = $shop + (float)$box - (float)$pickups - (float)$qc - (float)$planFee - (float)$carriedIn;
    $r = fn($n) => round($n + 0.0, 2);
    return [
        'gross_sales' => $r($gross), 'commission' => $r($commission), 'shop_earnings' => $r($shop),
        'box_supply_value' => $r($box), 'pickup_fees' => $r($pickups), 'qc_deductions' => $r($qc),
        'plan_fee' => $r($planFee), 'carried_in' => $r($carriedIn),
        'net_payout' => $net > 0 ? $r($net) : 0.0, 'carried_out' => $net < 0 ? $r(-$net) : 0.0,
    ];
}
// Farm Shop money for a date range, split automatically by each farmer's plan (used by Revenue and Farmer Payouts).
//   gross        = farmers' produce paid for by customers (paid, not cancelled; by the day the order was placed)
//   commission   = what Akuafo keeps from those sales (each item's saved rate)
//   farmer_share = gross - commission;  plan_fees = farmers' monthly plan fees taken from payouts in the range
//   to_farmers   = what actually goes to farmers (farmer_share - plan_fees)
function farmer_split_totals($db, $from, $to)
{
    $out = ['gross' => 0.0, 'commission' => 0.0, 'farmer_share' => 0.0, 'plan_fees' => 0.0, 'to_farmers' => 0.0];
    try {
        $r = $db->fetchOne("SELECT COALESCE(SUM(i.price * i.qty), 0) AS gross,
                                   COALESCE(SUM(i.price * i.qty * COALESCE(i.commission_rate, f.commission_rate) / 100), 0) AS commission
                              FROM aku_order_items i JOIN aku_orders o ON o.id = i.order_id JOIN aku_farmers f ON f.id = i.farmer_id
                             WHERE o.payment_status = 'paid' AND o.status <> 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?", [$from, $to]);
        $fees = $db->fetchOne('SELECT COALESCE(SUM(plan_fee), 0) AS v FROM aku_farmer_payouts WHERE period_end BETWEEN ? AND ?', [$from, $to]);
        $out['gross'] = round((float)$r['gross'], 2);
        $out['commission'] = round((float)$r['commission'], 2);
        $out['farmer_share'] = round((float)$r['gross'] - (float)$r['commission'], 2);
        $out['plan_fees'] = round((float)$fees['v'], 2);
        $out['to_farmers'] = max(0, round($out['farmer_share'] - $out['plan_fees'], 2));
    } catch (Throwable $e) { error_log('Farmer split: ' . $e->getMessage()); }   // before Database Update: shows 0
    return $out;
}

// Box supply price per unit: farmgate + premium %, but never below the agreed minimum.
function farmer_agreed_price($farmgate, $premiumRate, $minPrice = null)
{
    $p = (float)$farmgate * (1 + (float)$premiumRate / 100);
    if ($minPrice !== null && $minPrice !== '' && (float)$minPrice > $p) $p = (float)$minPrice;
    return round($p, 2);
}
// The payout week a date falls in: [Saturday, Friday] as Y-m-d.
function payout_week($date = 'today')
{
    $t = strtotime(date('Y-m-d', strtotime($date)));
    $start = strtotime('-' . (((int)date('w', $t) + 1) % 7) . ' days', $t);
    return [date('Y-m-d', $start), date('Y-m-d', strtotime('+6 days', $start))];
}

// "basic = free, 15% commission · grower = GH₵ 100/month, 10% · ..."
function farmer_plan_help()
{
    $bits = [];
    foreach (farmer_plans() as $code => $p) {
        $bits[] = $code . ' = ' . ((float)$p['monthly_fee'] > 0 ? money($p['monthly_fee']) . '/month' : 'free') . ', ' . (float)$p['commission_rate'] . '% commission';
    }
    return 'Farm Shop plan. ' . implode(' · ', $bits) . '. The monthly fee is taken from their payout.';
}

function partner_names()
{
    static $names = null;
    if ($names !== null) return $names;
    try { $names = array_column((new Database())->fetchAll("SELECT DISTINCT COALESCE(NULLIF(TRIM(restaurant_name), ''), TRIM(name)) AS b FROM aku_partners ORDER BY b"), 'b'); }
    catch (Throwable $e) { $names = []; }
    return $names;
}
// How many members-only recipes each partner has created: ['Adom Kitchen' => 2, ...].
function partner_recipe_counts()
{
    try { return array_column((new Database())->fetchAll("SELECT partner, COUNT(*) AS n FROM aku_recipes WHERE members_only = 1 AND partner IS NOT NULL AND partner <> '' GROUP BY partner"), 'n', 'partner'); }
    catch (Throwable $e) { return []; }
}
// Homepage "Our clients": the first 4 partners that have a logo.
function top_partners($limit = 4)
{
    try { return (new Database())->fetchAll("SELECT * FROM aku_partners WHERE image IS NOT NULL AND image <> '' ORDER BY sort_order, id LIMIT " . (int)$limit); }
    catch (Throwable $e) { return []; }
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
        'farmers'  => ['label' => 'Farmers',  'dir' => 'farmers',  'commas' => ['produce', 'certifications'],
                       'choices' => ['plan' => array_keys(farmer_plans()), 'supply_channel' => ['both', 'shop', 'box']]],
        'recipes'  => ['label' => 'Recipes',  'dir' => 'recipes',  'commas' => ['produce', 'tags'], 'lines' => ['ingredients', 'method'], 'choices' => ['partner' => partner_names()]],
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
const IMAGE_MAX_MB = 5;   // admin pages also shrink big photos in the browser before uploading

// Why the last save_image() call refused a picture (shown to the admin).
function save_image_error() { return $GLOBALS['__save_image_error'] ?? 'That picture could not be saved. Please try a JPG, PNG or WebP image.'; }

// $root: 'images' (catalogue pictures) or 'uploads' (e.g. partner portraits in uploads/partners/).
// Returns the saved path, null when no file was chosen, or false (then save_image_error() says why).
function save_image($file, $dir, $root = 'images')
{
    $fail = function ($why) { $GLOBALS['__save_image_error'] = $why; return false; };
    $err  = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if (empty($file['name']) || $err === UPLOAD_ERR_NO_FILE) return null;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE)
        return $fail('That picture is too large for the server (limit ' . ini_get('upload_max_filesize') . 'B). Please use a smaller photo.');
    if ($err !== UPLOAD_ERR_OK) return $fail('The picture did not upload completely. Please try again.');
    if ($file['size'] > IMAGE_MAX_MB * 1024 * 1024)
        return $fail('That picture is ' . round($file['size'] / 1048576, 1) . ' MB. Please use one under ' . IMAGE_MAX_MB . ' MB.');
    // Work out the real type from the file's contents (getimagesize needs no extra server extension).
    $info = @getimagesize($file['tmp_name']);
    $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$info['mime'] ?? ''] ?? null;
    if (!$ext) {
        $name = strtolower((string)$file['name']);
        if (preg_match('/\.(heic|heif)$/', $name)) return $fail('This is an iPhone HEIC photo. Please export it as JPG (or set Camera > Formats > Most Compatible) and upload again.');
        return $fail('That file isn\'t a picture we can use. Please upload a JPG, PNG or WebP image.');
    }
    $root = $root === 'uploads' ? 'uploads' : 'images';
    $dir  = preg_replace('/[^a-z0-9_-]/', '', strtolower($dir));
    $folder = __DIR__ . "/../$root/" . $dir;
    if (!is_dir($folder) && !@mkdir($folder, 0755, true)) return $fail("The server couldn't create the $root/$dir folder. Ask your host to make $root/ writable.");
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    return @move_uploaded_file($file['tmp_name'], "$folder/$name") ? "$root/$dir/$name"
         : $fail("The server couldn't save the picture into $root/$dir. Ask your host to make that folder writable.");
}

function delete_image($path)
{
    $path = (string)$path;
    // Shared placeholder images (images/*/placeholder.*) are used by many rows: never delete them.
    if (strpos(basename($path), 'placeholder.') === 0) return;
    if ($path && (strpos($path, 'images/') === 0 || strpos($path, 'uploads/') === 0) && strpos($path, '..') === false) {
        $f = __DIR__ . '/../' . $path;
        if (is_file($f)) unlink($f);
    }
}

const PARTNER_PLACEHOLDER = 'uploads/partners/placeholder.svg';   // shown when a partner has no portrait

// Partner testimonials (partners with a quote), managed in Admin > Partners.
// Before db/partners_testimonials.sql has run, falls back to the original three quotes.
function testimonials()
{
    try {
        $rows = (new Database())->fetchAll("SELECT name, role, restaurant_name, quote, photo FROM aku_partners WHERE quote IS NOT NULL AND quote <> '' ORDER BY sort_order, id");
        return array_map(fn($r) => ['name' => $r['name'], 'role' => (string)$r['role'], 'business' => trim((string)$r['restaurant_name']) ?: $r['name'],
                                    'quote' => $r['quote'], 'photo' => $r['photo'] ?: PARTNER_PLACEHOLDER], $rows);
    } catch (PDOException $e) {
        return [
            ['name' => 'Kwame Mensah', 'role' => 'Head Chef',      'business' => 'Adom Kitchen',    'photo' => PARTNER_PLACEHOLDER, 'quote' => 'Our kontomire arrives the morning it was picked, with the farm named on the crate. Guests taste the difference.'],
            ['name' => 'Efua Boateng', 'role' => 'Owner',          'business' => 'Sankofa Table',   'photo' => PARTNER_PLACEHOLDER, 'quote' => 'Weekly delivery slots let me plan the menu around what is in season, not what the market happens to have.'],
            ['name' => 'Yaw Darko',    'role' => 'Executive Chef', 'business' => 'Volta Bay Lodge', 'photo' => PARTNER_PLACEHOLDER, 'quote' => 'Consistent quality for a full hotel kitchen was the hard part. Akuafo made it routine.'],
        ];
    }
}

function img($src, $w = 600, $h = 600)
{
    $src = (string)$src;   // avoids the strpos(null) deprecation when an image is missing
    if (preg_match('#^https?://#', $src)) return $src;
    if (strpos($src, 'images/') === 0 || strpos($src, 'uploads/') === 0) return url($src);
    return "https://images.unsplash.com/$src?w=$w&h=$h&fit=crop&auto=format";
}