<?php
// public_html/favorites.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login('login.php');

$user_id = current_user_id();

// Fetch favorites
$stmt = $pdo->prepare(
    "SELECT
        a.id, a.title, a.price, a.thumbnail, a.is_available,
        c.name AS category,
        f.created_at AS favorited_at
     FROM favorites f
     JOIN artworks a ON f.artwork_id = a.id
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE f.user_id = ?
     ORDER BY f.created_at DESC"
);
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorites — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/gallery.css">
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
                        Favorites
                        <?php if (count($favorites) > 0): ?>
                        <span style="font-size:1.25rem;color:var(--text-dim);font-style:italic;">
                            (<?= count($favorites) ?>)
                        </span>
                        <?php endif; ?>
                    </h1>
                </div>
                <div class="dashboard-header__actions">
                    <a href="dashboard.php" class="btn btn--ghost">← Dashboard</a>
                    <a href="gallery.php" class="btn btn--primary">Browse Gallery</a>
                </div>
            </div>

            <?php show_flash(); ?>

            <?php if (count($favorites) === 0): ?>

            <!-- Empty State -->
            <div class="cart-empty" style="margin-top:2rem;">
                <div class="cart-empty__icon">♡</div>
                <h2>No favorites yet</h2>
                <p>Browse the gallery and click the heart icon on artworks you love.</p>
                <div class="cart-empty__actions">
                    <a href="gallery.php" class="btn btn--primary">Browse Gallery</a>
                    <a href="dashboard.php" class="btn btn--ghost">Back to Dashboard</a>
                </div>
            </div>

            <?php else: ?>

            <!-- Favorites Grid -->
            <div class="gallery-grid" style="margin-top:0.5rem;">
                <?php foreach ($favorites as $i => $art): ?>
                <div class="artwork-card" style="--delay:<?= $i * 0.05 ?>s">
                    <div class="artwork-card__img">
                        <img
                            src="assets/artworks/thumbnails/<?= htmlspecialchars($art['thumbnail']) ?>"
                            alt="<?= htmlspecialchars($art['title']) ?>"
                            loading="lazy"
                        >
                        <?php if (!$art['is_available']): ?>
                        <div style="
                            position:absolute;inset:0;
                            background:rgba(0,0,0,0.6);
                            display:flex;align-items:center;justify-content:center;
                        ">
                            <span style="font-family:var(--font-mono);font-size:0.7rem;color:#f87171;letter-spacing:0.1em;text-transform:uppercase;">
                                Unavailable
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="artwork-card__overlay">
                            <a href="artwork.php?id=<?= $art['id'] ?>" class="artwork-card__view">
                                View Work
                            </a>
                            <button
                                class="artwork-card__fav active"
                                data-id="<?= $art['id'] ?>"
                                title="Remove from favorites"
                            >♥</button>
                        </div>
                        <div class="artwork-card__category">
                            <?= htmlspecialchars($art['category'] ?? 'Art') ?>
                        </div>
                    </div>
                    <div class="artwork-card__info">
                        <div class="artwork-card__title">
                            <?= htmlspecialchars($art['title']) ?>
                        </div>
                        <div class="artwork-card__price">
                            <?= $art['is_available']
                                ? format_price((float)$art['price'])
                                : '<span style="color:var(--text-dim);">Unavailable</span>'
                            ?>
                        </div>
                    </div>
                    <div style="
                        font-family:var(--font-mono);
                        font-size:0.65rem;
                        color:var(--text-dim);
                        letter-spacing:0.05em;
                        margin-top:0.25rem;
                    ">
                        Saved <?= time_ago($art['favorited_at']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>

        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script>
    // Favorite toggle — removes card from page when unfavorited
    document.querySelectorAll('.artwork-card__fav').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id   = btn.dataset.id;
            const card = btn.closest('.artwork-card');

            try {
                const res  = await fetch('actions/favorite-remove.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `artwork_id=${id}`,
                });
                const data = await res.json();

                if (data.success) {
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity    = '0';
                    card.style.transform  = 'scale(0.95)';
                    setTimeout(() => {
                        card.remove();
                        // If no cards left, reload to show empty state
                        if (document.querySelectorAll('.artwork-card').length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                }
            } catch (err) {
                console.error('Remove favorite error:', err);
            }
        });
    });
    </script>

    <script src="assets/js/main.js"></script>
</body>
</html>