CREATE TABLE `gatepass_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gatepass_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `sub_equipment_id` int(11) DEFAULT NULL,
  `quantity` float NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
