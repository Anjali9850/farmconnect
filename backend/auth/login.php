<?php
// POST /backend/auth/login.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$body  = getBody();
$email = strtolower(trim($body['email']    ?? ''));
$pass  = $body['password'] ?? '';

if (!$email || !$pass) {
    jsonError('Email and password are required.');
}

$db   = getDB();
$stmt = $db->prepare(
    'SELECT id, name, email, phone, password, role, approved FROM users WHERE email = ? LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

// Verify password
if (!$user || !password_verify($pass, $user['password'])) {
    jsonError('Incorrect email or password.', 401);
}

// Check farmer is approved
if ($user['role'] === 'farmer' && !$user['approved']) {
    jsonError('Your farmer account is pending approval by the admin. Please check back soon.', 403);
}

// Store in session (never store password)
unset($user['password']);
$_SESSION['user'] = $user;

jsonSuccess(
    [
        'id'       => (int)$user['id'],
        'name'     => $user['name'],
        'email'    => $user['email'],
        'role'     => $user['role'],
        'approved' => (bool)$user['approved'],
    ],
    'Welcome back, ' . $user['name'] . '!'
);
