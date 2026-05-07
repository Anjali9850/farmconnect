<?php
// /backend/orders/cart.php
// GET    → fetch cart for logged-in user
// POST   → add/increase item  { product_id, quantity }
// DELETE → remove item        { product_id }
require_once __DIR__ . '/../config.php';

$user   = requireLogin();
$userId = (int)$user['id'];
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ───────────────────────────────────────────────────────
if ($method === 'GET') {

    $stmt = $db->prepare(
        'SELECT c.product_id, c.quantity,
                p.name, p.price, p.unit, p.image,
                u.name AS farmer_name
         FROM cart c
         JOIN products p ON c.product_id = p.id
         JOIN users    u ON p.farmer_id  = u.id
         WHERE c.user_id = ?
         ORDER BY c.added_at DESC'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total = 0;
    foreach ($rows as &$row) {
        $row['image_url'] = $row['image']
            ? BASE_URL . '/backend/uploads/' . $row['image']
            : null;
        $row['subtotal'] = round($row['price'] * $row['quantity'], 2);
        $total          += $row['subtotal'];
    }
    unset($row);

    jsonSuccess(['items' => $rows, 'total' => round($total, 2)], 'Cart loaded.');

// ── POST ──────────────────────────────────────────────────────
} elseif ($method === 'POST') {

    $body      = getBody();
    $productId = (int)($body['product_id'] ?? 0);
    $qty       = max(1, (int)($body['quantity'] ?? 1));

    if (!$productId) jsonError('product_id is required.');

    // Check product exists and get farmer info
    $chk = $db->prepare('SELECT farmer_id, name FROM products WHERE id = ? LIMIT 1');
    $chk->bind_param('i', $productId);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) { $chk->close(); jsonError('Product not found.', 404); }
    $product_result = $chk->get_result();
    $product = $product_result->fetch_assoc();
    $chk->close();
    
    $farmerId = $product['farmer_id'];
    $productName = $product['name'];

    // Upsert: add to existing quantity if already in cart
    $stmt = $db->prepare(
        'INSERT INTO cart (user_id, product_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
    );
    $stmt->bind_param('iii', $userId, $productId, $qty);
    if (!$stmt->execute()) { $stmt->close(); jsonError('Could not add item to cart.'); }
    $stmt->close();

    // Send notification to farmer
    $notification_title = 'Product Added to Cart';
    $notification_message = $user['name'] . ' added ' . $qty . 'x ' . $productName . ' to their cart.';
    sendNotification($farmerId, 'product', $notification_title, $notification_message, $userId, null, $productId);

    jsonSuccess([], 'Item added to cart.');


// ── DELETE ────────────────────────────────────────────────────
} elseif ($method === 'DELETE') {

    $body      = getBody();
    $productId = (int)($body['product_id'] ?? 0);

    if (!$productId) jsonError('product_id is required.');

    $stmt = $db->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    $stmt->close();

    jsonSuccess([], 'Item removed from cart.');

} else {
    jsonError('Method not allowed.', 405);
}
