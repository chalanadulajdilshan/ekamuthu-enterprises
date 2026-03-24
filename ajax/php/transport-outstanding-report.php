<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'get_report') {
    $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    
    $db = Database::getInstance();
    
    // Get all unsettled credit transport details grouped by rent/bill
    $query = "SELECT 
                td.id AS transport_id,
                td.rent_id,
                td.transport_date,
                td.start_location,
                td.end_location,
                td.deliver_amount,
                td.pickup_amount,
                td.total_amount,
                td.payment_method,
                td.is_settled,
                td.remark,
                em.name AS employee_name,
                em.code AS employee_code,
                v.vehicle_no,
                v.brand AS vehicle_brand,
                r.bill_number,
                r.rental_date,
                c.name AS customer_name,
                c.code AS customer_code,
                c.id AS customer_id,
                COALESCE(ts_sum.total_settled, 0) AS total_settled,
                (td.total_amount - COALESCE(ts_sum.total_settled, 0)) AS remaining_amount
              FROM transport_details td
              LEFT JOIN employee_master em ON td.employee_id = em.id
              LEFT JOIN vehicles v ON td.vehicle_id = v.id
              LEFT JOIN equipment_rent r ON td.rent_id = r.id
              LEFT JOIN customer_master c ON r.customer_id = c.id
              LEFT JOIN (
                  SELECT transport_id, SUM(amount) AS total_settled
                  FROM transport_settlements
                  GROUP BY transport_id
              ) ts_sum ON td.id = ts_sum.transport_id
              WHERE td.payment_method = 'credit'
                AND td.is_settled = 0";
    
    if ($customerId > 0) {
        $query .= " AND r.customer_id = " . $customerId;
    }
    
    $query .= " ORDER BY c.name ASC, td.transport_date DESC";
    
    $result = $db->readQuery($query);
    $data = [];
    $totalAmount = 0;
    $totalSettled = 0;
    $totalRemaining = 0;
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $remaining = floatval($row['remaining_amount']);
            if ($remaining <= 0) continue; // Skip fully settled
            
            $totalAmount += floatval($row['total_amount']);
            $totalSettled += floatval($row['total_settled']);
            $totalRemaining += $remaining;
            
            $data[] = [
                'transport_id' => $row['transport_id'],
                'rent_id' => $row['rent_id'],
                'bill_number' => $row['bill_number'] ?? '-',
                'rental_date' => $row['rental_date'] ?? '-',
                'customer_name' => $row['customer_name'] ?? '-',
                'customer_code' => $row['customer_code'] ?? '-',
                'customer_id' => $row['customer_id'],
                'transport_date' => $row['transport_date'],
                'employee_name' => $row['employee_name'] ?? '-',
                'employee_code' => $row['employee_code'] ?? '',
                'vehicle_no' => $row['vehicle_no'] ?? '-',
                'vehicle_brand' => $row['vehicle_brand'] ?? '',
                'start_location' => $row['start_location'] ?? '-',
                'end_location' => $row['end_location'] ?? '-',
                'deliver_amount' => number_format(floatval($row['deliver_amount']), 2, '.', ''),
                'pickup_amount' => number_format(floatval($row['pickup_amount']), 2, '.', ''),
                'total_amount' => number_format(floatval($row['total_amount']), 2, '.', ''),
                'total_settled' => number_format(floatval($row['total_settled']), 2, '.', ''),
                'remaining_amount' => number_format($remaining, 2, '.', ''),
                'remark' => $row['remark'] ?? ''
            ];
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'summary' => [
            'total_amount' => number_format($totalAmount, 2, '.', ''),
            'total_settled' => number_format($totalSettled, 2, '.', ''),
            'total_remaining' => number_format($totalRemaining, 2, '.', ''),
            'total_records' => count($data)
        ]
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
