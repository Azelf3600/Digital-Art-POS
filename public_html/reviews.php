<?php
// public_html/reviews.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login('login.php');

$user_id = current_user_id();

// Fetch reviews already submitted by this user
$reviews_stmt = $pdo->prepare(
    "SELECT r.id, r.rating, r.comment, r.created_at, r.is_approved,
            a.id AS artwork_id, a.title AS artwork_title, a.thumbnail,
            o.order_number
     FROM reviews r
     JOIN artworks a ON r.artwork_id = a.id
     JOIN orders o ON r.order_id = o.id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC"
);
$reviews_stmt->execute([$user_id]);
$my_reviews = $reviews_stmt->fetchAll();

// Fetch completed orders with artworks that haven't been reviewed yet
$reviewable_stmt = $pdo->prepare(
    "SELECT DISTINCT
        a.id AS artwork_id, a.title, a.thumbnail,
        o.id AS order_id, o.order_number, o.created_at AS order_date
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     JOIN artworks a ON oi.artwork_id = a.id
     WHERE o.user_id = ?
       AND o.status IN ('paid', 'completed')
       AND NOT EXISTS (
           SELECT 1 FROM reviews r
           WHERE r.user_id = ?
             AND r.artwork_id = a.id
             AND r.order_id = o.id
       )
     ORDER BY o.created_at DESC"
);
$reviewable_stmt->execute([$user_id, $user_id]);
$reviewable = $reviewable_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Review Form ── */
        .review-form-card {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1px;
        }

        .review-form-card:last-child { margin-bottom: 0; }

        .review-artwork-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .review-artwork-info img {
            width: 56px; height: 56px;
            object-fit: cover;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            flex-shrink: 0;
        }

        .review-artwork-info__title {
            font-family: var(--font-serif);
            font-size: 1rem;
            font-style: italic;
            color: var(--text);
            margin-bottom: 0.2rem;
        }

        .review-artwork-info__order {
            font-family: var(--font-mono);
            font-size: 0.68rem;
            color: var(--text-dim);
            letter-spacing: 0.05em;
        }

        /* Star Rating Input */
        .star-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1rem;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 1.5rem;
            color: var(--border-mid);
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: var(--gold);
        }

        .review-textarea {
            width: 100%;
            background: var(--ink);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 0.75rem 1rem;
            color: var(--text);
            font-family: var(--font-display);
            font-size: 0.875rem;
            resize: vertical;
            min-height: 80px;
            outline: none;
            transition: var(--transition);
            line-height: 1.6;
            margin-bottom: 0.875rem;
        }

        .review-textarea::placeholder { color: var(--text-dim); }
        .review-textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.1);
        }

        .review-submit-btn {
            font-size: 0.78rem;
            padding: 0.6rem 1.5rem;
        }

        /* Submitted Review Card */
        .my-review-card {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1px;
        }

        .my-review-card:last-child { margin-bottom: 0; }

        .my-review-card__header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .my-review-card__img {
            width: 48px; height: 48px;
            object-fit: cover;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            flex-shrink: 0;
        }

        .my-review-card__title {
            font-family: var(--font-serif);
            font-size: 0.95rem;
            font-style: italic;
            color: var(--text);
            margin-bottom: 0.1rem;
        }

        .my-review-card__meta {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            color: var(--text-dim);
            letter-spacing: 0.05em;
        }

        .my-review-card__stars {
            margin-left: auto;
            display: flex;
            gap: 2px;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .star-filled { color: var(--gold); }
        .star-empty  { color: var(--border-mid); }

        .my-review-card__comment {
            font-size: 0.875rem;
            color: var(--text-soft);
            line-height: 1.65;
            margin-bottom: 0.5rem;
        }

        .my-review-card__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .my-review-card__date {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            color: var(--text-dim);
            letter-spacing: 0.05em;
        }

        .pending-badge {
            font-family: var(--font-mono);
            font-size: 0.62rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #facc15;
            background: rgba(234,179,8,0.1);
            border: 1px solid rgba(234,179,8,0.2);
            padding: 0.15rem 0.6rem;
            border-radius: 100px;
        }

        .review-success-msg {
            display: none;
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: var(--radius);
            padding: 0.75rem 1rem;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: #4ade80;
            margin-top: 0.75rem;
            letter-spacing: 0.03em;
        }

        .review-error-msg {
            display: none;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: var(--radius);
            padding: 0.75rem 1rem;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: #f87171;
            margin-top: 0.75rem;
            letter-spacing: 0.03em;
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
                    <h1 class="dashboard-header__title">Reviews</h1>
                </div>
                <div class="dashboard-header__actions">
                    <a href="dashboard.php" class="btn btn--ghost">← Dashboard</a>
                </div>
            </div>

            <?php show_flash(); ?>

            <div class="dashboard-grid">

                <!-- Left: Pending Reviews -->
                <div class="dashboard-main">

                    <!-- Artworks to Review -->
                    <?php if (count($reviewable) > 0): ?>
                    <div class="dash-card" style="margin-bottom:1.5rem;">
                        <div class="dash-card__header">
                            <span class="dash-card__title">
                                Awaiting Your Review
                                <span style="margin-left:0.4rem;font-family:var(--font-mono);font-size:0.7rem;color:var(--gold);">
                                    (<?= count($reviewable) ?>)
                                </span>
                            </span>
                        </div>
                        <?php foreach ($reviewable as $item): ?>
                        <div class="review-form-card" id="reviewable-<?= $item['artwork_id'] ?>-<?= $item['order_id'] ?>">
                            <div class="review-artwork-info">
                                <?php if ($item['thumbnail']): ?>
                                <img
                                    src="assets/artworks/thumbnails/<?= htmlspecialchars($item['thumbnail']) ?>"
                                    alt="<?= htmlspecialchars($item['title']) ?>"
                                >
                                <?php endif; ?>
                                <div>
                                    <div class="review-artwork-info__title">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </div>
                                    <div class="review-artwork-info__order">
                                        Order <?= htmlspecialchars($item['order_number']) ?>
                                        · <?= format_date($item['order_date']) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Star Rating -->
                            <div class="star-rating" id="stars-<?= $item['artwork_id'] ?>-<?= $item['order_id'] ?>">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                <input
                                    type="radio"
                                    name="rating-<?= $item['artwork_id'] ?>-<?= $item['order_id'] ?>"
                                    id="star-<?= $item['artwork_id'] ?>-<?= $item['order_id'] ?>-<?= $s ?>"
                                    value="<?= $s ?>"
                                >
                                <label for="star-<?= $item['artwork_id'] ?>-<?= $item['order_id'] ?>-<?= $s ?>">★</label>
                                <?php endfor; ?>
                            </div>

                            <!-- Comment -->
                            <textarea
                                class="review-textarea"
                                id="comment-<?= $item['artwork_id'] ?>-<?= $item['order_id'] ?>"
                                placeholder="Share your experience with this artwork... (optional)"
                                maxlength="1000"
                            ></textarea>

                            <button
                                class="btn btn--primary review-submit-btn"
                                onclick="submitReview(<?= $item['artwork_id'] ?>, <?= $item['order_id'] ?>)"
                            >
                                Submit Review
                            </button>

                            <div class="review-success-msg" id="success-<?= $item['artwork_id'] ?>-<?= $item['order_id'] ?>"></div>
                            <div class="review-error-msg"   id="error-<?= $item['artwork_id'] ?>-<?= $item['order_id'] ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- My Submitted Reviews -->
                    <div class="dash-card">
                        <div class="dash-card__header">
                            <span class="dash-card__title">My Reviews</span>
                        </div>

                        <?php if (count($my_reviews) > 0): ?>
                        <?php foreach ($my_reviews as $rev): ?>
                        <div class="my-review-card">
                            <div class="my-review-card__header">
                                <?php if ($rev['thumbnail']): ?>
                                <img
                                    class="my-review-card__img"
                                    src="assets/artworks/thumbnails/<?= htmlspecialchars($rev['thumbnail']) ?>"
                                    alt="<?= htmlspecialchars($rev['artwork_title']) ?>"
                                >
                                <?php endif; ?>
                                <div>
                                    <div class="my-review-card__title">
                                        <a href="artwork.php?id=<?= $rev['artwork_id'] ?>" style="color:inherit;text-decoration:none;">
                                            <?= htmlspecialchars($rev['artwork_title']) ?>
                                        </a>
                                    </div>
                                    <div class="my-review-card__meta">
                                        Order <?= htmlspecialchars($rev['order_number']) ?>
                                    </div>
                                </div>
                                <div class="my-review-card__stars">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <span class="<?= $s <= $rev['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if (!empty($rev['comment'])): ?>
                            <p class="my-review-card__comment">
                                "<?= htmlspecialchars($rev['comment']) ?>"
                            </p>
                            <?php endif; ?>
                            <div class="my-review-card__footer">
                                <span class="my-review-card__date">
                                    <?= format_date($rev['created_at']) ?>
                                </span>
                                <?php if (!$rev['is_approved']): ?>
                                <span class="pending-badge">Pending approval</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php else: ?>
                        <div class="dash-empty">
                            <span class="dash-empty__icon">★</span>
                            <div class="dash-empty__text">No reviews submitted yet</div>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Right: Info -->
                <div class="dashboard-side">
                    <div class="dash-card">
                        <div class="dash-card__header">
                            <span class="dash-card__title">About Reviews</span>
                        </div>
                        <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:0.875rem;">
                            <div style="font-size:0.875rem;color:var(--text-soft);line-height:1.65;">
                                Reviews help other customers discover great artworks. You can only
                                review artworks from completed orders.
                            </div>
                            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                                <div style="font-family:var(--font-mono);font-size:0.7rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-dim);">
                                    Guidelines
                                </div>
                                <?php foreach ([
                                    'Be honest and specific',
                                    'Focus on the artwork quality',
                                    'Mention communication & delivery',
                                    'Keep it respectful',
                                ] as $tip): ?>
                                <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.82rem;color:var(--text-soft);">
                                    <span style="color:var(--gold);">✓</span>
                                    <?= $tip ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="dashboard.php" class="btn btn--ghost" style="justify-content:center;font-size:0.78rem;margin-top:0.5rem;">
                                ← Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script>
    async function submitReview(artworkId, orderId) {
        const key     = artworkId + '-' + orderId;
        const ratingEl = document.querySelector(
            `input[name="rating-${key}"]:checked`
        );
        const comment  = document.getElementById('comment-' + key)?.value || '';
        const successEl = document.getElementById('success-' + key);
        const errorEl   = document.getElementById('error-' + key);
        const btn       = document.querySelector(
            `#reviewable-${key} .review-submit-btn`
        );

        // Reset messages
        successEl.style.display = 'none';
        errorEl.style.display   = 'none';

        if (!ratingEl) {
            errorEl.textContent   = 'Please select a star rating before submitting.';
            errorEl.style.display = 'block';
            return;
        }

        btn.disabled    = true;
        btn.textContent = 'Submitting...';

        try {
            const res  = await fetch('actions/review-submit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `artwork_id=${artworkId}&order_id=${orderId}&rating=${ratingEl.value}&comment=${encodeURIComponent(comment)}`,
            });
            const data = await res.json();

            if (data.success) {
                successEl.textContent   = data.message;
                successEl.style.display = 'block';
                btn.style.display       = 'none';

                // Fade out and remove the form after 2s
                setTimeout(() => {
                    const card = document.getElementById('reviewable-' + key);
                    if (card) {
                        card.style.transition = 'opacity 0.4s ease';
                        card.style.opacity    = '0';
                        setTimeout(() => {
                            card.remove();
                            // Reload to show new review in submitted list
                            window.location.reload();
                        }, 400);
                    }
                }, 2000);
            } else {
                errorEl.textContent   = data.error || 'Something went wrong.';
                errorEl.style.display = 'block';
                btn.disabled    = false;
                btn.textContent = 'Submit Review';
            }

        } catch (err) {
            errorEl.textContent   = 'Could not submit review. Please try again.';
            errorEl.style.display = 'block';
            btn.disabled    = false;
            btn.textContent = 'Submit Review';
        }
    }
    </script>

    <script src="assets/js/main.js"></script>
</body>
</html>