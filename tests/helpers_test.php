<?php
// General helpers: money, URLs, notice periods, membership tiers.
require __DIR__ . '/bootstrap.php';

section('Money (GH₵)');
checkTest('Whole amounts have no decimals', money(22), 'GH₵ 22');
checkTest('Pence are shown with 2 decimals', money(52.5), 'GH₵ 52.50');
checkTest('Thousands get a comma', money(2342), 'GH₵ 2,342');
checkTest('Zero', money(0), 'GH₵ 0');

section('URLs');
checkTest('url() adds the site folder', url('views/shop.php'), BASE_URL . '/views/shop.php');
checkTest('url() ignores a leading slash', url('/views/shop.php'), BASE_URL . '/views/shop.php');

section('Notice periods');
checkTest('24 hours', notice_label(24), '24 hours');
checkTest('48 hours stays in hours', notice_label(48), '48 hours');
checkTest('72 hours becomes 3 days', notice_label(72), '3 days');
checkTest('168 hours becomes 7 days', notice_label(168), '7 days');
checkTest('30 hours stays in hours', notice_label(30), '30 hours');

section('Membership tiers');
checkTest('Gold is found by name', tier_info('gold')['price'], 199);
checkTest('Name is case-insensitive', tier_info('PLATINUM')['name'], 'Platinum');
checkTest('Unknown tier', tier_info('diamond'), null);
checkTest('Three tiers', count(membership_tiers()), 3);

section('Order statuses');
checkTest('Grocery flow', order_statuses(), ['pending', 'paid', 'shipped', 'delivered', 'cancelled']);
checkTest('Entertaining flow starts at received', ent_order_statuses()[1], 'received');

finish();
