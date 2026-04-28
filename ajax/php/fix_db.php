<?php
include '../../class/include.php';
$db = Database::getInstance();

$queries = [
    "ALTER TABLE `equipment_rent` ADD COLUMN `is_branch_exchange` TINYINT(1) DEFAULT 0 AFTER `is_cancelled` ",
    "ALTER TABLE `equipment_rent_returns` ADD COLUMN `initial_customer_paid` DECIMAL(18,2) DEFAULT 0 AFTER `customer_paid` ",
    "ALTER TABLE `equipment_rent_returns` ADD COLUMN `initial_company_refund_paid` DECIMAL(18,2) DEFAULT 0 AFTER `company_refund_paid` "
];

foreach ($queries as $query) {
    echo "Executing: $query ... ";
    try {
        $result = $db->readQuery($query);
        echo "Success\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
