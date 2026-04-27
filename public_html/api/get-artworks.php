<?php
// public_html/api/get-artworks.php
ob_start(); // Buffer any accidental output before JSON

require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Clear any buffered warnings before sending JSON
ob_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$category = clean($_GET['category'] ?? '');
$sort     = clean($_GET['sort']     ?? 'newest');
$page     = max(1, clean_int($_GET['page'] ?? 1));
$per_page = 12;
$offset   = paginate_offset($page, $per_page);

$where  = ["a.is_available = 1"];
$params = [];

if (!empty($category) && $category !== 'all') {
    $where[]  = "c.slug = ?";
    $params[] = $category;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$order_sql = match($sort) {
    'price-low'  => 'ORDER BY a.price ASC',
    'price-high' => 'ORDER BY a.price DESC',
    'popular'    => 'ORDER BY a.views DESC',
    default      => 'ORDER BY a.created_at DESC',
};

// Count total
$count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM artworks a
     LEFT JOIN categories c ON a.category_id = c.id
     $where_sql"
);
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();

// Fetch artworks
$fetch_params = $params;
$fetch_params[] = $per_page;
$fetch_params[] = $offset;

$stmt = $pdo->prepare(
    "SELECT
        a.id, a.title, a.price, a.thumbnail,
        a.views, a.created_at,
        c.name AS category, c.slug AS category_slug
     FROM artworks a
     LEFT JOIN categories c ON a.category_id = c.id
     $where_sql
     $order_sql
     LIMIT ? OFFSET ?"
);
$stmt->execute($fetch_params);
$artworks = $stmt->fetchAll();

foreach ($artworks as &$art) {
    $art['price_formatted'] = format_price((float)$art['price']);
    $art['thumbnail_url']   = ARTWORK_THUMBS . $art['thumbnail'];
    $art['created_ago']     = time_ago($art['created_at']);
}
unset($art);

echo json_encode([
    'artworks'    => $artworks,
    'total'       => $total,
    'page'        => $page,
    'per_page'    => $per_page,
    'total_pages' => total_pages($total, $per_page),
]);