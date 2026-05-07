<?php
// GET /backend/orders/get_orders.php
// Returns orders filtered by the logged-in user's role
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$user = requireLogin();
$db   = getDB();
$filter = $_GET['filter'] ?? 'all';

if ($user['role'] === 'customer') {

    $query = 'SELECT o.id, o.customer_id, o.total_amount, o.status, o.created_at,
                COUNT(oi.id) AS item_count,
                GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ", ") AS items_summary
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         JOIN products p ON p.id = oi.product_id
         WHERE o.customer_id = ?';
    
    if ($filter !== 'all') {
        $query .= ' AND o.status = ?';
    }
    
    $query .= ' GROUP BY o.id ORDER BY o.created_at DESC';
    
    $stmt = $db->prepare($query);
    if ($filter !== 'all') {
        $stmt->bind_param('is', $user['id'], $filter);
    } else {
        $stmt->bind_param('i', $user['id']);
    }

} elseif ($user['role'] === 'farmer') {

    $query = 'SELECT DISTINCT o.id, o.customer_id, o.total_amount, o.status, o.created_at,
                u.name AS customer_name,
                u.email AS customer_email,
                GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ", ") AS items_summary
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         JOIN products p ON p.id = oi.product_id
         JOIN users u ON u.id = o.customer_id
         WHERE oi.farmer_id = ?';
    
    if ($filter !== 'all') {
        $query .= ' AND o.status = ?';
    }
    
    $query .= ' GROUP BY o.id ORDER BY o.created_at DESC';
    
    $stmt = $db->prepare($query);
    if ($filter !== 'all') {
        $stmt->bind_param('is', $user['id'], $filter);
    } else {
        $stmt->bind_param('i', $user['id']);
    }

} else {
    // Admin — all orders
    $query = 'SELECT o.id, o.customer_id, o.total_amount, o.status, o.created_at,
                u.name AS customer_name,
                u.email AS customer_email,
                COUNT(oi.id) AS item_count,
                GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ", ") AS items_summary
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         JOIN products p ON p.id = oi.product_id
         JOIN users u ON u.id = o.customer_id
         GROUP BY o.id
         ORDER BY o.created_at DESC';
    
    $stmt = $db->prepare($query);
}

$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

jsonSuccess($orders, 'Orders fetched.');
