<?php
include '../../class/include.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

if (isset($_POST['action'])) {
    
    // Get all blacklisted customers
    if ($_POST['action'] == 'get_blacklisted_customers') {
        
        $db = Database::getInstance();
        $query = "SELECT * FROM `customer_master` WHERE `is_blacklisted` = 1 ORDER BY `name` ASC";
        $result = $db->readQuery($query);
        
        $data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'id' => $row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'mobile_number' => $row['mobile_number'],
                'nic' => $row['nic'],
                'blacklist_reason' => $row['blacklist_reason']
            ];
        }
        
        echo json_encode(['data' => $data]);
        exit;
    }
    
    // Get outstanding rentals for a customer
    if ($_POST['action'] == 'get_customer_rentals') {
        
        $customer_id = $_POST['customer_id'];
        
        $db = Database::getInstance();
        
        // Query to get rentals that are RENTED or have outstanding items
        // We join with items to be precise if needed, but status 'rented' on rent table usually implies active.
        // Or checking outstanding item count.
        
        // Just checking main status 'rented' is simplest first step. 
        // Or if we want strictly "outstanding rent invoices", status != 'returned' is safer?
        
        $query = "SELECT * FROM `equipment_rent` 
                  WHERE `customer_id` = '$customer_id' 
                  AND `status` = 'rented' 
                  ORDER BY `rental_date` DESC";
                  
        $result = $db->readQuery($query);
        
        $data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            
            // Get item count
            $itemCountSql = "SELECT COUNT(*) as cnt FROM equipment_rent_items WHERE rent_id = " . $row['id'];
            $itemCountResult = mysqli_fetch_assoc($db->readQuery($itemCountSql));
            $totalItems = $itemCountResult['cnt'] ?? 0;
            
             // Get outstanding item count
            $outstandingCountSql = "SELECT COUNT(*) as cnt FROM equipment_rent_items WHERE rent_id = " . $row['id'] . " AND status = 'rented'";
            $outstandingCountResult = mysqli_fetch_assoc($db->readQuery($outstandingCountSql));
            $outstandingItems = $outstandingCountResult['cnt'] ?? 0;
            
            $data[] = [
                'id' => $row['id'],
                'bill_number' => $row['bill_number'],
                'rental_date' => $row['rental_date'],
                'total_items' => $totalItems,
                'outstanding_items' => $outstandingItems,
                'deposit_total' => number_format($row['deposit_total'], 2),
                'status' => ucfirst($row['status'])
            ];
        }
        
        echo json_encode(['data' => $data]);
        exit;
    }
}
?>
