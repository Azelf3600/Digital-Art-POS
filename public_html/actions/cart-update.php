<?php
// public_html/actions/cart-update.php
// Note: Digital artworks are always quantity 1
// This file exists for completeness but enforces qty = 1
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$cart_id  = clean_int($_POST['cart_id']  ?? 0);
$quantity = clean_int($_POST['quantity'] ?? 1);

// Digital art — always quantity 1
$quantity = 1;

if (!$cart_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid cart item']);
    exit;
}

$stmt = $pdo->prepare(
    "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?"
);
$stmt->execute([$quantity, $cart_id, current_user_id()]);

echo json_encode(['success' => true]);