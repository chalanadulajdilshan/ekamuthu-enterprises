-- =============================================
-- CLEANUP: Remove old Transport Details system
-- =============================================
-- This script removes the old transport_details and transport_settlements
-- tables, as all transport management now goes through trip_management.
-- 
-- ⚠️  WARNING: Back up your database before running this!
-- ⚠️  Make sure all existing transport data has been migrated to trip_management first.
-- =============================================

-- Step 1: Drop the transport_settlements table (child table first)
DROP TABLE IF EXISTS `transport_settlements`;

-- Step 2: Drop the transport_details table
DROP TABLE IF EXISTS `transport_details`;

-- Step 3: Remove transport_cost column from equipment_rent
-- (Transport cost is now calculated from trip_management.total_cost)
-- ALTER TABLE `equipment_rent` DROP COLUMN `transport_cost`;
-- NOTE: Keeping transport_cost column for now as it stores the computed value.
-- Uncomment above line only if you want to fully remove it.

-- =============================================
-- VERIFICATION QUERIES (run these first to check data)
-- =============================================

-- Check if any transport_details data exists
-- SELECT COUNT(*) AS transport_details_count FROM transport_details;

-- Check if any transport_settlements data exists  
-- SELECT COUNT(*) AS transport_settlements_count FROM transport_settlements;

-- Check trips linked to bills (these replace transport_details)
-- SELECT tm.trip_number, tm.total_cost, er.bill_number 
-- FROM trip_management tm 
-- JOIN equipment_rent er ON tm.bill_id = er.id 
-- ORDER BY tm.id DESC;
