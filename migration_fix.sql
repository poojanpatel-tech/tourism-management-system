-- =========================================================================
-- CANONICAL SCHEMA RECONCILIATION & UPGRADE MIGRATION
-- Project: Patel Travels — Tourism Management System
-- Purpose: Safely reconciles schema discrepancies, repairs column mismatches,
--          updates enquiry CRM lifecycle enum, restores missing settings table,
--          and corrects corrupted legacy administrator hashes.
-- =========================================================================

-- 1. Ensure settings table exists with default business settings
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('address', 'SBR, Ahmedabad, Gujarat, India'),
('company_name', 'Patel Travels'),
('email', 'info@pateltravels.local'),
('facebook', 'https://facebook.com/'),
('footer_description', 'Thoughtfully designed journeys, unforgettable destinations and travel experiences created around the way you want to explore.'),
('google_maps', 'https://maps.google.com/'),
('instagram', 'https://instagram.com/'),
('phone', '+91 9876 543 210'),
('tagline', 'Curated Travel Experiences'),
('whatsapp', '+91 9876 543 210'),
('working_hours', 'Mon - Sat, 10:00 AM - 6:00 PM');

-- 2. Ensure packages table has duration_nights column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'packages' AND column_name = 'duration_nights';
SET @query = IF(@col_exists = 0, 'ALTER TABLE `packages` ADD COLUMN `duration_nights` INT(11) DEFAULT NULL AFTER `duration_days`', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Ensure enquiries primary key is 'id' and columns are reconciled
SET @col_enq_id = 0;
SELECT COUNT(*) INTO @col_enq_id FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'enquiries' AND column_name = 'enquiry_id';
SET @query = IF(@col_enq_id > 0, 'ALTER TABLE `enquiries` CHANGE COLUMN `enquiry_id` `id` INT(11) NOT NULL AUTO_INCREMENT', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Map legacy statuses before modifying enum
UPDATE `enquiries` SET `status` = 'contacted' WHERE `status` = 'read';
UPDATE `enquiries` SET `status` = 'in_discussion' WHERE `status` = 'replied';

-- Upgrade enquiry status enum to full 7-stage CRM lifecycle
ALTER TABLE `enquiries` 
  MODIFY COLUMN `status` ENUM('new', 'contacted', 'in_discussion', 'quoted', 'confirmed', 'closed', 'cancelled') NOT NULL DEFAULT 'new';

-- 4. Reconcile plan_highlights column names
SET @col_hl_text = 0;
SELECT COUNT(*) INTO @col_hl_text FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'plan_highlights' AND column_name = 'highlight_text';
SET @query = IF(@col_hl_text > 0, 'ALTER TABLE `plan_highlights` CHANGE COLUMN `highlight_text` `highlight` VARCHAR(255) NOT NULL', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_hl_id = 0;
SELECT COUNT(*) INTO @col_hl_id FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'plan_highlights' AND column_name = 'highlight_id';
SET @query = IF(@col_hl_id > 0, 'ALTER TABLE `plan_highlights` CHANGE COLUMN `highlight_id` `id` INT(11) NOT NULL AUTO_INCREMENT', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Reconcile plan_inclusions typo and column names
SET @col_inc_type_typo = 0;
SELECT COUNT(*) INTO @col_inc_type_typo FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'plan_inclusions' AND column_name = 'ype';
SET @query = IF(@col_inc_type_typo > 0, 'ALTER TABLE `plan_inclusions` CHANGE COLUMN `ype` `type` ENUM(\'included\', \'excluded\') NOT NULL DEFAULT \'included\'', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_inc_content = 0;
SELECT COUNT(*) INTO @col_inc_content FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'plan_inclusions' AND column_name = 'content';
SET @query = IF(@col_inc_content > 0, 'ALTER TABLE `plan_inclusions` CHANGE COLUMN `content` `title` VARCHAR(200) NOT NULL', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Repair corrupted default admin password hash if present
UPDATE `admins` 
SET `password` = '$2y$10$qGZlJNWq4FY.0wbQ2iEgNugFs5OCsoM4RhsSA84/crq5ZfB8Q/Qui' 
WHERE `username` = 'admin' AND `password` = '/ma2.ycVdVXCdFAPMYlVRna';
