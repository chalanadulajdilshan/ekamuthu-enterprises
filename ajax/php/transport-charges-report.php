<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

if (isset($_POST['action']) && $_POST['action'] == 'get_transport_charges_report') {
    $from_date = $_POST['from_date'] ?? null;
    $to_date = $_POST['to_date'] ?? null;

    $db = Database::getInstance();

    $query = "SELECT r.*, c.name as customer_name 
              FROM `equipment_rent` r 
              LEFT JOIN `customer_master` c ON r.customer_id = c.id 
              WHERE r.transport_cost > 0";

    // Date filtering (using created_at or rental_date?)
    // Using created_at based on typical report logic, or rental_date if preferred by user context.
    // Usually reports track when the record was created/rented. Let's use created_at casting to DATE.
    if ($from_date && $to_date) {
        $query .= " AND DATE(r.created_at) BETWEEN '$from_date' AND '$to_date'";
    }

    $query .= " ORDER BY r.created_at DESC";

    $result = $db->readQuery($query);

    $data = [];
    $total_transport_cost = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        
        $transport_cost = floatval($row['transport_cost']);

        // Only include if there is a transport cost? User said "view transport charges", usually implies > 0, 
        // but report might need all rents to show 0s too. Let's include all.
        
        $data[] = [
            'id' => $row['id'],
            'bill_number' => $row['bill_number'],
            'created_at' => $row['created_at'],
            'customer_name' => $row['customer_name'] ?? 'Unknown',
            'code' => $row['code'],
            'status' => ucfirst($row['status']),
            'transport_cost' => number_format($transport_cost, 2),
            'val_transport_cost' => $transport_cost
        ];

        $total_transport_cost += $transport_cost;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'summary' => [
            'total_transport_cost' => number_format($total_transport_cost, 2)
        ]
    ]);
    exit();
}
?>
