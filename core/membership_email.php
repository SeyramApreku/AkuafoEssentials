<?php
// Membership welcome email. Loaded by actions/membership_callback.php after Paystack confirms the payment.
require_once __DIR__ . '/core.php';

// "Welcome to Gold": sent once, right after Paystack confirms a membership payment.
// Lists the plan's benefits, the voucher code, and the link to manage or cancel.
if (!function_exists('send_membership_welcome')) {
function send_membership_welcome($membership)
{
    require_once __DIR__ . '/../core/mailer.php';
    require_once __DIR__ . '/../classes/membership_class.php';
    $tier = tier_info($membership['tier']);
    if (!$tier) return false;
    $mc = new MembershipClass();
    $fresh = $mc->byRef($membership['order_ref']) ?: $membership;
    $voucher = $mc->voucherFor($fresh);
    $who = (new Database())->fetchOne('SELECT customer_name, customer_email FROM aku_customer WHERE customer_id = ?', [$fresh['user_id']]);
    if (!$who) return false;
    $until = !empty($fresh['expires_at']) ? date('j F Y', strtotime($fresh['expires_at'])) : '';
    $perks = [
        money($tier['voucher']) . ' voucher to spend at our partner restaurants' . ($voucher ? ' (your code: <strong>' . e($voucher['code']) . '</strong>)' : ''),
        money($tier['delivery']) . ' delivery credit this month, taken off at checkout automatically',
        $tier['recipes'] . ' exclusive recipe' . ($tier['recipes'] === 1 ? '' : 's') . ' a month from our partner restaurants',
        'Early access to new stock, ' . $tier['early'] . ' before everyone else',
        $tier['discount'] . '% off early-access stock',
    ];
    $list = '';
    foreach ($perks as $p) $list .= '<li style="margin:0 0 8px;">' . $p . '</li>';
    $html = email_layout('<tr><td style="padding:24px 30px;font-size:14px;line-height:1.6;">'
        . '<h2 style="font-family:Georgia,serif;font-weight:normal;margin:0 0 10px;">Welcome to ' . e($tier['name']) . ' membership</h2>'
        . '<p>Hi ' . e($who['customer_name']) . ',</p>'
        . '<p>Welcome to the Akuafo community! You are now a <strong>' . e($tier['name']) . ' member</strong>. Your <strong>' . e($tier['name']) . '</strong> membership (' . money($tier['price']) . ') is active' . ($until ? ' until <strong>' . e($until) . '</strong>' : '') . '. Here is what you get as a member:</p>'
        . '<ul style="padding-left:18px;margin:14px 0 18px;">' . $list . '</ul>'
        . '<p>Your delivery credit is taken off automatically at checkout, so the best way to start is to fill your basket.</p>'
        . '<p style="margin-top:18px;">' . email_button(email_link('views/shop.php'), 'Start shopping') . ' '
        . email_button(email_link('views/membership.php'), 'My membership', false) . ' '
        . email_button(email_link('views/member_recipes.php'), 'My recipes', false) . '</p>'
        . '<p style="font-size:12px;color:#6B6050;margin-top:22px;">Memberships do not renew automatically. Changed your mind? '
        . '<a href="' . e(email_link('views/membership.php?manage=1')) . '" style="color:#B24008;">Cancel your membership</a> any time from My membership.</p>'
        . '</td></tr>');
    return send_mail($who['customer_email'], $who['customer_name'], 'Welcome to ' . $tier['name'] . ' membership | Akuafo & Essentials', $html);
}
}

// "Your membership has been cancelled": sent when the member cancels, or when an admin cancels it for them.
if (!function_exists('send_membership_cancelled')) {
function send_membership_cancelled($membership, $byAdmin = false)
{
    require_once __DIR__ . '/mailer.php';
    $tier = tier_info($membership['tier']);
    $who = (new Database())->fetchOne('SELECT customer_name, customer_email FROM aku_customer WHERE customer_id = ?', [$membership['user_id']]);
    if (!$tier || !$who || empty($who['customer_email'])) return false;
    $html = email_layout('<tr><td style="padding:24px 30px;font-size:14px;line-height:1.6;">'
        . '<h2 style="font-family:Georgia,serif;font-weight:normal;margin:0 0 10px;">Your membership has been cancelled</h2>'
        . '<p>Hi ' . e($who['customer_name']) . ',</p>'
        . '<p>Your <strong>' . e($tier['name']) . '</strong> membership ended today' . ($byAdmin ? '' : ', as you asked') . '. '
        . 'From now on, these benefits are no longer available:</p>'
        . '<ul style="padding-left:18px;margin:12px 0 16px;">'
        . '<li>Your partner restaurant voucher (the code no longer works)</li>'
        . '<li>Your monthly delivery credit</li>'
        . '<li>Your exclusive recipes and early access to new stock</li>'
        . '</ul>'
        . '<p>Memberships are not refunded for the rest of the month. ' . ($byAdmin ? 'If you did not ask for this, just reply to this email and we will sort it out. ' : '') . 'We would love to have you back: you can join again whenever you like.</p>'
        . '<p style="margin-top:18px;">' . email_button(email_link('views/membership.php'), 'See membership plans') . ' '
        . email_button(email_link('views/shop.php'), 'Keep shopping', false) . '</p></td></tr>');
    return send_mail($who['customer_email'], $who['customer_name'], 'Your Akuafo membership has been cancelled', $html);
}
}

