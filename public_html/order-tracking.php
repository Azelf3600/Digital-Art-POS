<?php
// public_html/order-tracking.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login('login.php');

$user_id      = current_user_id();
$order_number = clean($_GET['order'] ?? '');
$order        = null;
$items        = [];
$payment      = null;

if (!empty($order_number)) {

    // Fetch order — must belong to this user
    $stmt = $pdo->prepare(
        "SELECT id, order_number, status, total_amount, notes, created_at, updated_at
         FROM orders
         WHERE order_number = ? AND user_id = ?
         LIMIT 1"
    );
    $stmt->execute([$order_number, $user_id]);
    $order = $stmt->fetch();

    if ($order) {
        // Fetch items
        $items_stmt = $pdo->prepare(
            "SELECT oi.title, oi.price, oi.quantity,
                    a.id AS artwork_id, a.thumbnail
             FROM order_items oi
             LEFT JOIN artworks a ON oi.artwork_id = a.id
             WHERE oi.order_id = ?"
        );
        $items_stmt->execute([$order['id']]);
        $items = $items_stmt->fetchAll();

        // Fetch payment
        $pay_stmt = $pdo->prepare(
            "SELECT paypal_order_id, paypal_payer_email, amount, status, paid_at
             FROM payments WHERE order_id = ? LIMIT 1"
        );
        $pay_stmt->execute([$order['id']]);
        $payment = $pay_stmt->fetch();
    }
}

// Fetch all orders for the dropdown
$all_orders = $pdo->prepare(
    "SELECT order_number, status, created_at
     FROM orders WHERE user_id = ?
     ORDER BY created_at DESC"
);
$all_orders->execute([$user_id]);
$all_orders = $all_orders->fetchAll();

