<?php
// POST /backend/farmers/update_profile.php
require_once __DIR__ . '/../config.php';

$user = requireRole('farmer', 'admin');
$farmer_id = $user['id'];

// Allow admin to edit other farmers
if ($user['role'] === 'admin') {
    $farmer_id = $_POST['farmer_id'] ?? $user['id'];
}

$db = getDB();

// Check if farmer exists
$check_stmt = $db->prepare('SELECT id FROM users WHERE id = ? AND role = "farmer"');
$check_stmt->bind_param('i', $farmer_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows === 0) {
    jsonError('Farmer not found.');
}
$check_stmt->close();

// Get posted data
$bio = $_POST['bio'] ?? '';
$location = $_POST['location'] ?? '';
$established_year = $_POST['established_year'] ?? null;
$farm_size = $_POST['farm_size'] ?? null;
$farm_size_unit = $_POST['farm_size_unit'] ?? 'acres';
$farming_type = $_POST['farming_type'] ?? 'mixed';
$experience_years = $_POST['experience_years'] ?? null;

// Convert to proper types
if (!empty($established_year)) {
    $established_year = (int)$established_year;
} else {
    $established_year = null;
}
if (!empty($farm_size)) {
    $farm_size = (float)$farm_size;
} else {
    $farm_size = null;
}
if (!empty($experience_years)) {
    $experience_years = (int)$experience_years;
} else {
    $experience_years = null;
}

// Validate farming type
if (!in_array($farming_type, ['organic', 'inorganic', 'mixed'], true)) {
    $farming_type = 'mixed';
}

// Handle profile photo upload
$profile_photo = null;
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($_FILES['profile_photo']['type'], $allowed_types, true)) {
        jsonError('Invalid image format. Allowed: JPEG, PNG, GIF, WebP');
    }

    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['profile_photo']['size'] > $max_size) {
        jsonError('Image too large. Maximum 5MB allowed.');
    }

    $upload_dir = __DIR__ . '/../uploads/farmer_profiles/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = 'farmer_' . $farmer_id . '_' . time() . '.' . pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $filepath)) {
        jsonError('Failed to upload image.');
    }

    $profile_photo = 'farmer_profiles/' . $filename;
}

// Check if profile exists
$check_profile = $db->prepare('SELECT id FROM farmer_profiles WHERE user_id = ?');
$check_profile->bind_param('i', $farmer_id);
$check_profile->execute();
$profile_exists = $check_profile->get_result()->num_rows > 0;
$check_profile->close();

if ($profile_exists) {
    // Update existing profile
    if ($profile_photo) {
        $update_stmt = $db->prepare('
            UPDATE farmer_profiles 
            SET bio = ?, location = ?, established_year = ?, farm_size = ?, 
                farm_size_unit = ?, farming_type = ?, experience_years = ?, profile_photo = ?
            WHERE user_id = ?
        ');
        $update_stmt->bind_param(
            'ssiisssi',
            $bio, $location, $established_year, $farm_size,
            $farm_size_unit, $farming_type, $experience_years, $profile_photo, $farmer_id
        );
    } else {
        $update_stmt = $db->prepare('
            UPDATE farmer_profiles 
            SET bio = ?, location = ?, established_year = ?, farm_size = ?, 
                farm_size_unit = ?, farming_type = ?, experience_years = ?
            WHERE user_id = ?
        ');
        $update_stmt->bind_param(
            'siiissi',
            $bio, $location, $established_year, $farm_size,
            $farm_size_unit, $farming_type, $experience_years, $farmer_id
        );
    }

    if (!$update_stmt->execute()) {
        jsonError('Failed to update profile: ' . $update_stmt->error);
    }
    $update_stmt->close();

    jsonSuccess(['id' => $farmer_id], 'Profile updated successfully');
} else {
    // Insert new profile
    $insert_stmt = $db->prepare('
        INSERT INTO farmer_profiles 
        (user_id, bio, location, established_year, farm_size, farm_size_unit, farming_type, experience_years, profile_photo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $insert_stmt->bind_param(
        'isiiisssi',
        $farmer_id, $bio, $location, $established_year, $farm_size,
        $farm_size_unit, $farming_type, $experience_years, $profile_photo
    );

    if (!$insert_stmt->execute()) {
        jsonError('Failed to create profile: ' . $insert_stmt->error);
    }
    $insert_stmt->close();

    jsonSuccess(['id' => $farmer_id], 'Profile created successfully', 201);
}

