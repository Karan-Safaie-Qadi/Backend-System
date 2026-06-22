-- ==========================================
-- Backend System Database Schema
-- ==========================================

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `email` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NULL,
    `password` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(100) NULL,
    `avatar` VARCHAR(255) NULL,
    `access_level` INT NOT NULL DEFAULT 1 COMMENT '1=user, 2=admin, 3=owner',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `remember_token` VARCHAR(255) NULL,
    `password_reset_token` VARCHAR(255) NULL,
    `password_reset_expires` DATETIME NULL,
    `email_verified_at` DATETIME NULL,
    `phone_verified_at` DATETIME NULL,
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `description` TEXT NULL,
    `parent_id` INT NULL,
    `type` ENUM('product', 'article') NOT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) UNIQUE NOT NULL,
    `description` TEXT NULL,
    `short_description` VARCHAR(500) NULL,
    `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `sale_price` DECIMAL(10, 2) NULL,
    `sku` VARCHAR(50) UNIQUE NULL,
    `stock_quantity` INT NOT NULL DEFAULT 0,
    `main_image` VARCHAR(255) NULL,
    `gallery` JSON NULL,
    `specifications` JSON NULL,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `category_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) UNIQUE NOT NULL,
    `summary` TEXT NULL,
    `main_image` VARCHAR(255) NULL,
    `author_id` INT NULL,
    `category_id` INT NULL,
    `is_published` TINYINT(1) DEFAULT 0,
    `published_at` DATETIME NULL,
    `meta_keywords` VARCHAR(255) NULL,
    `meta_description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `article_sections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT NOT NULL,
    `title` VARCHAR(200) NULL,
    `section_type` ENUM('text', 'list', 'table', 'image', 'mixed') NOT NULL DEFAULT 'text',
    `content` TEXT NULL,
    `list_items` JSON NULL,
    `table_data` JSON NULL,
    `image` VARCHAR(255) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NULL,
    `entity_id` INT NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    `value` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for performance
CREATE INDEX `idx_users_email` ON `users`(`email`);
CREATE INDEX `idx_users_phone` ON `users`(`phone`);
CREATE INDEX `idx_users_access_level` ON `users`(`access_level`);
CREATE INDEX `idx_products_category` ON `products`(`category_id`);
CREATE INDEX `idx_products_active` ON `products`(`is_active`);
CREATE INDEX `idx_articles_category` ON `articles`(`category_id`);
CREATE INDEX `idx_articles_author` ON `articles`(`author_id`);
CREATE INDEX `idx_articles_published` ON `articles`(`is_published`);
CREATE INDEX `idx_article_sections_article` ON `article_sections`(`article_id`);
CREATE INDEX `idx_activity_logs_user` ON `activity_logs`(`user_id`);
CREATE INDEX `idx_activity_logs_entity` ON `activity_logs`(`entity_type`, `entity_id`);
CREATE INDEX `idx_categories_parent` ON `categories`(`parent_id`);
CREATE INDEX `idx_categories_type` ON `categories`(`type`);
