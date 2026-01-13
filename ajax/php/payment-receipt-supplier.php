<?php
include '../../class/include.php';
header('Content-Type: application/json');


if ($_POST['action'] === 'get_credit_invoices') {
    $customerId = (int) $_POST['customer_id'];
    $ARN = new ARNMaster(null);
    $data = $ARN->getCreditInvoicesByCustomerAndStatus(2, $customerId);
    header('Content-Type: application/json');
    echo json_encode(['success' => !empty($data), 'data' => $data]);
    exit();
}

// Create a new payment receipt
if (isset($_POST['create'])) {

    $RECEIPT = new PaymentReceiptSupplier(NULL);

    $RECEIPT->receipt_no   = $_POST['code'];
    $RECEIPT->customer_id  = $_POST['customer_id'];
    $RECEIPT->entry_date   = $_POST['entry_date'];
    $RECEIPT->amount_paid  = $_POST['paid_amount'];
    $RECEIPT->remark       = $_POST['remark'];

    try {
        $res = $RECEIPT->create();

        if (!$res) {
            throw new Exception('Failed to create payment receipt');
        }
        // Check if new JSON format is being used
        if (isset($_POST['methods']) && !empty($_POST['methods'])) {
            $methods = json_decode($_POST['methods'], true);

            if (is_array($methods)) {
                foreach ($methods as $method) {
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER = new PaymentReceiptMethodSupplier(null);
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->receipt_id = $res;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id = $method['invoice_id'] ?? null;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->payment_type_id = $method['payment_type_id'] ?? null;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->amount = $method['amount'] ?? 0;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->cheq_no = $method['cheq_no'] ?? null;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->branch_id = $method['branch_id'] ?? null;
                    $PAYMENT_RECEIPT_METHOD_SUPPLIER->cheq_date = $method['cheq_date'] ?? null;

                    // Derive bank_id from branch_id when not provided explicitly
                    if (empty($method['bank_id']) && !empty($PAYMENT_RECEIPT_METHOD_SUPPLIER->branch_id)) {
                        $BRANCH = new Branch($PAYMENT_RECEIPT_METHOD_SUPPLIER->branch_id);
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->bank_id = $BRANCH->bank_id;
                    } else {
                        $PAYMENT_RECEIPT_METHOD_SUPPLIER->bank_id = $method['bank_id'] ?? null;
                    }

                    $methodResult = $PAYMENT_RECEIPT_METHOD_SUPPLIER->create();
                    if (!$methodResult) {
                        throw new Exception('Failed to create payment method');
                    }

                    // Update invoice outstanding if invoice_id is provided
                    if (!empty($PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id)) {
                        $ARN = new ARNMaster(null);
                        $ARN->updateInvoiceOutstanding($PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id, $PAYMENT_RECEIPT_METHOD_SUPPLIER->amount);

                        // Check if invoice is fully settled
                        $ARN_ = new ARNMaster($PAYMENT_RECEIPT_METHOD_SUPPLIER->invoice_id);
                        if ($ARN_->paid_amount >= $ARN_->total_arn_value) {
                            $PAYMENT_RECEIPT_METHOD_SUPPLIER->updateIsSettle($PAYMENT_RECEIPT_METHOD_SUPPLIER->id);
                        }
                    }
                }
            }
            $CUSTOMER_MASTER = new CustomerMaster(NULL);
            $CUSTOMER_MASTER->updateCustomerOutstanding($_POST['customer_id'], $_POST['paid_amount'], false);

            $DOCUMENT_TRACKING = new DocumentTracking(null);
            $DOCUMENT_TRACKING->incrementDocumentId('payment_receipt_supplier');

            // If we get here, everything was successful
            echo json_encode(["status" => 'success', "id" => $res]);
        }
    } catch (Exception $e) {
        error_log('Payment Receipt Error: ' . $e->getMessage());
        echo json_encode(["status" => 'error', "message" => $e->getMessage()]);
    }
    exit();
}

// Update payment receipt
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
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}

// Delete payment receipt
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $RECEIPT = new PaymentReceipt($_POST['id']);
    $res = $RECEIPT->delete();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}
