<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

$action = $_POST['action'];
$db = Database::getInstance();

if ($action === 'get_customer_bills') {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $from_date = $_POST['from_date'] ?? '';
    $to_date   = $_POST['to_date'] ?? '';

    if (!$customer_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing customer ID']);
        exit;
    }

    $dateFilter = "";
    if (!empty($from_date) && !empty($to_date)) {
        $from = $db->escapeString($from_date);
        $to   = $db->escapeString($to_date);
        $dateFilter = "AND rental_date BETWEEN '$from' AND '$to'";
    }

    $sql = "SELECT id, bill_number, rental_date, (deposit_total + transport_cost) as initial_amount, remark 
            FROM equipment_rent 
            WHERE customer_id = $customer_id $dateFilter 
            ORDER BY rental_date DESC";
    
    $result = $db->readQuery($sql);
    $bills = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $rentId = (int)$row['id'];
        
        // Fetch additional and refund for this specific bill
        $extraSql = "SELECT 
                        COALESCE(SUM(err.additional_payment), 0) AS bill_additional,
                        COALESCE(SUM(err.refund_amount), 0) AS bill_refund
                     FROM equipment_rent_items eri
                     JOIN equipment_rent_returns err ON eri.id = err.rent_item_id
                     WHERE eri.rent_id = $rentId";
        
        $extraRes = mysqli_fetch_assoc($db->readQuery($extraSql));
        $additional = (float)$extraRes['bill_additional'];
        $refund = (float)$extraRes['bill_refund'];
        
        $total_amount = (float)$row['initial_amount'] + $additional - $refund;
        
        // Fetch items for this bill
        $itemsSql = "SELECT 
                        e.item_name AS item_name,
                        eri.quantity
                    FROM equipment_rent_items eri
                    JOIN equipment e ON e.id = eri.equipment_id
                    WHERE eri.rent_id = $rentId";

        $itemsRes = $db->readQuery($itemsSql);
        $items = [];
        while ($itemRow = mysqli_fetch_assoc($itemsRes)) {
            $items[] = [
                'item_name' => $itemRow['item_name'],
                'quantity' => $itemRow['quantity']
            ];
        }

        $bills[] = [
            'bill_number' => $row['bill_number'],
            'date' => $row['rental_date'],
            'amount' => $total_amount,
            'remarks' => $row['remark'],
            'items' => $items
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $bills]);
    exit;
}

if ($action !== 'get_top_rent_customers') {
    echo json_encode(['status' => 'error', 'message' => 'Unsupported action']);
    exit;
}

$from_date = $_POST['from_date'] ?? '';
$to_date   = $_POST['to_date'] ?? '';
$dateFilter = "";

if (!empty($from_date) && !empty($to_date)) {
    $from = $db->escapeString($from_date);
    $to   = $db->escapeString($to_date);
    $dateFilter = "WHERE er.rental_date BETWEEN '$from' AND '$to'";
}

$sql = "SELECT 
            cm.id AS customer_id,
            cm.code AS customer_code,
            cm.name AS customer_name,
            cm.nic AS nic,
            cm.mobile_number,
            COUNT(er.id) AS rent_count,
            MAX(er.rental_date) AS last_rental_date,
            SUM(er.deposit_total + er.transport_cost) AS base_amount
        FROM equipment_rent er
        JOIN customer_master cm ON er.customer_id = cm.id
        $dateFilter
        GROUP BY cm.id
        ORDER BY rent_count DESC, last_rental_date DESC";

$result = $db->readQuery($sql);

$data = [];
$grand_total_amount = 0;
$rank = 1;
while ($row = mysqli_fetch_assoc($result)) {
    // Calculate additional/refund per customer
    $customerId = (int) $row['customer_id'];

    $extraSql = "SELECT 
                    COALESCE(SUM(err.additional_payment), 0) AS tot_additional,
                    COALESCE(SUM(err.refund_amount), 0) AS tot_refund
                 FROM equipment_rent er2
                 JOIN equipment_rent_items eri ON er2.id = eri.rent_id
                 JOIN equipment_rent_returns err ON eri.id = err.rent_item_id
                 WHERE er2.customer_id = $customerId " . (!empty($dateFilter) ? " AND er2.rental_date BETWEEN '$from' AND '$to'" : "");

    $extraRes = mysqli_fetch_assoc($db->readQuery($extraSql));
    $additional = (float) ($extraRes['tot_additional'] ?? 0);
    $refund = (float) ($extraRes['tot_refund'] ?? 0);

    $baseAmount = (float) ($row['base_amount'] ?? 0);
    $netAmount = $baseAmount + $additional - $refund;
    $grand_total_amount += $netAmount;

    $data[] = [
        'rank' => $rank++,
        'customer_id' => $row['customer_id'],
        'customer_code' => $row['customer_code'],
        'customer_name' => $row['customer_name'],
        'nic' => $row['nic'],
        'mobile_number' => $row['mobile_number'],
        'rent_count' => (int) $row['rent_count'],
        'last_rental_date' => $row['last_rental_date'],
        'total_amount' => $netAmount
    ];
}

echo json_encode([
    'status' => 'success',
    'data' => $data,
    'grand_total_amount' => $grand_total_amount
]);
exit;
