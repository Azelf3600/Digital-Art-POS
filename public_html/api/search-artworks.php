<?php
// public_html/api/search-artworks.php
ob_start();

require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

ob_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$query = clean($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['artworks' => [], 'total' => 0]);
    exit;
}

$search = '%' . $query . '%';

$stmt = $pdo->prepare(
    "SELECT
        a.id, a.title, a.price, a.thumbnail,
        c.name AS category
     FROM artworks a
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE a.is_available = 1
       AND (a.title LIKE ? OR c.name LIKE ?)
     ORDER BY a.views DESC
     LIMIT 8"
);
$stmt->execute([$search, $search]);
$artworks = $stmt->fetchAll();

foreach ($artworks as &$art) {
    $art['price_formatted'] = format_price((float)$art['price']);
    $art['thumbnail_url']   = ARTWORK_THUMBS . $art['thumbnail'];
}
unset($art);

echo json_encode([
    'artworks' => $artworks,
    'total'    => count($artworks),
]);