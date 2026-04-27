<?php
// public_html/api/upload-reference.php
// Handles AJAX single file upload for reference images
ob_start();
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
ob_clean();

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file  = $_FILES['file'];
$valid = validate_image($file);

if ($valid !== true) {
    echo json_encode(['success' => false, 'error' => $valid]);
    exit;
}

$filename = save_upload($file, UPLOAD_REFERENCES);

if (!$filename) {
    echo json_encode(['success' => false, 'error' => 'Upload failed. Please try again.']);
    exit;
}

echo json_encode([
    'success'  => true,
    'filename' => $filename,
    'original' => $file['name'],
]);