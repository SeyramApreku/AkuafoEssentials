<?php
// Customer: save / delete / make-default an address, or join the delivery waitlist.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/delivery_class.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('views/addresses.php'));
require_login('views/addresses.php');

$dc   = new DeliveryClass();
$act  = $_POST['act'] ?? '';
$id   = (int)($_POST['id'] ?? 0);
$next = safe_next($_POST['next'] ?? '');
$page = url('views/addresses.php' . ($next ? '?next=' . urlencode($next) : ''));
$cid  = customer_id();

if ($act === 'save') {
    $gps    = normalize_gps($_POST['gps_address'] ?? '');
    $areaId = (int)($_POST['area_id'] ?? 0);
    $phone  = trim($_POST['phone'] ?? '');
    $areaOk = false;
    foreach ($dc->areasForSelect() as $list) foreach ($list as $a) if ((int)$a['id'] === $areaId) $areaOk = true;

    $errors = [];
    if (!$gps)    $errors[] = 'Enter a valid GhanaPostGPS address, like GA-123-4567.';
    if (!$areaOk) $errors[] = 'Choose your area from the list.';
    if (!preg_match('/^[0-9 +]{9,15}$/', $phone)) $errors[] = 'Enter a valid phone number.';
    if ($id && !$dc->address($id, $cid)) redirect($page);   // not this customer's address
    if ($errors) {
        $_SESSION['address_msg'] = [false, implode(' ', $errors)];
        $_SESSION['address_old'] = ['label' => $_POST['label'] ?? '', 'gps_address' => $_POST['gps_address'] ?? '', 'area_id' => $areaId,
                                    'landmark' => $_POST['landmark'] ?? '', 'notes' => $_POST['notes'] ?? '', 'phone' => $phone];
        redirect(url('views/addresses.php?' . ($id ? 'edit=' . $id : 'new=1') . ($next ? '&next=' . urlencode($next) : '')));
    }
    $newId = $dc->saveAddress($id, $cid, [
        'label'        => mb_substr(trim($_POST['label'] ?? ''), 0, 40) ?: null,
        'gps_address'  => $gps,
        'area_id'      => $areaId,
        'landmark'     => mb_substr(trim($_POST['landmark'] ?? ''), 0, 150) ?: null,
        'notes'        => mb_substr(trim($_POST['notes'] ?? ''), 0, 255) ?: null,
        'phone'        => $phone,
        'make_default' => !empty($_POST['make_default']),
    ]);
    // Came from checkout: go straight back with this address selected.
    if ($next === 'views/checkout.php') { $_SESSION['checkout_address'] = $newId; redirect(url('views/checkout.php')); }
    $_SESSION['address_msg'] = [true, $id ? 'Address saved.' : 'Address added.'];
    redirect($page);
}

if ($act === 'default') {
    if ($dc->address($id, $cid)) { $dc->setDefaultAddress($id, $cid); $_SESSION['address_msg'] = [true, 'Default address updated.']; }
    redirect($page);
}

if ($act === 'delete') {
    if ($dc->deleteAddress($id, $cid)) $_SESSION['address_msg'] = [true, 'Address deleted.'];
    redirect($page);
}

if ($act === 'waitlist') {
    $area  = mb_substr(trim($_POST['area_name'] ?? ''), 0, 100);
    $email = (new Database())->fetchOne('SELECT customer_email FROM aku_customer WHERE customer_id = ?', [$cid])['customer_email'] ?? '';
    if ($area === '' || !$email) { $_SESSION['address_msg'] = [false, 'Tell us which area you are in.']; redirect($page . '#waitlist'); }
    $dc->joinWaitlist($cid, $email, $area);
    $_SESSION['address_msg'] = [true, "Thanks! We'll email $email when we start delivering to $area."];
    redirect($page);
}
redirect($page);
