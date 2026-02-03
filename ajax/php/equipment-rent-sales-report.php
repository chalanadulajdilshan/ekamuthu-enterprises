<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

if (isset($_POST['action']) && $_POST['action'] == 'get_sales_report') {
    $from_date = $_POST['from_date'] ?? date('Y-m-d');
    $to_date = $_POST['to_date'] ?? date('Y-m-d');

    $db = Database::getInstance();

    // Query to get rentals within the date range
    // We filter by rental_date (Date assignment)
    $query = "SELECT r.*, c.name as customer_name, c.mobile_number 
              FROM `equipment_rent` r 
              LEFT JOIN `customer_master` c ON r.customer_id = c.id 
              WHERE r.rental_date BETWEEN '$from_date' AND '$to_date' 
              ORDER BY r.rental_date DESC, r.id DESC";

    $result = $db->readQuery($query);

    $data = [];
    
    $total_deposit = 0;
    $total_transport = 0;
    $total_additional = 0;
    $total_refund = 0;
    $total_revenue = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        
        $rent_id = $row['id'];
        $deposit = floatval($row['deposit_total']);
        $transport = floatval($row['transport_cost']);
        
        // Get totals from returns for this rent
        // We join through items to get to returns
        $qReturns = "SELECT 
                        COALESCE(SUM(err.additional_payment), 0) as tot_additional,
                        COALESCE(SUM(err.refund_amount), 0) as tot_refund,
                        COALESCE(SUM(err.damage_amount), 0) as tot_damage
                     FROM equipment_rent_items eri
                     JOIN equipment_rent_returns err ON eri.id = err.rent_item_id
                     WHERE eri.rent_id = $rent_id";
        
        $resReturns = mysqli_fetch_assoc($db->readQuery($qReturns));
        
        $additional = floatval($resReturns['tot_additional']);
        $refund = floatval($resReturns['tot_refund']);
        $damage = floatval($resReturns['tot_damage']);
        
        // Revenue Calculation
        // Net Revenue = Deposit + Transport + Additional - Refund
        $net_revenue = $deposit + $transport + $additional - $refund;
        
        // Get basic equipment names for display (comma separated)
        $qItems = "SELECT GROUP_CONCAT(DISTINCT e.item_name SEPARATOR ', ') as items
                   FROM equipment_rent_items eri
                   JOIN equipment e ON eri.equipment_id = e.id
                   WHERE eri.rent_id = $rent_id";
        $resItems = mysqli_fetch_assoc($db->readQuery($qItems));
        $item_names = $resItems['items'] ?? '';

        $data[] = [
            'id' => $row['id'],
            'bill_number' => $row['bill_number'],
            'date' => $row['rental_date'],
            'customer_name' => $row['customer_name'] . ' (' . $row['mobile_number'] . ')',
            'items' => $item_names,
            'status' => ucfirst($row['status']),
            'deposit' => number_format($deposit, 2),
            'transport' => number_format($transport, 2),
            'additional' => number_format($additional, 2),
            'refund' => number_format($refund, 2),
            'damage' => number_format($damage, 2), // Included for info, part of revenue logic implicitly
            'revenue' => number_format($net_revenue, 2),
            
            // Raw values for summing
            'val_deposit' => $deposit,
            'val_transport' => $transport,
            'val_additional' => $additional,
            'val_refund' => $refund,
            'val_revenue' => $net_revenue
        ];

        $total_deposit += $deposit;
        $total_transport += $transport;
        $total_additional += $additional;
        $total_refund += $refund;
        $total_revenue += $net_revenue;
    }

    // Calculate Total Items Rented (Quantity)
    $qTotalItems = "SELECT SUM(eri.quantity) as total_qty 
                    FROM equipment_rent_items eri 
                    JOIN equipment_rent er ON eri.rent_id = er.id 
                    WHERE er.rental_date BETWEEN '$from_date' AND '$to_date'";
    $resTotalItems = mysqli_fetch_assoc($db->readQuery($qTotalItems));
    $total_items_count = $resTotalItems['total_qty'] ?? 0;

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'summary' => [
            'total_deposit' => number_format($total_deposit, 2),
            'total_transport' => number_format($total_transport, 2),
            'total_additional' => number_format($total_additional, 2),
            'total_refund' => number_format($total_refund, 2),
            'total_revenue' => number_format($total_revenue, 2),
            'total_items_count' => (int)$total_items_count
        ]
    ]);
    exit();
}
?>
