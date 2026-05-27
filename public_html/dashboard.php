<?php
// public_html/dashboard.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login('login.php');

$user    = get_logged_in_user($pdo);
$user_id = current_user_id();

// ── Fetch Orders ──
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

// ── Fetch Commissions ──
$commissions_stmt = $pdo->prepare(
    "SELECT id, title, tier, status, quoted_price, created_at
     FROM commissions
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 10"
);
$commissions_stmt->execute([$user_id]);
$commissions = $commissions_stmt->fetchAll();

// ── Fetch Notifications (before marking read, so unread dot shows) ──
$notifs_stmt = $pdo->prepare(
    "SELECT id, type, title, message, link, is_read, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 8"
);
$notifs_stmt->execute([$user_id]);
$notifications = $notifs_stmt->fetchAll();

// Count unread BEFORE marking them read
$unread_count = count(array_filter($notifications, fn($n) => !$n['is_read']));

// Mark all as read now that user has seen them
$pdo->prepare(
    "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0"
)->execute([$user_id]);

// ── Fetch Favorites Count ──
$fav_stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$fav_stmt->execute([$user_id]);
$fav_count = (int) $fav_stmt->fetchColumn();

// ── Stats ──
$total_orders      = count($orders);
$total_commissions = count($commissions);
$total_spent       = array_sum(array_column(
    array_filter($orders, fn($o) => in_array($o['status'], ['paid', 'completed'])),
    'total_amount'
));

// ── Active Tab ──
$active_tab = in_array($_GET['tab'] ?? '', ['orders', 'commissions'])
    ? $_GET['tab']
    : 'orders';

