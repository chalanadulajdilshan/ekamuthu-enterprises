<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');


if (isset($_POST['action']) && $_POST['action'] == 'check_invoice_id') {


    $invoice_no = trim($_POST['invoice_no']);
    $SALES_INVOICE = new SalesInvoice(NULL);
    $res = $SALES_INVOICE->checkInvoiceIdExist($invoice_no);

    // Send JSON response
    echo json_encode(['exists' => $res]);
    exit();
}

// Handle duplicate invoice with document tracking update
if (isset($_POST['action']) && $_POST['action'] == 'resolve_duplicate_invoice') {
    $payment_type = trim($_POST['payment_type']);

    // Get company profile details
    $USER = new User($_SESSION['id']);
    $company_id = $USER->company_id;
    $COMPANY_PROFILE_DETAILS = new CompanyProfile($company_id);

    $DOCUMENT_TRACKING = new DocumentTracking(1);
    $SALES_INVOICE = new SalesInvoice(NULL);

    // Determine document type and get current ID
    if ($payment_type === '1') {
        $documentType = 'cash';
        $currentId = $DOCUMENT_TRACKING->cash_id;
        $prefix = '/CA/0';
    } elseif ($payment_type === '2') {
        $documentType = 'credit';
        $currentId = $DOCUMENT_TRACKING->credit_id;
        $prefix = '/CR/0';
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid payment type']);
        exit();
    }

    // Keep incrementing until we find a unique invoice number
    $maxAttempts = 100; // Prevent infinite loop
    $attempts = 0;
    $nextId = $currentId + 1;
    $invoice_id = '';

    while ($attempts < $maxAttempts) {
        $invoiceNumber = str_pad($nextId, 4, '0', STR_PAD_LEFT);
        $invoice_id = $COMPANY_PROFILE_DETAILS->company_code . $prefix . $_SESSION['id'] . '/' . $invoiceNumber;

        // Check if this invoice number exists
        $exists = $SALES_INVOICE->checkInvoiceIdExist($invoice_id);

        if (!$exists) {
            // Found a unique number, update document tracking to nextId - 1
            // Because the normal invoice creation will increment by 1 again
            $db = Database::getInstance();
            $column = ($documentType === 'cash') ? 'cash_id' : 'credit_id';
            $updateToId = $nextId - 1; // Set to one less, so when incremented it becomes nextId
            $update_query = "UPDATE `document_tracking` SET 
                            `$column` = '$updateToId',
                            `updated_at` = NOW() 
                            WHERE `status` = 1";
            $db->readQuery($update_query);

            echo json_encode(['status' => 'success', 'invoice_id' => $invoice_id]);
            exit();
        }

        // This number exists, try the next one
        $nextId++;
        $attempts++;
    }

    // If we get here, we couldn't find a unique number after many attempts
    echo json_encode(['status' => 'error', 'message' => 'Unable to generate unique invoice number after multiple attempts. Please contact administrator.']);
    exit();
}


