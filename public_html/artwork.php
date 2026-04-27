<?php
// public_html/artwork.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Get artwork ID
$id = clean_int($_GET['id'] ?? 0);

if (!$id) {
    header('Location: gallery.php');
    exit;
}

// Fetch artwork
$stmt = $pdo->prepare(
    "SELECT a.*, c.name AS category, c.slug AS category_slug
     FROM artworks a
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE a.id = ? AND a.is_available = 1
     LIMIT 1"
);
$stmt->execute([$id]);
$artwork = $stmt->fetch();

if (!$artwork) {
    header('Location: gallery.php');
    exit;
}

// Increment views
$pdo->prepare("UPDATE artworks SET views = views + 1 WHERE id = ?")->execute([$id]);

// Fetch reviews
$reviews = $pdo->prepare(
    "SELECT r.rating, r.comment, r.created_at,
            u.username, u.profile_picture
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.artwork_id = ? AND r.is_approved = 1
     ORDER BY r.created_at DESC
     LIMIT 10"
);
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

// Average rating
$rating_row = $pdo->prepare(
    "SELECT ROUND(AVG(rating),1) as avg, COUNT(*) as count
     FROM reviews WHERE artwork_id = ? AND is_approved = 1"
);
$rating_row->execute([$id]);
$rating = $rating_row->fetch();

// Check if favorited by current user
$is_favorited = false;
if (is_logged_in()) {
    $fav = $pdo->prepare(
        "SELECT id FROM favorites WHERE user_id = ? AND artwork_id = ?"
    );
    $fav->execute([current_user_id(), $id]);
    $is_favorited = (bool)$fav->fetch();
}

