<?php
// public_html/api/get-dashboard-data.php
// Returns all dashboard data as JSON for AJAX refresh
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

$user_id = current_user_id();

// ── Orders ──
$orders_stmt = $pdo->prepare(
    "SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at,
            COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 10"
);
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll();

foreach ($orders as &$order) {
    $status               = order_status_label($order['status']);
    $order['status_label'] = $status['label'];
    $order['status_class'] = $status['class'];
    $order['total_fmt']    = format_price((float)$order['total_amount']);
    $order['date_fmt']     = format_date($order['created_at']);
}
unset($order);

// ── Commissions ──
$comm_stmt = $pdo->prepare(
    "SELECT id, title, tier, status, quoted_price, created_at
     FROM commissions
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 10"
);
$comm_stmt->execute([$user_id]);
$commissions = $comm_stmt->fetchAll();

foreach ($commissions as &$com) {
    $status               = commission_status_label($com['status']);
    $com['status_label']  = $status['label'];
    $com['status_class']  = $status['class'];
    $com['time_ago']      = time_ago($com['created_at']);
    $com['quoted_fmt']    = $com['quoted_price']
        ? format_price((float)$com['quoted_price'])
        : null;
}
unset($com);

// ── Stats ──
$fav_stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$fav_stmt->execute([$user_id]);
$fav_count = (int) $fav_stmt->fetchColumn();

$spent_stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(total_amount), 0)
     FROM orders
     WHERE user_id = ? AND status IN ('paid', 'completed')"
);
$spent_stmt->execute([$user_id]);
$total_spent = (float) $spent_stmt->fetchColumn();

// ── Unread notifications count ──
$notif_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
);
$notif_stmt->execute([$user_id]);
$unread_notifs = (int) $notif_stmt->fetchColumn();

echo json_encode([
    'orders'        => $orders,
    'commissions'   => $commissions,
    'stats' => [
        'order_count'      => count($orders),
        'commission_count' => count($commissions),
        'fav_count'        => $fav_count,
        'total_spent'      => format_price($total_spent),
        'unread_notifs'    => $unread_notifs,
    ],
]);