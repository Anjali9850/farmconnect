<?php
// DELETE /backend/farmers/delete_gallery_image.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireRole('farmer');
$farmer_id = $user['id'];

$body = getBody();
$image_id = (int)($body['image_id'] ?? 0);

if (!$image_id) {
    jsonError('image_id is required.');
}

$db = getDB();

// Get image and verify it belongs to farmer
$stmt = $db->prepare('SELECT image_path FROM farmer_gallery WHERE id = ? AND farmer_id = ?');
$stmt->bind_param('ii', $image_id, $farmer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    jsonError('Image not found or does not belong to you.', 404);
}

$image = $result->fetch_assoc();
$image_path = $image['image_path'];
$stmt->close();

// Delete from database
$delete_stmt = $db->prepare('DELETE FROM farmer_gallery WHERE id = ? AND farmer_id = ?');
$delete_stmt->bind_param('ii', $image_id, $farmer_id);

if (!$delete_stmt->execute()) {
    jsonError('Failed to delete image from database.');
}

$delete_stmt->close();

// Try to delete file
$filepath = __DIR__ . '/../uploads/' . $image_path;
if (file_exists($filepath)) {
    unlink($filepath);
}

jsonSuccess(['id' => $image_id], 'Image deleted successfully');

