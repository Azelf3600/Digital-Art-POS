<?php
// public_html/actions/review-submit.php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Please login to leave a review.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$artwork_id = clean_int($_POST['artwork_id'] ?? 0);
$order_id   = clean_int($_POST['order_id']   ?? 0);
$rating     = clean_int($_POST['rating']     ?? 0);
$comment    = clean($_POST['comment']        ?? '');
$user_id    = current_user_id();

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Please select a rating between 1 and 5.']);
    exit;
}

if (!$artwork_id || !$order_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid artwork or order.']);
    exit;
}

// Verify this user actually bought this artwork in this order
$stmt = $pdo->prepare(
    "SELECT oi.id
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     WHERE o.id = ?
       AND o.user_id = ?
       AND oi.artwork_id = ?
       AND o.status IN ('paid', 'completed')
     LIMIT 1"
);
$stmt->execute([$order_id, $user_id, $artwork_id]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'You can only review artworks you have purchased.']);
    exit;
}

// Check if already reviewed this artwork for this order
$check = $pdo->prepare(
    "SELECT id FROM reviews
     WHERE user_id = ? AND artwork_id = ? AND order_id = ?
     LIMIT 1"
);
$check->execute([$user_id, $artwork_id, $order_id]);

if ($check->fetch()) {
    echo json_encode(['success' => false, 'error' => 'You have already reviewed this artwork.']);
    exit;
}

// Insert review
try {
    $pdo->prepare(
        "INSERT INTO reviews (user_id, artwork_id, order_id, rating, comment, is_approved)
         VALUES (?, ?, ?, ?, ?, 1)"
    )->execute([$user_id, $artwork_id, $order_id, $rating, $comment]);

    // Return updated average so the page can update live
    $avg_stmt = $pdo->prepare(
        "SELECT ROUND(AVG(rating), 1) AS avg, COUNT(*) AS count
         FROM reviews
         WHERE artwork_id = ? AND is_approved = 1"
    );
    $avg_stmt->execute([$artwork_id]);
    $avg = $avg_stmt->fetch();

    echo json_encode([
        'success'      => true,
        'message'      => 'Your review has been submitted. Thank you!',
        'new_avg'      => $avg['avg'],
        'review_count' => $avg['count'],
    ]);

} catch (PDOException $e) {
    // Catch duplicate key in case of race condition
    if ($e->getCode() === '23000') {
        echo json_encode(['success' => false, 'error' => 'You have already reviewed this artwork.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not submit review. Please try again.']);
    }
}