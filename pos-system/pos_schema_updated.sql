
-- ==========================================================
-- POS SYSTEM - UPDATED COMPLETE SCHEMA
-- ==========================================================

-- 1. UPDATED ITEM MASTER (With Barcode, Pricing, and Settings)
CREATE TABLE IF NOT EXISTS `item_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'Product Code',
  `barcode` varchar(100) DEFAULT NULL COMMENT 'Entered Bar Code',
  `name` varchar(255) NOT NULL COMMENT 'Description / Item Name',
  `brand` int(11) DEFAULT '1' COMMENT 'Supplier / Brand Mapping',
  `department_id` int(11) DEFAULT '1' COMMENT 'Department Mapping',
  `stock_type` int(11) DEFAULT '1',
  `note` text DEFAULT NULL,
  `reminder_note` text DEFAULT NULL,
  
  -- PRICING
  `list_price` decimal(12,2) DEFAULT '0.00' COMMENT 'Cost Price',
  `net_price` decimal(12,2) DEFAULT '0.00' COMMENT 'Net Price after internal adjustments',
  `discount` decimal(12,2) DEFAULT '0.00' COMMENT 'Max Discount %',
  `invoice_price` decimal(12,2) DEFAULT '0.00' COMMENT 'Retail / Selling Price',
  `tax_type` varchar(20) DEFAULT 'T0-0',
  
  -- INVENTORY LIMITS
  `re_order_level` int(11) DEFAULT '0' COMMENT 'Min Stock Level',
  `max_stock` int(11) DEFAULT '0',
  `re_order_qty` int(11) DEFAULT '1' COMMENT 'Pack Quantity',
  
  -- STATUS
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Is Available to Sale',
  `image_file` varchar(255) DEFAULT NULL COMMENT 'Product Image Filename',
  
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2. SALES INVOICE (POS TRANSACTION HEADER)
CREATE TABLE IF NOT EXISTS `sales_invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` datetime NOT NULL DEFAULT current_timestamp(),
  `customer_id` int(11) DEFAULT '0',
  `customer_name` varchar(255) DEFAULT 'Walk-in Customer',
  `customer_mobile` varchar(20) DEFAULT '',
  `customer_address` text DEFAULT NULL,
  `department_id` int(11) DEFAULT '1',
  `payment_type` tinyint(2) DEFAULT '1' COMMENT '1:Cash, 2:Card, 3:Credit',
  `sub_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `final_cost` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Internal cost summary',
  `status` tinyint(1) DEFAULT '1' COMMENT '1:Active, 0:Cancelled',
  `remark` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 3. SALES INVOICE ITEMS (TRANSACTION LINE ITEMS)
CREATE TABLE IF NOT EXISTS `sales_invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `cost` decimal(12,2) DEFAULT '0.00',
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 4. STOCK MASTER (REALTIME INVENTORY MAPPING)
CREATE TABLE IF NOT EXISTS `stock_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cost_price` decimal(12,2) DEFAULT '0.00',
  `location_id` int(11) DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
