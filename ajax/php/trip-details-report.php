<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

if (isset($_POST['action']) && $_POST['action'] == 'get_trip_details_report') {
    $from_date = !empty($_POST['fromDate']) ? $_POST['fromDate'] : null;
    $to_date = !empty($_POST['toDate']) ? $_POST['toDate'] : null;
    $vehicle_id = !empty($_POST['vehicleId']) ? (int) $_POST['vehicleId'] : null;
    $customer_id = !empty($_POST['customerId']) ? (int) $_POST['customerId'] : null;

    $data = TripManagement::getByFilters($from_date, $to_date, $vehicle_id, $customer_id);
    
    $formattedData = [];
    $total_toll = 0;
    $total_helper = 0;
    $total_cost = 0;

    foreach ($data as $row) {
        $transport_amount = floatval($row['transport_amount']);
        $toll = floatval($row['toll']);
        $helper_payment = floatval($row['helper_payment']);
        $pay_amount = floatval($row['pay_amount']);
        $row_total = floatval($row['total_cost']);

        $km = 0;
        if ($row['start_meter'] && $row['end_meter']) {
            $km = floatval($row['end_meter']) - floatval($row['start_meter']);
        }

        $formattedData[] = [
            'id' => $row['id'],
            'transport_date' => $row['transport_date'],
            'trip_number' => $row['trip_number'],
            'trip_category' => ucfirst($row['trip_category']),
            'customer' => ($row['customer_name'] ? ($row['customer_code'] . ' - ' . $row['customer_name']) : '-') . ($row['bill_number'] ? ' (Bill: ' . $row['bill_number'] . ')' : ''),
            'vehicle' => $row['vehicle_no'] ? $row['vehicle_no'] . ' (' . $row['vehicle_brand'] . ')' : '-',
            'employee' => $row['employee_name'] ?? '-',
            'start_meter' => $row['start_meter'],
            'end_meter' => $row['end_meter'] ?? '-',
            'km' => number_format($km, 2),
            'start_location' => $row['start_location'] ?? '-',
            'end_location' => $row['end_location'] ?? '-',
            'transport_amount' => number_format($transport_amount, 2),
            'toll' => number_format($toll, 2),
            'helper_payment' => number_format($helper_payment, 2),
            'pay_amount' => number_format($pay_amount, 2),
            'total_cost' => number_format($row_total, 2),
            'val_transport' => $transport_amount,
            'val_toll' => $toll,
            'val_helper' => $helper_payment,
            'val_pay_amount' => $pay_amount,
            'val_total' => $row_total
        ];

        $total_toll += $toll;
        $total_helper += $helper_payment;
        $total_cost += $row_total;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $formattedData,
        'summary' => [
            'total_toll' => number_format($total_toll, 2),
            'total_helper' => number_format($total_helper, 2),
            'total_cost' => number_format($total_cost, 2)
        ]
    ]);
    exit();
}
?>
