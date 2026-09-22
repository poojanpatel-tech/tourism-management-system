-- ====================================================================
-- Tourism Management System - College Project Database Foundation
-- Compatible with MySQL 5.7+ / 8.0+ and MariaDB (XAMPP Default)
-- ====================================================================

-- 1. Create Database if not exists
CREATE DATABASE IF NOT EXISTS `tourism_management`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `tourism_management`;

-- --------------------------------------------------------------------
-- Disable foreign key checks during schema re-creation
-- --------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `packages`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `destinations`;
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------------------
-- Table 1: admins
-- Stores administrative user credentials and profile details
-- --------------------------------------------------------------------
CREATE TABLE `admins` (
    `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table 2: destinations
-- Tourist destinations available for package associations
-- --------------------------------------------------------------------
CREATE TABLE `destinations` (
    `destination_id` INT AUTO_INCREMENT PRIMARY KEY,
    `destination_name` VARCHAR(100) NOT NULL,
    `country` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_destination_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table 3: packages
-- Tour packages tied to destinations with pricing and capacity
-- --------------------------------------------------------------------
CREATE TABLE `packages` (
    `package_id` INT AUTO_INCREMENT PRIMARY KEY,
    `package_code` VARCHAR(50) NOT NULL UNIQUE,
    `package_name` VARCHAR(150) NOT NULL,
    `destination_id` INT NOT NULL,
    `duration` VARCHAR(50) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `maximum_capacity` INT NOT NULL DEFAULT 20,
    `travel_date` DATE NULL,
    `description` TEXT NULL,
    `included_services` TEXT NULL,
    `excluded_services` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive', 'sold_out') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_package_destination` (`destination_id`),
    INDEX `idx_package_status` (`status`),
    CONSTRAINT `fk_packages_destination`
        FOREIGN KEY (`destination_id`)
        REFERENCES `destinations` (`destination_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table 4: customers
-- Tourist/Customer directory and contact information
-- --------------------------------------------------------------------
CREATE TABLE `customers` (
    `customer_id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_code` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `date_of_birth` DATE NULL,
    `address` TEXT NULL,
    `city` VARCHAR(50) NULL,
    `country` VARCHAR(50) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_customer_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table 5: reservations
-- Bookings made by customers for particular packages
-- --------------------------------------------------------------------
CREATE TABLE `reservations` (
    `reservation_id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_number` VARCHAR(50) NOT NULL UNIQUE,
    `customer_id` INT NOT NULL,
    `package_id` INT NOT NULL,
    `travel_date` DATE NOT NULL,
    `number_of_travelers` INT NOT NULL DEFAULT 1,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `reservation_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_res_customer` (`customer_id`),
    INDEX `idx_res_package` (`package_id`),
    INDEX `idx_res_status` (`status`),
    CONSTRAINT `fk_reservations_customer`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customers` (`customer_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_reservations_package`
        FOREIGN KEY (`package_id`)
        REFERENCES `packages` (`package_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- REALISTIC SEED DATA
-- ====================================================================

-- 1. Insert Default Administrator
-- Default credentials: username = 'admin', password = 'Admin@12345'
INSERT INTO `admins` (`username`, `password`, `full_name`, `email`) VALUES
('admin', '$2y$10$lm22hL915ET8jQMC5jod4u.qMrNtt.7WaA.Y04K5sKbLRfgd/n8ge', 'System Administrator', 'admin@tourism.local');

-- 2. Insert Destinations
INSERT INTO `destinations` (`destination_id`, `destination_name`, `country`, `description`, `status`) VALUES
(1, 'Bali Island', 'Indonesia', 'Tropical paradise known for forested volcanic mountains, iconic rice paddies, beaches and coral reefs.', 'active'),
(2, 'Paris', 'France', 'World capital of art, fashion, gastronomy, culture and iconic landmarks such as the Eiffel Tower.', 'active'),
(3, 'Kyoto', 'Japan', 'Famous for numerous classical Buddhist temples, gardens, imperial palaces, Shinto shrines and traditional wooden houses.', 'active'),
(4, 'Swiss Alps (Interlaken)', 'Switzerland', 'Breathtaking alpine vistas, snow-capped peaks, glacier valleys, and world-class mountain railways.', 'active'),
(5, 'Dubai', 'United Arab Emirates', 'Futuristic metropolis renowned for luxury shopping, ultramodern architecture and lively nightlife scenes.', 'active'),
(6, 'Kerala Backwaters', 'India', 'Serene palm-fringed network of tranquil lagoons, lakes, traditional houseboats, and ayurvedic retreats.', 'active');

-- 3. Insert Packages
INSERT INTO `packages` (`package_id`, `package_code`, `package_name`, `destination_id`, `duration`, `price`, `maximum_capacity`, `travel_date`, `description`, `included_services`, `excluded_services`, `image`, `status`) VALUES
(1, 'PKG-BALI-001', 'Bali Tropical Haven & Beach Retreat', 1, '5 Days / 4 Nights', 750.00, 20, '2026-11-15', 'Experience lush Ubud landscapes, ancient sea temples, and sunset beach barbecues in Seminyak.', '4-Star Resort Stay, Daily Breakfast, Airport Transfers, Guided Ubud Tour, Speedboat Tickets', 'International Flights, Personal Expenses, Travel Insurance, Optional Watersports', 'bali.jpg', 'active'),
(2, 'PKG-PARIS-002', 'Romantic Paris & Seine River Odyssey', 2, '6 Days / 5 Nights', 1450.00, 15, '2026-10-20', 'Discover Parisian romance with Louvre museum access, Eiffel Tower summit tickets, and gourmet Seine cruise.', 'Boutique Hotel in 7th Arr., Breakfast, Seine River Dinner Cruise, Priority Museum Passes', 'Airfare, Visa Processing Fees, City Tourist Tax, Personal Shopping', 'paris.jpg', 'active'),
(3, 'PKG-KYOTO-003', 'Kyoto Heritage & Arashiyama Bamboo Trail', 3, '7 Days / 6 Nights', 1650.00, 18, '2026-11-05', 'Walk through the thousand vermilion torii gates at Fushimi Inari, tea ceremonies, and Zen gardens.', 'Ryokan & Hotel Accommodation, Traditional Kaiseki Dinners, JR Rail Pass, English Guide', 'Flights, Lunch meals, Luggage forwarding surcharge, Alcoholic beverages', 'kyoto.jpg', 'active'),
(4, 'PKG-SWISS-004', 'Majestic Swiss Alpine Adventure', 4, '6 Days / 5 Nights', 1980.00, 16, '2026-12-10', 'Journey to Jungfraujoch - Top of Europe, scenic lake cruises in Lake Thun, and mountain cable cars.', '4-Star Chalet Hotel, Swiss Travel Rail Pass, Daily Swiss Buffet Breakfast, Mountain Excursions', 'International Flights, Ski Equipment Rentals, Lunch and Dinners', 'swiss.jpg', 'active'),
(5, 'PKG-DXB-005', 'Dubai Desert Safari & Skyscraper Extravaganza', 5, '4 Days / 3 Nights', 620.00, 25, '2026-10-15', 'Enjoy high-octane dune bashing, Burj Khalifa observation deck, and luxury marina dinner yacht.', '5-Star City Hotel, Daily Breakfast, VIP Desert Safari with BBQ Dinner, Burj Khalifa 124th Floor Entry', 'Flight Tickets, UAE Tourist Visa, Tourism Dirham Fee, Quad Bike Add-on', 'dubai.jpg', 'active'),
(6, 'PKG-KER-006', 'Kerala Houseboat Serenity & Spice Hills', 6, '5 Days / 4 Nights', 420.00, 20, '2026-11-22', 'Unwind amidst Munnar tea plantations and spend an overnight cruising the tranquil Alleppey backwaters.', 'Private AC Houseboat Stay, Munnar Tea Estate Resort, All Meals on Houseboat, Chauffeur AC Car', 'Train/Flight Tickets, National Park Entrance Fees, Personal Tips', 'kerala.jpg', 'active');

-- 4. Insert Customers
INSERT INTO `customers` (`customer_id`, `customer_code`, `full_name`, `email`, `phone`, `gender`, `date_of_birth`, `address`, `city`, `country`) VALUES
(1, 'CUST-1001', 'Rahul Sharma', 'rahul.sharma@example.com', '+91 9876543210', 'Male', '1994-06-15', '42 MG Road, Indiranagar', 'Bengaluru', 'India'),
(2, 'CUST-1002', 'Sophia Martinez', 'sophia.m@example.com', '+1 415 555 2671', 'Female', '1998-03-22', '742 Evergreen Terrace', 'San Francisco', 'USA'),
(3, 'CUST-1003', 'Alexander Wright', 'alex.wright@example.co.uk', '+44 20 7946 0912', 'Male', '1989-11-04', '18 Baker Street', 'London', 'United Kingdom'),
(4, 'CUST-1004', 'Priya Patel', 'priya.patel@example.com', '+91 9822012345', 'Female', '1996-08-30', '101 Sunrise Enclave, Vastrapur', 'Ahmedabad', 'India'),
(5, 'CUST-1005', 'Kenji Tanaka', 'kenji.t@example.jp', '+81 90 1234 5678', 'Male', '1992-01-18', '3-4-1 Marunouchi, Chiyoda-ku', 'Tokyo', 'Japan'),
(6, 'CUST-1006', 'Emily Chen', 'emily.chen@example.ca', '+1 604 555 8921', 'Female', '1995-09-12', '888 Robson Street', 'Vancouver', 'Canada');

-- 5. Insert Reservations
INSERT INTO `reservations` (`reservation_id`, `booking_number`, `customer_id`, `package_id`, `travel_date`, `number_of_travelers`, `total_amount`, `reservation_date`, `status`, `notes`) VALUES
(1, 'RES-2026-001', 1, 1, '2026-11-15', 2, 1500.00, '2026-09-01 10:30:00', 'confirmed', 'Honeymoon arrangement requested. Vegetarian meal preference.'),
(2, 'RES-2026-002', 2, 2, '2026-10-20', 1, 1450.00, '2026-09-03 14:15:00', 'confirmed', 'Requested top-floor hotel room with Eiffel Tower view.'),
(3, 'RES-2026-003', 3, 4, '2026-12-10', 2, 3960.00, '2026-09-05 16:45:00', 'pending', 'Awaiting traveler passport details for Swiss rail reservations.'),
(4, 'RES-2026-004', 4, 6, '2026-11-22', 4, 1680.00, '2026-09-08 11:20:00', 'confirmed', 'Family trip. Required adjoining bedrooms on houseboat.'),
(5, 'RES-2026-005', 5, 5, '2026-10-15', 2, 1240.00, '2026-09-10 09:10:00', 'confirmed', 'Desert safari VIP seating confirmed.'),
(6, 'RES-2026-006', 6, 3, '2026-11-05', 1, 1650.00, '2026-09-12 17:00:00', 'pending', 'Customer requested adjustment for arrival train schedule.'),
(7, 'RES-2026-007', 1, 5, '2026-10-15', 1, 620.00, '2026-09-14 13:30:00', 'cancelled', 'Cancelled due to corporate meeting schedule clash.'),
(8, 'RES-2026-008', 3, 1, '2026-11-15', 3, 2250.00, '2026-09-18 15:40:00', 'pending', 'Group booking under verification.');
