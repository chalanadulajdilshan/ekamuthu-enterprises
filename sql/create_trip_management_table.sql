CREATE TABLE `trip_management` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `trip_number` VARCHAR(50) NOT NULL,
  `trip_category` ENUM('internal','customer') NOT NULL DEFAULT 'internal',
  `invoice_type` ENUM('invoice','non_invoice') NULL DEFAULT NULL,
  `bill_id` INT(11) NULL DEFAULT NULL,
  `customer_id` INT(11) NULL DEFAULT NULL,
  `vehicle_id` INT(11) NULL DEFAULT NULL,
  `employee_id` INT(11) NULL DEFAULT NULL,
  `start_location` VARCHAR(255) NULL DEFAULT NULL,
  `end_location` VARCHAR(255) NULL DEFAULT NULL,
  `start_meter` DECIMAL(15,2) DEFAULT 0.00,
  `end_meter` DECIMAL(15,2) NULL DEFAULT NULL,
  `trip_type` ENUM('single','return','back_and_forth') NULL DEFAULT NULL,
  `customer_fuel_cost` DECIMAL(15,2) DEFAULT 0.00,
  `toll` DECIMAL(15,2) DEFAULT 0.00,
  `helper_payment` DECIMAL(15,2) DEFAULT 0.00,
  `transport_amount` DECIMAL(15,2) DEFAULT 0.00,
  `total_cost` DECIMAL(15,2) DEFAULT 0.00,
  `pay_amount` DECIMAL(15,2) DEFAULT 0.00,
  `remark` TEXT NULL DEFAULT NULL,
  `status` ENUM('started','completed') NOT NULL DEFAULT 'started',
  `created_by` INT(11) NULL DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_trip_category` (`trip_category`),
  KEY `idx_bill_id` (`bill_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_vehicle_id` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If table already exists, add the new columns:
-- ALTER TABLE `trip_management` ADD COLUMN `total_cost` DECIMAL(15,2) DEFAULT 0.00 AFTER `transport_amount`;
-- ALTER TABLE `trip_management` ADD COLUMN `pay_amount` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_cost`;
