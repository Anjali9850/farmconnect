-- ============================================================
--  FarmConnect – Database Migration v2
--  Adds missing tables for complete functionality
--  HOW TO IMPORT:
--    Option A: phpMyAdmin → Import tab → select this file → Go
--    Option B: mysql -u root -p farmconnect < migration_v2.sql
-- ============================================================

USE farmconnect;

SET FOREIGN_KEY_CHECKS = 0;

-- ── Farmer Profiles ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS farmer_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    bio TEXT DEFAULT NULL,
    location VARCHAR(150) DEFAULT NULL,
    established_year INT DEFAULT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    farm_size DECIMAL(8,2) DEFAULT NULL,
    farm_size_unit ENUM('acres','hectares') DEFAULT 'acres',
    farming_type ENUM('organic','inorganic','mixed') DEFAULT 'mixed',
    experience_years INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Farmer Certifications ────────────────────────────────────
CREATE TABLE IF NOT EXISTS farmer_certifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    cert_name VARCHAR(150) NOT NULL,
    cert_file VARCHAR(255) NOT NULL,
    cert_type ENUM('organic','quality','award','other') DEFAULT 'other',
    is_verified TINYINT(1) DEFAULT 0,
    verified_by INT UNSIGNED DEFAULT NULL,
    verified_at DATETIME DEFAULT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_farmer (farmer_id),
    INDEX idx_verified (is_verified),
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Farming Practices ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS farming_practices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    practice_name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    practice_type ENUM('fertilizer','pesticide','irrigation','composting','other') DEFAULT 'other',
    is_organic TINYINT(1) DEFAULT 0,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_farmer (farmer_id),
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Gallery Images ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS farmer_gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    image_title VARCHAR(150) DEFAULT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_farmer (farmer_id),
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Ratings & Reviews ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS farmer_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    rating INT NOT NULL CHECK(rating >= 1 AND rating <= 5),
    review_text TEXT DEFAULT NULL,
    reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_farmer (farmer_id),
    INDEX idx_reviewer (reviewer_id),
    UNIQUE KEY uq_farmer_reviewer (farmer_id, reviewer_id),
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Subscriptions ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_type ENUM('basic','premium','farmer_support') DEFAULT 'basic',
    price DECIMAL(10,2) NOT NULL,
    discount_percent INT DEFAULT 0,
    free_delivery TINYINT(1) DEFAULT 0,
    priority_support TINYINT(1) DEFAULT 0,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    payment_status ENUM('pending','completed','failed','cancelled') DEFAULT 'pending',
    INDEX idx_user (user_id),
    INDEX idx_active (is_active),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Notifications ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('order','product','farmer','system','promotion') DEFAULT 'system',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    related_user_id INT UNSIGNED DEFAULT NULL,
    related_order_id INT UNSIGNED DEFAULT NULL,
    related_product_id INT UNSIGNED DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_type (type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (related_order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Update orders table with delivery tracking ────────────────
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_status 
    ENUM('pending','accepted','out_for_delivery','delivered','cancelled') DEFAULT 'pending' 
    AFTER status;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_address TEXT DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS farmer_notes TEXT DEFAULT NULL;
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_delivery_status (delivery_status);

-- ── Wishlist ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wishlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_product (user_id, product_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Update users table with additional fields ─────────────────
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER approved;
ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS state VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS pincode VARCHAR(10) DEFAULT NULL;
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_active (is_active);

-- ── Update products table ───────────────────────────────────
ALTER TABLE products ADD COLUMN IF NOT EXISTS quantity INT DEFAULT 0 AFTER description;
ALTER TABLE products ADD COLUMN IF NOT EXISTS stock_unit VARCHAR(50) DEFAULT NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER image;
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_active (is_active);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  DATA UPDATES - Add seed data to new tables
-- ============================================================

-- Farmer Profiles for approved farmers
INSERT INTO farmer_profiles (user_id, bio, location, established_year, farm_size, farm_size_unit, farming_type, experience_years)
VALUES
(2, 'Organic vegetable farmer with 15 years of experience. Specializing in leafy greens and cruciferous vegetables.', 'Karnataka', 2008, 5.5, 'acres', 'organic', 15),
(3, 'Fruit farmer from Kerala, growing tropical varieties. Focus on sustainable and pesticide-free farming.', 'Kerala', 2010, 8.0, 'acres', 'organic', 13),
(4, 'Mixed farming with grains and dairy. Currently pending approval for product listing.', 'Punjab', 2012, 12.0, 'acres', 'mixed', 11);

-- Farming Practices
INSERT INTO farming_practices (farmer_id, user_id, practice_name, description, practice_type, is_organic)
VALUES
(2, 2, 'Composting', 'Using kitchen waste and farm residue for organic compost', 'composting', 1),
(2, 2, 'Drip Irrigation', 'Water-efficient drip system reduces water usage by 60%', 'irrigation', 1),
(2, 2, 'Natural Pest Control', 'Using neem oil and organic pesticides only', 'pesticide', 1),
(3, 3, 'Organic Certification', 'NPOP certified organic farming since 2015', 'other', 1),
(3, 3, 'Mulching', 'Using organic mulch to retain soil moisture and nutrients', 'fertilizer', 1),
(4, 4, 'Mixed Farming', 'Crop rotation between grains and dairy farming', 'other', 0),
(4, 4, 'Biogas Generation', 'Using cow dung for organic energy and fertilizer', 'composting', 1);

-- Ratings (sample reviews)
INSERT INTO farmer_reviews (farmer_id, reviewer_id, rating, review_text)
VALUES
(2, 6, 5, 'Excellent quality vegetables! Fresh and organic. Highly recommend.'),
(2, 7, 5, 'Great farmer, always on time with deliveries. Best local source.'),
(3, 6, 5, 'Amazing mangoes this season. Best taste ever. Will order again.'),
(3, 7, 4, 'Good quality fruits. Packaging was excellent.');

-- Subscriptions (sample active subscriptions)
INSERT INTO subscriptions (user_id, plan_type, price, discount_percent, free_delivery, priority_support, expires_at, payment_status)
VALUES
(6, 'premium', 299.00, 15, 1, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), 'completed'),
(7, 'basic', 99.00, 5, 0, 0, DATE_ADD(NOW(), INTERVAL 30 DAY), 'completed');

-- Sample Notifications
INSERT INTO notifications (user_id, type, title, message, is_read)
VALUES
(2, 'order', 'New Order', 'New order received! 2x Organic Spinach ordered by Anjali Mehra', 0),
(2, 'product', 'Product Added', 'Your product "Organic Spinach" is now visible to customers', 1),
(6, 'system', 'Welcome Premium', 'You are now a Premium subscriber with 15% discount on all products!', 1),
(7, 'promotion', 'Special Offer', 'Get premium subscription and enjoy free delivery for 30 days!', 0);

COMMIT;
