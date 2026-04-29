<?php
// public_html/cart.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Handle ?add= from artwork page
if (isset($_GET['add']) && is_logged_in()) {
    $add_id = clean_int($_GET['add']);
    if ($add_id) {
        $stmt = $pdo->prepare(
            "SELECT id, title FROM artworks WHERE id = ? AND is_available = 1"
        );
        $stmt->execute([$add_id]);
        $art = $stmt->fetch();
        if ($art) {
            $pdo->prepare(
                "INSERT INTO cart (user_id, artwork_id, quantity)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE quantity = 1"
            )->execute([current_user_id(), $add_id]);
            set_flash('success', '"' . $art['title'] . '" added to your cart.');
        }
    }
    // Redirect to clean URL
    header('Location: cart.php');
    exit();
}

// Fetch cart items
$items = [];
$total = 0;

if (is_logged_in()) {
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

    foreach ($items as $item) {
        $total += (float)$item['price'] * (int)$item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cart.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <div class="cart-page">
        <div class="container">

            <div class="cart-header">
                <div class="section__label">Your Order</div>
                <h1 class="cart-header__title">Cart</h1>
            </div>

            <?php show_flash(); ?>

            <?php if (!is_logged_in()): ?>

            <!-- Not logged in -->
            <div class="cart-empty">
                <div class="cart-empty__icon">◻</div>
                <h2>Your cart is empty</h2>
                <p>Login to start adding artworks to your cart.</p>
                <div class="cart-empty__actions">
                    <a href="login.php" class="btn btn--primary">Login</a>
                    <a href="gallery.php" class="btn btn--ghost">Browse Gallery</a>
                </div>
            </div>

            <?php elseif (count($items) === 0): ?>

            <!-- Empty cart -->
            <div class="cart-empty">
                <div class="cart-empty__icon">◻</div>
                <h2>Your cart is empty</h2>
                <p>Browse our gallery and add artworks you love.</p>
                <div class="cart-empty__actions">
                    <a href="gallery.php" class="btn btn--primary">Browse Gallery</a>
                    <a href="commission.php" class="btn btn--ghost">Request Commission</a>
                </div>
            </div>

            <?php else: ?>

            <!-- Cart Layout -->
            <div class="cart-layout">

                <!-- Items -->
                <div class="cart-items">
                    <?php foreach ($items as $item): ?>
                    <div class="cart-item" id="cart-item-<?= $item['cart_id'] ?>">
                        <div class="cart-item__img">
                            <a href="artwork.php?id=<?= $item['artwork_id'] ?>">
                                <img
                                    src="assets/artworks/thumbnails/<?= htmlspecialchars($item['thumbnail']) ?>"
                                    alt="<?= htmlspecialchars($item['title']) ?>"
                                >
                            </a>
                        </div>
                        <div class="cart-item__info">
                            <a href="artwork.php?id=<?= $item['artwork_id'] ?>" class="cart-item__title">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                            <div class="cart-item__meta">Digital Artwork · PNG + PSD</div>
                            <?php if (!$item['is_available']): ?>
                            <div class="cart-item__unavailable">This item is no longer available</div>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item__price">
                            <?= format_price((float)$item['price']) ?>
                        </div>
                        <button
                            class="cart-item__remove"
                            data-cart-id="<?= $item['cart_id'] ?>"
                            title="Remove from cart"
                        >✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary -->
                <div class="cart-summary">
                    <div class="cart-summary__card">
                        <h3 class="cart-summary__title">Order Summary</h3>

                        <div class="cart-summary__rows">
                            <?php foreach ($items as $item): ?>
                            <div class="cart-summary__row">
                                <span><?= htmlspecialchars($item['title']) ?></span>
                                <span><?= format_price((float)$item['price']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="cart-summary__divider"></div>

                        <div class="cart-summary__total">
                            <span>Total</span>
                            <span id="cart-total"><?= format_price($total) ?></span>
                        </div>

                        <div class="cart-summary__note">
                            <span>✦</span>
                            <span>Digital files delivered after payment confirmation.</span>
                        </div>

                        <a href="checkout.php" class="btn btn--primary btn--full btn--lg">
                            Proceed to Checkout
                        </a>

                        <a href="gallery.php" class="cart-summary__continue">
                            ← Continue Shopping
                        </a>
                    </div>
                </div>

            </div>

            <?php endif; ?>

        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/cart.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>