// Create a new invoice
if (isset($_POST['create'])) {

    $invoiceId = $_POST['invoice_no'];
    $items = json_decode($_POST['items'], true); // array of items 

    $paymentType = $_POST['payment_type'];

    $departmentId = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
    $stockErrors = [];

    if (!empty($items) && $departmentId > 0) {
        $db = Database::getInstance();

        foreach ($items as $item) {

            if (!isset($item['code'], $item['item_id'], $item['qty'])) {
                continue;
            }

            if (substr($item['code'], 0, 2) === 'SI' || substr($item['code'], 0, 2) === 'SV') {
                continue;
            }

            $itemId = (int)$item['item_id'];
            $qtyForStock = (float)$item['qty'];
            $effectiveDepartmentId = $departmentId;

            if (!empty($item['arn_no'])) {
                $ARN_MASTER_CHECK = new ArnMaster(NULL);
                $arnCheckId = $ARN_MASTER_CHECK->getArnIdByArnNo($item['arn_no']);

                if ($arnCheckId) {
                    $deptResult = $db->readQuery("SELECT department_id FROM stock_item_tmp WHERE arn_id = '" . (int)$arnCheckId . "' AND item_id = '" . $itemId . "' LIMIT 1");
                    if ($deptRow = mysqli_fetch_assoc($deptResult)) {
                        $effectiveDepartmentId = (int)$deptRow['department_id'];
                    }
                }
            }

            $currentQty = StockMaster::getAvailableQuantity($effectiveDepartmentId, $itemId);

            if ($qtyForStock > $currentQty) {
                $stockErrors[] = [
                    'item_code' => $item['code'],
                    'item_name' => isset($item['name']) ? $item['name'] : '',
                    'requested_qty' => $qtyForStock,
                    'available_qty' => $currentQty,
                    'department_id' => $effectiveDepartmentId
                ];
            }
        }
    }

    if (!empty($stockErrors)) {
        echo json_encode([
            "status" => 'error',
            "code" => 'INSUFFICIENT_STOCK',
            "message" => "Insufficient stock for one or more items.",
            "items" => $stockErrors
        ]);
        exit();
    }

    $totalSubTotal = 0;
    $totalDiscount = 0;
    $final_cost = 0;

    // Calculate subtotal and discount
    foreach ($items as $item) {
        $price = floatval($item['price']); // Use list_price for subtotal calculation to match interface
        $qty = floatval($item['qty']);

        // Treat discount as a fixed value per unit
        if (isset($item['discount'])) {
            $discount_per_unit = (float)$item['discount'];
        } else {
            $discount_per_unit = 0;
        }


        //GET ARN ID BY ARN NO
        $ARN_MASTER = new ArnMaster(NULL);
        $arn_id = $ARN_MASTER->getArnIdByArnNo($item['arn_no']);

        $ITEM_MASTER = new ItemMaster($item['item_id']);


        if (substr($item['code'], 0, 2) === 'SV') {
            // Pure Service - no cost tracking, services are not inventory items
            $final_cost_item = 0;
            $final_cost += $final_cost_item;
        } elseif (substr($item['code'], 0, 2) !== 'SI') {
            // Regular item with ARN
            $ARN_ITEM = new ArnItem(NULL);
            $cost = $ARN_ITEM->getArnCostByArnId($arn_id);
            $final_cost_item = $cost * $item['qty'];
            $final_cost += $final_cost_item;
        } else {
            // Service Item (SI)
            $SERVICE_ITEM = new ServiceItem($item['item_id']);
            $final_cost_item = $SERVICE_ITEM->cost * $item['service_qty'];
            $final_cost += $final_cost_item;

            $available_qty = $SERVICE_ITEM->qty - $item['service_qty'];
            $SERVICE_ITEM->qty = $available_qty;
            $SERVICE_ITEM->update();
        }

        $itemTotal = $price * $qty;
        $discount_amount = $discount_per_unit * $qty;
        $totalSubTotal += $itemTotal;
        $totalDiscount += $discount_amount;
    }
    $netTotal = $totalSubTotal - $totalDiscount;

    $USER = new User($_SESSION['id']);
    $COMPANY_PROFILE = new CompanyProfile($USER->company_id);

    // VAT calculation - apply only when explicitly selected
    $tax = 0;
    $isVatInvoice = isset($_POST['is_vat_invoice']) && $_POST['is_vat_invoice'] == '1';

    if ($isVatInvoice) {
        $vat_percentage = $COMPANY_PROFILE->vat_percentage;
        if ($vat_percentage > 0) {
            $tax = round(($netTotal * $vat_percentage) / 100, 2);
        }
    }

    // Grand total = net total + VAT
    $grandTotal = $netTotal + $tax;

    // Create invoice
    $SALES_INVOICE = new SalesInvoice(NULL);
    $CUSTOMER_MASTER = new CustomerMaster(NULL);

    $SALES_INVOICE->invoice_no = $invoiceId;
    $SALES_INVOICE->invoice_type = 'INV';
    $SALES_INVOICE->invoice_date = $_POST['invoice_date'];
    $SALES_INVOICE->company_id = $_POST['company_id'];
    $SALES_INVOICE->customer_id = $_POST['customer_id'];
    $SALES_INVOICE->customer_name = ucwords(strtolower(trim($_POST['customer_name'])));
    $SALES_INVOICE->customer_mobile = $_POST['customer_mobile'];
    $SALES_INVOICE->customer_address = ucwords(strtolower(trim($_POST['customer_address'])));
    $SALES_INVOICE->recommended_person = isset($_POST['recommended_person']) ? ucwords(strtolower(trim($_POST['recommended_person']))) : null;
    $SALES_INVOICE->department_id = $_POST['department_id'];
    $SALES_INVOICE->sale_type = $_POST['sales_type'];
    $SALES_INVOICE->final_cost = $final_cost;

    $SALES_INVOICE->payment_type = $paymentType;
    $SALES_INVOICE->sub_total = $totalSubTotal;
    $SALES_INVOICE->discount = $totalDiscount;
    $SALES_INVOICE->tax = $tax;
    $SALES_INVOICE->grand_total = $grandTotal;
    $SALES_INVOICE->outstanding_settle_amount = $_POST['paidAmount'];
    $SALES_INVOICE->remark = !empty($_POST['remark']) ? $_POST['remark'] : null;

    if ($paymentType == 2 && !empty($_POST['credit_period'])) {
        $SALES_INVOICE->credit_period = $_POST['credit_period'];
        $CREDIT_PERIOD_OBJ = new CreditPeriod($_POST['credit_period']);
        if (isset($CREDIT_PERIOD_OBJ->days)) {
            $days = $CREDIT_PERIOD_OBJ->days;
            $due_date = date('Y-m-d', strtotime($_POST['invoice_date'] . ' + ' . $days . ' days'));
            $SALES_INVOICE->due_date = $due_date;
        } else {
            // Handle error: invalid credit period id
            echo json_encode(["status" => 'error', "message" => "Invalid credit period selected."]);
            exit();
        }
    }

    $invoiceResult = $SALES_INVOICE->create();

    // If this invoice was created from a quotation, mark that quotation as invoiced
    if ($invoiceResult && !empty($_POST['quotation_id'])) {
        $QUOTATION = new Quotation($_POST['quotation_id']);
        if ($QUOTATION->id) {
            $QUOTATION->is_invoiced = 1;
            $QUOTATION->update();
        }
    }

    if ($paymentType == 2) {
        $CUSTOMER_MASTER->updateCustomerOutstanding($_POST['customer_id'], $grandTotal, true);
    }

    $DOCUMENT_TRACKING = new DocumentTracking(null);

    if ($paymentType == 1) {
        $DOCUMENT_TRACKING->incrementDocumentId('cash');
    } else if ($paymentType == 2) {
        $DOCUMENT_TRACKING->incrementDocumentId('credit');
    } else {

        $DOCUMENT_TRACKING->incrementDocumentId('invoice');
    }


    if ($invoiceResult) {
        $invoiceTableId = $invoiceResult;

        foreach ($items as $item) {

            // Treat discount as a fixed value per unit
            $item_discount_per_unit = isset($item['discount']) ? (float)$item['discount'] : 0;

            $ITEM_MASTER = new ItemMaster($item['item_id']);

            //GET ARN ID BY ARN NO FIRST
            $ARN_MASTER = new ArnMaster(NULL);
            $arn_id = $ARN_MASTER->getArnIdByArnNo($item['arn_no']);

            // Get the correct department_id for this ARN before saving item
            $db = Database::getInstance();
            $deptQuery = "SELECT department_id FROM stock_item_tmp WHERE arn_id = '{$arn_id}' AND item_id = '{$item['item_id']}' LIMIT 1";
            $deptResult = $db->readQuery($deptQuery);
            $correctDepartmentId = $_POST['department_id']; // fallback to form department

            if ($deptRow = mysqli_fetch_assoc($deptResult)) {
                $correctDepartmentId = $deptRow['department_id'];
            }

            $SALES_ITEM = new SalesInvoiceItem(NULL);

            $SALES_ITEM->invoice_id = $invoiceTableId;

            if (substr($item['code'], 0, 2) === 'SV') {
                // Pure Service - no stock management
                $SALES_ITEM->item_code = $item['item_id'];
                $SALES_ITEM->quantity = $item['qty'];
                $qty_for_total = $item['qty'];
                $qty_for_stock = 0; // Services don't affect stock
            } elseif (substr($item['code'], 0, 2) !== 'SI') {
                // Regular item
                $SALES_ITEM->item_code = $item['item_id'];
                $SALES_ITEM->quantity = $item['qty'];
                $qty_for_total = $item['qty'];
                $qty_for_stock = $item['qty']; // Use regular qty for stock management
            } else {
                // Service item - use the actual quantity from the table (which is service_qty)
                $SALES_ITEM->service_item_code = $item['item_id'];
                // Use qty from table cell (which contains serviceQty for service items)
                $service_item_qty = !empty($item['service_qty']) ? $item['service_qty'] : $item['qty'];
                $SALES_ITEM->quantity = $service_item_qty;
                $qty_for_total = $service_item_qty; // Use actual qty for price calculations
                $qty_for_stock = $service_item_qty; // Use actual qty for stock management
            }

            $item_discount_amount = $item_discount_per_unit * $qty_for_total;

            // Store item name with ARN ID and department for cancellation tracking (skip for pure services)
            if (substr($item['code'], 0, 2) === 'SV') {
                $SALES_ITEM->item_name = $item['name'];
            } else {
                $SALES_ITEM->item_name = $item['name'] . '|ARN:' . $arn_id . '|DEPT:' . $correctDepartmentId;
            }
            $SALES_ITEM->list_price = $item['price']; // Save the original list price
            $SALES_ITEM->price = $item['selling_price']; // Save the actual selling price (price after discount per unit)
            $SALES_ITEM->cost = (substr($item['code'], 0, 2) === 'SV') ? 0 : $item['cost'];
            $SALES_ITEM->discount = $item_discount_amount;
            $SALES_ITEM->total = ($item['selling_price'] * $qty_for_total);
            $SALES_ITEM->vehicle_no = isset($item['vehicle_no']) ? $item['vehicle_no'] : '';
            $SALES_ITEM->current_km = isset($item['current_km']) ? $item['current_km'] : '';
            $SALES_ITEM->next_service_date = (isset($item['next_service_days']) && !empty($item['next_service_days']) && intval($item['next_service_days']) > 0) ? date('Y-m-d', strtotime($SALES_INVOICE->invoice_date . ' + ' . $item['next_service_days'] . ' days')) : null;
            $SALES_ITEM->created_at = date("Y-m-d H:i:s");
            $SALES_ITEM->create();

            // Only update stock for items that have physical inventory (not pure services)
            if (substr($item['code'], 0, 2) !== 'SV' && $qty_for_stock > 0) {
                //stock master update quantity (use the resolved department for this ARN/item)
                $STOCK_MASTER = new StockMaster(NULL);
                $currentQty = $STOCK_MASTER->getAvailableQuantity($correctDepartmentId, $item['item_id']);
                $newQty = $currentQty - $qty_for_stock; // Use the correct quantity for stock management
                $STOCK_MASTER->quantity = $newQty;
                $STOCK_MASTER->updateQtyByItemAndDepartment($correctDepartmentId, $item['item_id'], $newQty);

                // Update stock transaction with ARN reference if available
                $STOCK_TRANSACTION = new StockTransaction(NULL);
                $STOCK_TRANSACTION->item_id = $item['item_id'];

                // Update stock_item_tmp for ARN-based inventory
                $STOCK_ITEM_TMP = new StockItemTmp(NULL);
                // Use negative qty to reduce stock when updating a specific ARN lot
                $qtyToDeduct = -abs($qty_for_stock); // Use correct quantity for stock deduction

                $updatedTmp = false;
                if ($arn_id) {
                    $updatedTmp = $STOCK_ITEM_TMP->updateQtyByArnId(
                        $arn_id,
                        $item['item_id'],
                        $correctDepartmentId, // Use the correct department for this ARN
                        $qtyToDeduct
                    );
                }

                // If we don't have a valid ARN or the direct update failed (e.g. quotation-based invoice),
                // fall back to deducting from the latest ARN lots for this item + department.
                if (!$updatedTmp) {
                    $deductResult = $STOCK_ITEM_TMP->deductFromLatestArnLots(
                        $item['item_id'],
                        $correctDepartmentId,
                        $qty_for_stock
                    );

                    if (!$deductResult['success']) {
                        error_log("[sales-invoice] Failed to deduct stock_item_tmp for item {$item['item_id']} in dept {$correctDepartmentId}: " . ($deductResult['message'] ?? 'unknown error'));
                    }
                }


                //stock transaction table update
                $STOCK_TRANSACTION->type = 4; // get this id from stock adjustment type table PK
                $STOCK_TRANSACTION->date = date("Y-m-d");
                $STOCK_TRANSACTION->qty_in = 0;
                $STOCK_TRANSACTION->qty_out = $qty_for_stock; // Use correct quantity for transaction record
                $STOCK_TRANSACTION->remark = "INVOICE #$invoiceId " . (!empty($item['arn_id']) ? "(ARN: {$item['arn']}) " : "") . "Issued " . date("Y-m-d H:i:s");
                $STOCK_TRANSACTION->created_at = date("Y-m-d H:i:s");
                $STOCK_TRANSACTION->create();
            }

            if ($paymentType == 1) {
                $payments = json_decode($_POST['payments'], true); // decode JSON â†’ array

                if (is_array($payments)) {
                    foreach ($payments as $payment) {
                        $INVOICE_PAYMENT = new InvoicePayment(NULL);
                        $INVOICE_PAYMENT->invoice_id  = $invoiceTableId;
                        $INVOICE_PAYMENT->method_id   = $payment['method_id'];
                        $INVOICE_PAYMENT->amount      = $payment['amount'];
                        $INVOICE_PAYMENT->reference_no = $payment['reference_no'] ?? null;
                        $INVOICE_PAYMENT->bank_name    = $pssayment['bank_name'] ?? null;
                        $INVOICE_PAYMENT->cheque_date  = $payment['cheque_date'] ?? null;

                        $res = $INVOICE_PAYMENT->create();
                    }
                }
            }
            //audit log 
            $AUDIT_LOG = new AuditLog(NUll);
            $AUDIT_LOG->ref_id = $invoiceTableId;
            $AUDIT_LOG->ref_code = $_POST['invoice_no'];
            $AUDIT_LOG->action = 'CREATE';
            $AUDIT_LOG->description = 'CREATE INVOICE NO #' . $invoiceTableId;
            $AUDIT_LOG->user_id = $_SESSION['id'];
            $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
            $AUDIT_LOG->create();
        }

        echo json_encode([
            "status" => 'success',
            "invoice_id" => $invoiceTableId,
            "sub_total" => $totalSubTotal,
            "discount" => $totalDiscount,
            "vat" => $tax,
            "grand_total" => $grandTotal
        ]);
        exit();
    } else {
        echo json_encode(["status" => 'error']);
        exit();
    }
}


