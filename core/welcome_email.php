<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

function sendWelcomeEmail($toEmail, $toName)
{
    $baseUrl       = 'http://localhost/akuafo'; // change to live URL on deploy
    $shopUrl       = $baseUrl . '/views/shop.php';       // adjust to your shop path
    $membershipUrl = $baseUrl . '/views/membership.php'; // adjust to your membership path
    $name          = htmlspecialchars($toName);

    $html = '
    <div style="background:#f4efe3;padding:30px 0;font-family:Georgia,serif;">
      <table align="center" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;max-width:560px;">
        <tr><td style="background:#2f5d3a;color:#ffffff;text-align:center;padding:12px;font-family:Arial,sans-serif;font-size:13px;">
          Fresh from Ghanaian farms to your door
        </td></tr>
        <tr><td style="background:#ece4d0;padding:40px 30px;text-align:center;">
          <div style="font-size:14px;letter-spacing:3px;color:#2f5d3a;font-family:Arial,sans-serif;">AKUAFO ESSENTIALS</div>
          <h1 style="font-size:40px;line-height:1.15;color:#1a1a1a;margin:20px 0;">Welcome to the<br>good harvest.</h1>
          <p style="font-size:16px;line-height:1.6;color:#333;font-family:Arial,sans-serif;">
            Hi ' . $name . ', we are glad you joined us. Fresh produce from local farmers, delivered on your schedule.
          </p>
          <a href="' . $shopUrl . '" style="display:inline-block;background:#000;color:#fff;text-decoration:none;padding:14px 34px;border-radius:30px;font-family:Arial,sans-serif;font-weight:bold;margin:10px 5px;">Shop Now</a>
          <a href="' . $membershipUrl . '" style="display:inline-block;background:#ffffff;color:#000;text-decoration:none;padding:12px 32px;border:2px solid #000;border-radius:30px;font-family:Arial,sans-serif;font-weight:bold;margin:10px 5px;">Explore Membership</a>
        </td></tr>
        <tr><td style="padding:20px;text-align:center;font-size:12px;color:#777;font-family:Arial,sans-serif;">
          Akuafo Essentials, Accra, Ghana<br>You received this because you signed up on our website.
        </td></tr>
      </table>
    </div>';

        try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();

        
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'akuafoessentials@gmail.com';
        $mail->Password   = 'blhjqwnjigiyojhg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('akuafoessentials@gmail.com', 'Akuafo Essentials');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Akuafo Essentials';
        $mail->Body    = $html;
        $mail->AltBody = "Welcome to Akuafo Essentials, $toName. Shop: $shopUrl | Membership: $membershipUrl";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Welcome email failed: ' . $e->getMessage());
        return false; // never block signup
    }
}