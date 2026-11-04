-- Overdue rent follow-up + rep app.
-- RepFollowup::ensureSchema() creates all of this automatically; this file is for reference / manual setup.

-- Follow-up status and rep assignment per rent bill.
-- A bill with no row here is treated as "follow-up required, unassigned".
CREATE TABLE IF NOT EXISTS `rent_overdue_followup` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rent_id` INT(11) NOT NULL,
  `followup_required` TINYINT(1) NOT NULL DEFAULT 1,
  `rep_id` INT(11) DEFAULT NULL,
  `assigned_at` DATETIME DEFAULT NULL,
  `assigned_by` INT(11) DEFAULT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_rent` (`rent_id`),
  KEY `idx_rep` (`rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Visits recorded by reps in the rep app
CREATE TABLE IF NOT EXISTS `rep_visits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rep_id` INT(11) NOT NULL,
  `rent_id` INT(11) NOT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `visit_at` DATETIME NOT NULL,
  `outcome` VARCHAR(30) NOT NULL,
  `promised_date` DATE DEFAULT NULL,
  `remark` TEXT,
  `latitude` DECIMAL(10,7) DEFAULT NULL,
  `longitude` DECIMAL(10,7) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rep_date` (`rep_id`, `visit_at`),
  KEY `idx_rent` (`rent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments collected by reps. Kept apart from the accounts.
-- status: no_proof (not confirmed) -> proof_added -> verified / rejected (by office)
CREATE TABLE IF NOT EXISTS `rep_collections` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rep_id` INT(11) NOT NULL,
  `rent_id` INT(11) NOT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(20) NOT NULL DEFAULT 'cash',
  `reference` VARCHAR(100) DEFAULT NULL,
  `remark` VARCHAR(255) DEFAULT NULL,
  `proof_image` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'no_proof',
  `collected_at` DATETIME NOT NULL,
  `proof_added_at` DATETIME DEFAULT NULL,
  `verified_by` INT(11) DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `admin_remark` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rep_date` (`rep_id`, `collected_at`),
  KEY `idx_rent` (`rent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Day route per rep: which assigned bills to visit on a date, in order
CREATE TABLE IF NOT EXISTS `rep_routes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rep_id` INT(11) NOT NULL,
  `route_date` DATE NOT NULL,
  `rent_id` INT(11) NOT NULL,
  `stop_order` INT(11) NOT NULL DEFAULT 0,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_stop` (`rep_id`, `route_date`, `rent_id`),
  KEY `idx_rep_date` (`rep_id`, `route_date`, `stop_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mobile app login tokens (only the SHA-256 of the token is stored)
CREATE TABLE IF NOT EXISTS `rep_app_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rep_id` INT(11) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `device` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_token` (`token_hash`),
  KEY `idx_rep` (`rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rep app login on the sales executive record
ALTER TABLE `marketing_executive`
  ADD COLUMN `app_username` VARCHAR(50) DEFAULT NULL,
  ADD COLUMN `app_password` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `app_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `app_last_login` DATETIME DEFAULT NULL,
  ADD UNIQUE KEY `uniq_app_username` (`app_username`);
