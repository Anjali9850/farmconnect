<?php
header('Content-Type: application/json');
require_once '../config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;
$action = $data['action'] ?? null; // 'accept' or 'reject'

if (!$order_id || !$action) {
    echo json_encode(['error' => 'Missing order_id or action']);
    exit;
}

// Get order details
$stmt = $conn->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Verify farmer owns this order
$stmt = $conn->prepare('SELECT farmer_id FROM order_items WHERE order_id = ? LIMIT 1');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$farmer_id = $item['farmer_id'] ?? null;

if ($farmer_id != $user_id) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($action === 'accept') {
    $new_status = 'processing';
} elseif ($action === 'reject') {
    $new_status = 'cancelled';
    // Restore stock for rejected order
    $stmt = $conn->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ?');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    while ($item = $items->fetch_assoc()) {
        $stmt2 = $conn->prepare('UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?');
        $stmt2->bind_param('ii', $item['qty'], $item['product_id']);
        $stmt2->execute();
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

// Update order status
$stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->bind_param('si', $new_status, $order_id);
$stmt->execute();

// Create notification for customer
$customer_id = $order['customer_id'];
if ($action === 'accept') {
    $notif_msg = 'Your order #' . $order_id . ' has been accepted and is being processed.';
} else {
    $notif_msg = 'Your order #' . $order_id . ' has been rejected. Stock has been restored.';
}

$stmt = $conn->prepare('INSERT INTO notifications (user_id, type, message, order_id) VALUES (?, ?, ?, ?)');
$stmt->bind_param('issi', $customer_id, $action === 'accept' ? 'order_accepted' : 'order_rejected', $notif_msg, $order_id);
$stmt->execute();

echo json_encode(['success' => true, 'status' => $new_status]);
?>
