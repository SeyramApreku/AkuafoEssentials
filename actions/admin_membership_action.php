<?php
// Admin records a meal paid with a member's restaurant voucher: the amount comes off its balance.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/membership_class.php';
require_admin();

$back = url('views/admin/admin_memberships.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);

if (($_POST['act'] ?? '') === 'cancel') {   // admin ends a membership (e.g. at the member's request)
    $m = (new MembershipClass())->cancel((int)($_POST['id'] ?? 0));
    $_SESSION['admin_msg'] = $m ? ucfirst($m['tier']) . ' membership cancelled. Its benefits and voucher stopped now, and the member has been emailed.' : 'That membership is no longer active.';
    if (!$m) redirect($back);
    header('Location: ' . $back, true, 303);   // show the page now, email after
    finish_response();
    require_once __DIR__ . '/../core/membership_email.php';
    try { send_membership_cancelled($m, true); } catch (Throwable $e) { error_log('Cancel email: ' . $e->getMessage()); }
    exit;
}

$code = strtoupper(trim((string)($_POST['code'] ?? '')));
[$okAmount, $amount] = validate_field('money', $_POST['amount'] ?? '', 'Amount used');
[$okPlace, $restaurant] = validate_field('business_name', $_POST['restaurant'] ?? '', 'Restaurant');

if (!preg_match('/^AKU-[A-Z]-[A-Z0-9]{6}$/', $code)) $_SESSION['admin_msg'] = 'Please type the voucher code as it appears, e.g. AKU-G-7KQ2MX.';
elseif (!$okAmount) $_SESSION['admin_msg'] = $amount;
elseif (!$okPlace) $_SESSION['admin_msg'] = $restaurant;
else {
    [, $message] = (new MembershipClass())->redeem($code, $amount, $restaurant);
    $_SESSION['admin_msg'] = $message;
}
redirect($back);
