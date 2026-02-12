<?php
/**
 * Migration: Add customer_paid and outstanding_amount columns
 * Run once via browser: http://localhost/ekamuthu-enterprises/sql/migrate_outstanding.php
 */
require_once __DIR__ . '/../class/Database.php';

$db = Database::getInstance();

$queries = [
    "ALTER TABLE `equipment_rent_returns` ADD COLUMN `customer_paid` DECIMAL(12,2) DEFAULT 0 AFTER `additional_payment`",
    "ALTER TABLE `equipment_rent_returns` ADD COLUMN `outstanding_amount` DECIMAL(12,2) DEFAULT 0 AFTER `customer_paid`",
    "ALTER TABLE `customer_master` ADD COLUMN `rent_outstanding` DECIMAL(12,2) DEFAULT 0 AFTER `old_outstanding`",
];

echo "<h3>Running Outstanding Migration</h3>";
foreach ($queries as $q) {
    $result = mysqli_query($db->DB_CON, $q);
    if ($result) {
        echo "<p style='color:green'>✅ OK: " . htmlspecialchars(substr($q, 0, 80)) . "...</p>";
    } else {
        $err = mysqli_error($db->DB_CON);
        if (strpos($err, 'Duplicate column') !== false) {
            echo "<p style='color:orange'>⚠️ Column already exists: " . htmlspecialchars(substr($q, 0, 80)) . "...</p>";
        } else {
            echo "<p style='color:red'>❌ Error: $err<br>Query: " . htmlspecialchars($q) . "</p>";
        }
    }
}
echo "<h4>Migration complete!</h4>";
