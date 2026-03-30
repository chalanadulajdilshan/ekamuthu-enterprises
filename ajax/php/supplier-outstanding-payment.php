<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Get outstanding supplier invoices
if ($action === 'get_outstanding_invoices') {
    $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
    $invoiceNumber = isset($_POST['invoice_number']) ? trim($_POST['invoice_number']) : '';
    
    if ($supplierId <= 0 && empty($invoiceNumber)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
        exit;
    }

    $db = Database::getInstance();
    $where = "si.outstanding_amount > 0 AND si.payment_type = 'credit'";
    $supplierInfo = null;

    if (!empty($invoiceNumber)) {
        $escaped = mysqli_real_escape_string($db->DB_CON, $invoiceNumber);
        $billQuery = "SELECT supplier_id FROM `supplier_invoices` WHERE grn_number = '$escaped' OR invoice_no = '$escaped' LIMIT 1";
        $billRes = $db->readQuery($billQuery);
        if ($bRow = mysqli_fetch_assoc($billRes)) {
            $supplierId = $bRow['supplier_id'];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invoice not found']);
            exit;
        }
        $where .= " AND (si.grn_number = '$escaped' OR si.invoice_no = '$escaped')";
    } else {
        $where .= " AND si.supplier_id = $supplierId";
    }

    $query = "SELECT 
                si.id as invoice_id,
                si.grn_number,
                si.invoice_no,
                si.invoice_date,
                si.grand_total,
                si.outstanding_amount,
                sm.code as supplier_code,
                sm.name as supplier_name
              FROM `supplier_invoices` si
              LEFT JOIN `supplier_master` sm ON si.supplier_id = sm.id
              WHERE $where
              ORDER BY si.invoice_date ASC";

    $result = $db->readQuery($query);
    $data = [];
    $totalOutstanding = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        if (!$supplierInfo) {
            $supplierInfo = [
                'id' => $supplierId,
                'code' => $row['supplier_code'] . ' - ' . $row['supplier_name']
            ];
        }
        $data[] = [
            'invoice_id' => $row['invoice_id'],
            'date' => $row['invoice_date'],
            'grn_number' => $row['grn_number'],
            'invoice_no' => $row['invoice_no'],
            'amount' => floatval($row['outstanding_amount'])
        ];
        $totalOutstanding += floatval($row['outstanding_amount']);
    }

    // Get supplier info even if no outstanding items
    if (!$supplierInfo && $supplierId > 0) {
        $q = "SELECT code, name FROM `supplier_master` WHERE id = $supplierId";
        $r = $db->readQuery($q);
        if ($row = mysqli_fetch_assoc($r)) {
            $supplierInfo = [
                'id' => $supplierId,
                'code' => $row['code'] . ' - ' . $row['name']
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'items' => $data,
        'total_outstanding' => $totalOutstanding,
        'supplier' => $supplierInfo
    ]);
    exit;

} elseif ($action === 'get_branches') {
    $bankId = isset($_POST['bank_id']) ? (int)$_POST['bank_id'] : 0;
    if ($bankId > 0) {
        $BRANCH = new Branch(NULL);
        $branches = $BRANCH->getByBankId($bankId);
        $data = [];
        foreach ($branches as $branch) {
            $data[] = [
                'id' => $branch['id'],
                'name' => $branch['name'],
                'code' => $branch['code']
            ];
        }
        echo json_encode(['status' => 'success', 'branches' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Bank ID']);
    }
    exit;

} elseif ($action === 'save_supplier_payment') {
    $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
    $paymentMethodId = isset($_POST['payment_method_id']) ? (int)$_POST['payment_method_id'] : 1;
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $bankId = isset($_POST['bank_id']) ? (int)$_POST['bank_id'] : 0;
    $branchId = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;
    $chequeNo = $_POST['cheque_no'] ?? '';
    $chequeDate = $_POST['cheque_date'] ?? '';
    $accountNo = $_POST['account_no'] ?? '';
    $refNo = $_POST['ref_no'] ?? '';
    $transferDate = $_POST['transfer_date'] ?? '';
    
    $items = $_POST['items'] ?? [];

    if ($supplierId <= 0 || empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit;
    }

    $db = Database::getInstance();
    $totalAmount = 0;
    $validItems = [];

    foreach ($items as $item) {
        $invoiceId = (int)$item['id'];
        $amount = floatval($item['amount']);

        if ($amount <= 0) continue;

        $totalAmount += $amount;
        $validItems[] = [
            'id' => $invoiceId,
            'amount' => $amount
        ];
    }
    
    if ($totalAmount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Total amount is zero']);
        exit;
    }

    // 1. Create Payment Receipt (Header)
    $RECEIPT = new PaymentReceiptSupplier(NULL);
    $RECEIPT->receipt_no = 'SREC-' . time();
    $RECEIPT->customer_id = $supplierId;
    $RECEIPT->entry_date = $paymentDate;
    $RECEIPT->amount_paid = $totalAmount;
    $RECEIPT->remark = "Supplier Invoice Payment";
    $receiptId = $RECEIPT->create();

    if (!$receiptId) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create payment receipt']);
        exit;
    }

    // 2. Create Payment Receipt Method for each invoice
    foreach ($validItems as $vItem) {
        $METHOD = new PaymentReceiptMethodSupplier(NULL);
        $METHOD->receipt_id = $receiptId;
        $METHOD->invoice_id = $vItem['id'];
        $METHOD->payment_type_id = $paymentMethodId;
        $METHOD->amount = $vItem['amount'];
        $METHOD->cheq_no = '';
        $METHOD->bank_id = $bankId;
        $METHOD->branch_id = $branchId;
        $METHOD->cheq_date = '';
        $METHOD->is_settle = 1;

        if ($paymentMethodId == 2) { // Cheque
            $METHOD->cheq_no = $chequeNo;
            $METHOD->cheq_date = $chequeDate;
        } elseif ($paymentMethodId == 3) { // Bank Transfer
            $METHOD->cheq_date = $transferDate ?: $paymentDate;
        }

        $METHOD->create();
    }

    // 3. Update outstanding amounts on invoices
    $successCount = 0;
    $errors = [];

    foreach ($validItems as $vItem) {
        $invoiceId = $vItem['id'];
        $amount = $vItem['amount'];

        // Get current outstanding
        $q = "SELECT outstanding_amount FROM `supplier_invoices` WHERE id = $invoiceId";
        $r = mysqli_fetch_assoc($db->readQuery($q));
        
        if ($r) {
            $currentOutstanding = floatval($r['outstanding_amount']);
            $newOutstanding = $currentOutstanding - $amount;
            if ($newOutstanding < 0) $newOutstanding = 0;

            $updateQuery = "UPDATE `supplier_invoices` SET `outstanding_amount` = $newOutstanding WHERE `id` = $invoiceId";
            $db->readQuery($updateQuery);
            $successCount++;
        } else {
            $errors[] = "Invoice #$invoiceId not found";
        }
    }

    // 4. Decrease supplier_master outstanding
    $db->readQuery("UPDATE `supplier_master` SET `outstanding` = GREATEST(`outstanding` - $totalAmount, 0) WHERE `id` = $supplierId");

    // 5. Audit log
    if (isset($_SESSION['id'])) {
        $AUDIT_LOG = new AuditLog(null);
        $AUDIT_LOG->user_id = $_SESSION['id'];
        $AUDIT_LOG->action = 'Supplier Payment';
        $AUDIT_LOG->description = "Supplier payment of $totalAmount processed. Receipt #$receiptId";
        $AUDIT_LOG->create();
    }

    if ($successCount > 0) {
        echo json_encode(['status' => 'success', 'message' => "$successCount payments processed successfully. Receipt #$receiptId created." . (count($errors) > 0 ? " Errors: " . implode(", ", $errors) : "")]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No payments processed. ' . implode(", ", $errors)]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
