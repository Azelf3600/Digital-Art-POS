<?php
// public_html/actions/login.php
// Handles login form submission

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

// Verify CSRF token
verify_csrf();

// Collect inputs
$email    = clean_email($_POST['email']    ?? '');
$password = $_POST['password']             ?? '';

// Validate credentials
$result = validate_login($pdo, $email, $password);

if (is_string($result)) {
    // validate_login returned an error string
    set_flash('error', $result);
    redirect('login.php');
}

// Success — $result is the user array
login_user($result);

set_flash('success', 'Welcome back, ' . clean($result['username']) . '!');

// Redirect admin to admin panel, customers to dashboard
if ($result['role'] === 'admin') {
    redirect('admin/index.php');
} else {
    redirect('dashboard.php');
}