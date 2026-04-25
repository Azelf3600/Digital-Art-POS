<?php
// public_html/login.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// If already logged in, redirect away
if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Starflow</title>
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
            <a href="index.php" class="auth-back">← Back to Starflow</a>

            <div class="auth-card__logo">Star<span>flow</span></div>

            <div class="auth-form__header">
                <h1 class="auth-form__title">Welcome back</h1>
                <p class="auth-form__sub">
                    Don't have an account?
                    <a href="register.php">Sign up</a>
                </p>
            </div>

            <?php show_flash(); ?>

            <form class="auth-form" action="actions/login.php" method="POST" novalidate>
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

                <div class="form-group">
                    <div class="form-label-row">
                        <label class="form-label" for="password">
                            Password <span class="required">*</span>
                        </label>
                        <a href="forgot-password.php" class="form-forgot">Forgot password?</a>
                    </div>
                    <div class="form-input-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="form-eye" onclick="togglePassword('password')">👁</button>
                    </div>
                </div>

                <div class="form-group form-group--check">
                    <label class="form-check">
                        <input type="checkbox" name="remember_me">
                        <span class="form-check__box"></span>
                        <span>Remember me for 7 days</span>
                    </label>
                </div>

                <button type="submit" class="btn btn--primary btn--full">
                    Login
                </button>

                <div class="auth-divider">or</div>

                <a href="register.php" class="btn btn--ghost btn--full">
                    Create an Account
                </a>

            </form>

        </div>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>