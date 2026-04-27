<?php
// public_html/gallery.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// ── Read filter inputs from GET ──
$category = clean($_GET['category'] ?? 'all');
$sort     = clean($_GET['sort']     ?? 'newest');
$page     = max(1, clean_int($_GET['page'] ?? 1));
$per_page = 12;
$offset   = paginate_offset($page, $per_page);

// ── Build query ──
$where  = ["a.is_available = 1"];
$params = [];

if (!empty($category) && $category !== 'all') {
    $where[]  = "c.slug = ?";
    $params[] = $category;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$order_sql = match($sort) {
    'oldest'     => 'ORDER BY a.created_at ASC',
    'price-low'  => 'ORDER BY a.price ASC',
    'price-high' => 'ORDER BY a.price DESC',
    default      => 'ORDER BY a.created_at DESC', // newest
};

// ── Count total for this filter ──
$count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM artworks a
     LEFT JOIN categories c ON a.category_id = c.id
     $where_sql"
);
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();

// ── Fetch artworks ──
$fetch_params   = $params;
$fetch_params[] = $per_page;
$fetch_params[] = $offset;

$stmt = $pdo->prepare(
    "SELECT a.id, a.title, a.price, a.thumbnail,
            c.name AS category, c.slug AS category_slug
     FROM artworks a
     LEFT JOIN categories c ON a.category_id = c.id
     $where_sql
     $order_sql
     LIMIT ? OFFSET ?"
);
$stmt->execute($fetch_params);
$artworks = $stmt->fetchAll();

// ── Fetch categories for dropdown ──
$cats = $pdo->query(
    "SELECT c.slug, c.name, COUNT(a.id) AS count
     FROM categories c
     LEFT JOIN artworks a ON a.category_id = c.id AND a.is_available = 1
     GROUP BY c.id, c.slug, c.name
     HAVING count > 0
     ORDER BY c.name"
)->fetchAll();

// ── Grand total (all artworks regardless of filter) ──
$grand_total = (int) $pdo->query(
    "SELECT COUNT(*) FROM artworks WHERE is_available = 1"
)->fetchColumn();

$total_pages = total_pages($total, $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/gallery.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <!-- Page Header -->
    <div class="gallery-header">
        <div class="gallery-header__bg">
            <div class="gallery-header__orb"></div>
        </div>
        <div class="container">
            <div class="gallery-header__content">
                <div class="section__label">Portfolio</div>
                <h1 class="gallery-header__title">Gallery</h1>
                <p class="gallery-header__desc">
                    <?= $grand_total ?> original works — browse, favorite, and commission.
                </p>
            </div>

            <!-- Toolbar: all filters submit the same form -->
            <form method="GET" action="gallery.php" id="gallery-form">

                <div class="gallery-toolbar">

                    <!-- Category -->
                    <select name="category" class="gallery-select" onchange="this.form.submit()">
                        <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>
                            All Categories
                        </option>
                        <?php foreach ($cats as $cat): ?>
                        <option
                            value="<?= clean($cat['slug']) ?>"
                            <?= $category === $cat['slug'] ? 'selected' : '' ?>
                        >
                            <?= clean($cat['name']) ?> (<?= $cat['count'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Search -->
                    <div class="gallery-search">
                        <div class="gallery-search__wrap">
                            <span class="gallery-search__icon">⌕</span>
                            <input
                                type="text"
                                id="gallery-search-input"
                                class="gallery-search__input"
                                placeholder="Search artworks..."
                                autocomplete="off"
                            >
                            <button type="button" class="gallery-search__clear" id="search-clear" style="display:none">✕</button>
                        </div>
                        <div class="gallery-search__results" id="search-results" style="display:none"></div>
                    </div>

                    <!-- Sort -->
                    <select name="sort" class="gallery-select" onchange="this.form.submit()">
                        <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest"     <?= $sort === 'oldest'     ? 'selected' : '' ?>>Oldest First</option>
                        <option value="price-low"  <?= $sort === 'price-low'  ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price-high" <?= $sort === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>

                </div>
            </form>
        </div>
    </div>

    <!-- Gallery Main -->
    <main class="gallery-main">
        <div class="container">

            <!-- Info bar -->
            <div class="gallery-info">
                <?php if ($category !== 'all'): ?>
                    <?php
                    $cat_name = '';
                    foreach ($cats as $c) {
                        if ($c['slug'] === $category) { $cat_name = $c['name']; break; }
                    }
                    ?>
                    Showing <strong><?= $total ?></strong> works in
                    <strong><?= clean($cat_name) ?></strong>
                    — <a href="gallery.php" class="gallery-info__clear">Clear filter ✕</a>
                <?php else: ?>
                    Showing <strong><?= count($artworks) ?></strong> of
                    <strong><?= $total ?></strong> works
                <?php endif; ?>
            </div>

            <!-- Grid -->
            <?php if (count($artworks) > 0): ?>
            <div class="gallery-grid">
                <?php foreach ($artworks as $i => $art): ?>
                <div class="artwork-card" style="--delay:<?= $i * 0.05 ?>s">
                    <div class="artwork-card__img">
                        <img
                            src="assets/artworks/thumbnails/<?= htmlspecialchars($art['thumbnail']) ?>"
                            alt="<?= htmlspecialchars($art['title']) ?>"
                            loading="lazy"
                        >
                        <div class="artwork-card__overlay">
                            <a href="artwork.php?id=<?= $art['id'] ?>" class="artwork-card__view">
                                View Work
                            </a>
                            <?php if (is_logged_in()): ?>
                            <button
                                class="artwork-card__fav"
                                data-id="<?= $art['id'] ?>"
                                title="Add to favorites"
                            >♡</button>
                            <?php endif; ?>
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
                            <?= format_price((float)$art['price']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <!-- Empty State -->
            <div class="gallery-empty">
                <div class="gallery-empty__icon">◎</div>
                <h3>No artworks found</h3>
                <p>Try a different category or <a href="gallery.php">view all works</a>.</p>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="gallery-pagination">
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a
                    href="gallery.php?category=<?= $category ?>&sort=<?= $sort ?>&page=<?= $p ?>"
                    class="page-btn <?= $p === $page ? 'page-btn--active' : '' ?>"
                >
                    <?= $p ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>

    <script>
        const GALLERY_API = 'api/';
        const LOGGED_IN   = <?= is_logged_in() ? 'true' : 'false' ?>;
    </script>
    <script src="assets/js/gallery.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>