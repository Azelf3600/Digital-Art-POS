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

// ── Validate all fields and collect ALL errors ──
$errors = [];

if (empty($title)) {
    $errors[] = 'Commission title is required.';
}

if (!in_array($tier, ['sketch', 'full_color', 'illustrated'])) {
    $errors[] = 'Please select a commission tier (Sketch, Full Color, or Illustrated).';
}

if (empty($description)) {
    $errors[] = 'Description is required. Tell us about your commission.';
} elseif (strlen($description) < 10) {
    $errors[] = 'Description is too short. Please provide at least 10 characters.';
}

// If any errors, store them all and redirect back
if (!empty($errors)) {
    // Store all errors as a list
    $_SESSION['commission_errors'] = $errors;
    // Store form data so fields stay filled
    $_SESSION['commission_old'] = [
        'title'          => $title,
        'tier'           => $tier,
        'style'          => $style,
        'description'    => $description,
        'customer_notes' => $customer_notes,
    ];
    header('Location: ../commission.php');
    exit();
}

// ── Insert commission ──
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
    $_SESSION['commission_errors'] = ['Something went wrong saving your commission. Please try again.'];
    header('Location: ../commission.php');
    exit();
}

// ── Handle reference image uploads ──
if (!empty($_FILES['references']['name'][0])) {
    $files     = $_FILES['references'];
    $count     = count($files['name']);
    $max_files = 5;

    // Make sure the upload directory exists
    if (!is_dir(UPLOAD_REFERENCES)) {
        mkdir(UPLOAD_REFERENCES, 0755, true);
    }

    for ($i = 0; $i < min($count, $max_files); $i++) {
        // Skip empty slots
        if (empty($files['name'][$i])) continue;

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

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'ref_' . $commission_id . '_' . uniqid() . '.' . $ext;
        $target   = rtrim(UPLOAD_REFERENCES, '/') . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $pdo->prepare(
                "INSERT INTO commission_references
                    (commission_id, file_path, original_name)
                 VALUES (?, ?, ?)"
            )->execute([$commission_id, $filename, $file['name']]);
        }
    }
}

// ── Create notification ──
create_notification(
    $pdo,
    $user_id,
    'commission_update',
    'Commission Submitted!',
    'Your commission "' . $title . '" has been received. We\'ll send a quote within 24 hours.',
    'dashboard.php'
);

// ── Success ──
header('Location: ../commission.php?submitted=1');
exit();