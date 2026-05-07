<?php
// /backend/auth/admin_users.php  — Admin only
// GET  → list all users
// POST → { action: "approve"|"delete", user_id: N }
require_once __DIR__ . '/../config.php';

$user   = requireRole('admin');
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    $stmt = $db->prepare(
        'SELECT id, name, email, phone, role, approved, created_at
         FROM users ORDER BY created_at DESC'
    );
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    jsonSuccess($rows, 'Users fetched.');

} elseif ($method === 'POST') {

    $body   = getBody();
    $action = $body['action']  ?? '';
    $uid    = (int)($body['user_id'] ?? 0);

    if (!$uid)              jsonError('user_id is required.');
    if ($uid === $user['id']) jsonError('You cannot modify your own account.');

    if ($action === 'approve') {
        $stmt = $db->prepare('UPDATE users SET approved = 1 WHERE id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();
        jsonSuccess([], 'User approved successfully.');

    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();
        jsonSuccess([], 'User deleted successfully.');

    } else {
        jsonError('Invalid action. Use "approve" or "delete".');
    }

} else {
    jsonError('Method not allowed.', 405);
}
