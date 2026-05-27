<?php
// ============================================
// includes/auth.php
// Starflow — Page Protection & Auth Checks
// ============================================

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/session.php';
}

// ============================================
// USER FETCHING
// ============================================

/**
 * Get the full current user record from the database
 * Returns the user array or null if not logged in
 */
function get_logged_in_user(PDO $pdo): array|null {
    if (!is_logged_in()) return null;

    $stmt = $pdo->prepare(
        "SELECT id, username, email, role, full_name, profile_picture, is_active,  created_at
         FROM users WHERE id = ? LIMIT 1"
    );
    $stmt->execute([current_user_id()]);
    $user = $stmt->fetch();

    // If user was banned or deleted since login, force logout
    if (!$user || !$user['is_active']) {
        logout_user();
        redirect('login.php');
    }

    return $user;
}

/**
 * Get a user by their ID
 */
function get_user_by_id(PDO $pdo, int $id): array|false {
    $stmt = $pdo->prepare(
        "SELECT id, username, email, role, full_name, profile_picture, is_active, created_at
         FROM users WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get a user by their email
 */
function get_user_by_email(PDO $pdo, string $email): array|false {
    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Check if a username is already taken
 */
function username_exists(PDO $pdo, string $username): bool {
    $stmt = $pdo->prepare(
        "SELECT id FROM users WHERE username = ? LIMIT 1"
    );
    $stmt->execute([$username]);
    return (bool) $stmt->fetch();
}

/**
 * Check if an email is already registered
 */
function email_exists(PDO $pdo, string $email): bool {
    $stmt = $pdo->prepare(
        "SELECT id FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->execute([$email]);
    return (bool) $stmt->fetch();
}

// ============================================
// REGISTRATION
// ============================================

/**
 * Validate registration form data
 * Returns an array of errors, empty if all valid
 */
function validate_registration(
    PDO    $pdo,
    string $username,
    string $email,
    string $password,
    string $confirm
): array {
    $errors = [];

    // Username
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    } elseif (username_exists($pdo, $username)) {
        $errors[] = 'That username is already taken.';
    }

    // Email
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (email_exists($pdo, $email)) {
        $errors[] = 'That email is already registered.';
    }

    // Password
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }

    // Confirm password
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    return $errors;
}

/**
 * Register a new user
 * Returns the new user's ID
 */
function register_user(
    PDO    $pdo,
    string $username,
    string $email,
    string $password,
    string $full_name = ''
): int {
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (username, email, password, full_name, role)
         VALUES (?, ?, ?, ?, 'customer')"
    );
    $stmt->execute([$username, $email, $hashed, $full_name]);
    return (int) $pdo->lastInsertId();
}

// ============================================
// LOGIN
// ============================================

/**
 * Validate login credentials
 * Returns the user array on success, or an error string on failure
 */
function validate_login(
    PDO    $pdo,
    string $email,
    string $password
): array|string {
    if (empty($email) || empty($password)) {
        return 'Please fill in all fields.';
    }

    $user = get_user_by_email($pdo, $email);

    if (!$user) {
        return 'Invalid email or password.';
    }

    if (!$user['is_active']) {
        return 'Your account has been suspended. Please contact support.';
    }

    if (!password_verify($password, $user['password'])) {
        return 'Invalid email or password.';
    }

    return $user;
}

// ============================================
// PASSWORD RESET
// ============================================

/**
 * Create a password reset token for a user
 * Returns the token string
 */
function create_reset_token(PDO $pdo, int $user_id): string {
    // Invalidate any existing tokens first
    $stmt = $pdo->prepare(
        "UPDATE password_resets SET used = 1 WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);

    // Create new token — let MySQL handle the expiry time so timezone matches
    $token = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare(
        "INSERT INTO password_resets (user_id, token, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
    );
    $stmt->execute([$user_id, $token]);

    return $token;
}

/**
 * Validate a password reset token
 * Returns the user_id if valid, or false if invalid/expired
 */
function validate_reset_token(PDO $pdo, string $token): int|false {
    $stmt = $pdo->prepare(
        "SELECT user_id FROM password_resets
         WHERE token = ?
           AND used = 0
           AND expires_at > NOW()
         LIMIT 1"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ? (int) $row['user_id'] : false;
}

/**
 * Mark a reset token as used after password is changed
 */
function use_reset_token(PDO $pdo, string $token): void {
    $stmt = $pdo->prepare(
        "UPDATE password_resets SET used = 1 WHERE token = ?"
    );
    $stmt->execute([$token]);
}

/**
 * Update a user's password
 */
function update_password(PDO $pdo, int $user_id, string $new_password): void {
    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt   = $pdo->prepare(
        "UPDATE users SET password = ? WHERE id = ?"
    );
    $stmt->execute([$hashed, $user_id]);
}