<?php
// POST /backend/farmers/upload_gallery.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireRole('farmer');
$farmer_id = $user['id'];

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    jsonError('No image uploaded or upload error.');
}

$image_file = $_FILES['image'];
$image_title = $_POST['image_title'] ?? '';

// Validate file
if ($image_file['size'] > 5 * 1024 * 1024) {
    jsonError('Image too large (max 5MB)');
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($image_file['type'], $allowed_types)) {
    jsonError('Invalid image format. Allowed: JPEG, PNG, GIF, WebP');
}

// Create upload directory
$upload_dir = __DIR__ . '/../uploads/farmer_gallery/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$ext = pathinfo($image_file['name'], PATHINFO_EXTENSION);
$filename = 'gallery_' . $farmer_id . '_' . time() . '_' . uniqid() . '.' . $ext;
$filepath = $upload_dir . $filename;

if (!move_uploaded_file($image_file['tmp_name'], $filepath)) {
    jsonError('Failed to upload image.');
}

$db = getDB();

// Save to database
$image_path = 'farmer_gallery/' . $filename;
$stmt = $db->prepare('INSERT INTO farmer_gallery (farmer_id, user_id, image_path, image_title) VALUES (?, ?, ?, ?)');

if (!$stmt) {
    unlink($filepath);
    jsonError('Database error: ' . $db->error, 500);
}

$stmt->bind_param('iiss', $farmer_id, $farmer_id, $image_path, $image_title);

if (!$stmt->execute()) {
    unlink($filepath);
    jsonError('Failed to save image to database: ' . $stmt->error);
}

$gallery_id = $db->insert_id;
$stmt->close();

jsonSuccess([
    'id' => $gallery_id,
    'image_path' => $image_path,
    'image_title' => $image_title
], 'Image uploaded successfully', 201);