// Update invoice details
if (isset($_POST['update'])) {
    $invoiceId = $_POST['invoice_id']; // Retrieve invoice ID

    // Create SalesInvoice object and load the data by ID
    $SALES_INVOICE = new SalesInvoice($invoiceId);

    // Update invoice details
    $SALES_INVOICE->invoice_date = $_POST['invoice_date']; // You can update the date or other details here
    $SALES_INVOICE->company_id = $_POST['company_id'];
    $SALES_INVOICE->customer_id = $_POST['customer_id'];
    $SALES_INVOICE->customer_name = ucwords(strtolower(trim($_POST['customer_name'])));
    $SALES_INVOICE->customer_mobile = $_POST['customer_mobile'];
    $SALES_INVOICE->customer_address = ucwords(strtolower(trim($_POST['customer_address'])));
    $SALES_INVOICE->recommended_person = isset($_POST['recommended_person']) ? ucwords(strtolower(trim($_POST['recommended_person']))) : null;


    // Attempt to update the invoice
    $result = $SALES_INVOICE->update();

    if ($result) {
        $result = [
            "status" => 'success'
        ];
        echo json_encode($result);
        exit();
    } else {
        $result = [
            "status" => 'error'
        ];
        echo json_encode($result);
        exit();
    }
}

if (isset($_POST['filter'])) {

    $SALES_INVOICE = new SalesInvoice();
    $response = $SALES_INVOICE->fetchInvoicesForDataTable($_REQUEST);


    echo json_encode($response);
    exit;
}

