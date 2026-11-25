<?php
// Farmer payouts are worked out automatically; the admin only confirms a payout was sent by MoMo.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/farmer_payout_class.php';
require_admin();

$week = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['week'] ?? '') ? $_POST['week'] : payout_week()[0];
$back = url('views/admin/admin_farmer_payouts.php?week=' . payout_week($week)[0]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);

if (($_POST['act'] ?? '') === 'paid') {
    $ref = trim((string)($_POST['momo_reference'] ?? ''));
    if ($ref !== '' && !preg_match('/^[A-Za-z0-9 .\/-]{4,80}$/', $ref)) {
        $_SESSION['admin_msg'] = 'The MoMo transaction ID should be letters and numbers (or leave it empty).';
        redirect($back);
    }
    try {
        $n = (new FarmerPayoutClass())->markPaid((int)($_POST['id'] ?? 0), $ref);
        $_SESSION['admin_msg'] = $n ? 'Payout marked as paid.' : 'That payout was already marked as paid.';
    } catch (PDOException $e) {
        error_log('Farmer payouts: ' . $e->getMessage());
        $_SESSION['admin_msg'] = 'Farmer payouts need the database update. Go to Menu > Database Update and run both "Farmer revenue" rows.';
    }
}
redirect($back);
