<?php
// public_html/api/get-cart.php
ob_start();
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
ob_clean();

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['items' => [], 'total' => 0, 'count' => 0]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        c.id AS cart_id,
        c.quantity,
        a.id AS artwork_id,
        a.title,
        a.price,
        a.thumbnail
     FROM cart c
     JOIN artworks a ON c.artwork_id = a.id
     WHERE c.user_id = ?
     ORDER BY c.added_at DESC"
);
$stmt->execute([current_user_id()]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as &$item) {
    $item['price_formatted'] = format_price((float)$item['price']);
    $item['thumbnail_url']   = ARTWORK_THUMBS . $item['thumbnail'];
    $total += (float)$item['price'] * (int)$item['quantity'];
}
unset($item);

echo json_encode([
    'items' => $items,
    'count' => count($items),
    'total' => $total,
    'total_formatted' => format_price($total),
]);