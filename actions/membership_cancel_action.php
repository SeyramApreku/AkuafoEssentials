<?php
// A member cancels their membership from "My membership". Benefits end now; no automatic refund.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../core/membership_email.php';
require_once __DIR__ . '/../classes/membership_class.php';

$back = url('views/membership.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);
require_login('views/membership.php');

$mc = new MembershipClass();
$active = $mc->activeFor(customer_id());
$m = $active ? $mc->cancel($active['id'], customer_id()) : null;
if (!$m) { $_SESSION['membership_msg'] = 'You have no active membership to cancel.'; redirect($back); }

$tier = ucfirst($m['tier']);
$_SESSION['membership_msg'] = "Your $tier membership has been cancelled. You can join again any time.";

// Confirmation email after the page has been sent.
header('Location: ' . $back, true, 303);
finish_response();
try { send_membership_cancelled($m); } catch (Throwable $e) { error_log('Cancel email: ' . $e->getMessage()); }
exit;
