<?php
// Invoice controller: builds the HTML invoice and emails it once per paid order.
require_once __DIR__ . '/../core/mailer.php';
require_once __DIR__ . '/../classes/order_class.php';

// Everything the invoice email and invoice page both need, in one place.
function invoice_data($order)
{
    $orders  = new OrderClass();
    $credit  = (float)$order['delivery_credit'];
    $account = $orders->customer($order['user_id']);
    return [
        'order'    => $order,
        'items'    => $orders->items($order['id']),
        'name'     => $account['customer_name'] ?? '',
        'email'    => $account['customer_email'] ?? $order['email'],   // the signup email, always
        'fee'      => (float)$order['delivery'] + $credit,   // full delivery fee before membership credit
        'credit'   => $credit,
        'pass_fee' => (float)($order['pass_fee'] ?? 0),
        'date'     => date('j M Y', strtotime($order['created_at'])),
        'delivery' => date('D, j M Y', strtotime($order['delivery_date'])) . ', ' . $order['time_window'],
        'pay_ref'  => $order['paystack_reference'] ?: '-',
    ];
}

function build_invoice_html($d)
{
    $o = $d['order'];
    $green = '#2B4A12'; $muted = '#6B6050'; $line = '#D5CBBA';
    $cell = 'padding:10px 0;border-bottom:1px solid ' . $line . ';font-size:14px;';
    $th   = 'padding:8px 0;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:' . $muted . ';border-bottom:2px solid ' . $green . ';';
    $sum  = fn($label, $value) => '<tr><td style="padding:4px 0;font-size:14px;color:' . $muted . '">' . $label . '</td><td style="padding:4px 0;font-size:14px;text-align:right">' . $value . '</td></tr>';

    $rows = '';
    foreach ($d['items'] as $i) {
        $detail = !empty($i['detail']) ? '<div style="font-size:12px;color:' . $muted . '">' . e($i['detail']) . '</div>' : '';
        $rows .= '<tr>
          <td style="' . $cell . '">' . e($i['name']) . $detail . '</td>
          <td style="' . $cell . 'text-align:center">' . (int)$i['qty'] . '</td>
          <td style="' . $cell . 'text-align:right;white-space:nowrap">' . money($i['price']) . '</td>
          <td style="' . $cell . 'text-align:right;white-space:nowrap">' . money($i['price'] * $i['qty']) . '</td>
        </tr>';
    }

    $totals = $sum('Subtotal', money($o['subtotal']))
            . $sum('Shipping &amp; delivery (' . e($o['delivery_zone']) . ')', money($d['fee']))
            . ($d['credit'] > 0 ? $sum('Membership delivery credit', '- ' . money($d['credit'])) : '')
            . ($d['pass_fee'] > 0 ? $sum('Delivery pass', money($d['pass_fee'])) : '');

    $info = fn($label, $value) => '<tr><td style="color:' . $muted . ';padding:2px 14px 2px 0;font-size:13px;white-space:nowrap">' . $label . '</td><td style="font-size:13px">' . $value . '</td></tr>';

    $inner = '
    <tr><td style="padding:24px 30px 0;">
      <table width="100%" cellpadding="0" cellspacing="0"><tr>
        <td valign="top"><div style="font-family:Georgia,serif;font-size:30px;color:' . $green . ';">Invoice</div>
          <div style="font-size:14px;margin-top:6px;">Thank you for your order' . ($d['name'] ? ', ' . e($d['name']) : '') . '. Your payment was successful.</div></td>
      </tr></table>
    </td></tr>
    <tr><td style="padding:20px 30px 0;">
      <table cellpadding="0" cellspacing="0">
        ' . $info('Order number', '<strong>' . e($o['ref']) . '</strong>') . '
        ' . $info('Order date', e($d['date'])) . '
        ' . $info('Payment reference', e($d['pay_ref']) . ' (Paystack)') . '
        ' . $info('Billed to', e($d['name'] ? $d['name'] . ', ' : '') . e($d['email'])) . '
        ' . (!empty($o['phone']) ? $info('Phone', e($o['phone'])) : '') . '
        ' . $info(($o['order_type'] ?? '') === 'entertaining' ? 'Event delivery' : 'Delivery', e($d['delivery']) . ' (' . e($o['delivery_zone']) . ')') . '
        ' . (order_address_line($o) ? $info('Deliver to', e(order_address_line($o))) : '') . '
        ' . (!empty($o['event_address']) ? $info('Event address', e($o['event_address'])) : '') . '
      </table>
    </td></tr>
    <tr><td style="padding:22px 30px 0;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr><th align="left" style="' . $th . '">Item</th><th align="center" style="' . $th . '">Qty</th>
            <th align="right" style="' . $th . '">Unit price</th><th align="right" style="' . $th . '">Subtotal</th></tr>
        ' . $rows . '
      </table>
    </td></tr>
    <tr><td style="padding:14px 30px 0;" align="right">
      <table cellpadding="0" cellspacing="0" style="width:65%;min-width:260px">
        ' . $totals . '
        <tr><td style="padding-top:10px;border-top:2px solid ' . $green . ';font-family:Georgia,serif;font-size:18px;color:' . $green . '">Total paid (GHS)</td>
            <td style="padding-top:10px;border-top:2px solid ' . $green . ';font-size:18px;font-weight:bold;text-align:right;white-space:nowrap">' . money($o['total']) . '</td></tr>
      </table>
    </td></tr>
    <tr><td style="padding:26px 30px;text-align:center;">
      ' . email_button(email_link('views/invoice.php?ref=' . $o['ref']), 'View invoice online') . '
      <p style="font-size:12px;color:' . $muted . ';margin:14px 0 0;">Paid online via Paystack. We will confirm your delivery slot by SMS.</p>
    </td></tr>';

    return email_layout($inner);
}

