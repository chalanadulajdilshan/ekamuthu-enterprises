CREATE TABLE `loyalty_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `points` decimal(10,2) NOT NULL DEFAULT '0.00',
  `type` enum('earn','redeem','adjustment') NOT NULL DEFAULT 'earn',
  `reference_id` int(11) DEFAULT NULL COMMENT 'Optional: rent_id or invoice_id',
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `fk_loyalty_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
