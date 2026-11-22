<?php
require_once __DIR__ . '/../controllers/customer_controller.php';
header('Content-Type: application/json');

$email = trim($_POST['customer_email'] ?? '');
$pass  = $_POST['customer_pass'] ?? '';

if ($email === '' || $pass === '') {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']); exit;
}

$result = (new CustomerController())->login($email, $pass);

if ($result['success']) {
    $chk = (new Database())->fetchOne("SELECT is_active FROM aku_customer WHERE customer_id = ?", [$result['customer']['customer_id']]);
    if ($chk && (int)$chk['is_active'] === 0) {
        echo json_encode(['success' => false, 'message' => 'This account has been deactivated. Contact support.']); exit;
    }

    session_regenerate_id(true);
    $c = $result['customer'];
    $_SESSION['customer_id']    = $c['customer_id'];
    $_SESSION['customer_name']  = $c['customer_name'];
    $_SESSION['customer_email'] = $c['customer_email'];
    $_SESSION['user_role']      = $c['user_role'];
    $next = safe_next($_POST['next'] ?? '');
    $to = ((int)$c['user_role'] === 1) ? url('views/admin/admin_manage.php') : url($next ?: 'index.php');
    echo json_encode(['success' => true, 'message' => 'Login successful.', 'redirect' => $to]);
} else {
    echo json_encode(['success' => false, 'message' => $result['error']]);
}