<?php
// public_html/actions/cart-add.php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Must be logged in
if (!is_logged_in()) {
    // Check if AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Please login to add items to cart.']);
        exit;
    }
    header('Location: ../login.php');
    exit();
}

$artwork_id = clean_int($_POST['artwork_id'] ?? $_GET['add'] ?? 0);

if (!$artwork_id) {
    header('Location: ../gallery.php');
    exit();
}

// Check artwork exists and is available
$stmt = $pdo->prepare(
    "SELECT id, title, price FROM artworks
     WHERE id = ? AND is_available = 1 LIMIT 1"
);
$stmt->execute([$artwork_id]);
$artwork = $stmt->fetch();

if (!$artwork) {
    set_flash('error', 'Artwork not found or no longer available.');
    header('Location: ../gallery.php');
    exit();
}

// Add to cart — if already exists just keep it (digital art = 1 copy)
try {
    $pdo->prepare(
        "INSERT INTO cart (user_id, artwork_id, quantity)
         VALUES (?, ?, 1)
         ON DUPLICATE KEY UPDATE quantity = 1"
    )->execute([current_user_id(), $artwork_id]);

    // AJAX response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        // Get updated cart count
        $count = $pdo->prepare(
            "SELECT COUNT(*) FROM cart WHERE user_id = ?"
        );
        $count->execute([current_user_id()]);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => '"' . $artwork['title'] . '" added to cart.',
            'count'   => (int) $count->fetchColumn(),
        ]);
        exit;
    }

    set_flash('success', '"' . $artwork['title'] . '" has been added to your cart.');
    header('Location: ../cart.php');
    exit();

} catch (PDOException $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Could not add to cart.']);
        exit;
    }
    set_flash('error', 'Could not add to cart. Please try again.');
    header('Location: ../gallery.php');
    exit();
}