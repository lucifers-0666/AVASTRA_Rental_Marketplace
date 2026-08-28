-- =============================================================================
-- SpaceShare — Flexible Space Utilization & Time-Based Rental Marketplace
-- Database Schema for MySQL 8.x / XAMPP (MariaDB compatible)
-- Database Name: spaceshare_db
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `spaceshare_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `spaceshare_db`;

-- -----------------------------------------------------------------------------
-- 1. Roles Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_name` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `role_name`, `description`) VALUES
(1, 'admin', 'System Administrator with full access'),
(2, 'user', 'Registered User (Can act as Space Seeker & Space Owner)');

-- -----------------------------------------------------------------------------
-- 2. Users Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT NOT NULL DEFAULT 2,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT 'default-avatar.png',
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip_code` VARCHAR(20) DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'blocked', 'pending') NOT NULL DEFAULT 'active',
  `email_verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin Account: admin@spaceshare.com / admin123
INSERT INTO `users` (`id`, `role_id`, `full_name`, `email`, `password_hash`, `phone`, `status`, `email_verified`) VALUES
(1, 1, 'System Administrator', 'admin@spaceshare.com', '$2y$10$xbwmT/NkMoC8.SE7f3LZn.Y7e1h0NirkSs8DsmpoQCU5oEE5xVBT.', '+91 9876543210', 'active', 1),
(2, 2, 'Jay Patel', 'jay@example.com', '$2y$10$xbwmT/NkMoC8.SE7f3LZn.Y7e1h0NirkSs8DsmpoQCU5oEE5xVBT.', '+91 9876543211', 'active', 1),
(3, 2, 'Rahul Sharma', 'rahul@example.com', '$2y$10$xbwmT/NkMoC8.SE7f3LZn.Y7e1h0NirkSs8DsmpoQCU5oEE5xVBT.', '+91 9876543212', 'active', 1);

-- -----------------------------------------------------------------------------
-- 3. Categories Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(100) DEFAULT 'bi-building',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `is_active`) VALUES
(1, 'Warehouse & Storage', 'warehouse-storage', 'Secure storage spaces for commercial inventory, household items, or goods.', 'bi-box-seam', 1),
(2, 'Office & Co-working', 'office-coworking', 'Fully equipped office desks, private cabins, and meeting rooms.', 'bi-laptop', 1),
(3, 'Event & Venue Space', 'event-venue-space', 'Spacious areas suitable for weddings, parties, workshops, and gatherings.', 'bi-calendar-event', 1),
(4, 'Workshop & Studio', 'workshop-studio', 'Creative studios, craft workshops, and light industrial spaces.', 'bi-tools', 1),
(5, 'Pop-up Shop & Retail', 'popup-shop-retail', 'Retail storefronts and high-footfall promotional kiosks.', 'bi-shop', 1),
(6, 'Garage & Parking Space', 'garage-parking-space', 'Covered garages and reserved parking slots for vehicles.', 'bi-car-front', 1);

