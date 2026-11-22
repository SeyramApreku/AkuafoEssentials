<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/invoice_controller.php';
require_once __DIR__ . '/../classes/rating_class.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('index.php'));

$ref     = $_POST['ref'] ?? '';
$t       = $_POST['t'] ?? '';
$rating  = (int)($_POST['rating'] ?? 0);
$comment = mb_substr(trim($_POST['comment'] ?? ''), 0, 1000);

if (!rating_token_ok($ref, $t) || $rating < 1 || $rating > 5) { http_response_code(400); exit('Invalid request.'); }

$order = (new OrderClass())->byRef($ref);
if (!$order || $order['payment_status'] !== 'paid') { http_response_code(404); exit('Order not found.'); }

(new RatingClass())->save((int)$order['id'], $rating, $comment !== '' ? $comment : null);

redirect(url('views/rate.php?' . http_build_query(['ref' => $ref, 't' => $t, 'done' => 1])));