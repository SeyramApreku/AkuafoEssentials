<?php
// Entertaining section: booking rules (notice, capacity, blocked dates) and the guest calculator.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/catalog_controller.php';   // entertaining()
require_once __DIR__ . '/cart_controller.php';

const ENT_BOOK_AHEAD_DAYS = 90;   // how far ahead an event can be booked

function ent_slot_capacity()   { return max(1, (int)entertaining()->setting('ent_slot_capacity', 5)); }
function ent_cutoff_hours()    { return max(0, (int)entertaining()->setting('ent_change_cutoff_hours', 48)); }

// "For 40 guests you need 3" (one unit serves $serves people). Null when the item isn't portioned.
function ent_suggest_qty($serves, $guests)
{
    if (!$serves || $guests < 1) return null;
    return (int)ceil($guests / $serves);
}

// Start hour of a delivery window, from its label: "Afternoon (12pm - 5pm)" => 12.
function ent_window_start_hour($window)
{
    if (!preg_match('/\((\d{1,2})\s*(am|pm)/i', $window, $m)) return 9;
    $h = (int)$m[1] % 12;
    return strtolower($m[2]) === 'pm' ? $h + 12 : $h;
}

// When the event delivery starts, as a DateTime.
function ent_event_start($date, $window)
{
    return new DateTime($date . ' ' . sprintf('%02d:00', ent_window_start_hour($window)));
}

// Longest notice period of the entertaining items in the cart (hours).
function ent_cart_notice_hours()
{
    $max = 0;
    foreach (cart()->items() as $i) if (($i['type'] ?? '') === 'entertaining') $max = max($max, (int)($i['notice_hours'] ?? 0));
    return $max;
}

// First date an event can be booked, given a notice period: the delivery must start at least
// $hours after now, and we never book the same day.
function ent_earliest_date($hours)
{
    $earliest = new DateTime('tomorrow');
    $ready = (new DateTime())->modify('+' . (int)$hours . ' hours');
    // If even the first window of a day starts before the food can be ready, move to the next day.
    $firstStart = min(array_map('ent_window_start_hour', time_windows()));
    while (new DateTime($earliest->format('Y-m-d') . sprintf(' %02d:00', $firstStart)) < $ready) $earliest->modify('+1 day');
    return $earliest;
}

// Is this date + window bookable? Returns [true, ''] or [false, reason].
function ent_slot_check($date, $window, $noticeHours, $exceptOrderId = 0)
{
    $d = DateTime::createFromFormat('!Y-m-d', (string)$date);
    if (!$d || $d->format('Y-m-d') !== $date)        return [false, 'Choose a valid event date.'];
    if (!in_array($window, time_windows(), true))     return [false, 'Choose a delivery time slot.'];
    if (ent_event_start($date, $window) < (new DateTime())->modify('+' . (int)$noticeHours . ' hours'))
        return [false, 'Your order needs at least ' . notice_label($noticeHours) . ' notice. Please choose a later date or time.'];
    if ($d > (new DateTime('today'))->modify('+' . ENT_BOOK_AHEAD_DAYS . ' days'))
        return [false, 'Events can be booked up to ' . ENT_BOOK_AHEAD_DAYS . ' days ahead.'];

    $rules = entertaining()->rulesFor($date);
    $day = $rules[''] ?? null;
    $slot = $rules[$window] ?? null;
    if (($day && $day['is_blocked']) || ($slot && $slot['is_blocked'])) return [false, 'We are fully booked on that date. Please choose another.'];

    if ($day && $day['max_orders'] !== null && entertaining()->bookedCount($date, null, $exceptOrderId) >= (int)$day['max_orders'])
        return [false, 'We are fully booked on that date. Please choose another.'];
    $slotMax = ($slot && $slot['max_orders'] !== null) ? (int)$slot['max_orders'] : ent_slot_capacity();
    if (entertaining()->bookedCount($date, $window, $exceptOrderId) >= $slotMax)
        return [false, 'That time slot is fully booked. Please choose another slot or date.'];
    return [true, ''];
}

// Whole days that are blocked or full between two dates (for the date picker).
function ent_unavailable_dates($from, $to)
{
    $out = [];
    foreach (entertaining()->rulesBetween($from, $to) as $r) {
        if ($r['time_window'] !== '') continue;
        if ($r['is_blocked'] || ($r['max_orders'] !== null && entertaining()->bookedCount($r['rule_date']) >= (int)$r['max_orders'])) $out[] = $r['rule_date'];
    }
    return $out;
}

// Can the customer still change or cancel this order? Returns [bool, reason].
function ent_can_change($order)
{
    if (($order['order_type'] ?? '') !== 'entertaining')          return [false, 'Only entertaining orders can be changed online.'];
    if ($order['payment_status'] !== 'paid')                       return [false, 'This order has not been paid.'];
    if (in_array($order['status'], ['cancelled', 'out_for_delivery', 'delivered'], true)) return [false, 'This order can no longer be changed.'];
    $cutoff = ent_event_start($order['delivery_date'], $order['time_window'])->modify('-' . ent_cutoff_hours() . ' hours');
    if (new DateTime() >= $cutoff) return [false, 'Changes close ' . ent_cutoff_hours() . ' hours before your event. Please contact us.'];
    return [true, $cutoff->format('D j M, g:ia')];
}

// Notice needed by the items in an existing order (for re-booking a new date).
function ent_order_notice_hours($orderId)
{
    $r = entertaining()->fetchOne(
        "SELECT MAX(i.notice_hours) AS h FROM aku_order_items oi JOIN aku_ent_items i ON oi.item_key = CONCAT('ent-', i.id) WHERE oi.order_id = ?",
        [(int)$orderId]);
    return (int)($r['h'] ?? 48);
}
