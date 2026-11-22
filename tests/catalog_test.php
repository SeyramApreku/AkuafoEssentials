<?php
// Produce links on farmer and recipe pages (matching a produce name to a product).
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../controllers/catalog_controller.php';

$products = [
    ['id' => 1, 'name' => 'White Yam'],
    ['id' => 2, 'name' => 'Ripe Plantain'],
    ['id' => 3, 'name' => 'Kontomire (Cocoyam Leaves)'],
    ['id' => 5, 'name' => 'Scotch Bonnet Peppers'],
    ['id' => 7, 'name' => 'Dawadawa (Locust Bean)'],
];
$page = fn($id) => url('views/product.php?id=' . $id);

section('Produce name to shop link');
checkTest('Exact name', produce_match_url('Ripe Plantain', $products), $page(2));
checkTest('Case does not matter', produce_match_url('white yam', $products), $page(1));
checkTest('Short name finds the full product', produce_match_url('Kontomire', $products), $page(3));
checkTest('Name with extra words', produce_match_url('Dawadawa (Locust Bean)', $products), $page(7));
checkTest('Partial name inside a longer one', produce_match_url('Bonnet', $products), $page(5));
checkTest('Unknown produce goes to the shop', produce_match_url('Pineapple', $products), url('views/shop.php'));
checkTest('Empty name goes to the shop', produce_match_url('', $products), url('views/shop.php'));

finish();
