<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/catalog_class.php';

function catalog() { static $c; return $c ??= new CatalogClass(); }

// Shop link for a produce name used on farmer and recipe pages ("Kontomire" -> the Kontomire product page).
// Exact name first, then a product whose name starts with or contains it; otherwise the whole shop.
function produce_url($name)
{
    static $products = null;
    $products ??= catalog()->products();
    return produce_match_url($name, $products);
}

// Pure matching (no database), so it can be unit tested.
function produce_match_url($name, array $products)
{
    $n = mb_strtolower(trim((string)$name));
    if ($n === '') return url('views/shop.php');
    foreach ([fn($p) => $p === $n, fn($p) => strpos($p, $n) === 0 || strpos($n, $p) === 0, fn($p) => strpos($p, $n) !== false] as $test) {
        foreach ($products as $p) {
            if ($test(mb_strtolower($p['name']))) return url('views/product.php?id=' . (int)$p['id']);
        }
    }
    return url('views/shop.php');
}

require_once __DIR__ . '/../classes/entertaining_class.php';
function entertaining() { static $c; return $c ??= new EntertainingClass(); }
