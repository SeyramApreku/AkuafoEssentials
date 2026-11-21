<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/cart_controller.php';
require_once __DIR__ . '/auth_controller.php';

function find_membership_tier($key)
{
    foreach (membership_tiers() as $t) {
        if (strtolower($t['name']) === strtolower((string)$key)) return $t + ['key' => strtolower($t['name'])];
    }
    return null;
}

// Puts a membership in the cart. The price always comes from membership_tiers() on the
// server, never from the browser. Returns [true, ''] or [false, 'reason'].
function start_membership($key)
{
    $tier = find_membership_tier($key);
    if (!$tier) return [false, 'That membership does not exist.'];

    $user = current_user();
    if (!$user) return [false, 'Please log in first.'];

    $active = (new MembershipClass())->activeFor($user['id']);
    if ($active) {
        return [false, 'You already have an active ' . ucfirst($active['tier']) . ' membership. Contact us if you would like to change plan.'];
    }

    $cart = cart();
    foreach ($cart->items() as $id => $item) {            // one membership per order
        if (($item['type'] ?? '') === 'membership') $cart->remove($id);
    }
    $cart->add([
        'id'     => 'membership-' . $tier['key'],
        'name'   => $tier['name'] . ' Membership',
        'price'  => $tier['price'],
        'qty'    => 1,
        'image'  => img('photo-1665904285523-47c0a6fdfc0e', 96, 96),
        'type'   => 'membership',
        'detail' => 'Billed monthly',
    ]);
    return [true, ''];
}

// Runs after a successful login/registration.
function continue_after_auth()
{
    $key = $_SESSION['pending_tier'] ?? null;
    unset($_SESSION['pending_tier']);
    if ($key) {
        [$ok, $msg] = start_membership($key);
        if ($ok) redirect(url('views/checkout.php'));
        flash_set('error', $msg);
        redirect(url('views/membership.php'));
    }
    redirect(after_login_path());
}