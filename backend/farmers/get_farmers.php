<?php
// GET /backend/farmers/get_farmers.php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed.', 405);
}

$db = getDB();

// Get all approved farmers with their profiles
$stmt = $db->prepare('
    SELECT u.id, u.name, u.email, u.phone, u.created_at,
           fp.bio, fp.location, fp.established_year, fp.profile_photo,
           fp.experience_years, fp.farming_type,
           COUNT(DISTINCT fc.id) as certifications_count,
           COUNT(DISTINCT fr.id) as reviews_count,
           AVG(fr.rating) as avg_rating,
           COUNT(DISTINCT p.id) as products_count
    FROM users u
    LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
    LEFT JOIN farmer_certifications fc ON u.id = fc.farmer_id AND fc.is_verified = 1
    LEFT JOIN farmer_reviews fr ON u.id = fr.farmer_id
    LEFT JOIN products p ON u.id = p.farmer_id AND p.is_active = 1
    WHERE u.role = "farmer" AND u.is_active = 1 AND u.approved = 1
    GROUP BY u.id
    ORDER BY COALESCE(AVG(fr.rating), 0) DESC, u.name ASC
');

if (!$stmt->execute()) {
    jsonError('Failed to fetch farmers.', 500);
}

$result = $stmt->get_result();
$farmers = [];

while ($row = $result->fetch_assoc()) {
    $row['certifications_count'] = (int)$row['certifications_count'];
    $row['reviews_count'] = (int)$row['reviews_count'];
    $row['avg_rating'] = $row['avg_rating'] ? round((float)$row['avg_rating'], 2) : 0;
    $row['products_count'] = (int)$row['products_count'];
    $farmers[] = $row;
}

$stmt->close();

jsonSuccess($farmers, 'Farmers fetched successfully');

