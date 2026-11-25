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

section('Shop search');
checkTest('A word becomes a contains-search', like_pattern('yam'), '%yam%');
checkTest('A typed % is searched literally', like_pattern('50%'), '%50\%%');
checkTest('A typed _ is searched literally', like_pattern('a_b'), '%a\_b%');

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

section('Weekly box plans');
checkTest('Weekly for 4 weeks = 4 boxes', box_delivery_count('weekly', 4), 4);
checkTest('Every 2 weeks for 8 weeks = 4 boxes', box_delivery_count('biweekly', 8), 4);
checkTest('Monthly for 12 weeks = 3 boxes', box_delivery_count('monthly', 12), 3);
checkTest('Monthly for 4 weeks = 1 box', box_delivery_count('monthly', 4), 1);
checkTest('Plan label', box_plan_label('weekly', 8), '8 deliveries over 8 weeks, paid upfront');
checkTest('Plan read back from a box key', box_plan_from_key('box-veg-1-w8-biweekly-a1b2c3d4'), ['biweekly', 8]);
checkTest('Plan read back from a custom box key', box_plan_from_key('custom-w12-monthly-0123456789'), ['monthly', 12]);
checkTest('Old box keys count as one week', box_plan_from_key('box-veg-1-a1b2c3d4'), ['weekly', 1]);

section('Breadcrumbs');
checkTest('Home page has none', breadcrumbs('index.php'), []);
checkTest('Shop: Home > The Produce', array_column(breadcrumbs('views/shop.php', 'The Produce | Akuafo & Essentials'), 0), ['Home', 'The Produce']);
checkTest('Product: Home > The Produce > name', array_column(breadcrumbs('views/product.php', 'White Yam | Akuafo & Essentials'), 0), ['Home', 'The Produce', 'White Yam']);
checkTest('Product links back to the shop', breadcrumbs('views/product.php', 'White Yam')[1][1], url('views/shop.php'));
checkTest('Current page is not a link', end(breadcrumbs('views/product.php', 'White Yam'))[1], null);
checkTest('One farmer: Home > Our Farms > name', array_column(breadcrumbs('views/farms.php', 'Kofi Asante | Akuafo', true), 0), ['Home', 'Our Farms', 'Kofi Asante']);
checkTest('Invoice: Home > My orders > Invoice', array_column(breadcrumbs('views/invoice.php', 'x'), 0), ['Home', 'My orders', 'Invoice']);
checkTest('Order summary: Home > Checkout > Order summary', array_column(breadcrumbs('views/order_summary.php'), 0), ['Home', 'Checkout', 'Order summary']);

section('Order statuses');
checkTest('Grocery flow', order_statuses(), ['pending', 'paid', 'shipped', 'delivered', 'cancelled']);
checkTest('Entertaining flow starts at received', ent_order_statuses()[1], 'received');

finish();
