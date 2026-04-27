<?php
// ============================================
// config/config.php
// Starflow — Site Configuration
// ============================================

// Load secrets from env.php (outside public_html, not in git)
$env_path = dirname(__DIR__, 2) . '/env.php';
if (file_exists($env_path)) {
    require_once $env_path;
} else {
    // If env.php not found, define empty fallbacks
    define('SMTP_PASSWORD', '');
    define('DB_PASS',       '');
    define('PAYPAL_SECRET', '');
}

// ── Site Info ──
define('SITE_NAME',     'Starflow');
define('SITE_TAGLINE',  'Starflow Digital Art Shop');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script   = $_SERVER['SCRIPT_NAME'] ?? '';
// Find public_html in the path
$pos      = strpos($script, '/public_html');
$base     = ($pos !== false) ? substr($script, 0, $pos + strlen('/public_html')) : '';
define('SITE_URL', $protocol . '://' . $host . $base);

define('SITE_EMAIL',    'admin@starflow.com');

// ── Database ──
define('DB_HOST',       'localhost:3307');
define('DB_NAME',       'starflow');
define('DB_USER',       'root');        // default XAMPP username
define('DB_CHARSET',    'utf8mb4');

// ── File Paths ──
// Absolute path to private_uploads (outside public_html)
define('PRIVATE_UPLOADS', dirname(__DIR__, 2) . '/private_uploads/');
define('UPLOAD_REFERENCES',  PRIVATE_UPLOADS . 'references/');
define('UPLOAD_FINISHED',    PRIVATE_UPLOADS . 'finished/');
define('UPLOAD_RECEIPTS',    PRIVATE_UPLOADS . 'receipts/');
define('UPLOAD_PROFILES',    PRIVATE_UPLOADS . 'profile-pictures/');

// Public asset paths (relative to public_html)
define('ASSET_URL',          SITE_URL . '/assets/');
define('ARTWORK_THUMBS',     ASSET_URL . 'artworks/thumbnails/');
define('ARTWORK_WATERMARKED',ASSET_URL . 'artworks/watermarked/');

// ── Upload Settings ──
define('MAX_FILE_SIZE',   5 * 1024 * 1024);   // 5MB in bytes
define('ALLOWED_IMAGES',  ['image/jpeg', 'image/png', 'image/webp']);

// ── Session Settings ──
define('SESSION_LIFETIME', 60 * 60 * 24 * 7); // 7 days in seconds

// ── Error Reporting ──
// Set to true during development, false on live site
define('DEV_MODE', true);

if (DEV_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__, 2) . '/logs/errors.log');
}

// ── PayPal ──
define('PAYPAL_MODE',       'sandbox');  // Change to 'live' when deploying
define('PAYPAL_CLIENT_ID',  'YOUR_PAYPAL_CLIENT_ID_HERE');

// ── Order Settings ──
define('ORDER_PREFIX', 'SF');   // Order numbers will be SF-000001