// ── Tier Labels ──
$tier_labels = [
    'sketch'      => 'Sketch · $10',
    'full_color'  => 'Full Color · $25',
    'illustrated' => 'Illustrated · $40',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <div class="dashboard-page">
        <div class="container">

            <!-- Header -->
            <div class="dashboard-header">
                <div class="dashboard-header__left">
                    <div class="dashboard-header__greeting">My Account</div>
                    <h1 class="dashboard-header__title">
                        Welcome back,
                        <span><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></span>
                    </h1>
                </div>
                <div class="dashboard-header__actions">
                    <a href="gallery.php" class="btn btn--ghost">Browse Gallery</a>
                    <a href="commission.php" class="btn btn--primary">New Commission</a>
                </div>
            </div>

            <?php show_flash(); ?>

            <!-- Stat Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <span class="stat-card__icon">◻</span>
                    <div class="stat-card__value"><?= $total_orders ?></div>
                    <div class="stat-card__label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <span class="stat-card__icon">◈</span>
                    <div class="stat-card__value"><?= $total_commissions ?></div>
                    <div class="stat-card__label">Commissions</div>
                </div>
                <div class="stat-card">
                    <span class="stat-card__icon">♡</span>
                    <div class="stat-card__value"><?= $fav_count ?></div>
                    <div class="stat-card__label">Favorites</div>
                </div>
                <div class="stat-card">
                    <span class="stat-card__icon">✦</span>
                    <div class="stat-card__value"><?= format_price($total_spent) ?></div>
                    <div class="stat-card__label">Total Spent</div>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="dashboard-grid">

                <!-- Left: Main Content -->
                <div class="dashboard-main">
                    <div class="dash-card">

                        <!-- Tabs -->
                        <div class="dash-tabs">
                            <a
                                href="dashboard.php?tab=orders"
                                class="dash-tab <?= $active_tab === 'orders' ? 'dash-tab--active' : '' ?>"
                            >
                                Orders
                                <?php if ($total_orders > 0): ?>
                                <span style="margin-left:0.3rem;opacity:0.6">(<?= $total_orders ?>)</span>
                                <?php endif; ?>
                            </a>
                            <a
                                href="dashboard.php?tab=commissions"
                                class="dash-tab <?= $active_tab === 'commissions' ? 'dash-tab--active' : '' ?>"
                            >
                                Commissions
                                <?php if ($total_commissions > 0): ?>
                                <span style="margin-left:0.3rem;opacity:0.6">(<?= $total_commissions ?>)</span>
                                <?php endif; ?>
                            </a>
                        </div>

                        <!-- Orders Tab -->
                        <?php if ($active_tab === 'orders'): ?>

                            <?php if ($total_orders > 0): ?>
                            <div class="order-list">
                                <?php foreach ($orders as $order):
                                    $status = order_status_label($order['status']);
                                ?>
                                <a
                                    href="order-tracking.php?order=<?= urlencode($order['order_number']) ?>"
                                    class="order-row"
                                >
                                    <div class="order-row__icon">◎</div>
                                    <div>
                                        <div class="order-row__number">
                                            <?= htmlspecialchars($order['order_number']) ?>
                                        </div>
                                        <div class="order-row__date">
                                            <?= format_date($order['created_at']) ?>
                                            · <?= (int)$order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?>
                                        </div>
                                    </div>
                                    <div class="order-row__amount">
                                        <?= format_price((float)$order['total_amount']) ?>
                                    </div>
                                    <span class="status-badge <?= $status['class'] ?>">
                                        <?= $status['label'] ?>
                                    </span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="dash-empty">
                                <span class="dash-empty__icon">◻</span>
                                <div class="dash-empty__text">No orders yet</div>
                                <a href="gallery.php" class="btn btn--ghost">Browse Gallery</a>
                            </div>
                            <?php endif; ?>

                        <!-- Commissions Tab -->
                        <?php else: ?>

                            <?php if ($total_commissions > 0): ?>
                            <div class="commission-list">
                                <?php foreach ($commissions as $com):
                                    $status = commission_status_label($com['status']);
                                ?>
                                <div class="commission-row">
                                    <div>
                                        <div class="commission-row__title">
                                            <?= htmlspecialchars($com['title']) ?>
                                        </div>
                                        <div class="commission-row__tier">
                                            <?= $tier_labels[$com['tier']] ?? ucfirst(str_replace('_', ' ', $com['tier'])) ?>
                                            <?php if (!empty($com['quoted_price'])): ?>
                                            · Quoted: <?= format_price((float)$com['quoted_price']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="commission-row__date">
                                        <?= time_ago($com['created_at']) ?>
                                    </div>
                                    <span class="status-badge <?= $status['class'] ?>">
                                        <?= $status['label'] ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="dash-empty">
                                <span class="dash-empty__icon">◈</span>
                                <div class="dash-empty__text">No commissions yet</div>
                                <a href="commission.php" class="btn btn--ghost">Request Commission</a>
                            </div>
                            <?php endif; ?>

                        <?php endif; ?>

                    </div>
                </div>

                <!-- Right: Sidebar -->
                <div class="dashboard-side">

                    <!-- Quick Links -->
                    <div class="dash-card">
                        <div class="dash-card__header">
                            <span class="dash-card__title">Quick Links</span>
                        </div>
                        <div class="quick-links">
                            <a href="gallery.php" class="quick-link">
                                <span class="quick-link__icon">◎</span>
                                <span class="quick-link__label">Gallery</span>
                            </a>
                            <a href="commission.php" class="quick-link">
                                <span class="quick-link__icon">◈</span>
                                <span class="quick-link__label">Commission</span>
                            </a>
                            <a href="favorites.php" class="quick-link">
                                <span class="quick-link__icon">♡</span>
                                <span class="quick-link__label">Favorites</span>
                            </a>
                            <a href="order-tracking.php" class="quick-link">
                                <span class="quick-link__icon">◉</span>
                                <span class="quick-link__label">Track Order</span>
                            </a>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="dash-card">
                        <div class="dash-card__header">
                            <span class="dash-card__title">
                                Notifications
                                <?php if ($unread_count > 0): ?>
                                <span style="
                                    background:var(--gold);
                                    color:var(--ink);
                                    font-size:0.6rem;
                                    padding:0.1rem 0.5rem;
                                    border-radius:100px;
                                    margin-left:0.4rem;
                                    font-weight:600;
                                "><?= $unread_count ?></span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if (count($notifications) > 0): ?>
                        <div class="notif-list">
                            <?php foreach ($notifications as $notif): ?>
                            <div class="notif-item <?= !$notif['is_read'] ? 'notif-item--unread' : '' ?>">
                                <div class="notif-item__dot <?= $notif['is_read'] ? 'notif-item__dot--read' : '' ?>"></div>
                                <div style="min-width:0;">
                                    <div class="notif-item__title">
                                        <?php if (!empty($notif['link'])): ?>
                                        <a href="<?= htmlspecialchars($notif['link']) ?>" style="color:inherit;text-decoration:none;">
                                            <?= htmlspecialchars($notif['title']) ?>
                                        </a>
                                        <?php else: ?>
                                        <?= htmlspecialchars($notif['title']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notif-item__msg">
                                        <?= htmlspecialchars($notif['message']) ?>
                                    </div>
                                    <div class="notif-item__time">
                                        <?= time_ago($notif['created_at']) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="dash-empty">
                            <span class="dash-empty__icon">◎</span>
                            <div class="dash-empty__text">No notifications yet</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Account Info -->
                    <div class="dash-card">
                        <div class="dash-card__header">
                            <span class="dash-card__title">Account</span>
                        </div>
                        <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:0.875rem;">
                            <div>
                                <div style="font-family:var(--font-mono);font-size:0.65rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-dim);margin-bottom:0.25rem;">Username</div>
                                <div style="font-size:0.875rem;color:var(--text);"><?= htmlspecialchars($user['username']) ?></div>
                            </div>
                            <div>
                                <div style="font-family:var(--font-mono);font-size:0.65rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-dim);margin-bottom:0.25rem;">Email</div>
                                <div style="font-size:0.875rem;color:var(--text);"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                            <div>
                                <div style="font-family:var(--font-mono);font-size:0.65rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-dim);margin-bottom:0.25rem;">Member Since</div>
                                <div style="font-size:0.875rem;color:var(--text);"><?= format_date($user['created_at']) ?></div>
                            </div>
                            <a href="logout.php" class="btn btn--ghost" style="margin-top:0.25rem;justify-content:center;font-size:0.78rem;">
                                Logout
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>