<?php
// Shared email sending for the whole site (PHPMailer + Gmail SMTP).
// The Gmail address and app password come ONLY from settings/mail_config.php.

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/core.php';
require_once __DIR__ . '/../libs/PhPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PhPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PhPMailer/src/SMTP.php';
require_once __DIR__ . '/../settings/mail_config.php';

const MAIL_FROM_NAME = 'Akuafo & Essentials';

// Sends one HTML email. Returns true/false and never throws, so a mail problem
// can never break signup or checkout. Failures are written to the PHP error log.
function send_mail($toEmail, $toName, $subject, $html, $text = '')
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 10;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, (string)$toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text !== '' ? $text : trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Email "' . $subject . '" to ' . $toEmail . ' failed: ' . $e->getMessage());
        return false;
    }
}

// Shared branded frame for every email (colours match css/style.css).
function email_layout($innerHtml)
{
    return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#EDE6D6;">
<div style="background:#EDE6D6;padding:30px 12px;font-family:Arial,Helvetica,sans-serif;color:#1A1916;">
  <table align="center" width="600" cellpadding="0" cellspacing="0" style="background:#FAF7F0;max-width:600px;width:100%;">
    <tr><td style="background:#2B4A12;color:#F4EFE4;text-align:center;padding:12px;font-size:13px;">
      Freshness You Can Trace, From Source to Table
    </td></tr>
    <tr><td style="padding:28px 30px 0;">
      <span style="display:inline-block;width:30px;height:30px;line-height:30px;border-radius:15px;background:#2B4A12;color:#F4EFE4;text-align:center;font-weight:bold;font-size:13px;vertical-align:middle;">A</span>
      <span style="font-family:Georgia,serif;font-size:20px;vertical-align:middle;margin-left:8px;">Akuafo <span style="color:#B24008;">&amp;</span> Essentials</span>
    </td></tr>
    ' . $innerHtml . '
    <tr><td style="padding:20px 30px;text-align:center;font-size:12px;color:#6B6050;border-top:1px solid #D5CBBA;">
      Akuafo &amp; Essentials, Accra, Ghana
    </td></tr>
  </table>
</div></body></html>';
}

// Every link inside an email goes through the login page first, then on to $path.
// force=1 makes views/login.php sign out whoever is logged in on that browser and ask
// for the password again, so an email link never opens an account on its own.
function email_link($path)
{
    return site_url('views/login.php?next=' . urlencode($path) . '&force=1');
}

function email_button($href, $label, $primary = true)
{
    $style = $primary
        ? 'background:#2B4A12;color:#F4EFE4;border:2px solid #2B4A12;'
        : 'background:#FAF7F0;color:#2B4A12;border:2px solid #2B4A12;';
    return '<a href="' . e($href) . '" style="display:inline-block;' . $style . 'text-decoration:none;padding:12px 30px;font-weight:bold;font-size:14px;margin:6px 4px;">' . e($label) . '</a>';
}

/* ---------- Welcome email (sent on signup) ---------- */

function sendWelcomeEmail($toEmail, $toName)
{
    $shopUrl       = email_link('views/shop.php');
    $membershipUrl = email_link('views/membership.php');
    $loginUrl      = email_link('index.php');

    $inner = '
    <tr><td style="padding:30px;text-align:center;">
      <h1 style="font-family:Georgia,serif;font-weight:normal;font-size:36px;line-height:1.15;margin:10px 0 18px;">Welcome to the<br>good harvest.</h1>
      <p style="font-size:15px;line-height:1.6;margin:0 0 8px;">Hi ' . e($toName) . ',</p>
      <p style="font-size:15px;line-height:1.6;color:#6B6050;margin:0 0 20px;">
        Your Akuafo &amp; Essentials account is ready. You signed up with <strong style="color:#1A1916;">' . e($toEmail) . '</strong>.
        Fresh produce from named Ghanaian farmers, delivered on your schedule.
      </p>
      ' . email_button($shopUrl, 'Shop Now') . email_button($membershipUrl, 'Explore Membership', false) . '
      <p style="font-size:13px;color:#6B6050;margin:22px 0 0;">Log in any time at <a href="' . e($loginUrl) . '" style="color:#B24008;">' . e($loginUrl) . '</a></p>
      <p style="font-size:12px;color:#6B6050;margin:14px 0 0;">Didn\'t create this account? You can ignore this email.</p>
    </td></tr>';

    $text = "Hi $toName,\n\nYour Akuafo & Essentials account is ready ($toEmail).\n\n"
          . "Shop: $shopUrl\nMembership: $membershipUrl\nLog in: $loginUrl\n";

    return send_mail($toEmail, $toName, 'Welcome to Akuafo & Essentials', email_layout($inner), $text);
}
