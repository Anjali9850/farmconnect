<?php
// GET /backend/subscriptions/get_plan.php
require_once __DIR__ . '/../config.php';

$user = requireLogin();
$user_id = $user['id'];
$db = getDB();

// Get active subscription
$stmt = $db->prepare('
    SELECT id, plan_type, price, discount_percent, free_delivery, priority_support,
           started_at, expires_at, is_active, payment_status
    FROM subscriptions 
    WHERE user_id = ? AND is_active = 1 
    ORDER BY expires_at DESC 
    LIMIT 1
');

if (!$stmt) {
    jsonError('Database error: ' . $db->error, 500);
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$subscription = $result->fetch_assoc();
$stmt->close();

// Define available plans
$available_plans = [
    ['plan_type' => 'basic', 'price' => 99, 'discount_percent' => 5, 'free_delivery' => false, 'priority_support' => false, 'duration_days' => 30],
    ['plan_type' => 'premium', 'price' => 299, 'discount_percent' => 15, 'free_delivery' => true, 'priority_support' => true, 'duration_days' => 30],
    ['plan_type' => 'farmer_support', 'price' => 499, 'discount_percent' => 20, 'free_delivery' => true, 'priority_support' => true, 'duration_days' => 30]
];

$response = [
    'current_subscription' => null,
    'available_plans' => $available_plans
];

if ($subscription) {
    $is_expired = strtotime($subscription['expires_at']) < time();
    $subscription['is_expired'] = $is_expired;
    $subscription['days_remaining'] = max(0, intdiv(strtotime($subscription['expires_at']) - time(), 86400));
    $response['current_subscription'] = $subscription;
}

jsonSuccess($response, 'Subscription plans fetched successfully');

