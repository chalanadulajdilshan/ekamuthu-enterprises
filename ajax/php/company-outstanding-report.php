<?php
header('Content-Type: application/json');

require_once('../../class/include.php');

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $action = $_POST['action'] ?? '';

    if ($action !== 'get_company_outstanding') {
        throw new Exception('Invalid action');
    }

    $fromDate = $_POST['from_date'] ?? '';
    $toDate = $_POST['to_date'] ?? '';
    $billSearch = trim($_POST['bill_no'] ?? '');
    $companyOnly = ($_POST['company_only'] ?? '1') === '1';

    // If one date is supplied, require both
    if ((!empty($fromDate) && empty($toDate)) || (empty($fromDate) && !empty($toDate))) {
        throw new Exception('Both from date and to date are required when filtering by date');
    }

    $db = Database::getInstance();

    $whereParts = ["er.status = 'returned'"];

    if (!empty($fromDate) && !empty($toDate)) {
        $fromSafe = $db->escapeString($fromDate);
        $toSafe = $db->escapeString($toDate);
        $whereParts[] = "er.rental_date BETWEEN '{$fromSafe} 00:00:00' AND '{$toSafe} 23:59:59'";
    }

    if (!empty($billSearch)) {
        $billSafe = mysqli_real_escape_string($db->DB_CON, $billSearch);
        $whereParts[] = "er.bill_number LIKE '%{$billSafe}%'";
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);

    $query = "SELECT 
                er.id,
                er.bill_number,
                er.rental_date,
                er.received_date,
                er.status,
                er.is_cancelled,
                er.total_items,
                cm.name AS customer_name,
                cm.code AS customer_code,
                (
                    SELECT COALESCE(SUM(err.company_outstanding), 0)
                    FROM equipment_rent_returns err
                    INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                    WHERE eri.rent_id = er.id
                ) AS company_outstanding_total
            FROM equipment_rent er
            LEFT JOIN customer_master cm ON er.customer_id = cm.id
            {$whereSql}
            ORDER BY er.rental_date DESC";

    $result = $db->readQuery($query);
    if (!$result) {
        throw new Exception('Error executing query');
    }

    $data = [];
    $totalOutstanding = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $companyOutstanding = (float) ($row['company_outstanding_total'] ?? 0);
        if ($companyOnly && $companyOutstanding <= 0) {
            continue;
        }

        $totalOutstanding += $companyOutstanding;

        $data[] = [
            'id' => (int) $row['id'],
            'bill_number' => $row['bill_number'],
            'customer' => trim(($row['customer_code'] ?? '') . ' - ' . ($row['customer_name'] ?? '')),
            'rental_date' => $row['rental_date'],
            'received_date' => $row['received_date'],
            'items' => (int) ($row['total_items'] ?? 0),
            'status' => $row['status'],
            'is_cancelled' => (int) ($row['is_cancelled'] ?? 0),
            'company_outstanding' => $companyOutstanding
        ];
    }

    $response = [
        'status' => 'success',
        'message' => 'Data retrieved successfully',
        'data' => $data,
        'totals' => [
            'company_outstanding' => round($totalOutstanding, 2)
        ]
    ];
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => []
    ];
}

echo json_encode($response);
