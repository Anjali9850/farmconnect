<?php
// POST /backend/farmers/add_certificate.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireRole('farmer');
$farmer_id = $user['id'];
$db = getDB();

if (!isset($_FILES['cert_file']) || $_FILES['cert_file']['error'] !== UPLOAD_ERR_OK) {
    jsonError('No file uploaded or upload error.');
}

$cert_file = $_FILES['cert_file'];
$cert_name = $_POST['cert_name'] ?? 'Certification';
$cert_type = $_POST['cert_type'] ?? 'other';

// Validate file
if ($cert_file['size'] > 10 * 1024 * 1024) {
    jsonError('File too large (max 10MB)');
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
if (!in_array($cert_file['type'], $allowed_types)) {
    jsonError('Invalid file type. Allowed: JPG, PNG, GIF, PDF');
}

// Validate cert_type
if (!in_array($cert_type, ['organic', 'quality', 'award', 'other'], true)) {
    $cert_type = 'other';
}

// Create upload directory
$upload_dir = __DIR__ . '/../uploads/farmer_certs/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$ext = pathinfo($cert_file['name'], PATHINFO_EXTENSION);
$filename = 'cert_' . $farmer_id . '_' . time() . '.' . $ext;
$filepath = $upload_dir . $filename;

if (!move_uploaded_file($cert_file['tmp_name'], $filepath)) {
    jsonError('Failed to upload file.');
}

// Save to database
$stmt = $db->prepare('
    INSERT INTO farmer_certifications (farmer_id, user_id, cert_name, cert_file, cert_type, is_verified)
    VALUES (?, ?, ?, ?, ?, 0)
');

$cert_file_path = 'farmer_certs/' . $filename;
$stmt->bind_param('iissi', $farmer_id, $farmer_id, $cert_name, $cert_file_path, $is_verified = 0);

if (!$stmt->execute()) {
    unlink($filepath);
    jsonError('Failed to save certificate to database: ' . $stmt->error);
}

$stmt->close();

jsonSuccess([
    'id' => $db->insert_id,
    'cert_name' => $cert_name,
    'cert_type' => $cert_type,
    'is_verified' => 0
], 'Certificate uploaded successfully. Awaiting admin verification.', 201);

