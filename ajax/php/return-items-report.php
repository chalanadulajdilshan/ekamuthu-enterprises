<?php

include '../../class/include.php';

header('Content-Type: application/json; charset=UTF8');

// Get return items report
if (isset($_POST['action']) && $_POST['action'] == 'get_return_items_report') {
    $from_date = $_POST['from_date'] ?? null;
    $to_date = $_POST['to_date'] ?? null;

    if (!$from_date || !$to_date) {
        echo json_encode(['status' => 'error', 'message' => 'Date range is required']);
        exit();
    }

    $db = Database::getInstance();

    // Build query to get return items with all related data
    $query = "SELECT
                sr.return_no,
                sr.return_date,
                sr.invoice_no,
                sr.return_reason,
                sr.total_amount as return_total,
                cm.name as customer_name,
                cm.mobile_number as customer_mobile,
                sri.item_id,
                im.code as item_code,
                im.name as item_name,
                sri.quantity as return_quantity,
                sri.unit_price,
                sri.discount,
                sri.net_amount as total_amount,
                sri.remarks as item_remarks
              FROM `sales_return` sr
              LEFT JOIN `sales_return_items` sri ON sr.id = sri.return_id
              LEFT JOIN `customer_master` cm ON sr.customer_id = cm.id
              LEFT JOIN `item_master` im ON sri.item_id = im.id
              WHERE sr.return_date BETWEEN '$from_date' AND '$to_date'
              ORDER BY sr.return_date DESC, sr.return_no DESC, sri.id ASC";

    $result = $db->readQuery($query);

    $data = [];
    $total_returns = 0;
    $total_items = 0;
    $total_amount = 0;
    $total_quantity = 0;

    $unique_returns = [];

    while ($row = mysqli_fetch_array($result)) {
        $data[] = [
            'return_no' => $row['return_no'],
            'return_date' => $row['return_date'],
            'invoice_no' => $row['invoice_no'],
            'customer_name' => $row['customer_name'] . ($row['customer_mobile'] ? ' - ' . $row['customer_mobile'] : ''),
            'item_code' => $row['item_code'],
            'item_name' => $row['item_name'],
            'return_quantity' => $row['return_quantity'],
            'unit_price' => number_format($row['unit_price'], 2),
            'total_amount' => number_format($row['total_amount'], 2),
            'return_reason' => $row['return_reason'] ?: '-'
        ];

        // Track unique returns
        if (!in_array($row['return_no'], $unique_returns)) {
            $unique_returns[] = $row['return_no'];
            $total_returns++;
        }

        $total_items++;
        $total_amount += floatval($row['total_amount']);
        $total_quantity += intval($row['return_quantity']);
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'summary' => [
            'total_returns' => $total_returns,
            'total_items' => $total_items,
            'total_amount' => number_format($total_amount, 2),
            'total_quantity' => $total_quantity,
            'date_range' => $from_date . ' to ' . $to_date
        ]
    ]);
    exit();
}

?>
