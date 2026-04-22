<?php
// ============================================
// config/session.php
// Starflow — Session Configuration
// ============================================

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config.php';
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {

    // Configure session settings before starting
    ini_set('session.cookie_httponly', 1);   // JS cannot access cookie
    ini_set('session.use_strict_mode', 1);   // Reject unrecognized session IDs
    ini_set('session.cookie_samesite', 'Strict');

    // Session lifetime
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false,   // Set to true when on HTTPS (live site)
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();

    // Regenerate session ID periodically to prevent fixation attacks
    if (!isset($_SESSION['last_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['last_regenerated'] = time();
    } elseif (time() - $_SESSION['last_regenerated'] > 1800) {
        // Regenerate every 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regenerated'] = time();
    }
}

// ============================================
// Helper functions for session management
// ============================================

/**
 * Check if a user is logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is an admin
 */
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get the current logged-in user's ID
 */
function current_user_id(): int|null {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current logged-in user's role
 */
function current_user_role(): string|null {
    return $_SESSION['role'] ?? null;
}

/**
 * Log a user in — call this after verifying credentials
 */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['full_name']= $user['full_name'] ?? '';
    $_SESSION['last_regenerated'] = time();
}

/**
 * Log the current user out
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Redirect to login if not logged in
 */
function require_login(string $redirect = 'login.php'): void {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/' . $redirect);
        exit();
    }
}

/**
 * Redirect to home if not admin
 */
function require_admin(): void {
    if (!is_admin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

/**
 * Set a one-time flash message (shows once then disappears)
 * Usage: set_flash('success', 'Your order was placed!')
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear a flash message
 * Usage: get_flash('success')
 */
function get_flash(string $type): string|null {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

/**
 * Display flash message as HTML if it exists
 * Call this near the top of any page
 */
function show_flash(): void {
    $types = ['success', 'error', 'warning', 'info'];
    foreach ($types as $type) {
        $msg = get_flash($type);
        if ($msg) {
            echo '<div class="flash flash--' . $type . '">'
               . htmlspecialchars($msg)
               . '</div>';
        }
    }
}

/**
 * Generate a CSRF token and store in session
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field — put inside every form
 * Usage: <?= csrf_field() ?>
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify a submitted CSRF token — call in every action file
 */
function verify_csrf(): void {
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Invalid request. Please go back and try again.');
    }
}