// Related artworks (same category)
$related = $pdo->prepare(
    "SELECT a.id, a.title, a.price, a.thumbnail
     FROM artworks a
     WHERE a.category_id = ? AND a.id != ? AND a.is_available = 1
     ORDER BY RAND()
     LIMIT 3"
);
$related->execute([$artwork['category_id'], $id]);
$related = $related->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($artwork['title']) ?> — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/artwork.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <div class="artwork-page">
        <div class="container">

            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="gallery.php">Gallery</a>
                <span>→</span>
                <a href="gallery.php?category=<?= clean($artwork['category_slug'] ?? '') ?>">
                    <?= clean($artwork['category'] ?? 'Art') ?>
                </a>
                <span>→</span>
                <span><?= htmlspecialchars($artwork['title']) ?></span>
            </div>

            <!-- Main Layout -->
            <div class="artwork-layout">

                <!-- Left: Image -->
                <div class="artwork-image-col">
                    <div class="artwork-image-wrap">
                        <img
                            src="assets/artworks/watermarked/<?= htmlspecialchars($artwork['watermarked'] ?? $artwork['thumbnail']) ?>"
                            alt="<?= htmlspecialchars($artwork['title']) ?>"
                            class="artwork-image"
                            id="artwork-main-img"
                        >
                        <div class="artwork-image-badge">Watermarked Preview</div>
                    </div>

                    <!-- Thumbnail strip if multiple images exist -->
                    <div class="artwork-thumbs">
                        <div class="artwork-thumb artwork-thumb--active">
                            <img
                                src="assets/artworks/thumbnails/<?= htmlspecialchars($artwork['thumbnail']) ?>"
                                alt="Thumbnail"
                            >
                        </div>
                    </div>
                </div>

                <!-- Right: Info -->
                <div class="artwork-info-col">

                    <!-- Category -->
                    <div class="artwork-cat">
                        <a href="gallery.php?category=<?= clean($artwork['category_slug'] ?? '') ?>">
                            <?= clean($artwork['category'] ?? 'Art') ?>
                        </a>
                    </div>

                    <!-- Title -->
                    <h1 class="artwork-title"><?= htmlspecialchars($artwork['title']) ?></h1>

                    <!-- Rating -->
                    <?php if ($rating['count'] > 0): ?>
                    <div class="artwork-rating">
                        <div class="stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <span class="star <?= $s <= round($rating['avg']) ? 'star--filled' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="artwork-rating__num"><?= $rating['avg'] ?></span>
                        <span class="artwork-rating__count">(<?= $rating['count'] ?> review<?= $rating['count'] !== 1 ? 's' : '' ?>)</span>
                    </div>
                    <?php endif; ?>

                    <!-- Price -->
                    <div class="artwork-price"><?= format_price((float)$artwork['price']) ?></div>

                    <!-- Description -->
                    <?php if (!empty($artwork['description'])): ?>
                    <div class="artwork-desc">
                        <p><?= nl2br(htmlspecialchars($artwork['description'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Meta -->
                    <div class="artwork-meta">
                        <div class="artwork-meta__item">
                            <span class="artwork-meta__label">Category</span>
                            <span class="artwork-meta__value"><?= clean($artwork['category'] ?? '—') ?></span>
                        </div>
                        <div class="artwork-meta__item">
                            <span class="artwork-meta__label">Views</span>
                            <span class="artwork-meta__value"><?= number_format($artwork['views']) ?></span>
                        </div>
                        <div class="artwork-meta__item">
                            <span class="artwork-meta__label">Added</span>
                            <span class="artwork-meta__value"><?= format_date($artwork['created_at']) ?></span>
                        </div>
                        <div class="artwork-meta__item">
                            <span class="artwork-meta__label">File</span>
                            <span class="artwork-meta__value">PNG + PSD</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="artwork-actions">
                        <?php if (is_logged_in()): ?>
                            <a href="cart.php?add=<?= $artwork['id'] ?>" class="btn btn--primary btn--lg artwork-actions__buy">
                                Add to Cart
                            </a>
                            <button
                                class="artwork-actions__fav <?= $is_favorited ? 'active' : '' ?>"
                                id="fav-btn"
                                data-id="<?= $artwork['id'] ?>"
                                title="<?= $is_favorited ? 'Remove from favorites' : 'Add to favorites' ?>"
                            >
                                <?= $is_favorited ? '♥' : '♡' ?>
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="btn btn--primary btn--lg">
                                Login to Purchase
                            </a>
                            <a href="login.php" class="artwork-actions__fav" title="Login to favorite">♡</a>
                        <?php endif; ?>
                    </div>

                    <!-- Commission CTA -->
                    <div class="artwork-commission-cta">
                        <p>Want something similar but custom?</p>
                        <a href="commission.php" class="btn btn--ghost">Request a Commission →</a>
                    </div>

                </div>
            </div>

            <!-- Reviews Section -->
            <section class="artwork-reviews">
                <div class="artwork-reviews__header">
                    <h2 class="artwork-reviews__title">
                        Reviews
                        <?php if ($rating['count'] > 0): ?>
                        <span class="artwork-reviews__count"><?= $rating['count'] ?></span>
                        <?php endif; ?>
                    </h2>
                </div>

                <?php if (count($reviews) > 0): ?>
                <div class="reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-card__header">
                            <div class="review-card__avatar">
                                <?= strtoupper(substr($review['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="review-card__name"><?= clean($review['username']) ?></div>
                                <div class="review-card__date"><?= time_ago($review['created_at']) ?></div>
                            </div>
                            <div class="review-card__stars">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                <span class="star <?= $s <= $review['rating'] ? 'star--filled' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if (!empty($review['comment'])): ?>
                        <p class="review-card__text"><?= htmlspecialchars($review['comment']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="reviews-empty">
                    <p>No reviews yet. Be the first to review this artwork after purchase.</p>
                </div>
                <?php endif; ?>
            </section>

            <!-- Related Artworks -->
            <?php if (count($related) > 0): ?>
            <section class="artwork-related">
                <h2 class="artwork-related__title">More in <?= clean($artwork['category'] ?? 'Gallery') ?></h2>
                <div class="artwork-related__grid">
                    <?php foreach ($related as $rel): ?>
                    <a href="artwork.php?id=<?= $rel['id'] ?>" class="related-card">
                        <div class="related-card__img">
                            <img
                                src="assets/artworks/thumbnails/<?= htmlspecialchars($rel['thumbnail']) ?>"
                                alt="<?= htmlspecialchars($rel['title']) ?>"
                                loading="lazy"
                            >
                        </div>
                        <div class="related-card__info">
                            <div class="related-card__title"><?= htmlspecialchars($rel['title']) ?></div>
                            <div class="related-card__price"><?= format_price((float)$rel['price']) ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/artwork.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>