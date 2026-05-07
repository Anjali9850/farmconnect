<?php
// GET /backend/orders/wishlist.php
// POST /backend/orders/wishlist.php - Add to wishlist { product_id }
// DELETE /backend/orders/wishlist.php - Remove from wishlist { product_id }
require_once __DIR__ . '/../config.php';

$user = requireLogin();
$user_id = $user['id'];
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: Fetch wishlist ───────────────────────────────────────
if ($method === 'GET') {
    $stmt = $db->prepare('
        SELECT p.id, p.farmer_id, p.name, p.category, p.price, p.unit, 
               p.description, p.image, p.quantity,
               u.name AS farmer_name,
               w.added_at
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        JOIN users u ON p.farmer_id = u.id
        WHERE w.user_id = ? AND p.is_active = 1
        ORDER BY w.added_at DESC
    ');
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    foreach ($items as &$item) {
        $item['image_url'] = $item['image'] ? BASE_URL . '/backend/uploads/' . $item['image'] : null;
        $item['price'] = (float)$item['price'];
        $item['quantity'] = (int)$item['quantity'];
    }
    unset($item);
    
    jsonSuccess(['items' => $items, 'total' => count($items)], 'Wishlist loaded');

// ── POST: Add to wishlist ─────────────────────────────────────
} elseif ($method === 'POST') {
    $body = getBody();
    $product_id = (int)($body['product_id'] ?? 0);
    
    if (!$product_id) {
        jsonError('product_id is required.');
    }
    
    // Verify product exists
    $check = $db->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
    $check->bind_param('i', $product_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $check->close();
        jsonError('Product not found or inactive.', 404);
    }
    $check->close();
    
    // Add to wishlist
    $stmt = $db->prepare('INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $user_id, $product_id);
    
    if (!$stmt->execute()) {
        if (strpos($stmt->error, 'Duplicate') !== false) {
            $stmt->close();
            jsonError('Product already in wishlist.');
        }
        jsonError('Failed to add to wishlist: ' . $stmt->error);
    }
    $stmt->close();
    
    jsonSuccess(['product_id' => $product_id], 'Added to wishlist', 201);

// ── DELETE: Remove from wishlist ──────────────────────────────
} elseif ($method === 'DELETE') {
    $body = getBody();
    $product_id = (int)($body['product_id'] ?? 0);
    
    if (!$product_id) {
        jsonError('product_id is required.');
    }
    
    $stmt = $db->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?');
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    $stmt->close();
    
    jsonSuccess(['product_id' => $product_id], 'Removed from wishlist');

} else {
    jsonError('Method not allowed.', 405);
}
