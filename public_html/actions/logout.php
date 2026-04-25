<?php
// public_html/actions/logout.php
// Destroys the session and redirects to home

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Must be logged in to logout
if (!is_logged_in()) {
    redirect('index.php');
}

// Grab username for the goodbye message before destroying session
$username = $_SESSION['username'] ?? '';

// Destroy the session
logout_user();

// Flash message will show on the login page
set_flash('success', 'You have been logged out. See you next time' . ($username ? ', ' . clean($username) : '') . '!');

redirect('login.php');