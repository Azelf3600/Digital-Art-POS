<?php
// public_html/register.php
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
    <title>Create Account — Starflow</title>
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
                <h1 class="auth-form__title">Create Account</h1>
                <p class="auth-form__sub">
                    Already have one?
                    <a href="login.php">Login</a>
                </p>
            </div>

            <?php show_flash(); ?>

            <form class="auth-form" action="actions/register.php" method="POST" novalidate>
                <?= csrf_field() ?>

                <div class="form-row form-row--two">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-input"
                            placeholder="Juan Dela Cruz"
                            value="<?= clean($_POST['full_name'] ?? '') ?>"
                            autocomplete="name"
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="username">
                            Username <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            placeholder="juandelacruz"
                            value="<?= clean($_POST['username'] ?? '') ?>"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>

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

                <div class="form-row form-row--two">
                    <div class="form-group">
                        <label class="form-label" for="password">
                            Password <span class="required">*</span>
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
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            Confirm <span class="required">*</span>
                        </label>
                        <div class="form-input-wrap">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-input"
                                placeholder="Repeat password"
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" class="form-eye" onclick="togglePassword('confirm_password')">👁</button>
                        </div>
                    </div>
                </div>

                <div class="password-strength">
                    <div class="password-strength__bar">
                        <div class="password-strength__fill" id="strength-fill"></div>
                    </div>
                    <span class="password-strength__label" id="strength-label"></span>
                </div>

                <div class="form-group form-group--check">
                    <label class="form-check">
                        <input type="checkbox" name="agree_terms" required>
                        <span class="form-check__box"></span>
                        <span>I agree to the
                            <a href="terms.php" target="_blank">Terms</a>
                            and
                            <a href="privacy.php" target="_blank">Privacy Policy</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn--primary btn--full">
                    Create Account
                </button>

                <div class="auth-divider">or</div>

                <a href="login.php" class="btn btn--ghost btn--full">
                    Sign In Instead
                </a>

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