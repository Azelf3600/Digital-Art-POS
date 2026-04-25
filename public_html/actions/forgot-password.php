<?php
// public_html/actions/forgot-password.php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('forgot-password.php');
}

verify_csrf();

$email = clean_email($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Please enter a valid email address.');
    redirect('forgot-password.php');
}

// Always show success message even if email not found
// This prevents user enumeration (telling hackers which emails exist)
$success_msg = 'If that email is registered, a reset link has been sent. Check your inbox.';

$user = get_user_by_email($pdo, $email);

if ($user && $user['is_active']) {
    $token    = create_reset_token($pdo, $user['id']);
    $resetUrl = SITE_URL . './reset-password.php?token=' . $token;

    // Send the email using mailer
    require_once '../includes/mailer.php';
    send_password_reset_email($user['email'], $user['username'], $resetUrl);
}

set_flash('success', $success_msg);
redirect('forgot-password.php');