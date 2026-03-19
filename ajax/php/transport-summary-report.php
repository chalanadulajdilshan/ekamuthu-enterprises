<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

if (isset($_POST['action']) && $_POST['action'] == 'get_transport_summary_report') {
    $from_date = $_POST['fromDate'] ?? date('Y-m-01');
    $to_date = $_POST['toDate'] ?? date('Y-m-d');
    $employee_id = !empty($_POST['employeeId']) ? (int) $_POST['employeeId'] : null;

    $data = TransportDetail::getByDateRange($from_date, $to_date, $employee_id);
    
    $formattedData = [];
    $total_deliver = 0;
    $total_pickup = 0;
    $total_amount = 0;

    foreach ($data as $row) {
        $deliver_amount = floatval($row['deliver_amount']);
        $pickup_amount = floatval($row['pickup_amount']);
        $row_total = floatval($row['total_amount']);

        $formattedData[] = [
            'id' => $row['id'],
            'transport_date' => $row['transport_date'],
            'bill_number' => $row['bill_number'] ?? '-',
            'vehicle' => $row['vehicle_no'] ? $row['vehicle_no'] . ' (' . $row['vehicle_brand'] . ' ' . $row['vehicle_model'] . ')' : '-',
            'employee' => $row['employee_name'] ?? '-',
            'start_location' => $row['start_location'] ?? '-',
            'end_location' => $row['end_location'] ?? '-',
            'deliver_amount' => number_format($deliver_amount, 2),
            'pickup_amount' => number_format($pickup_amount, 2),
            'total_amount' => number_format($row_total, 2),
            'val_deliver' => $deliver_amount,
            'val_pickup' => $pickup_amount,
            'val_total' => $row_total
        ];

        $total_deliver += $deliver_amount;
        $total_pickup += $pickup_amount;
        $total_amount += $row_total;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $formattedData,
        'summary' => [
            'total_deliver' => number_format($total_deliver, 2),
            'total_pickup' => number_format($total_pickup, 2),
            'total_amount' => number_format($total_amount, 2)
        ]
    ]);
    exit();
}
?>
