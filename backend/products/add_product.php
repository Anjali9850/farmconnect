<?php
// POST /backend/products/add_product.php  (farmer or admin only)
// Accepts multipart/form-data: name, category, price, unit, description, [image file]
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireRole('farmer', 'admin');

// ── Read and validate fields ──────────────────────────────────
$name        = clean($_POST['name']        ?? '');
$category    = clean($_POST['category']    ?? '');
$priceRaw    = $_POST['price']             ?? '0';
$price       = floatval($priceRaw);
$unit        = clean($_POST['unit']        ?? '');
$description = clean($_POST['description'] ?? '');

$validCats = ['vegetable', 'fruit', 'grain', 'dairy', 'other'];

if (!$name)                                      jsonError('Product name is required.');
if (!in_array($category, $validCats, true))      jsonError('Please select a valid category.');
if ($price <= 0)                                 jsonError('Price must be greater than zero.');
if (!$unit)                                      jsonError('Unit is required (e.g. kg, 500g, piece).');

// ── Image upload (optional) ───────────────────────────────────
$imageName = '';   // empty string stored when no image

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file      = $_FILES['image'];
    $allowed   = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $maxBytes  = 3 * 1024 * 1024;   // 3 MB

    if (!in_array($file['type'], $allowed, true)) {
        jsonError('Image must be JPEG, PNG, WebP or GIF.');
    }
    if ($file['size'] > $maxBytes) {
        jsonError('Image file must be under 3 MB.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $imageName = 'prod_' . uniqid('', true) . '.' . $ext;
    $dest      = UPLOAD_DIR . $imageName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonError('Image could not be saved. Check that backend/uploads/ folder exists and is writable.');
    }
}

// ── Determine farmer ID ───────────────────────────────────────
$farmerId = (int)$user['id'];
if ($user['role'] === 'admin' && !empty($_POST['farmer_id'])) {
    $farmerId = (int)$_POST['farmer_id'];
}

// ── Insert into DB ────────────────────────────────────────────
$db   = getDB();
$stmt = $db->prepare(
    'INSERT INTO products (farmer_id, name, category, price, unit, description, image)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);

// Types: i=int  s=string  s=string  d=double  s=string  s=string  s=string
$stmt->bind_param('issdsss',
    $farmerId, $name, $category, $price, $unit, $description, $imageName
);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    jsonError('Database insert failed: ' . $err);
}

$productId = $db->insert_id;
$stmt->close();

jsonSuccess(['id' => $productId], 'Product added successfully!', 201);
