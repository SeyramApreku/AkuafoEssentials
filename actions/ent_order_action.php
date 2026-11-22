<?php
// Customer changes or cancels an entertaining order (before the cutoff). Cancelling refunds through Paystack.
require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../controllers/entertaining_controller.php';
require_once __DIR__ . '/../classes/order_class.php';
require_once __DIR__ . '/../settings/paystack_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('views/orders.php'));
require_login('views/orders.php');

$ref    = $_POST['ref'] ?? '';
$act    = $_POST['act'] ?? '';
$orders = new OrderClass();
$order  = $orders->byRef($ref);
$back   = url('views/ent_order.php?ref=' . urlencode($ref));
if (!owns_order($order) || ($order['order_type'] ?? '') !== 'entertaining') redirect(url('views/orders.php'));

[$canChange, $why] = ent_can_change($order);
if (!$canChange) { $_SESSION['ent_order_msg'] = [false, $why]; redirect($back); }

$account = $orders->customer($order['user_id']);
$notify = function ($subject, $body) use ($account, $order) {
    $html = email_layout('<tr><td style="padding:24px 30px;font-size:14px;line-height:1.6;">' . $body
        . '<p style="margin-top:18px;">' . email_button(email_link('views/orders.php'), 'View my orders') . '</p></td></tr>');
    send_mail($account['customer_email'], $account['customer_name'], $subject . ' (' . $order['ref'] . ')', $html);
};

if ($act === 'change') {
    $date    = $_POST['date'] ?? '';
    $window  = $_POST['window'] ?? '';
    $address = mb_substr(trim($_POST['event_address'] ?? ''), 0, 255);
    $sameSlot = $date === $order['delivery_date'] && $window === $order['time_window'];
    if (!$sameSlot) {
        [$ok, $err] = ent_slot_check($date, $window, ent_order_notice_hours($order['id']), (int)$order['id']);
        if (!$ok) { $_SESSION['ent_order_msg'] = [false, $err]; redirect($back); }
    }
    $orders->updateEvent((int)$order['id'], $date, $window, $address !== '' ? $address : null);
    $nice = date('l j F Y', strtotime($date)) . ', ' . $window;
    $notify('Your event order has been updated', '<h2 style="font-family:Georgia,serif;font-weight:normal;margin:0 0 10px;">Changes saved</h2>'
        . '<p>Your Akuafo &amp; Essentials entertaining order <strong>' . e($order['ref']) . '</strong> is now booked for <strong>' . e($nice) . '</strong>.</p>'
        . ($address !== '' ? '<p>Event address: ' . e($address) . '</p>' : ''));
    $_SESSION['ent_order_msg'] = [true, 'Saved. Your event is now ' . $nice . '.'];
    redirect($back);
}

if ($act === 'cancel') {
    // Mark cancelled first (only one request can do this), then refund, so a double click can't refund twice.
    if ($orders->cancel((int)$order['id'], 'requested') !== 1) redirect($back);

    $res = paystack_request('POST', '/refund', ['transaction' => $order['paystack_reference']]);
    if (!empty($res['status'])) {
        $refund = $res['data']['status'] ?? 'pending';          // e.g. pending, processing, processed
    } else {
        $refund = 'failed';
        error_log('Paystack refund failed for ' . $order['ref'] . ': ' . json_encode($res));
        // Tell the store so someone refunds by hand from the Paystack dashboard.
        send_mail(MAIL_USER, MAIL_FROM_NAME, 'ACTION NEEDED: refund ' . $order['ref'] . ' by hand',
            email_layout('<tr><td style="padding:24px 30px;font-size:14px;">Order <strong>' . e($order['ref']) . '</strong> (' . money($order['total'])
                . ') was cancelled by the customer but the automatic Paystack refund failed: ' . e($res['message'] ?? 'no response')
                . '. Please refund it from the Paystack dashboard.</td></tr>'));
    }
    $orders->execute('UPDATE aku_orders SET refund_status = ? WHERE id = ?', [$refund, $order['id']]);

    $notify('Your event order has been cancelled', '<h2 style="font-family:Georgia,serif;font-weight:normal;margin:0 0 10px;">Order cancelled</h2>'
        . '<p>We have cancelled your entertaining order <strong>' . e($order['ref']) . '</strong>.</p>'
        . ($refund === 'failed'
            ? '<p>Our team will refund <strong>' . money($order['total']) . '</strong> to you manually within 2 working days.</p>'
            : '<p>A refund of <strong>' . money($order['total']) . '</strong> has been sent to your original payment method. It usually arrives within 5 to 10 working days.</p>'));
    $_SESSION['ent_order_msg'] = [true, $refund === 'failed'
        ? 'Your order is cancelled. Our team will refund you manually within 2 working days.'
        : 'Your order is cancelled and your refund of ' . money($order['total']) . ' is on its way.'];
    redirect($back);
}
redirect($back);
