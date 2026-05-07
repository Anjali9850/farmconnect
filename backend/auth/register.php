<?php
// POST /backend/auth/register.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$body = getBody();

$name  = clean($body['name']     ?? '');
$email = strtolower(trim($body['email']    ?? ''));
$phone = clean($body['phone']    ?? '');
$pass  = $body['password']       ?? '';
$role  = $body['role']           ?? 'customer';

// Validate
if (!$name)                                     jsonError('Full name is required.');
if (!$email)                                    jsonError('Email address is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Please enter a valid email address.');
if (strlen($pass) < 6)                          jsonError('Password must be at least 6 characters.');
if (!in_array($role, ['customer', 'farmer'], true)) jsonError('Invalid role selected.');

$db = getDB();

// Check for existing email
$chk = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$chk->bind_param('s', $email);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $chk->close();
    jsonError('An account with this email already exists.');
}
$chk->close();

// Hash password
$hash     = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]);
$approved = ($role === 'customer') ? 1 : 0;

// Insert user
$stmt = $db->prepare(
    'INSERT INTO users (name, email, phone, password, role, approved) VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('sssssi', $name, $email, $phone, $hash, $role, $approved);

if (!$stmt->execute()) {
    $stmt->close();
    jsonError('Registration failed. Please try again.');
}

$userId = $db->insert_id;
$stmt->close();

// Auto login after register
$_SESSION['user'] = [
    'id'       => $userId,
    'name'     => $name,
    'email'    => $email,
    'role'     => $role,
    'approved' => $approved,
];

$msg = ($role === 'farmer')
    ? 'Account created! Waiting for admin approval before you can list products.'
    : 'Welcome to FarmConnect! Your account is ready.';

jsonSuccess(
    ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role, 'approved' => $approved],
    $msg,
    201
);
