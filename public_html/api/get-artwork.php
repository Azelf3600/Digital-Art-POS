<?php
// public_html/api/get-artwork.php
ob_start();

require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

ob_clean();
header('Content-Type: application/json');

$id = clean_int($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => 'Invalid artwork ID']);
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
    echo json_encode(['error' => 'Artwork not found']);
    exit;
}

// Increment view count
$pdo->prepare("UPDATE artworks SET views = views + 1 WHERE id = ?")->execute([$id]);

// Fetch reviews for this artwork
$reviews_stmt = $pdo->prepare(
    "SELECT r.rating, r.comment, r.created_at,
            u.username, u.profile_picture
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.artwork_id = ? AND r.is_approved = 1
     ORDER BY r.created_at DESC
     LIMIT 10"
);
$reviews_stmt->execute([$id]);
$reviews = $reviews_stmt->fetchAll();

// Average rating
$avg_stmt = $pdo->prepare(
    "SELECT AVG(rating) as avg, COUNT(*) as count
     FROM reviews WHERE artwork_id = ? AND is_approved = 1"
);
$avg_stmt->execute([$id]);
$rating_data = $avg_stmt->fetch();

$artwork['price_formatted']  = format_price((float)$artwork['price']);
$artwork['thumbnail_url']    = ARTWORK_THUMBS . $artwork['thumbnail'];
$artwork['watermarked_url']  = ARTWORK_WATERMARKED . ($artwork['watermarked'] ?? $artwork['thumbnail']);
$artwork['avg_rating']       = round((float)$rating_data['avg'], 1);
$artwork['review_count']     = (int)$rating_data['count'];

echo json_encode([
    'artwork' => $artwork,
    'reviews' => $reviews,
]);