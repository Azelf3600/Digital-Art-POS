<?php
// public_html/actions/register.php
// Handles registration form submission

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

// Verify CSRF token
verify_csrf();

// Collect and sanitize inputs
$full_name = clean($_POST['full_name']        ?? '');
$username  = clean($_POST['username']          ?? '');
$email     = clean_email($_POST['email']       ?? '');
$password  = $_POST['password']                ?? '';
$confirm   = $_POST['confirm_password']        ?? '';
$agreed    = isset($_POST['agree_terms']);

// Must agree to terms
if (!$agreed) {
    set_flash('error', 'You must agree to the Terms of Service to register.');
    redirect('register.php');
}

// Validate all fields
$errors = validate_registration($pdo, $username, $email, $password, $confirm);

if (!empty($errors)) {
    // Show first error as flash message
    set_flash('error', $errors[0]);
    redirect('register.php');
}

// All good — create the user
try {
    $user_id = register_user($pdo, $username, $email, $password, $full_name);

    // Fetch the newly created user to log them in immediately
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Log them in right away
    login_user($user);

    // Welcome notification
    create_notification(
        $pdo,
        $user_id,
        'welcome',
        'Welcome to Starflow!',
        'Your account has been created. Start browsing our gallery or submit a commission.',
        'gallery.php'
    );

    set_flash('success', 'Welcome to Starflow, ' . clean($username) . '! Your account has been created.');
    redirect('dashboard.php');

} catch (PDOException $e) {
    // Handle rare duplicate race condition
    if ($e->getCode() === '23000') {
        set_flash('error', 'That username or email is already registered. Please try again.');
    } else {
        set_flash('error', 'Something went wrong. Please try again.');
        if (DEV_MODE) {
            set_flash('error', $e->getMessage());
        }
    }
    redirect('register.php');
}