<?php
// Delivery: GhanaPostGPS addresses, address lines on orders, zone days.
require __DIR__ . '/bootstrap.php';

section('GhanaPostGPS validation');
checkTest('Standard format', normalize_gps('GA-123-4567'), 'GA-123-4567');
checkTest('Lowercase with spaces', normalize_gps('ga 123 4567'), 'GA-123-4567');
checkTest('No dashes, 7 digits', normalize_gps('GA1234567'), 'GA-123-4567');
checkTest('4-digit middle block', normalize_gps('GT-0123-4567'), 'GT-0123-4567');
checkTest('No dashes, 8 digits', normalize_gps('ga12345678'), 'GA-1234-5678');
checkTest('Mixed dash and space', normalize_gps('GA-123 4567'), 'GA-123-4567');
checkTest('Street address is refused', normalize_gps('V-2, DODI ROAD'), null);
checkTest('One letter is refused', normalize_gps('G-123-4567'), null);
checkTest('Short middle block is refused', normalize_gps('GA-12-3456'), null);
checkTest('Short last block is refused', normalize_gps('GA-123-456'), null);
checkTest('Long last block is refused', normalize_gps('GA-123-45678'), null);
checkTest('Empty is refused', normalize_gps(''), null);

section('Delivery address on orders');
checkTest('Full line', order_address_line(['gps_address' => 'GA-123-4567', 'area_name' => 'Osu', 'landmark' => 'Opposite Shell']), 'GA-123-4567, Osu · near Opposite Shell');
checkTest('Without landmark', order_address_line(['gps_address' => 'GA-123-4567', 'area_name' => 'Osu', 'landmark' => null]), 'GA-123-4567, Osu');
checkTest('Old order without address', order_address_line(['gps_address' => null]), '');

section('Zone delivery days');
checkTest('Monday to Sunday', zone_day_names(), ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']);

finish();
