-- Phase 1 Updates for Tourism Management System
-- Run this to update the existing schema

-- 1. users table (Client Accounts)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `email_verified_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_email` (`email`),
    CONSTRAINT `fk_users_customer`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customers` (`customer_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. payments table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reservation_id` INT NOT NULL,
    `payment_reference` VARCHAR(100) NOT NULL UNIQUE,
    `gateway` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
    `status` ENUM('pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    `transaction_id` VARCHAR(100) NULL,
    `paid_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_payment_res` (`reservation_id`),
    INDEX `idx_payment_status` (`status`),
    CONSTRAINT `fk_payments_reservation`
        FOREIGN KEY (`reservation_id`)
        REFERENCES `reservations` (`reservation_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. enquiries table
CREATE TABLE IF NOT EXISTS `enquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_enquiry_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. reviews table
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `package_id` INT NOT NULL,
    `reservation_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `review` TEXT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_review_package` (`package_id`),
    INDEX `idx_review_status` (`status`),
    CONSTRAINT `fk_reviews_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_package`
        FOREIGN KEY (`package_id`)
        REFERENCES `packages` (`package_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_reservation`
        FOREIGN KEY (`reservation_id`)
        REFERENCES `reservations` (`reservation_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    UNIQUE KEY `uk_review_reservation` (`reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
