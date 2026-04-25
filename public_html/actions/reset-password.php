<?php
// public_html/actions/reset-password.php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('forgot-password.php');
}

verify_csrf();

$token    = clean($_POST['token']            ?? '');
$password = $_POST['password']               ?? '';
$confirm  = $_POST['confirm_password']       ?? '';

// Validate token again on submission
$user_id = $token ? validate_reset_token($pdo, $token) : false;

if (!$user_id) {
    set_flash('error', 'This reset link is invalid or has expired. Please request a new one.');
    redirect('forgot-password.php');
}

// Validate new password
if (empty($password)) {
    set_flash('error', 'Password is required.');
    redirect('reset-password.php?token=' . $token);
}

if (strlen($password) < 8) {
    set_flash('error', 'Password must be at least 8 characters.');
    redirect('reset-password.php?token=' . $token);
}

if (!preg_match('/[A-Z]/', $password)) {
    set_flash('error', 'Password must contain at least one uppercase letter.');
    redirect('reset-password.php?token=' . $token);
}

if (!preg_match('/[0-9]/', $password)) {
    set_flash('error', 'Password must contain at least one number.');
    redirect('reset-password.php?token=' . $token);
}

if ($password !== $confirm) {
    set_flash('error', 'Passwords do not match.');
    redirect('reset-password.php?token=' . $token);
}

// All good — update the password and mark token as used
update_password($pdo, $user_id, $password);
use_reset_token($pdo, $token);

set_flash('success', 'Your password has been reset. You can now sign in.');
redirect('login.php');