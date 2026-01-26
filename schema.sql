ALTER TABLE `equipment_rent` 
ADD COLUMN `transport_cost` DECIMAL(10,2) DEFAULT '0.00' AFTER `total_items`;

ALTER TABLE `equipment_rent` 
ADD COLUMN `deposit_total` DECIMAL(10,2) DEFAULT '0.00' AFTER `transport_cost`;

CREATE TABLE `equipment_rent_quotation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_number` varchar(50) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `rental_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `total_items` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `equipment_rent_quotation_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `sub_equipment_id` int(11) DEFAULT NULL,
  `rental_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `rent_type` varchar(20) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `document_tracking` ADD COLUMN `equipment_rent_quotation_id` int(11) DEFAULT 0;

-- Repair Job Management Tables
CREATE TABLE `repair_jobs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_code` VARCHAR(50) NOT NULL,
  `item_type` ENUM('company', 'customer') NOT NULL DEFAULT 'customer',
  `machine_code` VARCHAR(100) DEFAULT NULL,
  `machine_name` VARCHAR(255) DEFAULT NULL,
  `customer_name` VARCHAR(255) DEFAULT NULL,
  `customer_address` TEXT DEFAULT NULL,
  `customer_phone` VARCHAR(50) DEFAULT NULL,
  `item_breakdown_date` DATE DEFAULT NULL,
  `technical_issue` TEXT DEFAULT NULL,
  `job_status` ENUM('pending', 'checking', 'in_progress', 'completed', 'cannot_repair') DEFAULT 'pending',
  `repair_charge` DECIMAL(10,2) DEFAULT 0.00,
  `commission_percentage` DECIMAL(5,2) DEFAULT 15.00,
  `commission_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_cost` DECIMAL(10,2) DEFAULT 0.00,
  `remark` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_code` (`job_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `repair_job_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `quantity` INT(11) DEFAULT 1,
  `unit_price` DECIMAL(10,2) DEFAULT 0.00,
  `total_price` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Warehouse Issue Note Tables
CREATE TABLE `issue_notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `issue_note_code` VARCHAR(50) NOT NULL,
  `rent_invoice_id` INT(11) NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `issue_date` DATE NOT NULL,
  `issue_status` ENUM('pending', 'issued', 'cancelled') DEFAULT 'pending',
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `issue_note_code` (`issue_note_code`),
  KEY `rent_invoice_id` (`rent_invoice_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `issue_note_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `issue_note_id` INT(11) NOT NULL,
  `equipment_id` INT(11) DEFAULT NULL,
  `sub_equipment_id` INT(11) DEFAULT NULL,
  `ordered_quantity` INT(11) DEFAULT 0,
  `issued_quantity` INT(11) DEFAULT 0,
  `rent_type` VARCHAR(20) DEFAULT NULL,
  `duration` FLOAT DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `issue_note_id` (`issue_note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `document_tracking` ADD COLUMN `issue_note_id` INT(11) DEFAULT 0;


ALTER TABLE `customer_master` 
ADD COLUMN `is_blacklisted` TINYINT(1) DEFAULT 0,
ADD COLUMN `blacklist_reason` TEXT DEFAULT NULL;