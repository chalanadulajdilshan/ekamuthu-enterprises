<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'get_outstanding_report') {
    $customerId = isset($_POST['customer_id']) && !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

    $where = '';
    if ($customerId > 0) {
        $where = "WHERE `id` = $customerId";
    }

    $db = Database::getInstance();
    
    // Get customers first
    $query = "SELECT `id`, `name` FROM `customer_master` $where ORDER BY `name` ASC";
    $result = $db->readQuery($query);
    
    $data = [];
    $grandTotalRent = 0;
    $grandTotalPaid = 0;
    $grandTotalBalance = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $cusId = $row['id'];
        
        // 1. Calculate Totals from Equipment Rent Returns (The source of truth for outstanding)
        // We join returns -> items -> rent to filter by customer
        // We sum outstanding_amount and customer_paid from returns
        
        $summaryQuery = "SELECT 
                            SUM(err.outstanding_amount) as total_outstanding,
                            SUM(err.customer_paid) as total_paid_for_items
                         FROM `equipment_rent_returns` err
                         INNER JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
                         INNER JOIN `equipment_rent` er ON eri.rent_id = er.id
                         WHERE er.customer_id = '$cusId'";
        
        $summaryResult = $db->readQuery($summaryQuery);
        $summaryRow = mysqli_fetch_assoc($summaryResult);
        
        $totalOutstanding = floatval($summaryRow['total_outstanding'] ?? 0);
        $totalPaidForItems = floatval($summaryRow['total_paid_for_items'] ?? 0);

        // Total Rent (Billed) = Outstanding + Paid
        $totalRent = $totalOutstanding + $totalPaidForItems;
        
        // Use the paid amount from returns as the "Total Paid" for this report context
        $totalPaid = $totalPaidForItems;

        $balance = $totalOutstanding;

        // Show the customer only when they currently have an outstanding balance
        if ($balance > 0) {
            $data[] = [
                'customer_name' => $row['name'],
                'total_rent' => number_format($totalRent, 2),
                'total_paid' => number_format($totalPaid, 2),
                'balance' => number_format($balance, 2)
            ];
            
            $grandTotalRent += $totalRent;
            $grandTotalPaid += $totalPaid;
            $grandTotalBalance += $balance;
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'grand_total_rent' => number_format($grandTotalRent, 2),
        'grand_total_paid' => number_format($grandTotalPaid, 2),
        'grand_total_balance' => number_format($grandTotalBalance, 2)
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
