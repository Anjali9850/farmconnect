<?php
// POST /backend/products/update_product.php  (farmer or admin)
// multipart/form-data: id, name, category, price, unit, description, [image]
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireRole('farmer', 'admin');

$id          = (int)($_POST['id']          ?? 0);
$name        = clean($_POST['name']        ?? '');
$category    = clean($_POST['category']    ?? '');
$price       = (float)($_POST['price']     ?? 0);
$unit        = clean($_POST['unit']        ?? '');
$description = clean($_POST['description'] ?? '');

$validCats = ['vegetable','fruit','grain','dairy','other'];

if (!$id)                                     jsonError('Product ID is required.');
if (!$name)                                   jsonError('Product name is required.');
if (!in_array($category, $validCats, true))   jsonError('Please select a valid category.');
if ($price <= 0)                               jsonError('Price must be greater than zero.');
if (!$unit)                                    jsonError('Unit is required.');

$db = getDB();

// Fetch existing product
$chk = $db->prepare('SELECT farmer_id, image FROM products WHERE id = ? LIMIT 1');
$chk->bind_param('i', $id);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$existing) jsonError('Product not found.', 404);

// Ownership check
if ($user['role'] !== 'admin' && (int)$existing['farmer_id'] !== (int)$user['id']) {
    jsonError('You are not authorised to edit this product.', 403);
}

// Optional new image
$imageName = $existing['image'];
if (!empty($_FILES['image']['name'])) {
    $file    = $_FILES['image'];
    $allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/gif'];

    if ($file['error'] !== UPLOAD_ERR_OK)    jsonError('Image upload failed.');
    if (!in_array($file['type'], $allowed))  jsonError('Invalid image type.');
    if ($file['size'] > 3 * 1024 * 1024)    jsonError('Image must be under 3 MB.');

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $imageName = 'prod_' . uniqid() . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $imageName)) {
        jsonError('Could not save image. Check uploads/ permissions.');
    }

    // Delete old image
    if ($existing['image'] && file_exists(UPLOAD_DIR . $existing['image'])) {
        @unlink(UPLOAD_DIR . $existing['image']);
    }
}

$stmt = $db->prepare(
    'UPDATE products SET name=?, category=?, price=?, unit=?, description=?, image=? WHERE id=?'
);
$stmt->bind_param('ssdsssi', $name, $category, $price, $unit, $description, $imageName, $id);

if (!$stmt->execute()) {
    $stmt->close();
    jsonError('Update failed. Please try again.');
}
$stmt->close();

jsonSuccess(['id' => $id], 'Product updated successfully.');
