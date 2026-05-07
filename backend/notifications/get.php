<?php
// GET /backend/notifications/get.php?limit=20&offset=0
require_once __DIR__ . '/../config.php';

$user = requireLogin();
$user_id = $user['id'];
$limit = (int)($_GET['limit'] ?? 20);
$offset = (int)($_GET['offset'] ?? 0);

if ($limit < 1 || $limit > 100) {
    $limit = 20;
}
if ($offset < 0) {
    $offset = 0;
}

$db = getDB();

// Get notifications (newest first)
$stmt = $db->prepare('
    SELECT id, user_id, type, title, message, related_user_id, related_order_id, 
           related_product_id, is_read, created_at, read_at
    FROM notifications 
    WHERE user_id = ?
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
');

if (!$stmt) {
    jsonError('Database error: ' . $db->error, 500);
}

$stmt->bind_param('iii', $user_id, $limit, $offset);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unread count
$count_stmt = $db->prepare('SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0');
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$count = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

jsonSuccess([
    'notifications' => $notifications,
    'unread_count' => (int)$count,
    'limit' => $limit,
    'offset' => $offset
], 'Notifications fetched successfully');

