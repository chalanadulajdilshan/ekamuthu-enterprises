<?php
header('Content-Type: application/json');
require_once('../../class/Database.php');

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

    if ($action === 'get_outstanding_report') {
        $supplierId = $_POST['customer_id'] ?? '';
        $fromDate   = $_POST['from_date'] ?? '';
        $toDate     = $_POST['to_date'] ?? '';

        if ((!empty($fromDate) && empty($toDate)) || (empty($fromDate) && !empty($toDate))) {
            throw new Exception('Both from date and to date are required when filtering by date');
        }

        $db = Database::getInstance();

        // ✅ Build base query
        $query = "
            SELECT 
                arn.id AS arn_id,
                arn.arn_no,
                DATE_FORMAT(arn.invoice_date, '%Y-%m-%d') AS invoice_date,
                arn.entry_date,
                arn.total_arn_value AS invoice_amount,
                COALESCE(arn.paid_amount, 0) AS paid_amount,
                (arn.total_arn_value - COALESCE(arn.paid_amount, 0)) AS outstanding,
                c.name AS supplier_name,
                arn.supplier_id
            FROM arn_master arn
            LEFT JOIN customer_master c ON arn.supplier_id = c.id
            WHERE 
                arn.is_cancelled = 0 
                AND arn.supplier_id <> 0
        ";

        // Supplier filter - check both POST and GET for supplier_id
        $supplierId = $_POST['supplier_id'] ?? '';
        if (!empty($supplierId)) {
            $query .= " AND arn.supplier_id = " . (int)$supplierId;
        }

        // Date filter
        $fromDate = $_POST['from_date'] ?? '';
        $toDate = $_POST['to_date'] ?? '';
        
        if (!empty($fromDate) && !empty($toDate)) {
            $query .= " AND DATE(arn.entry_date) >= '" . $db->escapeString($fromDate) . " 00:00:00' ";
            $query .= " AND DATE(arn.entry_date) <= '" . $db->escapeString($toDate) . " 23:59:59' ";
        }

        // ✅ Order by invoice_date DESC
        $query .= " ORDER BY arn.invoice_date DESC";

        $result = $db->readQuery($query);
        if (!$result) {
            throw new Exception('Error executing query: ' . mysqli_error($db->DB_CON));
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'arn_id'         => (int)$row['arn_id'],
                'arn_no'         => $row['arn_no'],
                'invoice_date'   => $row['invoice_date'],
                'entry_date'     => $row['entry_date'],
                'supplier_name'  => $row['supplier_name'],
                'invoice_amount' => (float)$row['invoice_amount'],
                'paid_amount'    => (float)$row['paid_amount'],
                'outstanding'    => (float)$row['outstanding']
            ];
        }

        $response = [
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $data
        ];
    } else {
        throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => []
    ];
}

echo json_encode($response);
