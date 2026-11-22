<?php
require_once __DIR__ . '/../core/core.php';
require_admin();

$id  = (int)($_POST['id'] ?? 0);
$act = $_POST['act'] ?? '';
$list = url('views/admin/admin_customers.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) redirect($list);

$db = new Database();
$c = $db->fetchOne("SELECT * FROM aku_customer WHERE customer_id = ? AND user_role = 2", [$id]);
if (!$c) { $_SESSION['admin_msg'] = 'Customer not found.'; redirect($list); }

if ($act === 'toggle') {
    $new = (int)($c['is_active'] ?? 1) ? 0 : 1;
    $db->execute("UPDATE aku_customer SET is_active = ? WHERE customer_id = ?", [$new, $id]);
    $_SESSION['admin_msg'] = $new ? 'Account reactivated.' : 'Account deactivated.';
} elseif ($act === 'save') {
    $name    = trim($_POST['customer_name'] ?? '');
    $email   = trim($_POST['customer_email'] ?? '');
    $country = trim($_POST['customer_country'] ?? '');
    $city    = trim($_POST['customer_city'] ?? '');
    $contact = trim($_POST['customer_contact'] ?? '');

    if ($name === '' || $country === '' || $city === '' || $contact === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['admin_msg'] = 'Fill in all fields with a valid email.';
    } else {
        try {
            $db->execute("UPDATE aku_customer SET customer_name = ?, customer_email = ?, customer_country = ?, customer_city = ?, customer_contact = ? WHERE customer_id = ?",
                [$name, $email, $country, $city, $contact, $id]);
            $_SESSION['admin_msg'] = 'Changes saved.';
        } catch (PDOException $e) {
            $_SESSION['admin_msg'] = 'That email is already used by another account.';
        }
    }
}
redirect($list . '?id=' . $id);