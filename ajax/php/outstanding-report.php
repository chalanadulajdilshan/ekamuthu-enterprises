<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'get_outstanding_report') {
    $customerId = isset($_POST['customer_id']) && !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

    $where = "WHERE 1=1";
    if ($customerId > 0) {
        $where .= " AND er.customer_id = $customerId";
    }

    $db = Database::getInstance();
    
    // Get Outstanding Invoices
    $query = "SELECT 
                er.id as rent_id,
                er.bill_number,
                er.rental_date,
                cm.name as customer_name,
                pt.name as payment_type_name,
                SUM(err.outstanding_amount) as total_outstanding,
                SUM(err.customer_paid) as total_paid_for_items
              FROM `equipment_rent` er
              LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
              LEFT JOIN `payment_type` pt ON er.payment_type_id = pt.id
              INNER JOIN `equipment_rent_items` eri ON er.id = eri.rent_id
              INNER JOIN `equipment_rent_returns` err ON eri.id = err.rent_item_id
              $where
              GROUP BY er.id
              HAVING total_outstanding > 0
              ORDER BY er.rental_date DESC";

    $result = $db->readQuery($query);
    
    $data = [];
    $grandTotalRent = 0;
    $grandTotalPaid = 0;
    $grandTotalBalance = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        
        $totalOutstanding = floatval($row['total_outstanding'] ?? 0);
        $totalPaidForItems = floatval($row['total_paid_for_items'] ?? 0);

        // Total Rent (Billed) = Outstanding + Paid
        $totalRent = $totalOutstanding + $totalPaidForItems;
        
        // Use the paid amount from returns as the "Total Paid" for this report context
        $totalPaid = $totalPaidForItems;

        $balance = $totalOutstanding;

        $data[] = [
            'bill_number' => $row['bill_number'],
            'rental_date' => $row['rental_date'],
            'payment_type_name' => $row['payment_type_name'] ?? 'N/A',
            'customer_name' => $row['customer_name'],
            'total_rent' => number_format($totalRent, 2),
            'total_paid' => number_format($totalPaid, 2),
            'balance' => number_format($balance, 2)
        ];
        
        $grandTotalRent += $totalRent;
        $grandTotalPaid += $totalPaid;
        $grandTotalBalance += $balance;
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
