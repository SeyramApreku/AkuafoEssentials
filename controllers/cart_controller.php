<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/cart_class.php';

function cart() { static $c; return $c ??= new CartClass(); }

// Build a cart item from a posted type + key, reading price/name from the DB (never trust posted prices).
function build_cart_item($type, $key, $qty, $sizeIdx = 0, $freq = '', $day = '', $window = '')
{
    require_once __DIR__ . '/catalog_controller.php';
    $qty = max(1, (int)$qty);
    if ($type === 'produce') {
        $p = catalog()->product((int)$key);
        if (!$p) return null;
        return ['id' => 'p-' . $p['id'], 'name' => $p['name'], 'price' => (float)$p['price'], 'qty' => $qty, 'type' => 'produce', 'image' => img($p['image'], 200, 200), 'detail' => $p['unit']];
    }
        if ($type === 'box') {
        foreach (catalog()->boxes() as $b) if ($b['id'] === $key && isset($b['sizes'][(int)$sizeIdx])) {
            $s = $b['sizes'][(int)$sizeIdx];
            $id = "box-{$b['id']}-$sizeIdx";
            $when = 'Weekly';
            if ($freq !== '' || $day !== '' || $window !== '') {
                if (!isset(frequencies()[$freq]) || !in_array($day, delivery_days()) || !in_array($window, time_windows())) return null;
                $when = frequencies()[$freq] . ', ' . $day . 's, ' . $window;
                $id .= '-' . substr(md5($when), 0, 8);
            }
            return ['id' => $id, 'name' => "{$b['name']} ({$s['label']})", 'price' => (float)$s['price'], 'qty' => 1, 'type' => 'box', 'image' => img($b['image'], 200, 200), 'detail' => $when . ' · ' . $s['description']];
        }
    }
    
    if ($type === 'ent') {
        $i = entertaining()->item((int)$key);
        if (!$i || !(int)$i['is_available']) return null;
        $detail = ($i['serves'] ? 'Serves ' . (int)$i['serves'] . ' · ' : '') . notice_label($i['notice_hours']) . ' notice';
        return ['id' => 'ent-' . $i['id'], 'name' => $i['name'], 'price' => (float)$i['price'], 'qty' => min(99, $qty), 'type' => 'entertaining',
                'image' => img($i['image'], 200, 200), 'detail' => $detail, 'notice_hours' => (int)$i['notice_hours']];
    }
    if ($type === 'hamper') {
        foreach (catalog()->hampers() as $h) if ($h['id'] === $key)
            return ['id' => $h['id'], 'name' => $h['name'], 'price' => (float)$h['price'], 'qty' => 1, 'type' => 'preorder', 'image' => img($h['image'], 200, 200), 'detail' => $h['occasion'] . ' · Serves ' . $h['serves']];
    }
    if ($type === 'bulk') {
        foreach (catalog()->bulkItems() as $b) if ($b['id'] === $key)
            return ['id' => $b['id'], 'name' => $b['name'], 'price' => (float)$b['price'], 'qty' => 1, 'type' => 'preorder', 'image' => img($b['image'], 200, 200), 'detail' => $b['detail']];
    }
    return null;
}

function build_custom_box($qtys, $freq, $day, $window)
{
    require_once __DIR__ . '/catalog_controller.php';
    if (!isset(frequencies()[$freq]) || !in_array($day, delivery_days()) || !in_array($window, time_windows())) return null;
    $lines = []; $total = 0;
    foreach ($qtys as $id => $q) {
        $q = (int)$q;
        if ($q < 1) continue;
        $p = catalog()->product((int)$id);
        if (!$p) continue;
        $lines[] = $p['name'] . ' ×' . $q;
        $total += $p['price'] * $q;
    }
    if (!$lines) return null;
    $schedule = frequencies()[$freq] . ', ' . $day . 's, ' . $window;
    return ['id' => 'custom-' . substr(md5($schedule . implode('|', $lines)), 0, 10), 'name' => 'Custom Box',
            'price' => (float)$total, 'qty' => 1, 'type' => 'box',
            'image' => img('photo-1627989147125-a004d05946d3', 200, 200),
            'detail' => $schedule . ' · ' . implode(', ', $lines)];
}
