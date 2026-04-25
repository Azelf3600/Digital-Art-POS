<?php
// public_html/forgot-password.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="auth-body">

    <div class="auth-bg">
        <div class="auth-bg__orb auth-bg__orb--1"></div>
        <div class="auth-bg__orb auth-bg__orb--2"></div>
        <div class="auth-bg__grid"></div>
    </div>
    <div class="auth-center">
        <div class="auth-card">
            <a href="login.php" class="auth-back">← Back to Login</a>

            <div class="auth-card__logo">Star<span>flow</span></div>

            <div class="auth-form__header">
                <h1 class="auth-form__title">Forgot Password</h1>
                <p class="auth-form__sub">
                    Enter your email and we'll send you a reset link.
                </p>
            </div>

            <?php show_flash(); ?>

            <form class="auth-form" action="actions/forgot-password.php" method="POST" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="email">
                        Email Address <span class="required">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="juan@email.com"
                        value="<?= clean($_POST['email'] ?? '') ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <button type="submit" class="btn btn--primary btn--full">
                    Send Reset Link
                </button>

                <div class="auth-divider">or</div>

                <a href="login.php" class="btn btn--ghost btn--full">
                    Back to Sign In
                </a>

            </form>

        </div>
    </div>

</body>
</html>