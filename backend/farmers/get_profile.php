<?php
// GET /backend/farmers/get_profile.php?id=FARMER_ID
require_once __DIR__ . '/../config.php';

$farmer_id = $_GET['id'] ?? null;

if (!$farmer_id || !is_numeric($farmer_id)) {
    jsonError('Farmer ID is required and must be numeric.');
}

$farmer_id = (int)$farmer_id;
$db = getDB();

// Get farmer profile with stats
$stmt = $db->prepare('
    SELECT u.id, u.name, u.email, u.phone, u.created_at, u.approved,
           fp.bio, fp.location, fp.established_year, fp.profile_photo,
           fp.farm_size, fp.farm_size_unit, fp.farming_type, fp.experience_years,
           COUNT(DISTINCT fc.id) as certifications_count,
           COUNT(DISTINCT fr.id) as reviews_count,
           AVG(fr.rating) as avg_rating,
           COUNT(DISTINCT p.id) as products_count
    FROM users u
    LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
    LEFT JOIN farmer_certifications fc ON u.id = fc.farmer_id AND fc.is_verified = 1
    LEFT JOIN farmer_reviews fr ON u.id = fr.farmer_id
    LEFT JOIN products p ON u.id = p.farmer_id AND p.is_active = 1
    WHERE u.id = ? AND u.role = "farmer" AND u.is_active = 1
    GROUP BY u.id
');

if (!$stmt) {
    jsonError('Database error: ' . $db->error, 500);
}

$stmt->bind_param('i', $farmer_id);
$stmt->execute();
$profile_result = $stmt->get_result();

if ($profile_result->num_rows === 0) {
    jsonError('Farmer not found or is inactive.');
}

$profile = $profile_result->fetch_assoc();
$stmt->close();

// Get certifications
$cert_stmt = $db->prepare('SELECT id, cert_name, cert_type, cert_file, is_verified, verified_at FROM farmer_certifications WHERE farmer_id = ? ORDER BY uploaded_at DESC');
$cert_stmt->bind_param('i', $farmer_id);
$cert_stmt->execute();
$certifications = $cert_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cert_stmt->close();

// Get farming practices
$practice_stmt = $db->prepare('SELECT id, practice_name, description, practice_type, is_organic FROM farming_practices WHERE farmer_id = ? ORDER BY added_at DESC');
$practice_stmt->bind_param('i', $farmer_id);
$practice_stmt->execute();
$practices = $practice_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$practice_stmt->close();

// Get gallery
$gallery_stmt = $db->prepare('SELECT id, image_path, image_title FROM farmer_gallery WHERE farmer_id = ? ORDER BY uploaded_at DESC');
$gallery_stmt->bind_param('i', $farmer_id);
$gallery_stmt->execute();
$gallery = $gallery_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$gallery_stmt->close();

// Get farmer's products
$product_stmt = $db->prepare('SELECT id, name, category, price, unit, description, image, quantity FROM products WHERE farmer_id = ? AND is_active = 1 ORDER BY created_at DESC');
$product_stmt->bind_param('i', $farmer_id);
$product_stmt->execute();
$products = $product_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$product_stmt->close();

// Get reviews
$review_stmt = $db->prepare('
    SELECT fr.id, fr.rating, fr.review_text, fr.reviewed_at, u.name as reviewer_name
    FROM farmer_reviews fr
    JOIN users u ON fr.reviewer_id = u.id
    WHERE fr.farmer_id = ?
    ORDER BY fr.reviewed_at DESC
');
$review_stmt->bind_param('i', $farmer_id);
$review_stmt->execute();
$reviews = $review_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$review_stmt->close();

// Format response
$profile['certifications_count'] = (int)$profile['certifications_count'];
$profile['reviews_count'] = (int)$profile['reviews_count'];
$profile['avg_rating'] = $profile['avg_rating'] ? round((float)$profile['avg_rating'], 2) : 0;
$profile['products_count'] = (int)$profile['products_count'];
$profile['certifications'] = $certifications;
$profile['practices'] = $practices;
$profile['gallery'] = $gallery;
$profile['products'] = $products;
$profile['reviews'] = $reviews;

jsonSuccess($profile, 'Farmer profile fetched successfully');