if (isset($_POST['get_by_id'])) {

    $SALES_INVOICE = new SalesInvoice();
    $response = $SALES_INVOICE->getByID($_POST['id']);

    $CUSTOMER_MASTER = new CustomerMaster($response['customer_id']);
    $response['customer_code'] = $CUSTOMER_MASTER->code;
    $response['customer_name'] = $CUSTOMER_MASTER->name;
    $response['customer_vat_no'] = $CUSTOMER_MASTER->vat_no;
    $response['customer_address'] = $CUSTOMER_MASTER->address;
    $response['customer_mobile'] = $CUSTOMER_MASTER->mobile_number;
    $response['recommended_person'] = $response['recommended_person'] ?? null;

    echo json_encode($response);
    exit;
}



// Delete invoice
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $invoice = new SalesInvoice($_POST['id']);
    $result = $invoice->delete(); // Make sure this method exists in your class

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}



if (isset($_POST['action']) && $_POST['action'] == 'latest') {
    $SALES_INVOICE = new SalesInvoice();
    $invoices = $SALES_INVOICE->latest();

    echo json_encode(["data" => $invoices]);
    exit();
}


if (isset($_POST['action']) && $_POST['action'] == 'search') {
    $SALES_INVOICE = new SalesInvoice();
    $invoices = $SALES_INVOICE->search($_POST['q']);

    echo json_encode(["data" => $invoices]);
    exit();
}


