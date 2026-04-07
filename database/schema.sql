-- BrightStage Video — Full Database Schema
-- Run this in phpMyAdmin to set up all tables.
-- Database: convertpods_iu

-- ============================================
-- USERS
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    credits_balance INT NOT NULL DEFAULT 100,
    plan ENUM('free', 'starter', 'pro', 'business') NOT NULL DEFAULT 'free',
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    stripe_customer_id VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_plan (plan),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SUBSCRIPTIONS
-- ============================================
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stripe_subscription_id VARCHAR(255) NOT NULL,
    plan ENUM('starter', 'pro', 'business') NOT NULL,
    status ENUM('active', 'cancelled', 'past_due', 'paused') NOT NULL DEFAULT 'active',
    credits_per_month INT NOT NULL,
    current_period_start DATETIME NOT NULL,
    current_period_end DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    UNIQUE INDEX idx_stripe_sub (stripe_subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CREDIT PACKAGES
-- ============================================
CREATE TABLE IF NOT EXISTS credit_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    credits INT NOT NULL,
    price_cents INT NOT NULL,
    is_active TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default packages
INSERT INTO credit_packages (name, credits, price_cents) VALUES
    ('Small', 100, 500),
    ('Medium', 350, 1500),
    ('Large', 800, 3000);

-- ============================================
-- PRESENTATIONS
-- ============================================
CREATE TABLE IF NOT EXISTS presentations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    topic TEXT NOT NULL,
    audience VARCHAR(255) NOT NULL DEFAULT '',
    duration_minutes INT NOT NULL DEFAULT 10,
    tone VARCHAR(100) NOT NULL DEFAULT 'professional',
    template_id VARCHAR(100) NULL,
    status ENUM('draft', 'outline_ready', 'slides_ready', 'audio_ready', 'video_ready', 'exported') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SLIDES
-- ============================================
CREATE TABLE IF NOT EXISTS slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presentation_id INT NOT NULL,
    slide_order INT NOT NULL DEFAULT 1,
    title VARCHAR(255) NOT NULL DEFAULT '',
    content TEXT NOT NULL,
    speaker_notes TEXT NOT NULL,
    html_content MEDIUMTEXT NULL COMMENT 'AI-generated HTML/CSS for rendering',
    image_url VARCHAR(500) NULL COMMENT 'Rendered PNG path',
    audio_url VARCHAR(500) NULL COMMENT 'TTS audio file path',
    layout_type VARCHAR(50) NOT NULL DEFAULT 'bullets',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (presentation_id) REFERENCES presentations(id) ON DELETE CASCADE,
    INDEX idx_presentation (presentation_id),
    INDEX idx_order (presentation_id, slide_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIDEOS
-- ============================================
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presentation_id INT NOT NULL,
    file_url VARCHAR(500) NULL,
    file_size_bytes BIGINT NULL,
    duration_seconds INT NULL,
    resolution VARCHAR(20) NOT NULL DEFAULT '1920x1080',
    status ENUM('queued', 'processing', 'complete', 'failed') NOT NULL DEFAULT 'queued',
    progress_message VARCHAR(255) NULL,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (presentation_id) REFERENCES presentations(id) ON DELETE CASCADE,
    INDEX idx_presentation (presentation_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MEDIA KITS
-- ============================================
CREATE TABLE IF NOT EXISTS media_kits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presentation_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('queued', 'processing', 'complete', 'failed') NOT NULL DEFAULT 'queued',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (presentation_id) REFERENCES presentations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_presentation (presentation_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MEDIA KIT ASSETS
-- ============================================
CREATE TABLE IF NOT EXISTS media_kit_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    media_kit_id INT NOT NULL,
    asset_type ENUM('social_post', 'email', 'article', 'press_release', 'landing_page', 'image') NOT NULL,
    platform VARCHAR(50) NOT NULL DEFAULT 'general',
    variant INT NOT NULL DEFAULT 1,
    content_text MEDIUMTEXT NULL,
    file_url VARCHAR(500) NULL,
    dimensions VARCHAR(20) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (media_kit_id) REFERENCES media_kits(id) ON DELETE CASCADE,
    INDEX idx_kit (media_kit_id),
    INDEX idx_type (asset_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CREDIT TRANSACTIONS
-- ============================================
CREATE TABLE IF NOT EXISTS credit_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL COMMENT 'Negative=spent, Positive=purchased/bonus',
    action VARCHAR(100) NOT NULL,
    presentation_id INT NULL,
    stripe_payment_id VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (presentation_id) REFERENCES presentations(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TEMPLATES
-- ============================================
CREATE TABLE IF NOT EXISTS templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(500) NULL,
    config_json JSON NOT NULL,
    is_active TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default templates
INSERT INTO templates (name, config_json, sort_order) VALUES
    ('Corporate', '{"primary":"#1e3a5f","secondary":"#ffffff","accent":"#3498db","font_heading":"Inter","font_body":"Inter","style":"clean"}', 1),
    ('Creative', '{"primary":"#ff6b6b","secondary":"#ffeaa7","accent":"#6c5ce7","font_heading":"Poppins","font_body":"Open Sans","style":"bold"}', 2),
    ('Minimal', '{"primary":"#2d3436","secondary":"#ffffff","accent":"#00b894","font_heading":"Playfair Display","font_body":"Source Sans Pro","style":"minimal"}', 3),
    ('Dark', '{"primary":"#0a0a0a","secondary":"#e0e0e0","accent":"#e94560","font_heading":"Raleway","font_body":"Roboto","style":"dark"}', 4),
    ('Vibrant', '{"primary":"#667eea","secondary":"#ffffff","accent":"#f093fb","font_heading":"Montserrat","font_body":"Nunito","style":"gradient"}', 5);
