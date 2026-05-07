<?php
// GET /backend/products/get_products.php
// ?category=vegetable  ?search=tomato  ?farmer_id=2
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$db       = getDB();
$category = clean($_GET['category']  ?? '');
$search   = clean($_GET['search']    ?? '');
$farmerId = (int)($_GET['farmer_id'] ?? 0);

// Build query dynamically
$sql    = 'SELECT p.id, p.farmer_id, p.name, p.category, p.price, p.unit,
                  p.description, p.image, p.created_at,
                  u.name AS farmer_name
           FROM products p
           JOIN users u ON p.farmer_id = u.id
           WHERE 1=1';
$params = [];
$types  = '';

$validCats = ['vegetable','fruit','grain','dairy','other'];
if ($category && in_array($category, $validCats, true)) {
    $sql      .= ' AND p.category = ?';
    $params[]  = $category;
    $types    .= 's';
}

if ($search) {
    $like      = '%' . $search . '%';
    $sql      .= ' AND (p.name LIKE ? OR p.description LIKE ? OR u.name LIKE ?)';
    $params[]  = $like;
    $params[]  = $like;
    $params[]  = $like;
    $types    .= 'sss';
}

if ($farmerId > 0) {
    $sql      .= ' AND p.farmer_id = ?';
    $params[]  = $farmerId;
    $types    .= 'i';
}

$sql .= ' ORDER BY p.created_at DESC';

$stmt = $db->prepare($sql);
if ($types && $params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Add full image URL to each row
foreach ($rows as &$row) {
    $row['image_url'] = ($row['image'])
        ? BASE_URL . '/backend/uploads/' . $row['image']
        : null;
}
unset($row);

jsonSuccess($rows, 'Products fetched successfully.');
