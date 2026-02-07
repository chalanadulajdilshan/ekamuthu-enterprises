-- Issue Return Note Tables
CREATE TABLE `issue_returns` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `return_code` VARCHAR(50) NOT NULL,
  `issue_note_id` INT(11) NOT NULL,
  `return_date` DATE NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_code` (`return_code`),
  KEY `issue_note_id` (`issue_note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `issue_return_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `return_id` INT(11) NOT NULL,
  `equipment_id` INT(11) DEFAULT NULL,
  `sub_equipment_id` INT(11) DEFAULT NULL,
  `issued_quantity` INT(11) DEFAULT 0,
  `return_quantity` INT(11) DEFAULT 0,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `document_tracking` ADD COLUMN `issue_return_id` INT(11) DEFAULT 0;
