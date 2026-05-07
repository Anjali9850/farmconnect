<?php
// POST /backend/farmers/verify_certificate.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireRole('admin');
$db = getDB();

$body = getBody();
$cert_id = (int)($body['cert_id'] ?? 0);
$is_verified = (int)($body['is_verified'] ?? 0);

if (!$cert_id) {
    jsonError('cert_id is required.');
}

if (!in_array($is_verified, [0, 1], true)) {
    jsonError('is_verified must be 0 or 1.');
}

// Get certificate info
$cert_stmt = $db->prepare('SELECT farmer_id FROM farmer_certifications WHERE id = ?');
$cert_stmt->bind_param('i', $cert_id);
$cert_stmt->execute();
$cert_result = $cert_stmt->get_result();

if ($cert_result->num_rows === 0) {
    $cert_stmt->close();
    jsonError('Certificate not found.', 404);
}

$cert = $cert_result->fetch_assoc();
$farmer_id = $cert['farmer_id'];
$cert_stmt->close();

// Update certificate verification status
$update_stmt = $db->prepare('
    UPDATE farmer_certifications 
    SET is_verified = ?, verified_by = ?, verified_at = NOW()
    WHERE id = ?
');

$admin_id = $user['id'];
$update_stmt->bind_param('iii', $is_verified, $admin_id, $cert_id);

if (!$update_stmt->execute()) {
    jsonError('Failed to update certificate verification.');
}

$update_stmt->close();

// Send notification to farmer
if ($is_verified) {
    $notification_title = 'Certificate Verified';
    $notification_message = 'Your certificate has been verified by the admin. You now have a verified badge on your profile!';
} else {
    $notification_title = 'Certificate Rejected';
    $notification_message = 'Your certificate submission was rejected. Please review and resubmit if needed.';
}

sendNotification($farmer_id, 'farmer', $notification_title, $notification_message);

jsonSuccess(['cert_id' => $cert_id, 'is_verified' => (bool)$is_verified], 'Certificate ' . ($is_verified ? 'verified' : 'rejected'));
