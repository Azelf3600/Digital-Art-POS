<?php
// public_html/actions/favorite-remove.php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
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

// Delete — only if it belongs to this user
try {
    $stmt = $pdo->prepare(
        "DELETE FROM favorites WHERE user_id = ? AND artwork_id = ?"
    );
    $stmt->execute([current_user_id(), $artwork_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Could not remove favorite.']);
}