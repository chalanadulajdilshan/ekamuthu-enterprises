<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'get_outstanding_rents') {
    $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $billNumber = isset($_POST['bill_number']) ? trim($_POST['bill_number']) : '';
    
    if ($customerId <= 0 && empty($billNumber)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
        exit;
    }

    $db = Database::getInstance();
    $where = "err.outstanding_amount > 0";
    $customerInfo = null;

    if (!empty($billNumber)) {
        $billQuery = "SELECT customer_id FROM `equipment_rent` WHERE bill_number = '" . mysqli_real_escape_string($db->DB_CON, $billNumber) . "'";
        $billRes = $db->readQuery($billQuery);
        if ($bRow = mysqli_fetch_assoc($billRes)) {
            $customerId = $bRow['customer_id'];
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Bill not found']);
             exit;
        }
        $where .= " AND er.bill_number = '" . mysqli_real_escape_string($db->DB_CON, $billNumber) . "'";
    } else {
        $where .= " AND er.customer_id = $customerId";
    }

    $query = "SELECT 
                err.id as return_id,
                err.return_date,
                err.outstanding_amount,
                eri.equipment_id,
                e.item_name,
                e.code as item_code,
                er.bill_number,
                er.id as rent_id,
                cm.code as customer_code,
                cm.name as customer_name
              FROM `equipment_rent_returns` err
              INNER JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
              INNER JOIN `equipment_rent` er ON eri.rent_id = er.id
              LEFT JOIN `equipment` e ON eri.equipment_id = e.id
              LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
              WHERE $where
              ORDER BY err.return_date ASC";

    $result = $db->readQuery($query);
    $data = [];
    $totalOutstanding = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        if (!$customerInfo) {
             $customerInfo = [
                 'id' => $customerId,
                 'code' => $row['customer_code'] . ' - ' . $row['customer_name']
             ];
        }
        $data[] = [
            'return_id' => $row['return_id'],
            'date' => $row['return_date'],
            'bill_number' => $row['bill_number'],
            'item_name' => $row['item_name'] . ' (' . $row['item_code'] . ')',
            'amount' => floatval($row['outstanding_amount'])
        ];
        $totalOutstanding += floatval($row['outstanding_amount']);
    }

    // Attempt to get customer info if no outstanding items found for the requested bill/customer
    if (!$customerInfo && $customerId > 0) {
        $q = "SELECT code, name FROM `customer_master` WHERE id = $customerId";
        $r = $db->readQuery($q);
        if ($row = mysqli_fetch_assoc($r)) {
            $customerInfo = [
                'id' => $customerId,
                'code' => $row['code'] . ' - ' . $row['name']
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'items' => $data,
        'total_outstanding' => $totalOutstanding,
        'customer' => $customerInfo
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

} elseif ($action === 'save_rent_payment') {
    $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    
    // Validating required fields based on method is done on frontend, but we collect them here
    $paymentMethodId = isset($_POST['payment_method_id']) ? (int)$_POST['payment_method_id'] : 1;
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    
    // Optional fields
    $bankId = isset($_POST['bank_id']) ? (int)$_POST['bank_id'] : 0;
    $branchId = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;
    $chequeNo = $_POST['cheque_no'] ?? '';
    $refNo = $_POST['ref_no'] ?? '';
    $chequeDate = $_POST['cheque_date'] ?? '';
    // If cheque_date is not provided (e.g. for Bank Transfer), use the main payment_date
    // If cheque_date is not provided (e.g. for Bank Transfer), check logic later
    $accountNo = $_POST['account_no'] ?? '';
    
    $items = $_POST['items'] ?? []; // Array of {id, amount}

    if ($customerId <= 0 || empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit;
    }

    $db = Database::getInstance();
    
    // 3. Prepare items and group by Rent ID (Invoice ID)
    $groupedByRent = []; // rent_id => amount
    $totalAmount = 0;
    $validItems = [];

    foreach ($items as $item) {
        $returnId = (int)$item['id'];
        $amount = floatval($item['amount']);

        if ($amount <= 0) continue;

        // Fetch Rent ID for this return item
        $q = "SELECT eri.rent_id 
              FROM `equipment_rent_returns` err
              INNER JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
              WHERE err.id = $returnId";
        $r = mysqli_fetch_assoc($db->readQuery($q));
        
        if ($r) {
            $rentId = $r['rent_id'];
            if (!isset($groupedByRent[$rentId])) {
                $groupedByRent[$rentId] = 0;
            }
            $groupedByRent[$rentId] += $amount;
            $totalAmount += $amount;
            
            $validItems[] = [
                'id' => $returnId,
                'amount' => $amount
            ];
        }
    }
    
    if ($totalAmount <= 0) {
       echo json_encode(['status' => 'error', 'message' => 'Total amount is zero']);
       exit; 
    }

    // 1. Create Payment Receipt (Header)
    $RECEIPT = new PaymentReceipt(NULL);
    $RECEIPT->receipt_no = 'REC-' . time(); // Basic receipt number generation
    $RECEIPT->customer_id = $customerId;
    $RECEIPT->entry_date = $paymentDate;
    $RECEIPT->amount_paid = $totalAmount;
    $RECEIPT->remark = "Rent Payment Settlement";
    $receiptId = $RECEIPT->create();

    if (!$receiptId) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create payment receipt']);
        exit;
    }

    // 2. Create Payment Receipt Methods (Details - One per Rent/Invoice)
    foreach ($groupedByRent as $rentId => $rentAmount) {
        $METHOD = new PaymentReceiptMethod(NULL);
        $METHOD->receipt_id = $receiptId;
        $METHOD->invoice_id = $rentId; // Set Rent ID as Invoice ID
        $METHOD->payment_type_id = $paymentMethodId;
        $METHOD->amount = $rentAmount; // Amount specific to this rent bill
        $METHOD->payment_date = $paymentDate; 
        // Handle dates and reference numbers based on payment type
        if ($paymentMethodId == 1) { // Cash
             $METHOD->cheq_date = ''; 
             $METHOD->transfer_date = '';
             $METHOD->cheq_no = '';
             $METHOD->ref_no = '';
        } elseif ($paymentMethodId == 3) { // Bank Transfer
             $METHOD->cheq_date = ''; 
             $METHOD->transfer_date = empty($transferDate) ? $paymentDate : $transferDate; 
             $METHOD->cheq_no = '';
             $METHOD->ref_no = $refNo;
        } else { // Cheque
             $METHOD->cheq_date = $chequeDate;
             $METHOD->transfer_date = '';
             $METHOD->cheq_no = $chequeNo;
             $METHOD->ref_no = '';
        }

        $METHOD->is_settle = 1; // Mark as settled
        $METHOD->account_no = $accountNo; 
        
        $METHOD->create();
    }

    // 3. Update Rent Items
    $successCount = 0;
    $errors = [];

    foreach ($validItems as $vItem) {
        $returnId = $vItem['id'];
        $amount = $vItem['amount'];

        $RENT_RETURN = new EquipmentRentReturn($returnId);
        $result = $RENT_RETURN->settleOutstanding($amount);

        if ($result['error']) {
            $errors[] = "Item #$returnId: " . $result['message'];
        } else {
            $successCount++;
            // Link payment to this return
            $query = "UPDATE `equipment_rent_returns` SET `payment_receipt_id` = $receiptId WHERE `id` = $returnId";
            $db->readQuery($query);
        }
    }

    if ($successCount > 0) {
        echo json_encode(['status' => 'success', 'message' => "$successCount payments processed successfully. Receipt #$receiptId created." . (count($errors) > 0 ? " Errors: " . implode(", ", $errors) : "")]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No payments processed. ' . implode(", ", $errors)]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
