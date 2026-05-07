<?php
// POST /backend/orders/update_status.php
// JSON body: { "order_id": 3, "status": "completed" }
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user    = requireRole('admin', 'farmer');
$body    = getBody();
$orderId = (int)($body['order_id'] ?? 0);
$status  = clean($body['status']   ?? '');

$valid = ['pending', 'processing', 'completed', 'cancelled'];

if (!$orderId)                  jsonError('order_id is required.');
if (!in_array($status, $valid)) jsonError('Invalid status. Must be: pending, processing, completed, or cancelled.');

$db   = getDB();

// Get customer_id for notification
$stmt = $db->prepare('SELECT customer_id FROM orders WHERE id = ?');
$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    jsonError('Order not found.');
}

// Update status
$stmt = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->bind_param('si', $status, $orderId);
if (!$stmt->execute()) { $stmt->close(); jsonError('Status update failed.'); }
$stmt->close();

// Create notification for customer
$customer_id = $order['customer_id'];
$notif_types = ['processing' => 'order_processing', 'completed' => 'order_completed', 'cancelled' => 'order_cancelled'];
$notif_messages = [
    'processing' => 'Your order #' . $orderId . ' is being processed.',
    'completed' => 'Your order #' . $orderId . ' has been completed!',
    'cancelled' => 'Your order #' . $orderId . ' has been cancelled.'
];

if (isset($notif_types[$status]) && isset($notif_messages[$status])) {
    $notif_type = $notif_types[$status];
    $notif_msg = $notif_messages[$status];
    $stmt = $db->prepare('INSERT INTO notifications (user_id, type, message, order_id) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('issi', $customer_id, $notif_type, $notif_msg, $orderId);
    $stmt->execute();
    $stmt->close();
}

jsonSuccess([], 'Order #' . $orderId . ' updated to "' . $status . '".');
