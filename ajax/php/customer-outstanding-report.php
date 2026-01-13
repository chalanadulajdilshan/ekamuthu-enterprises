<?php
header('Content-Type: application/json');
require_once('../../class/Database.php');
require_once('../../class/InvoicePayments.php');

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get the action
    $action = $_POST['action'] ?? '';

    if ($action === 'get_outstanding_report') {
        $customerId = $_POST['customer_id'] ?? '';
        $fromDate = $_POST['from_date'] ?? '';
        $toDate = $_POST['to_date'] ?? '';

        // Validate that if dates are provided, both from and to dates must be present
        if ((!empty($fromDate) && empty($toDate)) || (empty($fromDate) && !empty($toDate))) {
            throw new Exception('Both from date and to date are required when filtering by date');
        }

        $db = Database::getInstance();

        // Build the base query for sales invoices with old outstanding
        $query = "SELECT
                    si.invoice_no,
                    si.invoice_date,
                    si.customer_name,
                    si.grand_total as invoice_amount,
                    COALESCE(si.outstanding_settle_amount, 0) as paid_amount,
                    (si.grand_total - COALESCE(si.outstanding_settle_amount, 0)) as outstanding,
                    si.due_date,
                    DATEDIFF(si.due_date, CURDATE()) as days_until_due,
                    cm.mobile_number,
                    cm.old_outstanding
                  FROM
                    sales_invoice si
                  LEFT JOIN
                    customer_master cm ON si.customer_id = cm.id
                  WHERE
                    si.status = 'active' AND
                    si.grand_total > 0 AND
                    si.is_cancel = 0 AND
                    si.payment_type = 2";  // Only show credit invoices (payment_type = 2)

        // Add conditions based on provided filters
        if (!empty($customerId)) {
            $query .= " AND si.customer_id = " . (int)$customerId;
        }

        // Add date range filter if both dates are provided
        if (!empty($fromDate) && !empty($toDate)) {
            $query .= " AND si.invoice_date BETWEEN '" . $db->escapeString($fromDate) . " 00:00:00' AND '" . $db->escapeString($toDate) . " 23:59:59'";
        }

        $query .= " ORDER BY si.invoice_date DESC"; // Add sorting by date

        $result = $db->readQuery($query);
        if (!$result) {
            throw new Exception('Error executing query: ' . mysqli_error($db->DB_CON));
        }
        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            // Add invoice data to results
            $data[] = [
                'invoice_no' => $row['invoice_no'],
                'invoice_date' => $row['invoice_date'],
                'customer_name' => $row['customer_name'],
                'mobile_number' => $row['mobile_number'],
                'invoice_amount' => (float)$row['invoice_amount'],
                'paid_amount' => (float)$row['paid_amount'],
                'outstanding' => (float)$row['outstanding'],
                'old_outstanding' => (float)$row['old_outstanding'],
                'due_date' => $row['due_date'],
                'days_until_due' => (int)$row['days_until_due']
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
