<?php
// public_html/api/get-order-status.php
// Returns full order details for the order tracking page
ob_start();
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
ob_clean();

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$order_number = clean($_GET['order'] ?? '');

if (empty($order_number)) {
    echo json_encode(['error' => 'Order number is required']);
    exit;
}

// Fetch order — must belong to this user
$stmt = $pdo->prepare(
    "SELECT o.id, o.order_number, o.status, o.total_amount,
            o.notes, o.created_at, o.updated_at
     FROM orders o
     WHERE o.order_number = ? AND o.user_id = ?
     LIMIT 1"
);
$stmt->execute([$order_number, current_user_id()]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Fetch order items
$items_stmt = $pdo->prepare(
    "SELECT oi.title, oi.price, oi.quantity,
            a.id AS artwork_id, a.thumbnail
     FROM order_items oi
     LEFT JOIN artworks a ON oi.artwork_id = a.id
     WHERE oi.order_id = ?"
);
$items_stmt->execute([$order['id']]);
$items = $items_stmt->fetchAll();

foreach ($items as &$item) {
    $item['price_fmt']     = format_price((float)$item['price']);
    $item['thumbnail_url'] = $item['thumbnail']
        ? ARTWORK_THUMBS . $item['thumbnail']
        : null;
}
unset($item);

// Fetch payment info
$payment_stmt = $pdo->prepare(
    "SELECT paypal_order_id, paypal_payer_email, amount, status, paid_at
     FROM payments
     WHERE order_id = ?
     LIMIT 1"
);
$payment_stmt->execute([$order['id']]);
$payment = $payment_stmt->fetch();

// Format order status
$status = order_status_label($order['status']);

echo json_encode([
    'order' => [
        'id'            => $order['id'],
        'order_number'  => $order['order_number'],
        'status'        => $order['status'],
        'status_label'  => $status['label'],
        'status_class'  => $status['class'],
        'total'         => format_price((float)$order['total_amount']),
        'notes'         => $order['notes'],
        'created_at'    => format_datetime($order['created_at']),
        'updated_at'    => format_datetime($order['updated_at']),
    ],
    'items'   => $items,
    'payment' => $payment ? [
        'paypal_order_id'   => $payment['paypal_order_id'],
        'payer_email'       => $payment['paypal_payer_email'],
        'amount'            => format_price((float)$payment['amount']),
        'status'            => $payment['status'],
        'paid_at'           => $payment['paid_at']
            ? format_datetime($payment['paid_at'])
            : null,
    ] : null,
]);