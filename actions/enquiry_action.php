<?php
// "Feeding a larger event?" enquiry form on the Entertaining page: emails the store inbox.
require_once __DIR__ . '/../core/mailer.php';

$back = url('views/preorder.php#enquiry');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

// Bots fill the hidden "website" field; real people never see it. Pretend it worked.
if (!empty($_POST['website'])) { $_SESSION['enquiry_msg'] = [true, 'Thank you. We will be in touch by email.']; redirect($back); }

if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '' || mb_strlen($message) > 2000) {
    $_SESSION['enquiry_msg'] = [false, 'Please fill in your name, a valid email and your message.'];
    redirect($back);
}
if (time() - ($_SESSION['last_enquiry'] ?? 0) < 60) {
    $_SESSION['enquiry_msg'] = [false, 'You have just sent an enquiry. Please wait a minute before sending another.'];
    redirect($back);
}

$html = email_layout('
  <tr><td style="padding:24px 30px;">
    <h2 style="font-family:Georgia,serif;font-weight:normal;margin:0 0 12px;">New entertaining enquiry</h2>
    <p style="font-size:14px;margin:0 0 4px;"><strong>From:</strong> ' . e($name) . ' &lt;' . e($email) . '&gt;</p>
    ' . (customer_id() ? '<p style="font-size:14px;margin:0 0 4px;"><strong>Customer account:</strong> #' . customer_id() . '</p>' : '') . '
    <p style="font-size:14px;line-height:1.6;white-space:pre-wrap;border-left:3px solid #B24008;padding-left:12px;margin:14px 0 0;">' . e($message) . '</p>
    <p style="font-size:12px;color:#6B6050;margin-top:16px;">Reply to this email to answer the customer directly.</p>
  </td></tr>');

$sent = send_mail(MAIL_USER, MAIL_FROM_NAME, 'Entertaining enquiry from ' . $name, $html, "From: $name <$email>\n\n$message", $email);
$_SESSION['last_enquiry'] = time();
$_SESSION['enquiry_msg'] = $sent
    ? [true, 'Thank you, ' . $name . '. We have your enquiry and will reply to ' . $email . '.']
    : [false, 'Sorry, we could not send your enquiry just now. Please try again in a few minutes.'];
redirect($back);
