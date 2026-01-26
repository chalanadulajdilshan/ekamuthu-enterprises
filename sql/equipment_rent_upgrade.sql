-- =====================================================
-- Equipment Rent System Upgrade
-- Converts single-item rental to multi-item invoice-style
-- =====================================================

-- 1. Add rental_status to sub_equipment to track availability
ALTER TABLE `sub_equipment` 
ADD COLUMN `rental_status` ENUM('available', 'rented') NOT NULL DEFAULT 'available' AFTER `name`;

-- 2. Create equipment_rent_items table for individual rental items
CREATE TABLE IF NOT EXISTS `equipment_rent_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `rent_id` INT(11) NOT NULL COMMENT 'FK to equipment_rent.id',
    `equipment_id` INT(11) NOT NULL COMMENT 'FK to equipment.id (parent equipment like Generator)',
    `sub_equipment_id` INT(11) NOT NULL COMMENT 'FK to sub_equipment.id (specific unit code)',
    `rental_date` DATE NOT NULL,
    `return_date` DATE DEFAULT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `status` ENUM('rented', 'returned') NOT NULL DEFAULT 'rented',
    `remark` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rent_id` (`rent_id`),
    KEY `idx_equipment_id` (`equipment_id`),
    KEY `idx_sub_equipment_id` (`sub_equipment_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_rent_items_rent` FOREIGN KEY (`rent_id`) REFERENCES `equipment_rent` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rent_items_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`),
    CONSTRAINT `fk_rent_items_sub_equipment` FOREIGN KEY (`sub_equipment_id`) REFERENCES `sub_equipment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Modify equipment_rent table to be a master/header table
-- First backup existing data
CREATE TABLE IF NOT EXISTS `equipment_rent_backup` AS SELECT * FROM `equipment_rent`;

-- Add new columns to equipment_rent if they don't exist
ALTER TABLE `equipment_rent` 
ADD COLUMN IF NOT EXISTS `total_items` INT(11) NOT NULL DEFAULT 0 AFTER `remark`,
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `total_items`,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Store selected payment method (payment_type.id)
ALTER TABLE `equipment_rent`
ADD COLUMN IF NOT EXISTS `payment_type_id` INT(11) DEFAULT NULL AFTER `deposit_total`;

-- 4. Migrate existing single-item rentals to the new structure
-- This will create rent_items records from existing equipment_rent records
INSERT INTO `equipment_rent_items` (`rent_id`, `equipment_id`, `sub_equipment_id`, `rental_date`, `return_date`, `quantity`, `status`, `remark`)
SELECT 
    er.id,
    er.equipment_id,
    COALESCE(
        (SELECT se.id FROM sub_equipment se WHERE se.equipment_id = er.equipment_id LIMIT 1),
        0
    ),
    er.rental_date,
    er.received_date,
    er.quantity,
    CASE WHEN er.status = 'returned' THEN 'returned' ELSE 'rented' END,
    er.remark
FROM equipment_rent er
WHERE er.equipment_id IS NOT NULL AND er.equipment_id != ''
ON DUPLICATE KEY UPDATE rent_id = rent_id;

-- 5. Update total_items count in equipment_rent
UPDATE equipment_rent er
SET total_items = (
    SELECT COUNT(*) FROM equipment_rent_items eri WHERE eri.rent_id = er.id
);

-- 6. Create index for faster availability checks
CREATE INDEX IF NOT EXISTS `idx_sub_equipment_rental_status` ON `sub_equipment` (`rental_status`);
CREATE INDEX IF NOT EXISTS `idx_sub_equipment_equipment_id` ON `sub_equipment` (`equipment_id`);

-- =====================================================
-- IMPORTANT: Run these queries to check the migration
-- =====================================================
-- SELECT * FROM equipment_rent_items;
-- SELECT * FROM sub_equipment WHERE rental_status = 'rented';
-- SELECT COUNT(*) FROM equipment_rent_backup;
