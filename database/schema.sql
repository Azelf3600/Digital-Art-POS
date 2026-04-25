/*-- ============================================
-- schema.sql
-- Starflow — Digital Art Shop
-- Database: MySQL 5.7+
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================
-- Drop tables if rebuilding from scratch
-- ============================================
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS commission_references;
DROP TABLE IF EXISTS commissions;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS artwork_images;
DROP TABLE IF EXISTS artworks;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 1. USERS
-- Stores both customers and admins via role column
-- ============================================
CREATE TABLE users (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    username        VARCHAR(50)     NOT NULL UNIQUE,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    password        VARCHAR(255)    NOT NULL,           -- bcrypt hashed
    role            ENUM('customer','admin')
                                    NOT NULL DEFAULT 'customer',
    full_name       VARCHAR(100)    DEFAULT NULL,
    profile_picture VARCHAR(255)    DEFAULT NULL,       -- path to file
    is_active       TINYINT(1)      NOT NULL DEFAULT 1, -- 0 = banned
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. PASSWORD RESETS
-- Stores tokens for forgot-password flow
-- ============================================
CREATE TABLE password_resets (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    token       VARCHAR(100)    NOT NULL UNIQUE,
    expires_at  DATETIME        NOT NULL,
    used        TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. CATEGORIES
-- Artwork categories (Character Art, Portrait, etc.)
-- ============================================
CREATE TABLE categories (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100)    NOT NULL UNIQUE,
    slug        VARCHAR(100)    NOT NULL UNIQUE,        -- e.g. "character-art"
    description TEXT            DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. ARTWORKS
-- Shop items — pre-made artworks for sale
-- ============================================
CREATE TABLE artworks (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    category_id     INT UNSIGNED    DEFAULT NULL,
    title           VARCHAR(150)    NOT NULL,
    description     TEXT            DEFAULT NULL,
    price           DECIMAL(10,2)   NOT NULL,
    thumbnail       VARCHAR(255)    DEFAULT NULL,       -- assets/artworks/thumbnails/
    full_image      VARCHAR(255)    DEFAULT NULL,       -- assets/artworks/full/
    watermarked     VARCHAR(255)    DEFAULT NULL,       -- assets/artworks/watermarked/
    is_available    TINYINT(1)      NOT NULL DEFAULT 1, -- 0 = sold/hidden
    is_featured     TINYINT(1)      NOT NULL DEFAULT 0, -- shows on landing page
    views           INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category  (category_id),
    INDEX idx_featured  (is_featured),
    INDEX idx_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. CART
-- Temporary cart per user
-- ============================================
CREATE TABLE cart (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    artwork_id  INT UNSIGNED    NOT NULL,
    quantity    INT UNSIGNED    NOT NULL DEFAULT 1,
    added_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_cart_item (user_id, artwork_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. ORDERS
-- Created when customer checks out
-- ============================================
CREATE TABLE orders (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    order_number    VARCHAR(20)     NOT NULL UNIQUE,    -- e.g. SF-20240001
    status          ENUM(
                        'pending',
                        'paid',
                        'processing',
                        'completed',
                        'cancelled',
                        'refunded'
                    )               NOT NULL DEFAULT 'pending',
    total_amount    DECIMAL(10,2)   NOT NULL,
    notes           TEXT            DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id      (user_id),
    INDEX idx_status       (status),
    INDEX idx_order_number (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ORDER ITEMS
-- Line items inside each order
-- ============================================
CREATE TABLE order_items (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    order_id    INT UNSIGNED    NOT NULL,
    artwork_id  INT UNSIGNED    DEFAULT NULL,           -- NULL if artwork deleted
    title       VARCHAR(150)    NOT NULL,               -- snapshot at purchase time
    price       DECIMAL(10,2)   NOT NULL,               -- snapshot at purchase time
    quantity    INT UNSIGNED    NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. PAYMENTS
-- PayPal payment records per order
-- ============================================
CREATE TABLE payments (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    order_id            INT UNSIGNED    NOT NULL,
    user_id             INT UNSIGNED    NOT NULL,
    paypal_order_id     VARCHAR(100)    DEFAULT NULL,   -- PayPal order ID
    paypal_payer_id     VARCHAR(100)    DEFAULT NULL,   -- PayPal payer ID
    paypal_payer_email  VARCHAR(150)    DEFAULT NULL,
    amount              DECIMAL(10,2)   NOT NULL,
    currency            VARCHAR(10)     NOT NULL DEFAULT 'PHP',
    status              ENUM(
                            'pending',
                            'completed',
                            'failed',
                            'refunded'
                        )               NOT NULL DEFAULT 'pending',
    receipt_path        VARCHAR(255)    DEFAULT NULL,   -- private_uploads/receipts/
    paid_at             DATETIME        DEFAULT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    INDEX idx_paypal_order (paypal_order_id),
    INDEX idx_status       (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. COMMISSIONS
-- Custom artwork requests from customers
-- ============================================
CREATE TABLE commissions (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    title           VARCHAR(150)    NOT NULL,
    description     TEXT            NOT NULL,           -- customer brief
    style           VARCHAR(100)    DEFAULT NULL,
    tier            ENUM(
                        'sketch',
                        'full_color',
                        'illustrated'
                    )               NOT NULL DEFAULT 'full_color',
    status          ENUM(
                        'pending',
                        'in_progress',
                        'done',
                        'cancelled'
                    )               NOT NULL DEFAULT 'pending',
    quoted_price    DECIMAL(10,2)   DEFAULT NULL,       -- admin sets this
    final_file      VARCHAR(255)    DEFAULT NULL,       -- private_uploads/finished/
    admin_notes     TEXT            DEFAULT NULL,
    customer_notes  TEXT            DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. COMMISSION REFERENCES
-- Reference images uploaded with a commission
-- ============================================
CREATE TABLE commission_references (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    commission_id   INT UNSIGNED    NOT NULL,
    file_path       VARCHAR(255)    NOT NULL,           -- private_uploads/references/
    original_name   VARCHAR(255)    DEFAULT NULL,
    uploaded_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (commission_id) REFERENCES commissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. FAVORITES
-- Customer saved artworks
-- ============================================
CREATE TABLE favorites (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    artwork_id  INT UNSIGNED    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_favorite (user_id, artwork_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. REVIEWS
-- Customer reviews on purchased artworks
-- ============================================
CREATE TABLE reviews (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    artwork_id  INT UNSIGNED    NOT NULL,
    order_id    INT UNSIGNED    NOT NULL,               -- must have bought it
    rating      TINYINT         NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT            DEFAULT NULL,
    is_approved TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY one_review_per_order (user_id, artwork_id, order_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. MESSAGES
-- Contact form submissions
-- ============================================
CREATE TABLE messages (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    DEFAULT NULL,           -- NULL if not logged in
    name        VARCHAR(100)    NOT NULL,
    email       VARCHAR(150)    NOT NULL,
    subject     VARCHAR(200)    DEFAULT NULL,
    body        TEXT            NOT NULL,
    is_read     TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. NOTIFICATIONS
-- In-app notifications for users
-- ============================================
CREATE TABLE notifications (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    type        VARCHAR(50)     NOT NULL,               -- e.g. 'order_update'
    title       VARCHAR(200)    NOT NULL,
    message     TEXT            NOT NULL,
    link        VARCHAR(255)    DEFAULT NULL,
    is_read     TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA — Categories
-- ============================================
INSERT INTO categories (name, slug, description) VALUES
('Character Art',  'character-art',  'Original character illustrations and designs'),
('Portrait',       'portrait',       'Character headshots and bust portraits'),
('Fan Art',        'fan-art',        'Fan-made artwork of existing characters'),
('Concept Art',    'concept-art',    'Original concept and world-building art'),
('Illustrations',  'illustrations',  'Full scene and story illustrations'),
('Book Covers',    'book-covers',    'Cover art for books and stories');

-- ============================================
-- SEED DATA — Default Admin User
-- Password: admin123 (change after first login!)
-- ============================================
INSERT INTO users (username, email, password, role, full_name) VALUES
(
    'admin',
    'admin@starflow.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'Starflow Admin'
);

-- ============================================
-- SEED DATA — Sample Artworks
-- Matches your actual image filenames
-- ============================================
INSERT INTO artworks (category_id, title, price, thumbnail, full_image, watermarked, is_featured) VALUES
(1, 'CrrptManip Vs Dax',  2000.00, 'CrrptManipVsDax.png',     'CrrptManipVsDax.png',     'CrrptManipVsDax.png',     1),
(2, 'Dino Headshot',       1500.00, 'DinoHeadShot.png',        'DinoHeadShot.png',        'DinoHeadShot.png',        1),
(1, 'Fusion vs Tinian',    2200.00, 'Fusion vs Tinian.png',    'Fusion vs Tinian.png',    'Fusion vs Tinian.png',    1),
(2, 'Jouse Haavok',        1800.00, 'JouseHaavok.png',         'JouseHaavok.png',         'JouseHaavok.png',         1),
(3, 'Shadow Cat',          2500.00, 'Shadow cat enhanced.png', 'Shadow cat enhanced.png', 'Shadow cat enhanced.png', 1),
(2, 'Yzal Headshot',       1500.00, 'YzalHeadShot.png',        'YzalHeadShot.png',        'YzalHeadShot.png',        1);
*/