CREATE TABLE `gatepass` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gatepass_code` varchar(50) DEFAULT NULL,
  `invoice_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `equipment_type` varchar(255) DEFAULT NULL,
  `serial_no` varchar(255) DEFAULT NULL,
  `issued_by` varchar(255) DEFAULT NULL,
  `gatepass_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
