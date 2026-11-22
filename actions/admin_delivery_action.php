<?php
// Admin: save delivery zones, areas and settings (views/admin/admin_delivery.php).
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/delivery_class.php';
require_admin();   // also checks the CSRF token

$page = url('views/admin/admin_delivery.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($page);
$dc  = new DeliveryClass();
$act = $_POST['act'] ?? '';
$id  = (int)($_POST['id'] ?? 0);

if ($act === 'settings') {
    $min  = $_POST['min_order'] ?? '';
    $free = $_POST['free_delivery_threshold'] ?? '';
    $cut  = $_POST['order_cutoff_time'] ?? '';
    if (!is_numeric($min) || $min < 0 || !is_numeric($free) || $free < 0 || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $cut)) {
        $_SESSION['admin_msg'] = 'Enter amounts of 0 or more and a time like 20:00.';
        redirect($page . '?tab=settings');
    }
    $dc->setSetting('min_order', round((float)$min, 2));
    $dc->setSetting('free_delivery_threshold', round((float)$free, 2));
    $dc->setSetting('order_cutoff_time', $cut);
    $_SESSION['admin_msg'] = 'Delivery settings saved.';
    redirect($page . '?tab=settings');
}

if ($act === 'save_zone') {
    $name = trim($_POST['name'] ?? '');
    $fee  = $_POST['fee'] ?? '';
    $days = array_values(array_intersect(zone_day_names(), (array)($_POST['days'] ?? [])));   // keeps Mon..Sun order
    if ($name === '' || mb_strlen($name) > 100 || !is_numeric($fee) || $fee < 0 || !$days) {
        $_SESSION['admin_msg'] = 'A zone needs a name, a fee of 0 or more and at least one delivery day.';
        redirect($page . ($id ? '?zone=' . $id : '?new=1'));
    }
    try {
        $newId = $dc->saveZone($id, $name, round((float)$fee, 2), implode(',', $days), empty($_POST['is_active']) ? 0 : 1);
    } catch (PDOException $e) {
        $_SESSION['admin_msg'] = 'Could not save the zone. Is the name already used?';
        redirect($page . ($id ? '?zone=' . $id : '?new=1'));
    }
    $_SESSION['admin_msg'] = $id ? 'Zone saved.' : 'Zone added. Now add its areas below.';
    redirect($page . '?zone=' . $newId);
}

if ($act === 'delete_zone') {
    $_SESSION['admin_msg'] = $dc->deleteZone($id)
        ? 'Zone deleted.'
        : 'This zone still has areas or orders. Move or delete its areas first, or untick "Active" to switch it off.';
    redirect($page);
}

if ($act === 'save_area') {
    $name   = trim($_POST['name'] ?? '');
    $zoneId = (int)($_POST['zone_id'] ?? 0);
    if ($name === '' || mb_strlen($name) > 100 || !$dc->zone($zoneId)) { $_SESSION['admin_msg'] = 'An area needs a name and a zone.'; redirect($page . '?zone=' . $zoneId); }
    try {
        $dc->saveArea($id, $zoneId, $name, empty($_POST['is_active']) ? 0 : 1);
        $_SESSION['admin_msg'] = $id ? 'Area saved.' : 'Area added.';
    } catch (PDOException $e) {
        $_SESSION['admin_msg'] = 'There is already an area called "' . $name . '".';
    }
    redirect($page . '?zone=' . $zoneId);
}

if ($act === 'delete_waitlist') {
    $dc->deleteWaitlist($id);
    $_SESSION['admin_msg'] = 'Removed from the waitlist.';
    redirect($page);
}

if ($act === 'delete_area') {
    $_SESSION['admin_msg'] = $dc->deleteArea($id)
        ? 'Area deleted.'
        : 'Customers have saved addresses in this area, so it cannot be deleted. Untick "Active" to switch it off instead.';
    redirect($page . '?zone=' . (int)($_POST['zone'] ?? 0));
}
redirect($page);
