<?php
// POST /backend/farmers/add_review.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireRole('customer');
$reviewer_id = $user['id'];

$body = getBody();
$farmer_id = (int)($body['farmer_id'] ?? 0);
$rating = (int)($body['rating'] ?? 0);
$review_text = clean($body['review_text'] ?? '');

if (!$farmer_id) {
    jsonError('farmer_id is required.');
}

if ($rating < 1 || $rating > 5) {
    jsonError('Rating must be between 1 and 5.');
}

$db = getDB();

// Verify farmer exists
$check = $db->prepare('SELECT id FROM users WHERE id = ? AND role = "farmer" AND is_active = 1');
$check->bind_param('i', $farmer_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    $check->close();
    jsonError('Farmer not found or inactive.', 404);
}
$check->close();

// Insert or update review (one review per customer per farmer)
$stmt = $db->prepare('
    INSERT INTO farmer_reviews (farmer_id, reviewer_id, rating, review_text)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    rating = VALUES(rating),
    review_text = VALUES(review_text),
    reviewed_at = NOW()
');

if (!$stmt) {
    jsonError('Database error: ' . $db->error, 500);
}

$stmt->bind_param('iiis', $farmer_id, $reviewer_id, $rating, $review_text);

if (!$stmt->execute()) {
    jsonError('Failed to save review: ' . $stmt->error);
}

$stmt->close();

// Get reviewer info for notification
$reviewer_stmt = $db->prepare('SELECT name FROM users WHERE id = ?');
$reviewer_stmt->bind_param('i', $reviewer_id);
$reviewer_stmt->execute();
$reviewer = $reviewer_stmt->get_result()->fetch_assoc();
$reviewer_stmt->close();

// Send notification to farmer
$notification_title = 'New Review - ' . $rating . ' Stars';
$notification_message = $reviewer['name'] . ' left a ' . $rating . '-star review.';
sendNotification($farmer_id, 'farmer', $notification_title, $notification_message, $reviewer_id);

jsonSuccess([
    'farmer_id' => $farmer_id,
    'rating' => $rating,
    'review_text' => $review_text
], 'Review submitted successfully', 201);
