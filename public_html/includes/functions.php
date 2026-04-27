<?php
// ============================================
// includes/functions.php
// Starflow — Shared Helper Functions
// ============================================

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

// ============================================
// INPUT & SECURITY
// ============================================

/**
 * Sanitize a string input — always use this on user input
 * Usage: $name = clean($_POST['name']);
 */
function clean(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize an integer input
 * Usage: $id = clean_int($_GET['id']);
 */
function clean_int(mixed $data): int {
    return (int) filter_var($data, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize an email input
 */
function clean_email(string $email): string {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Redirect to a URL and stop execution
 * Usage: redirect('login.php');
 */
function redirect(string $path): void {
    header('Location: ' . SITE_URL . '/' . ltrim($path, '/'));
    exit();
}

/**
 * Redirect back to previous page
 */
function redirect_back(): void {
    $ref = $_SERVER['HTTP_REFERER'] ?? SITE_URL;
    header('Location: ' . $ref);
    exit();
}

/**
 * Return a JSON response and stop execution
 * Usage in API files: json_response(['status' => 'ok', 'data' => $result]);
 */
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// ============================================
// FORMATTING
// ============================================

/**
 * Format a price in Philippine Peso
 * Usage: echo format_price(1800.00);  // ₱1,800.00
 */
function format_price(float $amount): string {
    return '$' . number_format($amount, 2);
}

/**
 * Format a datetime string into a readable date
 * Usage: echo format_date('2024-01-15 10:30:00');  // Jan 15, 2024
 */
function format_date(string $datetime): string {
    return date('M d, Y', strtotime($datetime));
}

/**
 * Format a datetime string into date and time
 * Usage: echo format_datetime('2024-01-15 10:30:00');  // Jan 15, 2024 10:30 AM
 */
function format_datetime(string $datetime): string {
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Return a human-readable time difference
 * Usage: echo time_ago('2024-01-15 10:30:00');  // 2 hours ago
 */
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return format_date($datetime);
}

// ============================================
// ORDER NUMBERS
// ============================================

/**
 * Generate a unique order number
 * Format: SF-YYYYMMDD-XXXXX (e.g. SF-20240115-00042)
 */
function generate_order_number(PDO $pdo): string {
    $date   = date('Ymd');
    $prefix = ORDER_PREFIX . '-' . $date . '-';

    // Find the highest order number for today
    $stmt = $pdo->prepare(
        "SELECT order_number FROM orders
         WHERE order_number LIKE ?
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();

    if ($last) {
        $lastNum = (int) substr($last, -5);
        $next    = $lastNum + 1;
    } else {
        $next = 1;
    }

    return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
}

// ============================================
// FILE UPLOADS
// ============================================

/**
 * Validate an uploaded image file
 * Returns true if valid, or an error string if not
 */
function validate_image(array $file): true|string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'File upload failed. Please try again.';
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return 'File is too large. Maximum size is 5MB.';
    }
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, ALLOWED_IMAGES)) {
        return 'Invalid file type. Only JPG, PNG, and WebP are allowed.';
    }
    return true;
}

/**
 * Move an uploaded file to a destination folder
 * Returns the saved filename or false on failure
 */
function save_upload(array $file, string $destination): string|false {
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('sf_', true) . '.' . strtolower($ext);
    $target   = rtrim($destination, '/') . '/' . $filename;

    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $filename;
    }
    return false;
}

// ============================================
// PAGINATION
// ============================================

/**
 * Calculate pagination offset
 * Usage: $offset = paginate_offset($page, 12);
 */
function paginate_offset(int $page, int $per_page = 12): int {
    return ($page - 1) * $per_page;
}

/**
 * Calculate total pages
 */
function total_pages(int $total_rows, int $per_page = 12): int {
    return (int) ceil($total_rows / $per_page);
}

// ============================================
// STATUS LABELS
// ============================================

/**
 * Get a display label and CSS class for an order status
 */
function order_status_label(string $status): array {
    return match($status) {
        'pending'    => ['label' => 'Pending',    'class' => 'status--pending'],
        'paid'       => ['label' => 'Paid',        'class' => 'status--paid'],
        'processing' => ['label' => 'Processing',  'class' => 'status--processing'],
        'completed'  => ['label' => 'Completed',   'class' => 'status--completed'],
        'cancelled'  => ['label' => 'Cancelled',   'class' => 'status--cancelled'],
        'refunded'   => ['label' => 'Refunded',    'class' => 'status--refunded'],
        default      => ['label' => ucfirst($status), 'class' => 'status--default'],
    };
}

/**
 * Get a display label and CSS class for a commission status
 */
function commission_status_label(string $status): array {
    return match($status) {
        'pending'     => ['label' => 'Pending',     'class' => 'status--pending'],
        'in_progress' => ['label' => 'In Progress', 'class' => 'status--processing'],
        'done'        => ['label' => 'Done',         'class' => 'status--completed'],
        'cancelled'   => ['label' => 'Cancelled',   'class' => 'status--cancelled'],
        default       => ['label' => ucfirst($status), 'class' => 'status--default'],
    };
}

// ============================================
// NOTIFICATIONS
// ============================================

/**
 * Create a notification for a user
 * Usage: create_notification($pdo, $user_id, 'order_update', 'Order Shipped', 'Your order SF-001 is on the way', 'order-tracking.php?id=1');
 */
function create_notification(
    PDO    $pdo,
    int    $user_id,
    string $type,
    string $title,
    string $message,
    string $link = ''
): void {
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, type, title, message, link)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$user_id, $type, $title, $message, $link]);
}

/**
 * Get unread notification count for a user
 */
function unread_notifications(PDO $pdo, int $user_id): int {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications
         WHERE user_id = ? AND is_read = 0"
    );
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}