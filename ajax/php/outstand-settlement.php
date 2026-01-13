<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'get_settlement_data') {
    $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $fromDate = $_POST['from_date'] ?? '';
    $toDate = $_POST['to_date'] ?? '';
    $status = $_POST['status'] ?? 'all'; // all | settled | unsettled

    $db = Database::getInstance();
    $conn = $db->DB_CON;

    $conditions = [];

    // Only add customer filter if a customer is selected
    if ($customerId > 0) {
        $conditions[] = "pr.customer_id = " . intval($customerId);
    }

    // Date filter on receipt entry date
    if (!empty($fromDate) && !empty($toDate)) {
        $from = mysqli_real_escape_string($conn, $fromDate);
        $to = mysqli_real_escape_string($conn, $toDate);
        $conditions[] = "DATE(pr.entry_date) BETWEEN '$from' AND '$to'";
    } elseif (!empty($fromDate)) {
        $from = mysqli_real_escape_string($conn, $fromDate);
        $conditions[] = "DATE(pr.entry_date) >= '$from'";
    } elseif (!empty($toDate)) {
        $to = mysqli_real_escape_string($conn, $toDate);
        $conditions[] = "DATE(pr.entry_date) <= '$to'";
    }

    // Status filter using prm.is_settle
    if ($status === 'settled') {
        $conditions[] = "prm.is_settle = 1";
    } elseif ($status === 'unsettled') {
        $conditions[] = "(prm.is_settle = 0 OR prm.is_settle IS NULL)";
    }

    $where = count($conditions) ? ('WHERE ' . implode(' AND ', $conditions)) : '';

    // Build base query for invoices with payment info
    $invoiceSql = "SELECT 
                    si.id as invoice_id,
                    si.invoice_no,
                    si.invoice_date,
                    si.grand_total as invoice_amount,
                    (SELECT MAX(pr.entry_date) 
                     FROM payment_receipt pr 
                     INNER JOIN payment_receipt_method prm ON pr.id = prm.receipt_id 
                     WHERE prm.invoice_id = si.id) as last_payment_date,
                    CASE 
                        WHEN (SELECT MAX(pr.entry_date) 
                              FROM payment_receipt pr 
                              INNER JOIN payment_receipt_method prm ON pr.id = prm.receipt_id 
                              WHERE prm.invoice_id = si.id) IS NOT NULL
                        THEN DATEDIFF(
                            (SELECT MAX(pr.entry_date) 
                             FROM payment_receipt pr 
                             INNER JOIN payment_receipt_method prm ON pr.id = prm.receipt_id 
                             WHERE prm.invoice_id = si.id),
                            si.invoice_date
                        )
                        ELSE 0
                    END as days_between
                FROM sales_invoice si
                LEFT JOIN payment_receipt_method prm ON prm.invoice_id = si.id
                LEFT JOIN payment_receipt pr ON pr.id = prm.receipt_id
                WHERE si.sale_type = 2";

    // Add customer filter to the main query if a customer is selected
    if ($customerId > 0) {
        $invoiceSql .= " AND si.customer_id = " . intval($customerId);
    }

    // Add date range filter
    if (!empty($fromDate) && !empty($toDate)) {
        $invoiceSql .= " AND DATE(si.invoice_date) BETWEEN '" . mysqli_real_escape_string($conn, $fromDate) . "' AND '" . mysqli_real_escape_string($conn, $toDate) . "'";
    } elseif (!empty($fromDate)) {
        $invoiceSql .= " AND DATE(si.invoice_date) >= '" . mysqli_real_escape_string($conn, $fromDate) . "'";
    } elseif (!empty($toDate)) {
        $invoiceSql .= " AND DATE(si.invoice_date) <= '" . mysqli_real_escape_string($conn, $toDate) . "'";
    }

    // Add status filter
    if ($status === 'settled') {
        $invoiceSql .= " AND (SELECT COALESCE(SUM(prm.amount), 0) FROM payment_receipt_method prm 
                           INNER JOIN payment_receipt pr ON pr.id = prm.receipt_id 
                           WHERE prm.invoice_id = si.id) >= si.grand_total";
    } elseif ($status === 'unsettled') {
        $invoiceSql .= " AND (SELECT COALESCE(SUM(prm.amount), 0) FROM payment_receipt_method prm 
                           INNER JOIN payment_receipt pr ON pr.id = prm.receipt_id 
                           WHERE prm.invoice_id = si.id) < si.grand_total";
    }

    // Group by and order
    $invoiceSql .= " GROUP BY si.id ORDER BY si.invoice_date DESC, si.id DESC";

    $invoiceResult = $db->readQuery($invoiceSql);
    $invoices = [];
    $allInvoiceIds = [];

    while ($invoice = mysqli_fetch_assoc($invoiceResult)) {
        $invoiceId = $invoice['invoice_id'];
        $invoices[$invoiceId] = [
            'invoice_no' => $invoice['invoice_no'],
            'invoice_date' => $invoice['invoice_date'],
            'invoice_amount' => (float)$invoice['invoice_amount'],
            'last_payment_date' => $invoice['last_payment_date'],
            'days_between' => (int)$invoice['days_between'],
            'receipts' => [],
            'total_receipts' => 0
        ];
        $allInvoiceIds[] = $invoiceId;
    }

    // If no invoices found, return empty result
    if (empty($allInvoiceIds)) {
        echo json_encode([
            'status' => 'success',
            'invoices' => [],
            'summary' => [
                'invoice_nos' => 'N/A',
                'first_payment_date' => null,
                'last_settle_date' => null,
                'days_between' => null
            ]
        ]);
        exit;
    }

    // Now get all receipts for these invoices with date filtering
    $invoiceIdsStr = !empty($allInvoiceIds) ? implode(',', $allInvoiceIds) : '0';

    $receiptSql = "SELECT 
                    prm.id as method_id,
                    pr.id as receipt_id,
                    pr.receipt_no,
                    pr.entry_date as receipt_date,
                    prm.amount,
                    prm.is_settle,
                    prm.invoice_id,
                    si.invoice_no,
                    pt.name as payment_method
                FROM payment_receipt_method prm
                INNER JOIN payment_receipt pr ON pr.id = prm.receipt_id
                INNER JOIN sales_invoice si ON si.id = prm.invoice_id
                LEFT JOIN payment_type pt ON pt.id = prm.payment_type_id
                WHERE prm.invoice_id IN ($invoiceIdsStr)";

    // Apply date filter to receipts as well
    if (!empty($fromDate) && !empty($toDate)) {
        $receiptSql .= " AND DATE(pr.entry_date) BETWEEN '" . mysqli_real_escape_string($conn, $fromDate) . "' AND '" . mysqli_real_escape_string($conn, $toDate) . "'";
    } elseif (!empty($fromDate)) {
        $receiptSql .= " AND DATE(pr.entry_date) >= '" . mysqli_real_escape_string($conn, $fromDate) . "'";
    } elseif (!empty($toDate)) {
        $receiptSql .= " AND DATE(pr.entry_date) <= '" . mysqli_real_escape_string($conn, $toDate) . "'";
    }

    $receiptSql .= " ORDER BY pr.entry_date ASC, pr.id ASC, prm.id ASC";

    $receiptResult = $db->readQuery($receiptSql);
    $invoiceNos = [];
    $firstPaymentDate = null;
    $lastSettleDate = null;

    while ($row = mysqli_fetch_assoc($receiptResult)) {
        $invoiceId = $row['invoice_id'];
        $receiptDate = date('Y-m-d', strtotime($row['receipt_date']));
        $amount = (float)$row['amount'];

        // Add receipt to its invoice
        $paymentMethod = $row['payment_method'] ?? 'N/A';

        // Map payment method names for better display
        if (strtolower($paymentMethod) === 'credit card') {
            $paymentMethod = 'Cheque';
        }

        $invoices[$invoiceId]['receipts'][] = [
            'receipt_no' => $row['receipt_no'],
            'receipt_date' => $receiptDate,
            'amount' => $amount,
            'payment_method' => $paymentMethod
        ];

        // Update invoice total receipts
        $invoices[$invoiceId]['total_receipts'] += $amount;

        // Track invoice numbers
        if (!empty($row['invoice_no'])) {
            $invoiceNos[$row['invoice_no']] = true;
        }

        // Track first payment date
        if ($firstPaymentDate === null || $receiptDate < $firstPaymentDate) {
            $firstPaymentDate = $receiptDate;
        }

        // Track last settle date
        if ((int)$row['is_settle'] === 1) {
            if ($lastSettleDate === null || $receiptDate > $lastSettleDate) {
                $lastSettleDate = $receiptDate;
            }
        }
    }

    // Compute days between first payment and last settle (if available)
    $daysBetween = null;
    if ($firstPaymentDate && $lastSettleDate) {
        $dt1 = new DateTime($firstPaymentDate);
        $dt2 = new DateTime($lastSettleDate);
        $diff = $dt1->diff($dt2);
        $daysBetween = (int)$diff->format('%a');
    }

    // Convert invoices to indexed array for JSON
    $invoicesArray = array_values($invoices);

    $summary = [
        'invoice_nos' => !empty($invoiceNos) ? implode(', ', array_keys($invoiceNos)) : 'N/A',
        'first_payment_date' => $firstPaymentDate ?? '-',
        'last_settle_date' => $lastSettleDate ?? '-',
        'days_between' => $daysBetween !== null ? $daysBetween : '-',
    ];

    echo json_encode([
        'status' => 'success',
        'invoices' => $invoicesArray,
        'summary' => $summary
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
