<?php
include '../../class/include.php';
header('Content-Type: application/json');

// Get credit invoices for a customer
if ($_POST['action'] === 'get_credit_invoices') {
    $customerId = (int) $_POST['customer_id'];

    $INVOICE = new SalesInvoice(null);
    $data = $INVOICE->getCreditInvoicesByCustomerAndStatus(0, $customerId); // status = 0 (Active)

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// Create a new payment receipt + methods
if (isset($_POST['create'])) {

    $RECEIPT = new PaymentReceipt(NULL); // New receipt object
    $RECEIPT->receipt_no   = $_POST['receipt_no'];
    $RECEIPT->customer_id  = $_POST['customer_id'];
    $RECEIPT->entry_date   = $_POST['entry_date'];
    $RECEIPT->amount_paid  = $_POST['amount_paid'];
    $RECEIPT->remark       = $_POST['remark'];

    $receiptId = $RECEIPT->create();

    if ($receiptId) {

        // If receipt methods were sent, save them
        if (isset($_POST['methods']) && is_array($_POST['methods'])) {
            foreach ($_POST['methods'] as $method) {
                $METHOD = new PaymentReceiptMethod(NULL);
                $METHOD->receipt_id      = $receiptId;
                $METHOD->invoice_id      = $method['invoice_id'] ?? null;
                $METHOD->payment_type_id = $method['payment_type_id'] ?? null;
                $METHOD->amount          = $method['amount'] ?? 0;
                $METHOD->cheq_no         = $method['cheq_no'] ?? null;
                $METHOD->bank_id         = $method['bank_id'] ?? null;
                $METHOD->branch_id       = $method['branch_id'] ?? null;
                $METHOD->cheq_date       = $method['cheq_date'] ?? null;
                $METHOD->create();
            }
        }

        echo json_encode(["status" => 'success', "id" => $receiptId]);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}

// Update payment receipt + methods
if (isset($_POST['update'])) {

    if (!isset($_POST['id'])) {
        echo json_encode(["status" => 'error', "message" => "Missing receipt ID"]);
        exit();
    }

    $RECEIPT = new PaymentReceipt($_POST['id']); // Load receipt by ID
    $RECEIPT->receipt_no   = $_POST['receipt_no'];
    $RECEIPT->customer_id  = $_POST['customer_id'];
    $RECEIPT->entry_date   = $_POST['entry_date'];
    $RECEIPT->amount_paid  = $_POST['amount_paid'];
    $RECEIPT->remark       = $_POST['remark'];

    $res = $RECEIPT->update();

    if ($res) {
        // Optionally replace existing methods
        if (isset($_POST['methods']) && is_array($_POST['methods'])) {

            // Delete existing methods first (to avoid duplicates)
            $db = Database::getInstance();
            $db->readQuery("DELETE FROM `payment_receipt_method` WHERE `receipt_id` = " . (int)$_POST['id']);

            // Insert updated methods
            foreach ($_POST['methods'] as $method) {
                $METHOD = new PaymentReceiptMethod(NULL);
                $METHOD->receipt_id      = $_POST['id'];
                $METHOD->invoice_id      = $method['invoice_id'] ?? null;
                $METHOD->payment_type_id = $method['payment_type_id'] ?? null;
                $METHOD->amount          = $method['amount'] ?? 0;
                $METHOD->cheq_no         = $method['cheq_no'] ?? null;
                $METHOD->bank_id         = $method['bank_id'] ?? null;
                $METHOD->branch_id       = $method['branch_id'] ?? null;
                $METHOD->cheq_date       = $method['cheq_date'] ?? null;
                $METHOD->create();
            }
        }

        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}

// Delete payment receipt + its methods
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $receiptId = (int)$_POST['id'];

    // Delete receipt methods first
    $db = Database::getInstance();
    $db->readQuery("DELETE FROM `payment_receipt_method` WHERE `receipt_id` = " . $receiptId);

    // Delete receipt itself
    $RECEIPT = new PaymentReceipt($receiptId);
    $res = $RECEIPT->delete();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}
