<?php
// public_html/reset-password.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

// Token must be in the URL
$token   = clean($_GET['token'] ?? '');
$user_id = $token ? validate_reset_token($pdo, $token) : false;

// Invalid or expired token
if (!$user_id) {
    set_flash('error', 'This reset link is invalid or has expired. Please request a new one.');
    redirect('forgot-password.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Starflow</title>
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
                <h1 class="auth-form__title">Reset Password</h1>
                <p class="auth-form__sub">Enter your new password below.</p>
            </div>

            <?php show_flash(); ?>

            <form class="auth-form" action="actions/reset-password.php" method="POST" novalidate>
                <?= csrf_field() ?>
                <!-- Pass the token through the form -->
                <input type="hidden" name="token" value="<?= clean($token) ?>">

                <div class="form-group">
                    <label class="form-label" for="password">
                        New Password <span class="required">*</span>
                    </label>
                    <div class="form-input-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Min. 8 characters"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="form-eye" onclick="togglePassword('password')">👁</button>
                    </div>
                </div>

                <div class="password-strength">
                    <div class="password-strength__bar">
                        <div class="password-strength__fill" id="strength-fill"></div>
                    </div>
                    <span class="password-strength__label" id="strength-label"></span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">
                        Confirm Password <span class="required">*</span>
                    </label>
                    <div class="form-input-wrap">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-input"
                            placeholder="Repeat new password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="form-eye" onclick="togglePassword('confirm_password')">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary btn--full">
                    Reset Password
                </button>

            </form>

        </div>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        document.getElementById('password').addEventListener('input', function () {
            const val   = this.value;
            const fill  = document.getElementById('strength-fill');
            const label = document.getElementById('strength-label');
            let score   = 0;

            if (val.length >= 8)           score++;
            if (/[A-Z]/.test(val))         score++;
            if (/[0-9]/.test(val))         score++;
            if (/[^A-Za-z0-9]/.test(val))  score++;

            const levels = [
                { label: '',       color: 'transparent', width: '0%'   },
                { label: 'Weak',   color: '#ef4444',     width: '25%'  },
                { label: 'Fair',   color: '#f97316',     width: '50%'  },
                { label: 'Good',   color: '#eab308',     width: '75%'  },
                { label: 'Strong', color: '#22c55e',     width: '100%' },
            ];

            fill.style.width           = levels[score].width;
            fill.style.backgroundColor = levels[score].color;
            label.textContent          = levels[score].label;
            label.style.color          = levels[score].color;
        });
    </script>
</body>
</html>