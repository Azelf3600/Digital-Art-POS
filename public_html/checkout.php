<?php
// public_html/checkout.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Must be logged in
require_login('login.php');

// Fetch cart items
$stmt = $pdo->prepare(
    "SELECT
        c.id AS cart_id,
        c.quantity,
        a.id AS artwork_id,
        a.title,
        a.price,
        a.thumbnail,
        a.is_available
     FROM cart c
     JOIN artworks a ON c.artwork_id = a.id
     WHERE c.user_id = ?
     ORDER BY c.added_at DESC"
);
$stmt->execute([current_user_id()]);
$items = $stmt->fetchAll();

// Redirect to cart if empty
if (empty($items)) {
    set_flash('info', 'Your cart is empty.');
    header('Location: cart.php');
    exit();
}

// Remove unavailable items
$available_items = array_filter($items, fn($i) => $i['is_available']);

if (empty($available_items)) {
    set_flash('error', 'None of your cart items are currently available.');
    header('Location: cart.php');
    exit();
}

// Calculate total
$total = array_sum(array_map(
    fn($i) => (float)$i['price'] * (int)$i['quantity'],
    $available_items
));

// Get current user info
$user = get_logged_in_user($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/checkout.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=USD" defer></script>
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <div class="checkout-page">
        <div class="container">

            <div class="checkout-header">
                <div class="section__label">Secure Checkout</div>
                <h1 class="checkout-header__title">Complete Your Order</h1>
            </div>

            <?php show_flash(); ?>

            <div class="checkout-layout">

                <!-- Left: Order Details -->
                <div class="checkout-main">

                    <!-- Items -->
                    <div class="checkout-section">
                        <h2 class="checkout-section__title">Order Items</h2>
                        <div class="checkout-items">
                            <?php foreach ($available_items as $item): ?>
                            <div class="checkout-item">
                                <div class="checkout-item__img">
                                    <img
                                        src="assets/artworks/thumbnails/<?= htmlspecialchars($item['thumbnail']) ?>"
                                        alt="<?= htmlspecialchars($item['title']) ?>"
                                    >
                                </div>
                                <div class="checkout-item__info">
                                    <div class="checkout-item__title">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </div>
                                    <div class="checkout-item__meta">
                                        Digital Artwork · PNG + PSD · Instant Delivery
                                    </div>
                                </div>
                                <div class="checkout-item__price">
                                    <?= format_price((float)$item['price']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="checkout-section">
                        <h2 class="checkout-section__title">Your Information</h2>
                        <div class="checkout-info-grid">
                            <div class="checkout-info-item">
                                <span class="checkout-info-item__label">Name</span>
                                <span class="checkout-info-item__value">
                                    <?= clean($user['full_name'] ?: $user['username']) ?>
                                </span>
                            </div>
                            <div class="checkout-info-item">
                                <span class="checkout-info-item__label">Email</span>
                                <span class="checkout-info-item__value">
                                    <?= clean($user['email']) ?>
                                </span>
                            </div>
                            <div class="checkout-info-item">
                                <span class="checkout-info-item__label">Delivery</span>
                                <span class="checkout-info-item__value">
                                    Digital download after payment
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="checkout-section">
                        <h2 class="checkout-section__title">Order Notes <span class="optional">(optional)</span></h2>
                        <textarea
                            id="order-notes"
                            class="checkout-notes"
                            placeholder="Any special instructions or notes for your order..."
                            rows="3"
                            maxlength="500"
                        ></textarea>
                    </div>

                    <!-- Payment -->
                    <div class="checkout-section">
                        <h2 class="checkout-section__title">Payment</h2>
                        <div class="checkout-payment">
                            <div class="checkout-payment__info">
                                <span class="checkout-payment__lock">🔒</span>
                                <span>Secured by PayPal. We never see your card details.</span>
                            </div>
                            <!-- PayPal button renders here -->
                            <div id="paypal-button-container"></div>
                            <div id="paypal-loading" class="paypal-loading">
                                <div class="paypal-loading__spinner"></div>
                                <span>Loading PayPal...</span>
                            </div>
                            <div id="paypal-error" class="paypal-error" style="display:none">
                                PayPal failed to load. Please refresh the page.
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right: Summary -->
                <div class="checkout-summary">
                    <div class="checkout-summary__card">
                        <h3 class="checkout-summary__title">Order Summary</h3>

                        <div class="checkout-summary__rows">
                            <?php foreach ($available_items as $item): ?>
                            <div class="checkout-summary__row">
                                <span><?= htmlspecialchars($item['title']) ?></span>
                                <span><?= format_price((float)$item['price']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="checkout-summary__divider"></div>

                        <div class="checkout-summary__total">
                            <span>Total</span>
                            <span><?= format_price($total) ?></span>
                        </div>

                        <div class="checkout-summary__badges">
                            <div class="checkout-badge">🔒 Secure Payment</div>
                            <div class="checkout-badge">⚡ Instant Delivery</div>
                            <div class="checkout-badge">✦ Original Files</div>
                        </div>
                    </div>

                    <a href="cart.php" class="checkout-back">← Back to Cart</a>
                </div>

            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <!-- Pass PHP data to JS -->
    <script>
        const CHECKOUT = {
            total:    <?= number_format($total, 2, '.', '') ?>,
            currency: 'USD',
            items:    <?= json_encode(array_values(array_map(fn($i) => [
                'id'    => $i['artwork_id'],
                'title' => $i['title'],
                'price' => number_format((float)$i['price'], 2, '.', ''),
                'qty'   => $i['quantity'],
            ], $available_items))) ?>,
            actionUrl: 'actions/payment.php',
        };
    </script>
    <script src="assets/js/checkout.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>