// Handle cancel invoice action
// Check invoice status
if (isset($_POST['action']) && $_POST['action'] == 'check_status') {
    $invoiceId = $_POST['id'];
    $SALES_INVOICE = new SalesInvoice($invoiceId);
    echo json_encode(['is_cancelled' => ($SALES_INVOICE->is_cancel == 1)]);
    exit();
}

// Cancel invoice
if (isset($_POST['action']) && $_POST['action'] == 'cancel') {


    $invoiceId = $_POST['id'];
    $arnIds = isset($_POST['arnIds']) ? $_POST['arnIds'] : [];

    $SALES_INVOICE = new SalesInvoice($invoiceId);




    if ($SALES_INVOICE->is_cancel == 1) {
        echo json_encode(['status' => 'already_cancelled']);
        exit();
    }
    $result = $SALES_INVOICE->cancel();

    if (is_array($result) && $result['success']) {
        $STOCK_TRANSACTION = new StockTransaction(NULL);
        $SALES_INVOICE_ITEM = new SalesInvoiceItem(NULL);
        $STOCK_ITEM_TMP = new StockItemTmp(NULL);

        $items = $SALES_INVOICE_ITEM->getItemsByInvoiceId($invoiceId);

        $CUSTOMER_MASTER = new CustomerMaster($SALES_INVOICE->customer_id);
        $CUSTOMER_MASTER->updateCustomerOutstanding($SALES_INVOICE->customer_id, $SALES_INVOICE->grand_total, false);


        foreach ($items as $item) {

            // Extract ARN ID and Department from item_name
            $arnId = null;
            $arnDepartmentId = $SALES_INVOICE->department_id; // fallback to invoice department

            if (strpos($item['item_name'], '|ARN:') !== false) {
                preg_match('/\|ARN:(\d+)\|DEPT:(\d+)/', $item['item_name'], $matches);
                if (isset($matches[1]) && isset($matches[2])) {
                    $arnId = (int)$matches[1];
                    $arnDepartmentId = (int)$matches[2];
                }
            }

            // Check if this is a Service (SV) - Services don't have ARN metadata in item_name
            $isService = (strpos($item['item_name'], '|ARN:') === false && $item['item_code'] != 0);

            if ($item['item_code'] != 0 && !$isService) {
                $STOCK_MASTER = new StockMaster(NULL);

                // Add quantity back to the ARN's original department, not invoice department
                $currentQty = $STOCK_MASTER->getAvailableQuantity($arnDepartmentId, $item['item_code']);
                $newQty = $currentQty + $item['quantity'];
                $STOCK_MASTER->quantity = $newQty;
                $STOCK_MASTER->updateQtyByItemAndDepartment($arnDepartmentId, $item['item_code'], $newQty);

                // Update stock transaction with ARN reference if available
                $STOCK_TRANSACTION->item_id = $item['item_code'];
                $STOCK_TRANSACTION->type = 14; // get this id from stock adjustment type table PK
                $STOCK_TRANSACTION->date = date("Y-m-d");
                $STOCK_TRANSACTION->qty_in = $item['quantity'];
                $STOCK_TRANSACTION->qty_out = 0;
                $STOCK_TRANSACTION->remark = "INVOICE CANCELLED #$invoiceId " . ($arnId ? "(ARN: {$arnId}) " : "") . "Cancelled " . date("Y-m-d H:i:s");
                $STOCK_TRANSACTION->created_at = date("Y-m-d H:i:s");
                $STOCK_TRANSACTION->create();

                // Add back quantity to the specific ARN in its original department
                if ($arnId) {
                    $qtyToAdd = abs($item['quantity']);
                    $STOCK_ITEM_TMP->updateQtyByArnId($arnId, $item['item_code'], $arnDepartmentId, $qtyToAdd);
                } else {
                    // No explicit ARN recorded (e.g. quotation-based or FIFO deduction) – restore using FIFO
                    $qtyToAdd = abs($item['quantity']);
                    $STOCK_ITEM_TMP->addBackQuantity($item['item_code'], $arnDepartmentId, $qtyToAdd);
                }
            } elseif ($item['item_code'] == 0) {
                $SERVICE_ITEM = new ServiceItem($item['service_item_code']);
                $currentQty = $SERVICE_ITEM->qty;
                $newQty = $currentQty + $item['quantity'];
                $SERVICE_ITEM->qty = $newQty;
                $SERVICE_ITEM->update();
            }
        }


        //audit log
        $AUDIT_LOG = new AuditLog(NUll);
        $AUDIT_LOG->ref_id = $invoiceId;
        $AUDIT_LOG->ref_code = $invoiceId;
        $AUDIT_LOG->action = 'CANCEL';
        $AUDIT_LOG->description = 'CANCEL INVOICE NO #' . $SALES_INVOICE->invoice_no;
        $AUDIT_LOG->user_id = $_SESSION['id'];
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $result =   $AUDIT_LOG->create();

        if ($result) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    } else {
        // Handle cancellation failure with specific error message
        $errorMessage = 'Failed to cancel invoice';
        if (is_array($result) && isset($result['message'])) {
            $errorMessage = $result['message'];
        }
        echo json_encode(['status' => 'error', 'message' => $errorMessage]);
    }
    exit();
}