// Status steps for the progress tracker
$status_steps = ['pending', 'paid', 'processing', 'completed'];
$current_step = array_search($order['status'] ?? 'pending', $status_steps);
if ($current_step === false) $current_step = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/checkout.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Order Progress Tracker ── */
        .order-progress {
            display: flex;
            align-items: center;
            gap: 0;
            margin: 2rem 0;
            position: relative;
        }

        .progress-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 16px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }

        .progress-step--done:not(:last-child)::after {
            background: var(--gold);
        }

        .progress-step__dot {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--surface-2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: var(--text-dim);
            position: relative;
            z-index: 1;
            transition: var(--transition);
        }

        .progress-step--done .progress-step__dot {
            border-color: var(--gold);
            background: var(--gold);
            color: var(--ink);
        }

        .progress-step--active .progress-step__dot {
            border-color: var(--gold);
            background: var(--gold-dim);
            color: var(--gold);
            box-shadow: 0 0 0 4px rgba(201,169,110,0.15);
        }

        .progress-step__label {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-top: 0.5rem;
            text-align: center;
        }

        .progress-step--done .progress-step__label,
        .progress-step--active .progress-step__label {
            color: var(--gold);
        }

        /* ── Order Select ── */
        .order-select-wrap {
            margin-bottom: 2rem;
        }

        .order-select-label {
            font-family: var(--font-mono);
            font-size: 0.72rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-bottom: 0.5rem;
            display: block;
        }

        .order-select {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            font-family: var(--font-mono);
            font-size: 0.8rem;
            padding: 0.75rem 1rem;
            outline: none;
            cursor: pointer;
            width: 100%;
            max-width: 400px;
            transition: var(--transition);
        }

        .order-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.1);
        }

        /* ── Cancelled Banner ── */
        .order-cancelled-banner {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: #f87171;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <div class="dashboard-page">
        <div class="container">

            <!-- Header -->
            <div class="dashboard-header">
                <div class="dashboard-header__left">
                    <div class="dashboard-header__greeting">My Account</div>
                    <h1 class="dashboard-header__title">Order Tracking</h1>
                </div>
                <div class="dashboard-header__actions">
                    <a href="dashboard.php" class="btn btn--ghost">← Dashboard</a>
                </div>
            </div>

            <?php show_flash(); ?>

            <!-- Order Selector -->
            <?php if (count($all_orders) > 0): ?>
            <div class="order-select-wrap">
                <label class="order-select-label" for="order-select">Select an Order</label>
                <select
                    class="order-select"
                    id="order-select"
                    onchange="window.location.href='order-tracking.php?order='+this.value"
                >
                    <option value="">— Choose an order —</option>
                    <?php foreach ($all_orders as $o): ?>
                    <option
                        value="<?= htmlspecialchars($o['order_number']) ?>"
                        <?= $order_number === $o['order_number'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($o['order_number']) ?>
                        · <?= format_date($o['created_at']) ?>
                        · <?= order_status_label($o['status'])['label'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!$order && !empty($order_number)): ?>
            <!-- Order not found -->
            <div class="dash-card" style="padding:3rem;text-align:center;">
                <div style="font-size:2.5rem;color:var(--text-dim);margin-bottom:1rem;">◎</div>
                <div style="font-family:var(--font-mono);font-size:0.8rem;color:var(--text-dim);">
                    Order not found or does not belong to your account.
                </div>
            </div>

            <?php elseif (!$order): ?>
            <!-- No order selected -->
            <div class="dash-card" style="padding:3rem;text-align:center;">
                <div style="font-size:2.5rem;color:var(--text-dim);margin-bottom:1rem;">◉</div>
                <div style="font-family:var(--font-serif);font-size:1.25rem;font-weight:300;color:var(--text-soft);margin-bottom:0.5rem;">
                    Select an order above to track it
                </div>
                <?php if (count($all_orders) === 0): ?>
                <div style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-dim);margin-top:0.5rem;">
                    You have no orders yet.
                    <a href="gallery.php" style="color:var(--gold);">Browse the gallery →</a>
                </div>
                <?php endif; ?>
            </div>

            <?php else: ?>

            <!-- Order Detail -->
            <?php $status_info = order_status_label($order['status']); ?>

            <?php if ($order['status'] === 'cancelled'): ?>
            <div class="order-cancelled-banner">
                <span>✕</span>
                <span>This order has been cancelled.</span>
            </div>
            <?php endif; ?>

            <div class="checkout-layout" style="margin-top:0;">

                <!-- Left: Order Info -->
                <div class="checkout-main">

                    <!-- Progress Tracker (only for non-cancelled) -->
                    <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'refunded'): ?>
                    <div class="checkout-section">
                        <h2 class="checkout-section__title">Order Progress</h2>
                        <div class="order-progress">
                            <?php
                            $steps = [
                                'pending'    => 'Pending',
                                'paid'       => 'Paid',
                                'processing' => 'Processing',
                                'completed'  => 'Completed',
                            ];
                            foreach ($steps as $key => $label):
                                $step_keys  = array_keys($steps);
                                $active_pos = array_search($order['status'], $step_keys);
                                $current_pos = array_search($key, $step_keys);

                                $is_active = $key === $order['status'];
                                $is_done   = $current_pos < $active_pos;
                                $class = $is_active ? 'progress-step--active'
                                        : ($is_done  ? 'progress-step--done' : '');
                            ?>
                            <div class="progress-step <?= $class ?>">
                                <div class="progress-step__dot">
                                    <?= $is_done ? '✓' : '' ?>
                                </div>
                                <div class="progress-step__label"><?= $label ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Order Items -->
                    <div class="checkout-section">
                        <h2 class="checkout-section__title">
                            Items
                            <span class="status-badge <?= $status_info['class'] ?>" style="margin-left:0.5rem;">
                                <?= $status_info['label'] ?>
                            </span>
                        </h2>
                        <div class="checkout-items">
                            <?php foreach ($items as $item): ?>
                            <div class="checkout-item">
                                <div class="checkout-item__img">
                                    <?php if ($item['thumbnail']): ?>
                                    <img
                                        src="assets/artworks/thumbnails/<?= htmlspecialchars($item['thumbnail']) ?>"
                                        alt="<?= htmlspecialchars($item['title']) ?>"
                                    >
                                    <?php endif; ?>
                                </div>
                                <div class="checkout-item__info">
                                    <div class="checkout-item__title">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </div>
                                    <div class="checkout-item__meta">
                                        Digital Artwork · Qty: <?= (int)$item['quantity'] ?>
                                    </div>
                                </div>
                                <div class="checkout-item__price">
                                    <?= format_price((float)$item['price']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <?php if ($payment): ?>
                    <div class="checkout-section">
                        <h2 class="checkout-section__title">Payment</h2>
                        <div class="checkout-info-grid">
                            <div class="checkout-info-item">
                                <span class="checkout-info-item__label">Method</span>
                                <span class="checkout-info-item__value">PayPal</span>
                            </div>
                            <div class="checkout-info-item">
                                <span class="checkout-info-item__label">Payer Email</span>
                                <span class="checkout-info-item__value">
                                    <?= htmlspecialchars($payment['paypal_payer_email'] ?: '—') ?>
                                </span>
                            </div>
                            <div class="checkout-info-item">
                                <span class="checkout-info-item__label">Amount Paid</span>
                                <span class="checkout-info-item__value">
                                    <?= format_price((float)$payment['amount']) ?>
                                </span>
                            </div>
                            <?php if ($payment['paid_at']): ?>
                            <div class="checkout-info-item">
                                <span class="checkout-info-item__label">Paid At</span>
                                <span class="checkout-info-item__value">
                                    <?= format_datetime($payment['paid_at']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($order['notes'])): ?>
                    <div class="checkout-section">
                        <h2 class="checkout-section__title">Order Notes</h2>
                        <p style="font-size:0.875rem;color:var(--text-soft);line-height:1.6;">
                            <?= htmlspecialchars($order['notes']) ?>
                        </p>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- Right: Summary -->
                <div class="checkout-summary">
                    <div class="checkout-summary__card">
                        <h3 class="checkout-summary__title">Order Summary</h3>

                        <div class="checkout-summary__rows">
                            <?php foreach ($items as $item): ?>
                            <div class="checkout-summary__row">
                                <span><?= htmlspecialchars($item['title']) ?></span>
                                <span><?= format_price((float)$item['price']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="checkout-summary__divider"></div>

                        <div class="checkout-summary__total">
                            <span>Total</span>
                            <span><?= format_price((float)$order['total_amount']) ?></span>
                        </div>

                        <div style="margin-top:1.25rem;display:flex;flex-direction:column;gap:0.5rem;">
                            <div style="font-family:var(--font-mono);font-size:0.65rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-dim);">
                                Order Number
                            </div>
                            <div style="font-family:var(--font-mono);font-size:0.82rem;color:var(--text);">
                                <?= htmlspecialchars($order['order_number']) ?>
                            </div>
                            <div style="font-family:var(--font-mono);font-size:0.65rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-dim);margin-top:0.5rem;">
                                Placed On
                            </div>
                            <div style="font-size:0.82rem;color:var(--text);">
                                <?= format_datetime($order['created_at']) ?>
                            </div>
                            <div style="font-family:var(--font-mono);font-size:0.65rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-dim);margin-top:0.5rem;">
                                Last Updated
                            </div>
                            <div style="font-size:0.82rem;color:var(--text);">
                                <?= format_datetime($order['updated_at']) ?>
                            </div>
                        </div>
                    </div>

                    <a href="dashboard.php" class="checkout-back">← Back to Dashboard</a>
                </div>

            </div>

            <?php endif; ?>

        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>