-- FORMA AR Furniture Catalog
-- Import this file in phpMyAdmin or: mysql -u root < sql/database.sql

CREATE DATABASE IF NOT EXISTS `ar_furniture`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ar_furniture`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(100) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `slug` VARCHAR(150) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(8) NOT NULL DEFAULT 'RM',
  `glb_path` VARCHAR(255) NULL,
  `usdz_path` VARCHAR(255) NULL,
  `thumb_path` VARCHAR(255) NULL,
  `width_cm` DECIMAL(10,2) NULL,
  `height_cm` DECIMAL(10,2) NULL,
  `depth_cm` DECIMAL(10,2) NULL,
  `glb_x_m` DECIMAL(10,4) NULL,
  `glb_y_m` DECIMAL(10,4) NULL,
  `glb_z_m` DECIMAL(10,4) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_active_sort` (`is_active`, `sort_order`),
  KEY `idx_products_category` (`category_id`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin: username `admin` / password `admin1234`
INSERT INTO `admins` (`username`, `password_hash`, `created_at`) VALUES
('admin', '$2y$10$hxIHkjpZTQ0TPchCYCdMn.iLkjSTDA01bQEF2DzH4EBpY5L9ZnypS', NOW());

INSERT INTO `categories` (`slug`, `name`, `sort_order`, `is_active`, `created_at`) VALUES
('chairs', 'Chairs', 10, 1, NOW()),
('sofas', 'Sofas', 20, 1, NOW()),
('tables', 'Tables', 30, 1, NOW()),
('beds', 'Beds', 40, 1, NOW()),
('storage', 'Storage', 50, 1, NOW()),
('lighting', 'Lighting', 60, 1, NOW());

INSERT INTO `products` (
  `category_id`, `slug`, `name`, `description`, `price`, `currency`,
  `glb_path`, `usdz_path`, `thumb_path`,
  `width_cm`, `height_cm`, `depth_cm`,
  `glb_x_m`, `glb_y_m`, `glb_z_m`,
  `is_active`, `sort_order`, `created_at`, `updated_at`
) VALUES
(1, 'modern-armchair', 'Modern Armchair',
 'A compact lounge chair with gently curved arms and a supportive seat. Ideal for reading corners and living rooms. Preview the silhouette in AR before you buy.',
 499.00, 'RM', 'models/modern-armchair.glb', NULL, 'assets/placeholders/armchair.svg',
 66.90, 90.00, 74.00, 0.6690, 0.9000, 0.7400, 1, 10, NOW(), NOW()),
(1, 'dining-chair', 'Dining Chair',
 'Stackable-style dining chair with a slim profile and durable finish. Designed for everyday meals and compact dining areas.',
 189.00, 'RM', 'models/dining-chair.glb', NULL, 'assets/placeholders/dining-chair.svg',
 45.00, 82.00, 52.00, 0.4500, 0.8200, 0.5200, 1, 20, NOW(), NOW()),
(2, 'three-seater-sofa', 'Three-Seater Sofa',
 'A balanced three-seat sofa with deep cushions and clean lines. Check clearances in your room using the AR viewer on your phone.',
 1899.00, 'RM', 'models/three-seater-sofa.glb', NULL, 'assets/placeholders/sofa.svg',
 210.00, 85.00, 95.00, 2.1000, 0.8500, 0.9500, 1, 30, NOW(), NOW()),
(3, 'coffee-table', 'Coffee Table',
 'Low coffee table with a generous top surface for living-room styling. Use AR to confirm height against your sofa seating.',
 399.00, 'RM', 'models/coffee-table.glb', NULL, 'assets/placeholders/coffee-table.svg',
 110.00, 42.00, 60.00, 1.1000, 0.4200, 0.6000, 1, 40, NOW(), NOW()),
(4, 'platform-bed', 'Platform Bed',
 'Minimal platform bed frame with a low profile. Measure your bedroom on-site with AR to ensure walking space around the mattress.',
 1299.00, 'RM', 'models/platform-bed.glb', NULL, 'assets/placeholders/bed.svg',
 160.00, 35.00, 200.00, 1.6000, 0.3500, 2.0000, 1, 50, NOW(), NOW()),
(6, 'floor-lamp', 'Floor Lamp',
 'Tall floor lamp with a soft conical shade. Place it beside a sofa or desk and verify scale in your real environment.',
 259.00, 'RM', 'models/floor-lamp.glb', NULL, 'assets/placeholders/lamp.svg',
 35.00, 160.00, 35.00, 0.3500, 1.6000, 0.3500, 1, 60, NOW(), NOW());
