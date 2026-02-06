<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_POST['action']) || $_POST['action'] !== 'get_top_rent_customers') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

$db = Database::getInstance();

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
