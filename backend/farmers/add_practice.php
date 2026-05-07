<?php
// POST /backend/farmers/add_practice.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$user = requireRole('farmer');
$farmer_id = $user['id'];

$body = getBody();
$practice_name = clean($body['practice_name'] ?? '');
$description = clean($body['description'] ?? '');
$practice_type = $body['practice_type'] ?? 'other';
$is_organic = (int)($body['is_organic'] ?? 0);

if (!$practice_name) {
    jsonError('Practice name is required.');
}

// Validate practice type
if (!in_array($practice_type, ['fertilizer', 'pesticide', 'irrigation', 'composting', 'other'], true)) {
    $practice_type = 'other';
}

$db = getDB();

// Insert farming practice
$stmt = $db->prepare('
    INSERT INTO farming_practices (farmer_id, user_id, practice_name, description, practice_type, is_organic)
    VALUES (?, ?, ?, ?, ?, ?)
');

if (!$stmt) {
    jsonError('Database error: ' . $db->error, 500);
}

$stmt->bind_param('iissi', $farmer_id, $farmer_id, $practice_name, $description, $is_organic, $practice_type);

// Note: I have 6 bind parameters but only 5 columns, let me fix that
$stmt->close();

// Correct version
$stmt = $db->prepare('
    INSERT INTO farming_practices (farmer_id, user_id, practice_name, description, practice_type, is_organic)
    VALUES (?, ?, ?, ?, ?, ?)
');

$stmt->bind_param('iisssi', $farmer_id, $farmer_id, $practice_name, $description, $practice_type, $is_organic);

if (!$stmt->execute()) {
    jsonError('Failed to add farming practice: ' . $stmt->error);
}

$practice_id = $db->insert_id;
$stmt->close();

jsonSuccess([
    'id' => $practice_id,
    'practice_name' => $practice_name,
    'practice_type' => $practice_type,
    'is_organic' => (bool)$is_organic
], 'Farming practice added successfully', 201);

