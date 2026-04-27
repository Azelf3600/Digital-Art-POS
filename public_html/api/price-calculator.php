<?php
// public_html/api/price-calculator.php
ob_start();
require_once '../config/config.php';
require_once '../includes/functions.php';
ob_clean();

header('Content-Type: application/json');

$tier = clean($_GET['tier'] ?? '');

$prices = [
    'sketch'      => ['base' => 10,  'label' => 'Sketch'],
    'full_color'  => ['base' => 25,  'label' => 'Full Color'],
    'illustrated' => ['base' => 40,  'label' => 'Illustrated'],
];

if (!isset($prices[$tier])) {
    echo json_encode(['error' => 'Invalid tier']);
    exit;
}

$price = $prices[$tier];

echo json_encode([
    'tier'      => $tier,
    'label'     => $price['label'],
    'base'      => $price['base'],
    'formatted' => '$' . $price['base'],
    'deposit'   => '$' . ($price['base'] * 0.5),
]);