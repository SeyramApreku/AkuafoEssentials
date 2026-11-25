<?php
// Farmer payout maths: farmer_payout_calc(), farmer_agreed_price(), payout_week() in core/core.php.
require __DIR__ . '/bootstrap.php';

section('1. Farm Shop only: sale price minus commission');
$c = farmer_payout_calc([['amount' => 200, 'rate' => 15]]);
checkTest('Commission is 15% of GH₵ 200', $c['commission'], 30.0);
checkTest('Farmer gets 170', $c['net_payout'], 170.0);

section('2. Box supply: farmgate + premium, never below the minimum');
checkTest('10 + 10% = 11', farmer_agreed_price(10, 10), 11.0);
checkTest('Minimum 12 beats 11', farmer_agreed_price(10, 10, 12), 12.0);
checkTest('Minimum lower than the price is ignored', farmer_agreed_price(10, 10, 9), 11.0);
checkTest('20 kg at 11 = 220 owed', farmer_payout_calc([], 220)['net_payout'], 220.0);

section('3. The worked example on the admin page (Kofi)');
$c = farmer_payout_calc([['amount' => 200, 'rate' => 15]], 220, 40, 33);
checkTest('170 + 220 − 40 − 33 = 317', $c['net_payout'], 317.0);
checkTest('Nothing carried forward', $c['carried_out'], 0.0);

section('4. Plan fee comes off');
$c = farmer_payout_calc([['amount' => 1000, 'rate' => 10]], 0, 0, 0, 100);
checkTest('Grower: 1000 − 10% − GH₵ 100 fee = 800', $c['net_payout'], 800.0);

section('5. Negative week is carried forward, then repaid');
$c = farmer_payout_calc([['amount' => 20, 'rate' => 15]], 0, 40);
checkTest('17 − 40: paid 0', $c['net_payout'], 0.0);
checkTest('23 carried to next week', $c['carried_out'], 23.0);
$n = farmer_payout_calc([['amount' => 100, 'rate' => 15]], 0, 0, 0, 0, 23);
checkTest('Next week: 85 − 23 = 62', $n['net_payout'], 62.0);

section('6. Rounding only at the end; each sale keeps its own rate');
$c = farmer_payout_calc([['amount' => 33.33, 'rate' => 15], ['amount' => 33.33, 'rate' => 15], ['amount' => 33.34, 'rate' => 10]]);
checkTest('Commission 4.9995 + 4.9995 + 3.334 = 13.333, rounded once to 13.33', $c['commission'], 13.33);
checkTest('Farmer gets 100 − 13.333 = 86.67', $c['net_payout'], 86.67);

section('Payout week (Saturday to Friday)');
checkTest('A Wednesday', payout_week('2026-11-25'), ['2026-11-21', '2026-11-27']);
checkTest('A Saturday starts its own week', payout_week('2026-11-21'), ['2026-11-21', '2026-11-27']);
checkTest('A Friday ends the week', payout_week('2026-11-27'), ['2026-11-21', '2026-11-27']);

finish();
