<?php
header('Content-Type: application/json');
require_once('../../class/include.php');

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => [],
    'summary' => []
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'fetch_supplier_invoice_report') {
        $supplierId = $_POST['supplier_id'] ?? '';
        $fromDate   = $_POST['from_date'] ?? '';
        $toDate     = $_POST['to_date'] ?? '';

        $db = Database::getInstance();

        // 1. Build Base Query for Invoices
        $query = "
            SELECT 
                si.id,
                si.grn_number,
                si.invoice_no,
                DATE_FORMAT(si.invoice_date, '%Y-%m-%d') AS invoice_date,
                si.supplier_id,
                sm.name AS supplier_name,
                sm.code AS supplier_code,
                si.payment_type,
                si.grand_total,
                (SELECT SUM(amount) FROM payment_receipt_method_supplier WHERE invoice_id = si.id AND status = 1) as paid_amount,
                si.status
            FROM supplier_invoices si
            LEFT JOIN supplier_master sm ON si.supplier_id = sm.id
            WHERE 1=1
        ";

        if (!empty($supplierId)) {
            $query .= " AND si.supplier_id = " . (int)$supplierId;
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $query .= " AND si.invoice_date BETWEEN '" . $db->escapeString($fromDate) . "' AND '" . $db->escapeString($toDate) . "'";
        }

        $query .= " ORDER BY si.invoice_date DESC, si.id DESC";

        $result = $db->readQuery($query);
        $data = [];
        $totalPurchases = 0;
        $totalPaid = 0;
        $totalOutstanding = 0;
        $cashPurchases = 0;
        $creditPurchases = 0;
        $chequePurchases = 0;

        while ($row = mysqli_fetch_assoc($result)) {
            $grand_total = (float)$row['grand_total'];
            $paid = (float)($row['paid_amount'] ?? 0);
            
            // If payment type is cash/cheque, we assume it's fully paid if status is processed, 
            // but for details we use the actual payment receipt records if available.
            // In some implementations, grand_total is the amount.
            
            $outstanding = $grand_total - $paid;
            if ($row['payment_type'] !== 'credit' && $paid == 0) {
                 // Fallback if no payment records yet but it's cash/cheque (depending on system logic)
                 // For now, let's stick to actual records.
            }

            $row['grand_total'] = $grand_total;
            $row['paid_amount'] = $paid;
            $row['outstanding'] = $outstanding;

            $data[] = $row;

            $totalPurchases += $grand_total;
            $totalPaid += $paid;
            $totalOutstanding += $outstanding;

            if ($row['payment_type'] == 'cash') $cashPurchases += $grand_total;
            elseif ($row['payment_type'] == 'credit') $creditPurchases += $grand_total;
            elseif ($row['payment_type'] == 'cheque') $chequePurchases += $grand_total;
        }

        // 2. Fetch Top Suppliers in this period
        $topSuppliersQuery = "
            SELECT sm.name, SUM(si.grand_total) as total
            FROM supplier_invoices si
            LEFT JOIN supplier_master sm ON si.supplier_id = sm.id
            WHERE 1=1
        ";
        if (!empty($fromDate) && !empty($toDate)) {
            $topSuppliersQuery .= " AND si.invoice_date BETWEEN '" . $db->escapeString($fromDate) . "' AND '" . $db->escapeString($toDate) . "'";
        }
        $topSuppliersQuery .= " GROUP BY si.supplier_id ORDER BY total DESC LIMIT 5";
        
        $topSuppliersRes = $db->readQuery($topSuppliersQuery);
        $topSuppliers = [];
        while($ts = mysqli_fetch_assoc($topSuppliersRes)) {
            $topSuppliers[] = $ts;
        }

        // 3. Purchases by Month (Last 12 months or selected range)
        $monthlyQuery = "
            SELECT DATE_FORMAT(si.invoice_date, '%b %Y') as month, SUM(si.grand_total) as total
            FROM supplier_invoices si
            WHERE 1=1
        ";
        if (!empty($fromDate) && !empty($toDate)) {
            $monthlyQuery .= " AND si.invoice_date BETWEEN '" . $db->escapeString($fromDate) . "' AND '" . $db->escapeString($toDate) . "'";
        }
        $monthlyQuery .= " GROUP BY DATE_FORMAT(si.invoice_date, '%Y-%m') ORDER BY si.invoice_date ASC";
        
        $monthlyRes = $db->readQuery($monthlyQuery);
        $monthlyTrends = [];
        while($m = mysqli_fetch_assoc($monthlyRes)) {
            $monthlyTrends[] = $m;
        }

        // 4. Top Purchased Items
        $topItemsQuery = "
            SELECT sii.item_name, SUM(sii.amount) as total_spent, SUM(sii.quantity) as total_qty
            FROM supplier_invoice_items sii
            JOIN supplier_invoices si ON sii.supplier_invoice_id = si.id
            WHERE 1=1
        ";
        if (!empty($fromDate) && !empty($toDate)) {
            $topItemsQuery .= " AND si.invoice_date BETWEEN '" . $db->escapeString($fromDate) . "' AND '" . $db->escapeString($toDate) . "'";
        }
        $topItemsQuery .= " GROUP BY sii.item_id ORDER BY total_spent DESC LIMIT 5";
        
        $topItemsRes = $db->readQuery($topItemsQuery);
        $topItems = [];
        while($ti = mysqli_fetch_assoc($topItemsRes)) {
            $topItems[] = $ti;
        }

        $response = [
            'status' => 'success',
            'data' => $data,
            'summary' => [
                'total_purchases' => $totalPurchases,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOutstanding,
                'cash_purchases' => $cashPurchases,
                'credit_purchases' => $creditPurchases,
                'cheque_purchases' => $chequePurchases,
                'count' => count($data),
                'top_suppliers' => $topSuppliers,
                'monthly_trends' => $monthlyTrends,
                'top_items' => $topItems
            ]
        ];

    } elseif ($action === 'get_invoice_items') {
        $invoiceId = $_POST['id'] ?? 0;
        $items = SupplierInvoiceItem::getByInvoiceId($invoiceId);
        $response = [
            'status' => 'success',
            'data' => $items
        ];
    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
