<?php
// public_html/actions/commission-submit.php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../commission.php');
    exit();
}

require_login('login.php');
verify_csrf();

$user_id        = current_user_id();
$title          = clean($_POST['title']          ?? '');
$tier           = clean($_POST['tier']            ?? '');
$style          = clean($_POST['style']           ?? '');
$description    = clean($_POST['description']     ?? '');
$customer_notes = clean($_POST['customer_notes']  ?? '');

// Validate
$errors = [];

if (empty($title)) {
    $errors[] = 'Commission title is required.';
}

if (!in_array($tier, ['sketch', 'full_color', 'illustrated'])) {
    $errors[] = 'Please select a commission tier.';
}

if (empty($description) || strlen($description) < 10) {
    $errors[] = 'Please provide a description of at least 10 characters.';
}

if (!empty($errors)) {
    set_flash('error', $errors[0]);
    header('Location: ../commission.php');
    exit();
}

// Insert into commissions table
try {
    $stmt = $pdo->prepare(
        "INSERT INTO commissions
            (user_id, title, description, style, tier, customer_notes, status)
         VALUES (?, ?, ?, ?, ?, ?, 'pending')"
    );
    $stmt->execute([
        $user_id,
        $title,
        $description,
        $style,
        $tier,
        $customer_notes,
    ]);
    $commission_id = (int) $pdo->lastInsertId();

} catch (PDOException $e) {
    set_flash('error', 'Something went wrong. Please try again.');
    header('Location: ../commission.php');
    exit();
}

// Handle reference image uploads
if (!empty($_FILES['references']['name'][0])) {
    $files     = $_FILES['references'];
    $count     = count($files['name']);
    $max_files = 5;

    for ($i = 0; $i < min($count, $max_files); $i++) {
        $file = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) continue;

        $valid = validate_image($file);
        if ($valid !== true) continue;

        $filename = save_upload($file, UPLOAD_REFERENCES);

        if ($filename) {
            $pdo->prepare(
                "INSERT INTO commission_references
                    (commission_id, file_path, original_name)
                 VALUES (?, ?, ?)"
            )->execute([$commission_id, $filename, $file['name']]);
        }
    }
}

// Create notification
create_notification(
    $pdo,
    $user_id,
    'commission_update',
    'Commission Submitted!',
    'Your commission "' . $title . '" has been received. We\'ll send a quote within 24 hours.',
    'dashboard.php'
);

// Success — redirect back with overlay flag
header('Location: ../commission.php?submitted=1');
exit();