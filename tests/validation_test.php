<?php
// Input validation rules (validate_field) and how they're applied to each form (validate_input).
require __DIR__ . '/bootstrap.php';

$ok   = fn($rule, $v) => validate_field($rule, $v, 'Field')[0];
$out  = fn($rule, $v) => validate_field($rule, $v, 'Field')[1];

section('Landmark (must describe a real place)');
checkTest('"22222" is refused', $ok('landmark', '22222'), false);
checkTest('"849383" is refused', $ok('landmark', '849383'), false);
checkTest('"Opposite Shell, Oxford Street" is fine', $ok('landmark', 'Opposite Shell, Oxford Street'), true);
checkTest('Numbers alongside words are fine ("Behind Block 12, Spintex")', $ok('landmark', 'Behind Block 12, Spintex'), true);
checkTest('Too short ("ab")', $ok('landmark', 'ab'), false);
checkTest('Odd symbols refused ("<b>x</b>")', $ok('landmark', '<b>Shell</b>'), false);
checkTest('Extra spaces are tidied', $out('landmark', '  Near   the   mall '), 'Near the mall');

section('Person name');
checkTest('Maame Afua Kome-Mensah', $ok('person_name', 'Maame Afua Kome-Mensah'), true);
checkTest("Apostrophe (Kofi O'Neil)", $ok('person_name', "Kofi O'Neil"), true);
checkTest('Accents (Ébène Asante)', $ok('person_name', 'Ébène Asante'), true);
checkTest('Numbers refused (John123)', $ok('person_name', 'John123'), false);
checkTest('Single letter refused', $ok('person_name', 'A'), false);
checkTest('Symbols refused (@@@)', $ok('person_name', '@@@'), false);

section('Places (city, country, area)');
checkTest('Accra', $ok('place', 'Accra'), true);
checkTest('Tema Community 25 (digits with words)', $ok('place', 'Tema Community 25'), true);
checkTest('Only numbers refused (12345)', $ok('place', '12345'), false);

section('Ghana phone numbers (stored as 0XXXXXXXXX)');
checkTest('024 123 4567', $out('phone_gh', '024 123 4567'), '0241234567');
checkTest('+233 24 123 4567', $out('phone_gh', '+233 24 123 4567'), '0241234567');
checkTest('233-20-123-4567', $out('phone_gh', '233-20-123-4567'), '0201234567');
checkTest('0596019155 (05 network)', $out('phone_gh', '0596019155'), '0596019155');
checkTest('Too short refused (024123)', $ok('phone_gh', '024123'), false);
checkTest('Wrong start refused (0811234567)', $ok('phone_gh', '0811234567'), false);
checkTest('Letters refused (024abc4567)', $ok('phone_gh', '024abc4567'), false);

section('Email');
checkTest('Normal email (lower-cased)', $out('email', 'Mkomemensah@Gmail.com'), 'mkomemensah@gmail.com');
checkTest('No dot in the domain refused (a@b)', $ok('email', 'a@b'), false);
checkTest('Not an email refused', $ok('email', 'hello'), false);

section('Free text');
checkTest('Delivery note "Leave at the door"', $ok('note', 'Leave at the door'), true);
checkTest('Delivery note of only numbers refused', $ok('note', '12345'), false);
checkTest('Enquiry message too short refused', $ok('message', 'Hi there'), false);
checkTest('Enquiry message fine', $ok('message', 'Wedding for 150 guests on 20 December'), true);
checkTest('Event address in words', $ok('address_text', 'Trinity Hall, Osu'), true);
checkTest('Event address of only numbers refused', $ok('address_text', '12345678'), false);
checkTest('Address name "Home"', $ok('label', 'Home'), true);
checkTest('Job title "Head Chef"', $ok('title', 'Head Chef'), true);
checkTest('Job title with numbers refused', $ok('title', 'Chef 2000'), false);

