<?php
// public_html/actions/checkout.php
// Called via AJAX from checkout.js to create a pending order
// Returns order details for PayPal to process
ob_start();
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
ob_clean();

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$user_id = current_user_id();
$notes   = clean($_POST['notes'] ?? '');

// Fetch cart
$stmt = $pdo->prepare(
    "SELECT c.quantity, a.id AS artwork_id, a.title, a.price, a.is_available
     FROM cart c
     JOIN artworks a ON c.artwork_id = a.id
     WHERE c.user_id = ?"
);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

$available = array_filter($items, fn($i) => $i['is_available']);

if (empty($available)) {
    echo json_encode(['success' => false, 'error' => 'No available items']);
    exit;
}

$total = array_sum(array_map(
    fn($i) => (float)$i['price'] * (int)$i['quantity'],
    $available
));

try {
    $pdo->beginTransaction();

    // Create DB order
    $order_number = generate_order_number($pdo);
    $pdo->prepare(
        "INSERT INTO orders (user_id, order_number, status, total_amount, notes)
         VALUES (?, ?, 'pending', ?, ?)"
    )->execute([$user_id, $order_number, $total, $notes]);
    $order_id = (int) $pdo->lastInsertId();

    // Create order items
    $item_stmt = $pdo->prepare(
        "INSERT INTO order_items (order_id, artwork_id, title, price, quantity)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($available as $item) {
        $item_stmt->execute([
            $order_id,
            $item['artwork_id'],
            $item['title'],
            $item['price'],
            $item['quantity'],
        ]);
    }

    // Create PayPal order server-side
    $pp = createPayPalOrder($total, array_values($available));

    if (!$pp['success']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $pp['error']]);
        exit;
    }

    $pdo->commit();

    echo json_encode([
        'success'         => true,
        'order_id'        => $order_id,
        'order_number'    => $order_number,
        'total'           => number_format($total, 2, '.', ''),
        'paypal_order_id' => $pp['paypal_order_id'],
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Could not create order']);
}

// ── PayPal Order Creation ──
function createPayPalOrder(float $total, array $items): array
{
    $credentials = base64_encode(PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET);
    $base_url    = PAYPAL_MODE === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';

    // Step 1: Get access token
    $ch = curl_init($base_url . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $token_res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($token_res['access_token'])) {
        return ['success' => false, 'error' => 'PayPal authentication failed'];
    }

    // Step 2: Build items array for PayPal
    $pp_items = array_map(fn($i) => [
        'name'        => $i['title'],
        'quantity'    => (string)(int)$i['quantity'],
        'unit_amount' => [
            'currency_code' => 'USD',
            'value'         => number_format((float)$i['price'], 2, '.', ''),
        ],
        'category' => 'DIGITAL_GOODS',
    ], $items);

    // Step 3: Create the PayPal order
    $ch = curl_init($base_url . '/v2/checkout/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token_res['access_token'],
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'USD',
                    'value'         => number_format($total, 2, '.', ''),
                    'breakdown'     => [
                        'item_total' => [
                            'currency_code' => 'USD',
                            'value'         => number_format($total, 2, '.', ''),
                        ],
                    ],
                ],
                'items' => $pp_items,
            ]],
        ]),
    ]);
    $order_res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($order_res['id'])) {
        return ['success' => false, 'error' => 'Could not create PayPal order'];
    }

    return [
        'success'         => true,
        'paypal_order_id' => $order_res['id'],
    ];
}