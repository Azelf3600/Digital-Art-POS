<?php
// ============================================
// config/db.php
// Starflow — Database Connection (PDO)
// ============================================

// Make sure config is loaded first
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

try {
    $dsn = 'mysql:host=' . DB_HOST
         . ';dbname='    . DB_NAME
         . ';charset='   . DB_CHARSET;

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // return arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                   // real prepared statements
    ]);

} catch (PDOException $e) {
    // Don't expose raw error to users
    if (DEV_MODE) {
        die('Database connection failed: ' . $e->getMessage());
    } else {
        die('Something went wrong. Please try again later.');
    }
}