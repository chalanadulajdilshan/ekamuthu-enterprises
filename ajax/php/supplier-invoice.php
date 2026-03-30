<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Get branches by bank ID
if (isset($_POST['action']) && $_POST['action'] == 'get_branches') {
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
    exit();
}

// Create a new supplier invoice
if (isset($_POST['action']) && $_POST['action'] == 'create_supplier_invoice') {

    $items = json_decode($_POST['items'], true);

    if (empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'No items added']);
        exit();
    }

    // Check for duplicate Invoice No
    $invoiceNo = $_POST['invoice_no'] ?? '';
    if (!empty($invoiceNo)) {
        $db = Database::getInstance();
        $checkInvoice = "SELECT id FROM supplier_invoices WHERE invoice_no = '" . $db->escapeString($invoiceNo) . "'";
        $result = $db->readQuery($checkInvoice);
        if (mysqli_num_rows($result) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice No already exists. Please use a unique Invoice No.']);
            exit();
        }
    }

    // Check for duplicate Order No
    $orderNo = $_POST['order_no'] ?? '';
    if (!empty($orderNo)) {
        $db = Database::getInstance();
        $checkOrder = "SELECT id FROM supplier_invoices WHERE order_no = '" . $db->escapeString($orderNo) . "'";
        $result = $db->readQuery($checkOrder);
        if (mysqli_num_rows($result) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Order No already exists. Please use a unique Order No.']);
            exit();
        }
    }

    // Calculate grand total
    $grandTotal = 0;
    foreach ($items as $item) {
        $grandTotal += floatval($item['amount']);
    }

    // Handle cheque image upload
    $chequeImageName = '';
    if (isset($_FILES['cheque_image']) && $_FILES['cheque_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/cheques/';
        $ext = pathinfo($_FILES['cheque_image']['name'], PATHINFO_EXTENSION);
        $chequeImageName = 'cheque_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        move_uploaded_file($_FILES['cheque_image']['tmp_name'], $uploadDir . $chequeImageName);
    }

    // Create supplier invoice
    $INVOICE = new SupplierInvoice(null);
    $INVOICE->grn_number = $_POST['grn_no'];
    $INVOICE->order_no = $_POST['order_no'] ?? '';
    $INVOICE->supplier_id = $_POST['supplier_id'];
    $INVOICE->invoice_no = $_POST['invoice_no'] ?? '';
    $INVOICE->invoice_date = $_POST['invoice_date'] ?? '';
    $INVOICE->delivery_date = $_POST['delivery_date'] ?? '';
    $INVOICE->grand_total = $grandTotal;
    $INVOICE->payment_type = $_POST['payment_type'] ?? 'cash';
    $INVOICE->cheque_no = $_POST['cheque_no'] ?? '';
    $INVOICE->cheque_date = $_POST['cheque_date'] ?? '';
    $INVOICE->bank_name = $_POST['bank_name'] ?? '';
    $INVOICE->branch_name = $_POST['branch_name'] ?? '';
    $INVOICE->cheque_image = $chequeImageName;
    $INVOICE->credit_period = $_POST['credit_period'] ?? 0;
    $INVOICE->created_by = $_SESSION['id'];
    $INVOICE->created_at = date("Y-m-d H:i:s");

    $invoiceResult = $INVOICE->create();

    if ($invoiceResult) {
        $newInvoiceId = $invoiceResult;

        // Insert items
        foreach ($items as $item) {
            $ITEM = new SupplierInvoiceItem();
            $ITEM->supplier_invoice_id = $newInvoiceId;
            $ITEM->item_id = $item['item_id'] ?? 0;
            $ITEM->item_code = $item['item_code'] ?? '';
            $ITEM->item_name = $item['item_name'] ?? '';
            $ITEM->unit = $item['unit'] ?? '';
            $ITEM->quantity = floatval($item['qty']);
            $ITEM->rate = floatval($item['rate']);
            $ITEM->discount_percentage = floatval($item['discount']);
            $ITEM->amount = floatval($item['amount']);
            $ITEM->create();
        }

        // Increment document tracking
        $DOCUMENT_TRACKING = new DocumentTracking(null);
        $DOCUMENT_TRACKING->incrementDocumentId('supplier_invoice');

        // Increase supplier outstanding if credit payment
        if (($INVOICE->payment_type ?? '') === 'credit') {
            $db = Database::getInstance();
            $db->readQuery("UPDATE `supplier_master` SET `outstanding` = `outstanding` + $grandTotal WHERE `id` = " . (int)$_POST['supplier_id']);
        }

        // Audit log
        $AUDIT_LOG = new AuditLog(null);
        $AUDIT_LOG->ref_id = $newInvoiceId;
        $AUDIT_LOG->ref_code = $_POST['grn_no'];
        $AUDIT_LOG->action = 'CREATE';
        $AUDIT_LOG->description = 'CREATE SUPPLIER INVOICE (GRN) #' . $_POST['grn_no'];
        $AUDIT_LOG->user_id = $_SESSION['id'];
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        echo json_encode([
            'status' => 'success',
            'invoiceId' => $newInvoiceId,
            'grand_total' => $grandTotal
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create supplier invoice']);
    }

    exit();
}

// Get supplier invoice by ID
if (isset($_POST['action']) && $_POST['action'] == 'get_supplier_invoice') {

    $INVOICE = new SupplierInvoice($_POST['id']);
    $ITEM_OBJ = new SupplierInvoiceItem(null);
    $items = $ITEM_OBJ->getByInvoiceId($_POST['id']);

    // Items already have item_code and item_name stored directly
    $enhancedItems = $items;

    $SUPPLIER = new SupplierMaster($INVOICE->supplier_id);

    $data = [
        'id' => $INVOICE->id,
        'grn_number' => $INVOICE->grn_number,
        'order_no' => $INVOICE->order_no,
        'supplier_id' => $INVOICE->supplier_id,
        'supplier_code' => $SUPPLIER->code,
        'supplier_name' => $SUPPLIER->name,
        'invoice_no' => $INVOICE->invoice_no,
        'invoice_date' => $INVOICE->invoice_date,
        'delivery_date' => $INVOICE->delivery_date,
        'grand_total' => $INVOICE->grand_total,
        'payment_type' => $INVOICE->payment_type,
        'cheque_no' => $INVOICE->cheque_no,
        'cheque_date' => $INVOICE->cheque_date,
        'bank_name' => $INVOICE->bank_name,
        'branch_name' => $INVOICE->branch_name,
        'cheque_image' => $INVOICE->cheque_image,
        'credit_period' => $INVOICE->credit_period,
        'items' => $enhancedItems
    ];

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit();
}

// Update supplier invoice
if (isset($_POST['action']) && $_POST['action'] == 'update_supplier_invoice') {

    $invoiceId = $_POST['id'];
    $items = json_decode($_POST['items'], true);

    // Check for duplicate Invoice No (excluding current record)
    $invoiceNo = $_POST['invoice_no'] ?? '';
    if (!empty($invoiceNo)) {
        $db = Database::getInstance();
        $checkInvoice = "SELECT id FROM supplier_invoices WHERE invoice_no = '" . $db->escapeString($invoiceNo) . "' AND id != " . (int)$invoiceId;
        $result = $db->readQuery($checkInvoice);
        if (mysqli_num_rows($result) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice No already exists. Please use a unique Invoice No.']);
            exit();
        }
    }

    // Check for duplicate Order No (excluding current record)
    $orderNo = $_POST['order_no'] ?? '';
    if (!empty($orderNo)) {
        $db = Database::getInstance();
        $checkOrder = "SELECT id FROM supplier_invoices WHERE order_no = '" . $db->escapeString($orderNo) . "' AND id != " . (int)$invoiceId;
        $result = $db->readQuery($checkOrder);
        if (mysqli_num_rows($result) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Order No already exists. Please use a unique Order No.']);
            exit();
        }
    }

    // Calculate grand total
    $grandTotal = 0;
    foreach ($items as $item) {
        $grandTotal += floatval($item['amount']);
    }

    // Get old invoice data before update to adjust outstanding
    $OLD_INVOICE = new SupplierInvoice($invoiceId);
    $oldPaymentType = $OLD_INVOICE->payment_type;
    $oldGrandTotal = floatval($OLD_INVOICE->grand_total);
    $oldSupplierId = (int)$OLD_INVOICE->supplier_id;

    // Handle cheque image upload
    $EXISTING = $OLD_INVOICE;
    $chequeImageName = $EXISTING->cheque_image;

    if (isset($_FILES['cheque_image']) && $_FILES['cheque_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/cheques/';
        // Delete old image if exists
        if (!empty($chequeImageName) && file_exists($uploadDir . $chequeImageName)) {
            unlink($uploadDir . $chequeImageName);
        }
        $ext = pathinfo($_FILES['cheque_image']['name'], PATHINFO_EXTENSION);
        $chequeImageName = 'cheque_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        move_uploaded_file($_FILES['cheque_image']['tmp_name'], $uploadDir . $chequeImageName);
    }

    $INVOICE = new SupplierInvoice($invoiceId);
    $INVOICE->grn_number = $_POST['grn_no'];
    $INVOICE->order_no = $_POST['order_no'] ?? '';
    $INVOICE->supplier_id = $_POST['supplier_id'];
    $INVOICE->invoice_no = $_POST['invoice_no'] ?? '';
    $INVOICE->invoice_date = $_POST['invoice_date'] ?? '';
    $INVOICE->delivery_date = $_POST['delivery_date'] ?? '';
    $INVOICE->grand_total = $grandTotal;
    $INVOICE->payment_type = $_POST['payment_type'] ?? 'cash';
    $INVOICE->cheque_no = $_POST['cheque_no'] ?? '';
    $INVOICE->cheque_date = $_POST['cheque_date'] ?? '';
    $INVOICE->bank_name = $_POST['bank_name'] ?? '';
    $INVOICE->branch_name = $_POST['branch_name'] ?? '';
    $INVOICE->cheque_image = $chequeImageName;
    $INVOICE->credit_period = $_POST['credit_period'] ?? 0;

    $updateResult = $INVOICE->update();

    if ($updateResult) {
        // Delete old items and re-insert
        SupplierInvoiceItem::deleteByInvoiceId($invoiceId);

        foreach ($items as $item) {
            $ITEM = new SupplierInvoiceItem();
            $ITEM->supplier_invoice_id = $invoiceId;
            $ITEM->item_id = $item['item_id'] ?? 0;
            $ITEM->item_code = $item['item_code'] ?? '';
            $ITEM->item_name = $item['item_name'] ?? '';
            $ITEM->unit = $item['unit'] ?? '';
            $ITEM->quantity = floatval($item['qty']);
            $ITEM->rate = floatval($item['rate']);
            $ITEM->discount_percentage = floatval($item['discount']);
            $ITEM->amount = floatval($item['amount']);
            $ITEM->create();
        }

        // Adjust supplier_master outstanding
        $db = Database::getInstance();
        $newPaymentType = $_POST['payment_type'] ?? 'cash';
        $newSupplierId = (int)$_POST['supplier_id'];

        // Reverse old outstanding if was credit
        if ($oldPaymentType === 'credit') {
            $db->readQuery("UPDATE `supplier_master` SET `outstanding` = GREATEST(`outstanding` - $oldGrandTotal, 0) WHERE `id` = $oldSupplierId");
        }
        // Add new outstanding if now credit
        if ($newPaymentType === 'credit') {
            $db->readQuery("UPDATE `supplier_master` SET `outstanding` = `outstanding` + $grandTotal WHERE `id` = $newSupplierId");
        }

        // Audit log
        $AUDIT_LOG = new AuditLog(null);
        $AUDIT_LOG->ref_id = $invoiceId;
        $AUDIT_LOG->ref_code = $INVOICE->grn_number;
        $AUDIT_LOG->action = 'UPDATE';
        $AUDIT_LOG->description = 'UPDATE SUPPLIER INVOICE (GRN) #' . $INVOICE->grn_number;
        $AUDIT_LOG->user_id = $_SESSION['id'];
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update supplier invoice']);
    }

    exit();
}

// Delete supplier invoice
if (isset($_POST['action']) && $_POST['action'] == 'delete') {

    $INVOICE = new SupplierInvoice($_POST['id']);

    // Reverse supplier outstanding if credit invoice
    if ($INVOICE->payment_type === 'credit') {
        $db = Database::getInstance();
        $outstandingAmount = floatval($INVOICE->outstanding_amount);
        $db->readQuery("UPDATE `supplier_master` SET `outstanding` = GREATEST(`outstanding` - $outstandingAmount, 0) WHERE `id` = " . (int)$INVOICE->supplier_id);
    }

    $result = $INVOICE->delete();

    // Audit log
    $AUDIT_LOG = new AuditLog(null);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $INVOICE->grn_number;
    $AUDIT_LOG->action = 'DELETE';
    $AUDIT_LOG->description = 'DELETE SUPPLIER INVOICE (GRN) #' . $INVOICE->grn_number;
    $AUDIT_LOG->user_id = $_SESSION['id'];
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }

    exit();
}
