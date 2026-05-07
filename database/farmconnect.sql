-- ============================================================
--  FarmConnect – Database Schema & Seed Data
--  HOW TO IMPORT:
--    Option A: phpMyAdmin → Import tab → select this file → Go
--    Option B: mysql -u root -p < farmconnect.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS farmconnect
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE farmconnect;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ── users ─────────────────────────────────────────────────────
CREATE TABLE users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    phone      VARCHAR(20)   DEFAULT NULL,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('customer','farmer','admin') NOT NULL DEFAULT 'customer',
    approved   TINYINT(1)    NOT NULL DEFAULT 0,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── products ──────────────────────────────────────────────────
CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farmer_id   INT UNSIGNED NOT NULL,
    name        VARCHAR(150) NOT NULL,
    category    ENUM('vegetable','fruit','grain','dairy','other') NOT NULL DEFAULT 'other',
    price       DECIMAL(10,2) NOT NULL,
    unit        VARCHAR(50)   NOT NULL DEFAULT 'kg',
    description TEXT          DEFAULT NULL,
    image       VARCHAR(255)  DEFAULT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_farmer   (farmer_id),
    INDEX idx_category (category),
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── orders ────────────────────────────────────────────────────
CREATE TABLE orders (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status       ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user   (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── order_items ───────────────────────────────────────────────
CREATE TABLE order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT          NOT NULL DEFAULT 1,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── cart ──────────────────────────────────────────────────────
CREATE TABLE cart (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT          NOT NULL DEFAULT 1,
    added_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_product (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  SEED DATA
--  Login credentials:
--    admin@farmconnect.com  /  admin123
--    ravi@farmconnect.com   /  farmer123   (approved)
--    priya@farmconnect.com  /  farmer123   (approved)
--    arjun@farmconnect.com  /  farmer123   (PENDING - demo approval flow)
--    meena@farmconnect.com  /  farmer123   (PENDING)
--    anjali@example.com     /  customer123
--    rohit@example.com      /  customer123
-- ============================================================

INSERT INTO users (name, email, phone, password, role, approved) VALUES
('Admin User',     'admin@farmconnect.com',  '9000000000',
 '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5Z6IzKxpNgMgO', 'admin',    1),
('Ravi Patel',     'ravi@farmconnect.com',   '9111111111',
 '$2y$12$QOs1ik6S1ItlWW1IZiPUxeVW.GH.DI8x1yH6X0QqUZaFBwLxWb2q6', 'farmer',   1),
('Priya Devi',     'priya@farmconnect.com',  '9222222222',
 '$2y$12$QOs1ik6S1ItlWW1IZiPUxeVW.GH.DI8x1yH6X0QqUZaFBwLxWb2q6', 'farmer',   1),
('Arjun Singh',    'arjun@farmconnect.com',  '9333333333',
 '$2y$12$QOs1ik6S1ItlWW1IZiPUxeVW.GH.DI8x1yH6X0QqUZaFBwLxWb2q6', 'farmer',   0),
('Meena Krishnan', 'meena@farmconnect.com',  '9444444444',
 '$2y$12$QOs1ik6S1ItlWW1IZiPUxeVW.GH.DI8x1yH6X0QqUZaFBwLxWb2q6', 'farmer',   0),
('Anjali Mehra',   'anjali@example.com',     '9555555555',
 '$2y$12$eiCGhC1N9UcxVWwVXw3t3e5YqMOIyM0YC3w7Bn4Nq8vDlKp2Ksx0a', 'customer', 1),
('Rohit Sharma',   'rohit@example.com',      '9666666666',
 '$2y$12$eiCGhC1N9UcxVWwVXw3t3e5YqMOIyM0YC3w7Bn4Nq8vDlKp2Ksx0a', 'customer', 1);

-- Products  (Ravi=2, Priya=3, Arjun=4)
INSERT INTO products (farmer_id, name, category, price, unit, description) VALUES
(2, 'Organic Spinach',   'vegetable',  45.00, '250g',    'Fresh organic spinach, hand-picked daily. Rich in iron and vitamins A & C.'),
(2, 'Red Tomatoes',      'vegetable',  38.00, '1 kg',    'Juicy vine-ripened tomatoes grown without pesticides.'),
(2, 'Purple Brinjal',    'vegetable',  32.00, '500g',    'Tender, glossy brinjal. Great for bhartha and curries.'),
(2, 'Green Capsicum',    'vegetable',  55.00, '500g',    'Crisp and sweet capsicum. Excellent for stir-fries and salads.'),
(3, 'Alphonso Mango',    'fruit',     380.00, '1 kg',    'The king of mangoes from Devgad, Ratnagiri. Naturally ripened.'),
(3, 'Baby Carrots',      'vegetable',  55.00, '500g',    'Sweet, crunchy baby carrots from the Ooty hills.'),
(3, 'Fresh Cucumber',    'vegetable',  28.00, '500g',    'Cool, crisp cucumbers freshly harvested every morning.'),
(3, 'Banana',            'fruit',      60.00, '1 dozen', 'Robusta bananas — sweet, energy-rich, from our Kerala farm.'),
(4, 'Whole Wheat Flour', 'grain',     120.00, '5 kg',    'Stone-ground from heritage wheat. High fibre, nutty flavour.'),
(4, 'Organic Honey',     'dairy',     340.00, '500g',    'Raw, unprocessed Himalayan honey. No additives.'),
(4, 'Fresh A2 Milk',     'dairy',      85.00, '1 L',     'Pure A2 milk from Gir cows. No hormones, straight from the farm.'),
(4, 'Brown Rice',        'grain',      95.00, '1 kg',    'Unpolished brown rice full of fibre and natural nutrients.');

-- Sample orders  (anjali=6, rohit=7)
INSERT INTO orders (user_id, total_amount, status) VALUES
(6,  463.00, 'completed'),
(6,  340.00, 'pending'),
(7,  545.00, 'processing');

INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 2,  45.00),
(1, 5, 1, 380.00),
(2, 10, 1, 340.00),
(3, 5,  1, 380.00),
(3, 11, 1,  85.00),
(3, 9,  1, 120.00);
