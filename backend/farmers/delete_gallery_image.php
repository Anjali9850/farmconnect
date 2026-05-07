<?php
header('Content-Type: application/json');
require_once '../config.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$image_id = $data['image_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$image_id) {
    echo json_encode(['success' => false, 'message' => 'Image ID required']);
    exit;
}

// Get image and verify it belongs to user
$stmt = $conn->prepare('SELECT image_url FROM farmer_gallery WHERE id = ? AND farmer_id = ?');
$stmt->bind_param('ii', $image_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Image not found']);
    exit;
}

$image = $result->fetch_assoc();

// Delete from database
$stmt = $conn->prepare('DELETE FROM farmer_gallery WHERE id = ? AND farmer_id = ?');
$stmt->bind_param('ii', $image_id, $user_id);

if ($stmt->execute()) {
    // Try to delete file if it exists
    $filepath = str_replace('/farmconnect/backend/', '../', $image['image_url']);
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    echo json_encode(['success' => true, 'message' => 'Image deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete']);
}
?>
