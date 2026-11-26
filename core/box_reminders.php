<?php
// Emails "your box is almost done, renew?" to customers whose box plan ends within BOX_REMIND_DAYS days.
// Run from views/layout/footer.php after the page has been sent, so the visitor never waits for it.
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../classes/subscription_class.php';

function send_box_reminders()
{
    $subs = new SubscriptionClass();
    $due = $subs->dueReminders();
    if (!$due) return;
    finish_response();   // the page is already on the visitor's screen; email in the background
    foreach ($due as $s) {
        if (!$subs->claimReminder($s['id'])) continue;   // another visitor's request took this one
        $last  = date('l j F', strtotime($s['ends_on']));
        $ended = strtotime($s['ends_on']) < strtotime(date('Y-m-d'));
        $html = email_layout('<tr><td style="padding:24px 30px;font-size:14px;line-height:1.6;">'
            . '<h2 style="font-family:Georgia,serif;font-weight:normal;margin:0 0 10px;">' . ($ended ? 'Your box plan has finished' : 'Your box plan is almost done') . '</h2>'
            . '<p>Hi ' . e($s['customer_name']) . ',</p>'
            . '<p>Your <strong>' . e($s['name']) . '</strong> ' . ($ended ? 'had its last delivery on' : 'has its last delivery on') . ' <strong>' . e($last) . '</strong>.</p>'
            . '<p>Renew now to keep fresh Ghanaian produce coming with the same schedule and length (' . (int)$s['weeks'] . ' weeks).</p>'
            . '<p style="margin-top:18px;">' . email_button(email_link('views/subscriptions.php'), 'Renew my box') . '</p></td></tr>');
        if (!send_mail($s['customer_email'], $s['customer_name'], $ended ? 'Your box plan has finished' : 'Your box plan ends on ' . $last, $html)) {
            $subs->releaseReminder($s['id']);   // try again on a later page view
        }
    }
}
