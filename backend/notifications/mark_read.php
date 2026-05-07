<?php
// POST /backend/notifications/mark_read.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireLogin();
$user_id = $user['id'];

$body = getBody();
$notification_id = $body['notification_id'] ?? null;
$mark_all = $body['mark_all'] ?? false;

$db = getDB();

if ($mark_all) {
    // Mark all as read for current user
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0');
    $stmt->bind_param('i', $user_id);
    
    if (!$stmt->execute()) {
        jsonError('Failed to mark notifications as read.');
    }
    
    $stmt->close();
    jsonSuccess(['marked_count' => $db->affected_rows], 'All notifications marked as read');
} elseif ($notification_id) {
    // Mark single as read
    $notification_id = (int)$notification_id;
    
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $notification_id, $user_id);
    
    if (!$stmt->execute()) {
        jsonError('Failed to mark notification as read.');
    }
    
    $stmt->close();
    
    if ($db->affected_rows === 0) {
        jsonError('Notification not found or does not belong to you.', 404);
    }
    
    jsonSuccess(['id' => $notification_id], 'Notification marked as read');
} else {
    jsonError('Missing notification_id or mark_all parameter.');
}

