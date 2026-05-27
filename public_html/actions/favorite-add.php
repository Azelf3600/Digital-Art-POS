<?php
// public_html/actions/favorite-add.php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Please login to save favorites.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$artwork_id = clean_int($_POST['artwork_id'] ?? 0);

if (!$artwork_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid artwork.']);
    exit;
}

// Check artwork exists
$stmt = $pdo->prepare("SELECT id FROM artworks WHERE id = ? AND is_available = 1 LIMIT 1");
$stmt->execute([$artwork_id]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Artwork not found.']);
    exit;
}

// Insert — ignore if already favorited
try {
    $pdo->prepare(
        "INSERT IGNORE INTO favorites (user_id, artwork_id) VALUES (?, ?)"
    )->execute([current_user_id(), $artwork_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Could not save favorite.']);
}