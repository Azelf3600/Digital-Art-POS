<?php
// public_html/api/get-notifications.php
// Returns notifications + unread count for navbar badge
ob_start();
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
ob_clean();

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['notifications' => [], 'unread_count' => 0]);
    exit;
}

$user_id = current_user_id();
$limit   = clean_int($_GET['limit'] ?? 8);
$limit   = max(1, min(20, $limit)); // cap between 1 and 20

// Fetch notifications
$stmt = $pdo->prepare(
    "SELECT id, type, title, message, link, is_read, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT ?"
);
$stmt->execute([$user_id, $limit]);
$notifications = $stmt->fetchAll();

// Format each
foreach ($notifications as &$notif) {
    $notif['time_ago'] = time_ago($notif['created_at']);
    $notif['is_read']  = (bool)$notif['is_read'];
}
unset($notif);

// Get unread count separately (not affected by limit)
$count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
);
$count_stmt->execute([$user_id]);
$unread_count = (int) $count_stmt->fetchColumn();

// Optional: mark all as read if ?mark_read=1 passed
if (isset($_GET['mark_read']) && $_GET['mark_read'] === '1') {
    $pdo->prepare(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0"
    )->execute([$user_id]);
    $unread_count = 0;
}

echo json_encode([
    'notifications' => $notifications,
    'unread_count'  => $unread_count,
]);