function build_invoice_text($d)
{
    $o = $d['order'];
    $t = "INVOICE - Akuafo & Essentials\n\nOrder number: {$o['ref']}\nOrder date: {$d['date']}\nPayment reference: {$d['pay_ref']}\nDelivery: {$d['delivery']} ({$o['delivery_zone']})\n\n";
    foreach ($d['items'] as $i) $t .= "{$i['name']}  x{$i['qty']}  @ " . money($i['price']) . "  = " . money($i['price'] * $i['qty']) . "\n";
    $t .= "\nSubtotal: " . money($o['subtotal']) . "\nShipping & delivery: " . money($d['fee']) . "\n";
    if ($d['credit'] > 0) $t .= "Membership delivery credit: -" . money($d['credit']) . "\n";
    if ($d['pass_fee'] > 0) $t .= "Delivery pass: " . money($d['pass_fee']) . "\n";
    return $t . "Total paid (GHS): " . money($o['total']) . "\n\nView online: " . email_link('views/invoice.php?ref=' . $o['ref']) . "\n";
}

// Tells the customer their order moved on. Only meaningful steps send an email.
function send_status_email($order, $status)
{
    $isEvent = ($order['order_type'] ?? '') === 'entertaining';
    $when = date('l j F', strtotime($order['delivery_date'])) . ', ' . $order['time_window'];
    $lines = [
        'shipped'          => ['Your order is on its way', 'Your order <strong>%s</strong> has left our farm hub and is on its way to you.'],
        'confirmed'        => ['Your event order is confirmed', 'Good news: our kitchen has confirmed your entertaining order <strong>%s</strong> for ' . e($when) . '.'],
        'out_for_delivery' => ['Your event order is on its way', 'Your entertaining order <strong>%s</strong> is out for delivery now.'],
        'delivered'        => ['Your order has been delivered', 'Your order <strong>%s</strong> has been delivered. We hope you enjoy it!'],
        'cancelled'        => ['Your order has been cancelled', 'Your order <strong>%s</strong> has been cancelled. If you have paid, our team will contact you about your refund.'],
    ];
    if (!isset($lines[$status]) || (!$isEvent && in_array($status, ['confirmed', 'out_for_delivery'], true))) return false;

    $account = (new OrderClass())->customer($order['user_id']);
    if (!$account) return false;
    [$subject, $body] = $lines[$status];
    $html = email_layout('<tr><td style="padding:24px 30px;font-size:14px;line-height:1.6;">
        <h2 style="font-family:Georgia,serif;font-weight:normal;margin:0 0 10px;">' . e($subject) . '</h2>
        <p>Hi ' . e($account['customer_name']) . ',</p><p>' . sprintf($body, e($order['ref'])) . '</p>
        <p style="margin-top:18px;">' . email_button(email_link('views/orders.php'), 'View my orders') . '</p></td></tr>');
    return send_mail($account['customer_email'], $account['customer_name'], $subject . ' (' . $order['ref'] . ')', $html);
}

// Emails the invoice for a PAID order exactly once. Safe to call more than once.
function send_invoice_email_once($ref)
{
    $orders = new OrderClass();
    $order  = $orders->byRef($ref);
    if (!$order || $order['payment_status'] !== 'paid') return false;
    if (!$orders->claimInvoice((int)$order['id'])) return true;   // already sent (or being sent)

    $d  = invoice_data($order);
    $ok = send_mail($d['email'], $d['name'], 'Your Akuafo & Essentials invoice (' . $order['ref'] . ')',
                    build_invoice_html($d), build_invoice_text($d));
    if (!$ok) $orders->releaseInvoice((int)$order['id']);           // let the next visit retry
    return $ok;
}
