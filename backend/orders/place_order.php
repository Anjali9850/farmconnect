<?php
// POST /backend/orders/place_order.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user   = requireRole('customer');
$userId = (int)$user['id'];
$db     = getDB();

// Fetch cart items
$stmt = $db->prepare(
    'SELECT c.product_id, c.quantity, p.price
     FROM cart c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($cartItems)) {
    jsonError('Your cart is empty. Add some products before placing an order.');
}

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
$total = round($total, 2);

// Place order in a transaction
$db->begin_transaction();

try {
    // Insert order
    $oStmt = $db->prepare('INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, "pending")');
    $oStmt->bind_param('id', $userId, $total);
    if (!$oStmt->execute()) throw new Exception('Failed to create order.');
    $orderId = $db->insert_id;
    $oStmt->close();

    // Insert order items
    $iStmt = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
    foreach ($cartItems as $item) {
        $iStmt->bind_param('iiid', $orderId, $item['product_id'], $item['quantity'], $item['price']);
        if (!$iStmt->execute()) throw new Exception('Failed to save order items.');
    }
    $iStmt->close();

    // Clear cart
    $clr = $db->prepare('DELETE FROM cart WHERE user_id = ?');
    $clr->bind_param('i', $userId);
    $clr->execute();
    $clr->close();

    $db->commit();

} catch (Exception $e) {
    $db->rollback();
    jsonError('Order placement failed: ' . $e->getMessage(), 500);
}

jsonSuccess(
    ['order_id' => $orderId, 'total' => $total],
    'Order placed successfully! Your farmer will process it shortly.',
    201
);
