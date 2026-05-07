<?php
// POST /backend/products/delete_product.php
// JSON body: { "id": 5 }
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireRole('farmer', 'admin');
$body = getBody();
$id   = (int)($body['id'] ?? 0);

if (!$id) jsonError('Product ID is required.');

$db = getDB();

// Get existing record
$chk = $db->prepare('SELECT farmer_id, image FROM products WHERE id = ? LIMIT 1');
$chk->bind_param('i', $id);
$chk->execute();
$prod = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$prod) jsonError('Product not found.', 404);

// Ownership check
if ($user['role'] !== 'admin' && (int)$prod['farmer_id'] !== (int)$user['id']) {
    jsonError('You are not authorised to delete this product.', 403);
}

// Remove image file
if ($prod['image'] && file_exists(UPLOAD_DIR . $prod['image'])) {
    @unlink(UPLOAD_DIR . $prod['image']);
}

$stmt = $db->prepare('DELETE FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    $stmt->close();
    jsonError('Delete failed.');
}
$stmt->close();

jsonSuccess([], 'Product deleted successfully.');
