<?php
// POST /backend/subscriptions/subscribe.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireLogin();
$user_id = $user['id'];

$body = getBody();
$plan_type = $body['plan_type'] ?? null;

// Validate plan
if (!$plan_type || !in_array($plan_type, ['basic', 'premium', 'farmer_support'], true)) {
    jsonError('Invalid plan type. Allowed: basic, premium, farmer_support');
}

// Plan configuration
$plans = [
    'basic' => [
        'price' => 99.00,
        'discount_percent' => 5,
        'free_delivery' => false,
        'priority_support' => false
    ],
    'premium' => [
        'price' => 299.00,
        'discount_percent' => 15,
        'free_delivery' => true,
        'priority_support' => true
    ],
    'farmer_support' => [
        'price' => 499.00,
        'discount_percent' => 20,
        'free_delivery' => true,
        'priority_support' => true
    ]
];

$plan_config = $plans[$plan_type];
$price = $plan_config['price'];
$discount_percent = $plan_config['discount_percent'];
$free_delivery = $plan_config['free_delivery'] ? 1 : 0;
$priority_support = $plan_config['priority_support'] ? 1 : 0;

$db = getDB();

// Cancel any existing active subscriptions
$cancel_stmt = $db->prepare('UPDATE subscriptions SET is_active = 0 WHERE user_id = ? AND is_active = 1');
$cancel_stmt->bind_param('i', $user_id);
$cancel_stmt->execute();
$cancel_stmt->close();

// Calculate subscription period (30 days)
$started_at = date('Y-m-d H:i:s');
$expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

// Create new subscription
$stmt = $db->prepare('
    INSERT INTO subscriptions 
    (user_id, plan_type, price, discount_percent, free_delivery, priority_support, started_at, expires_at, is_active, payment_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, "completed")
');

if (!$stmt) {
    jsonError('Database error: ' . $db->error, 500);
}

$stmt->bind_param(
    'issiiiss',
    $user_id, $plan_type, $price, $discount_percent, $free_delivery, $priority_support, $started_at, $expires_at
);

if (!$stmt->execute()) {
    jsonError('Failed to create subscription: ' . $stmt->error);
}

$subscription_id = $db->insert_id;
$stmt->close();

// Send notification to user
$notification_stmt = $db->prepare('
    INSERT INTO notifications (user_id, type, title, message, is_read)
    VALUES (?, "promotion", ?, ?, 0)
');

$title = "Subscription Activated: " . ucfirst(str_replace('_', ' ', $plan_type));
$message = "You have subscribed to the {$plan_type} plan. Enjoy {$discount_percent}% discount on all products!";
$notification_stmt->bind_param('iss', $user_id, $title, $message);
$notification_stmt->execute();
$notification_stmt->close();

jsonSuccess([
    'id' => $subscription_id,
    'plan_type' => $plan_type,
    'price' => $price,
    'discount_percent' => $discount_percent,
    'free_delivery' => (bool)$free_delivery,
    'priority_support' => (bool)$priority_support,
    'started_at' => $started_at,
    'expires_at' => $expires_at,
    'payment_status' => 'completed'
], 'Subscription activated successfully!', 201);