-- -----------------------------------------------------------------------------
-- 4. Amenities Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `amenities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(100) DEFAULT 'bi-check-circle',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `amenities` (`id`, `name`, `icon`) VALUES
(1, '24/7 Security CCTV', 'bi-shield-check'),
(2, 'High-Speed Wi-Fi', 'bi-wifi'),
(3, 'Air Conditioning', 'bi-snow'),
(4, 'Power Backup / Generator', 'bi-lightning-charge'),
(5, 'Loading Dock / Forklift', 'bi-truck'),
(6, 'Restroom Access', 'bi-door-closed'),
(7, 'Fire Extinguisher & Alarm', 'bi-fire'),
(8, 'Covered Parking', 'bi-p-square');

-- -----------------------------------------------------------------------------
-- 5. Spaces Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `spaces` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `zip_code` VARCHAR(20) NOT NULL,
  `latitude` DECIMAL(10, 8) DEFAULT NULL,
  `longitude` DECIMAL(11, 8) DEFAULT NULL,
  `total_sqft` INT NOT NULL,
  `max_capacity` INT DEFAULT 10,
  `daily_rate` DECIMAL(10, 2) NOT NULL,
  `weekly_rate` DECIMAL(10, 2) DEFAULT NULL,
  `monthly_rate` DECIMAL(10, 2) DEFAULT NULL,
  `security_deposit` DECIMAL(10, 2) DEFAULT 0.00,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Spaces Seed Data
INSERT INTO `spaces` (`id`, `owner_id`, `category_id`, `title`, `description`, `address`, `city`, `state`, `zip_code`, `latitude`, `longitude`, `total_sqft`, `daily_rate`, `weekly_rate`, `monthly_rate`, `security_deposit`, `verification_status`) VALUES
(1, 2, 1, 'Secure Commercial Storage Warehouse', 'Spacious 500 sq.ft. dry storage warehouse with 24/7 CCTV surveillance and loading dock.', 'Plot 42, GIDC Industrial Estate', 'Ahmedabad', 'Gujarat', '380015', 23.02250000, 72.57140000, 500, 800.00, 5000.00, 18000.00, 2000.00, 'approved'),
(2, 3, 2, 'Modern Co-Working Cabin & Meeting Space', 'Plug-and-play 250 sq.ft. private office cabin with fiber internet, AC, and tea/coffee.', '101 Corporate Towers, SG Highway', 'Ahmedabad', 'Gujarat', '380054', 23.03000000, 72.52000000, 250, 1200.00, 7000.00, 25000.00, 3000.00, 'approved'),
(3, 2, 3, 'Downtown Event Hall for Workshops & Popups', 'Air-conditioned 1200 sq.ft. hall suitable for corporate workshops, art exhibitions, and popups.', 'Ring Road Mall Complex', 'Surat', 'Gujarat', '395007', 21.17020000, 72.83110000, 1200, 3500.00, 20000.00, 70000.00, 5000.00, 'pending');

-- -----------------------------------------------------------------------------
-- 6. Space Images Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `space_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `space_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Space Amenities Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `space_amenities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `space_id` INT NOT NULL,
  `amenity_id` INT NOT NULL,
  FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. Space Availability Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `space_availability` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `space_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_blocked` TINYINT(1) DEFAULT 0,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. Space Purposes Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `space_purposes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `space_id` INT NOT NULL,
  `purpose_name` VARCHAR(100) NOT NULL,
  FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. Bookings Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_code` VARCHAR(50) NOT NULL UNIQUE,
  `space_id` INT NOT NULL,
  `seeker_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` INT NOT NULL,
  `purpose` VARCHAR(150) NOT NULL,
  `base_amount` DECIMAL(10, 2) NOT NULL,
  `platform_fee` DECIMAL(10, 2) NOT NULL,
  `deposit_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
  `cancellation_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seeker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Bookings Seed Data
INSERT INTO `bookings` (`id`, `booking_code`, `space_id`, `seeker_id`, `start_date`, `end_date`, `total_days`, `purpose`, `base_amount`, `platform_fee`, `deposit_amount`, `total_amount`, `status`) VALUES
(1, 'BK-202608-001', 1, 3, '2026-09-01', '2026-09-15', 15, 'Inventory Storage', 10000.00, 500.00, 2000.00, 12500.00, 'confirmed'),
(2, 'BK-202608-002', 2, 3, '2026-09-05', '2026-09-07', 3, 'Client Meetings', 3600.00, 180.00, 0.00, 3780.00, 'pending');

-- -----------------------------------------------------------------------------
-- 11. Payments Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `payment_method` ENUM('razorpay', 'cash', 'bank_transfer', 'pay_later') NOT NULL DEFAULT 'cash',
  `amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `booking_id`, `transaction_id`, `payment_method`, `amount`, `status`, `paid_at`) VALUES
(1, 1, 'TXN-893471982', 'razorpay', 12500.00, 'completed', CURRENT_TIMESTAMP);

-- -----------------------------------------------------------------------------
-- 12. Reviews Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `space_id` INT NOT NULL,
  `reviewer_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT DEFAULT NULL,
  `is_approved` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 13. Complaints Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
  `resolution_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. Notifications Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 15. Favorites Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `space_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_space_unique` (`user_id`, `space_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 16. Commission Settings Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `commission_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `platform_fee_percent` DECIMAL(5, 2) NOT NULL DEFAULT 5.00,
  `deposit_percent` DECIMAL(5, 2) NOT NULL DEFAULT 10.00,
  `min_payout_amount` DECIMAL(10, 2) NOT NULL DEFAULT 500.00,
  `contact_email` VARCHAR(150) NOT NULL DEFAULT 'support@spaceshare.com',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `commission_settings` (`id`, `platform_fee_percent`, `deposit_percent`, `min_payout_amount`, `contact_email`) VALUES
(1, 5.00, 10.00, 500.00, 'support@spaceshare.com');

-- -----------------------------------------------------------------------------
-- 17. Audit Logs Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) DEFAULT NULL,
  `entity_id` INT DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`) VALUES
(1, 'DATABASE_INITIALIZATION', 'SYSTEM', 1, 'Initial MySQL Database setup for SpaceShare completed.', '127.0.0.1');

-- -----------------------------------------------------------------------------
-- 18. Password Resets Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