section('Admin: prices, units and numbers');
checkTest('Price 0 refused', $ok('money', '0'), false);
checkTest('Price 0.00 refused', $ok('money', '0.00'), false);
checkTest('Negative price refused', $ok('money', '-5'), false);
checkTest('Price 22 saved as 22.00', $out('money', '22'), '22.00');
checkTest('Price "GH₵ 22.50" saved as 22.50', $out('money', 'GH₵ 22.50'), '22.50');
checkTest('Price with 3 decimals refused (22.555)', $ok('money', '22.555'), false);
checkTest('Price over GH₵ 100,000 refused', $ok('money', '250000'), false);
checkTest('Unit "0" refused', $ok('unit', '0'), false);
checkTest('Unit "per kilo" fine', $ok('unit', 'per kilo'), true);
checkTest('Unit "per tuber (~1.5kg)" fine', $ok('unit', 'per tuber (~1.5kg)'), true);
checkTest('Serves 0 refused', $ok('whole_positive', '0'), false);
checkTest('Serves 4 fine', $ok('whole_positive', '4'), true);
checkTest('Serves 2.5 refused', $ok('whole_positive', '2.5'), false);
checkTest('Stock 0 allowed (sold out)', $ok('whole', '0'), true);
checkTest('Stock -1 refused', $ok('whole', '-1'), false);
checkTest('Cooking time "45 min"', $ok('duration', '45 min'), true);
checkTest('Cooking time "1 hr 20 min"', $ok('duration', '1 hr 20 min'), true);
checkTest('Cooking time "0" refused', $ok('duration', '0'), false);
checkTest('Difficulty "easy" saved as "Easy"', $out('difficulty', 'easy'), 'Easy');
checkTest('Difficulty "Super hard" refused', $ok('difficulty', 'Super hard'), false);
checkTest('Product name "Rosemary"', $ok('product_name', 'Rosemary'), true);
checkTest('Product name "123" refused', $ok('product_name', '123'), false);
checkTest('Dropdown not chosen refused', $ok('choice', ''), false);
checkTest('Method keeps one step per line', $out('description', "Wash the leaves.\nBoil for 10 minutes."), "Wash the leaves.\nBoil for 10 minutes.");

section('Applied to the admin product form');
$post = ['t' => 'products', 'name' => 'Rosemary', 'twi' => '', 'price' => '0', 'unit' => '0', 'category' => '', 'region' => 'Ashanti', 'farmer' => 'Kofi Asante', 'description' => '', 'badge' => '', 'stock_qty' => ''];
[$errors] = validate_input('admin_save_action.php', $post);
checkTest('Price 0, unit 0 and no category: 3 problems reported', count($errors), 3);
$post = ['t' => 'products', 'name' => 'Rosemary', 'twi' => '', 'price' => '8', 'unit' => 'per bunch', 'category' => 'Herbs', 'region' => 'Ashanti', 'farmer' => 'Kofi Asante', 'description' => '', 'badge' => '', 'stock_qty' => '0'];
[$errors] = validate_input('admin_save_action.php', $post);
checkTest('A good product passes', $errors, []);
checkTest('  ...price stored as 8.00', $post['price'], '8.00');
$post = ['t' => 'regions', 'name' => 'Volta'];
[$errors] = validate_input('admin_save_action.php', $post);
checkTest('Columns a table lacks are not required (regions has only a name)', $errors, []);

section('Applied to the address form');
$post = ['act' => 'save', 'landmark' => '22222', 'phone' => '024 123 4567', 'notes' => '', 'label' => 'Home'];
[$errors] = validate_input('address_action.php', $post);
checkTest('Landmark "22222" stops the address being saved', count($errors), 1);
$post = ['act' => 'save', 'landmark' => 'Opposite Shell', 'phone' => '+233 24 123 4567', 'notes' => '', 'label' => ''];
[$errors] = validate_input('address_action.php', $post);
checkTest('A good address passes', $errors, []);
checkTest('  ...and its phone is stored as 0241234567', $post['phone'], '0241234567');
$post = ['act' => 'delete', 'landmark' => '22222'];
[$errors] = validate_input('address_action.php', $post);
checkTest('Deleting an address is not blocked by these rules', $errors, []);
$post = ['act' => 'waitlist', 'area_name' => '999'];
[$errors] = validate_input('address_action.php', $post);
checkTest('Waitlist area "999" refused', count($errors), 1);

section('Applied to registration');
$post = ['customer_name' => 'Maame Kome-Mensah', 'customer_email' => 'mkomemensah@gmail.com', 'customer_country' => 'Ghana',
         'customer_city' => 'Accra', 'customer_contact' => '0596019155'];
[$errors] = validate_input('register_action.php', $post);
checkTest('Your registration details pass', $errors, []);
$post['customer_name'] = 'M4ame'; $post['customer_contact'] = '12';
[$errors] = validate_input('register_action.php', $post);
checkTest('Bad name and bad phone are both reported', count($errors), 2);

finish();
