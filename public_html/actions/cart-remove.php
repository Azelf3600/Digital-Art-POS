<?php
// public_html/actions/cart-remove.php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$cart_id = clean_int($_POST['cart_id'] ?? 0);

if (!$cart_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid cart item']);
    exit;
}

// Make sure this cart item belongs to the logged-in user
$stmt = $pdo->prepare(
    "DELETE FROM cart WHERE id = ? AND user_id = ?"
);
$stmt->execute([$cart_id, current_user_id()]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Item not found']);
    exit;
}

// Return updated cart total
$total_stmt = $pdo->prepare(
    "SELECT SUM(a.price * c.quantity)
     FROM cart c
     JOIN artworks a ON c.artwork_id = a.id
     WHERE c.user_id = ?"
);
$total_stmt->execute([current_user_id()]);
$total = (float) ($total_stmt->fetchColumn() ?? 0);

$count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM cart WHERE user_id = ?"
);
$count_stmt->execute([current_user_id()]);
$count = (int) $count_stmt->fetchColumn();

echo json_encode([
    'success'         => true,
    'total_formatted' => '$' . number_format($total, 2),
    'count'           => $count,
]);