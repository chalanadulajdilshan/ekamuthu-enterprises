-- Stores the date/time the rent invoice and the return invoice were first printed
ALTER TABLE `equipment_rent`
ADD COLUMN `printed_at` DATETIME NULL DEFAULT NULL AFTER `created_at`,
ADD COLUMN `return_printed_at` DATETIME NULL DEFAULT NULL AFTER `printed_at`;
