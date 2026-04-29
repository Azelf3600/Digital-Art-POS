<?php
// public_html/actions/payment.php
// Called after PayPal approves payment
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

$paypal_order_id = clean($_POST['paypal_order_id'] ?? '');
$order_id        = clean_int($_POST['order_id']     ?? 0);

if (!$paypal_order_id || !$order_id) {
    echo json_encode(['success' => false, 'error' => 'Missing payment details']);
    exit;
}

// Verify the order belongs to this user and is still pending
$stmt = $pdo->prepare(
    "SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending' LIMIT 1"
);
$stmt->execute([$order_id, current_user_id()]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found or already processed']);
    exit;
}

// ── Capture & Verify with PayPal API ──
$paypal_result = captureAndVerifyPayPalPayment($paypal_order_id, (float)$order['total_amount']);

if (!$paypal_result['success']) {
    echo json_encode(['success' => false, 'error' => 'Payment verification failed: ' . $paypal_result['error']]);
    exit;
}

$payer = $paypal_result['payer'];

// ── Complete the order ──
try {
    $pdo->beginTransaction();

    // Update order status
    $pdo->prepare(
        "UPDATE orders SET status = 'paid', updated_at = NOW() WHERE id = ?"
    )->execute([$order_id]);

    // Record payment
    $pdo->prepare(
        "INSERT INTO payments
            (order_id, user_id, paypal_order_id, paypal_payer_id,
             paypal_payer_email, amount, currency, status, paid_at)
         VALUES (?, ?, ?, ?, ?, ?, 'USD', 'completed', NOW())"
    )->execute([
        $order_id,
        current_user_id(),
        $paypal_order_id,
        $payer['payer_id']    ?? '',
        $payer['email']       ?? '',
        $order['total_amount'],
    ]);

    // Clear the cart
    $pdo->prepare(
        "DELETE FROM cart WHERE user_id = ?"
    )->execute([current_user_id()]);

    $pdo->commit();

    // Send confirmation email
    $user = get_logged_in_user($pdo);
    if ($user) {
        require_once '../includes/mailer.php';
        send_order_confirmation_email(
            $user['email'],
            $user['full_name'] ?: $user['username'],
            $order['order_number'],
            (float)$order['total_amount']
        );
    }

    // Notify user
    create_notification(
        $pdo,
        current_user_id(),
        'order_update',
        'Payment Confirmed!',
        'Your order ' . $order['order_number'] . ' has been paid. Files will be delivered shortly.',
        'order-tracking.php'
    );

    echo json_encode([
        'success'      => true,
        'order_number' => $order['order_number'],
        'redirect'     => '../order-tracking.php?order=' . $order['order_number'],
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Could not complete order']);
}

// ── PayPal Capture + Verify Function ──
function captureAndVerifyPayPalPayment(string $paypal_order_id, float $expected_amount): array
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
        return ['success' => false, 'error' => 'Could not authenticate with PayPal'];
    }

    $access_token = $token_res['access_token'];

    // Step 2: Capture the order (this moves it from APPROVED → COMPLETED)
    $ch = curl_init($base_url . '/v2/checkout/orders/' . $paypal_order_id . '/capture');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '{}',
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ],
    ]);
    $capture_res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // Step 3: Confirm status is COMPLETED after capture
    if (($capture_res['status'] ?? '') !== 'COMPLETED') {
        $debug = $capture_res['details'][0]['description'] ?? 'Unknown error';
        return ['success' => false, 'error' => 'Capture failed: ' . $debug];
    }

    // Step 4: Verify the captured amount matches expected
    $paid_amount = (float)(
        $capture_res['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0
    );

    if (abs($paid_amount - $expected_amount) > 0.01) {
        return ['success' => false, 'error' => 'Payment amount mismatch'];
    }

    // Step 5: Return payer info
    return [
        'success' => true,
        'payer'   => [
            'payer_id' => $capture_res['payer']['payer_id']      ?? '',
            'email'    => $capture_res['payer']['email_address']  ?? '',
        ],
    ];
}