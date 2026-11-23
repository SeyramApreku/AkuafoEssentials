<?php
// Entertaining: guest calculator, delivery windows, earliest event date, separate carts.
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../controllers/entertaining_controller.php';

section('How much do I need? (guest calculator)');
checkTest('40 guests, tray serves 15: 3 trays', ent_suggest_qty(15, 40), 3);
checkTest('30 guests, cake serves 30: 1 cake', ent_suggest_qty(30, 30), 1);
checkTest('31 guests, cake serves 30: 2 cakes', ent_suggest_qty(30, 31), 2);
checkTest('Not portioned (bulk crate): no suggestion', ent_suggest_qty(null, 40), null);
checkTest('No guests entered: no suggestion', ent_suggest_qty(15, 0), null);

section('Delivery windows');
checkTest('Morning starts at 7', ent_window_start_hour('Morning (7am - 12pm)'), 7);
checkTest('Afternoon starts at 12 (noon)', ent_window_start_hour('Afternoon (12pm - 5pm)'), 12);
checkTest('Evening starts at 17', ent_window_start_hour('Evening (5pm - 8pm)'), 17);
checkTest('8-11am window starts at 8', ent_window_start_hour('Morning (8am - 11am)'), 8);
checkTest('Unknown label defaults to 9', ent_window_start_hour('Whenever'), 9);
checkTest('Event start time', ent_event_start('2026-12-01', 'Evening (5pm - 8pm)')->format('Y-m-d H:i'), '2026-12-01 17:00');

section('Earliest event date');
$tomorrow = (new DateTime('tomorrow'))->format('Y-m-d');
checkTest('No notice: never same day', ent_earliest_date(0)->format('Y-m-d') >= $tomorrow, true);
$fiveDays = (new DateTime())->modify('+120 hours');
checkTest('5 days notice: at least 5 days away', ent_earliest_date(120) >= (clone $fiveDays)->setTime(0, 0), true);
checkTest('5 days notice: not more than 6 days away', ent_earliest_date(120) <= (new DateTime('today'))->modify('+6 days'), true);

section('Separate carts');
$_SESSION = [];
$cart = new CartClass();
checkTest('Empty cart has no kind', $cart->kind(), null);
$cart->add(['id' => 'p-1', 'name' => 'Yam', 'price' => 22, 'qty' => 1, 'type' => 'produce']);
checkTest('Produce makes a standard cart', $cart->kind(), 'standard');
$cart->clear();
$cart->add(['id' => 'ent-6', 'name' => 'Jollof tray', 'price' => 420, 'qty' => 1, 'type' => 'entertaining']);
checkTest('Entertaining item makes an entertaining cart', $cart->kind(), 'entertaining');
checkTest('kindOf: box is standard', CartClass::kindOf(['type' => 'box']), 'standard');
checkTest('kindOf: entertaining', CartClass::kindOf(['type' => 'entertaining']), 'entertaining');

finish();
