<?php

include '../../class/include.php';

header('Content-Type: application/json; charset=UTF8');

// Check invoice exists and get invoice data
if (isset($_POST['action']) && $_POST['action'] == 'check_invoice_exists') {
    $invoice_no = trim($_POST['invoice_no']);
    $SALES_INVOICE = new SalesInvoice(NULL);
    $invoice = $SALES_INVOICE->getInvoiceByNo($invoice_no);

    if ($invoice) {
        $CUSTOMER = new CustomerMaster($invoice['customer_id']);
        $DEPARTMENT = new DepartmentMaster($invoice['department_id']);

        echo json_encode([
            'exists' => true,
            'invoice' => $invoice,
            'customer' => [
                'id' => $CUSTOMER->id,
                'code' => $CUSTOMER->code,
                'name' => $CUSTOMER->name,
                'address' => $CUSTOMER->address,
                'mobile_number' => $CUSTOMER->mobile_number
            ],
            'department' => [
                'id' => $DEPARTMENT->id,
                'name' => $DEPARTMENT->name
            ]
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit();
}

// Fetch invoice items
if (isset($_POST['action']) && $_POST['action'] == 'fetch_invoice_items') {
    $invoice_id = $_POST['invoice_id'];

    $SALES_INVOICE_ITEM = new SalesInvoiceItem(NULL);
    $items = $SALES_INVOICE_ITEM->getByInvoiceIdWithReturns($invoice_id);

    $result = [];
    foreach ($items as $item) {
        // Extract item_id from item_code if needed, or use the id field
        $item_id = isset($item['item_code']) ? $item['item_code'] : $item['item_code'];

        $item_code_display = $item['item_code'];
        if (!empty($item['item_code']) && $item['item_code'] != 0) {
            $ITEM_MASTER = new ItemMaster($item['item_code']);
            if (!empty($ITEM_MASTER->code)) {
                $item_code_display = $ITEM_MASTER->code;
            }
        } elseif (isset($item['service_item_code']) && $item['service_item_code'] != 0 && class_exists('ServiceItem')) {
            $SERVICE_ITEM = new ServiceItem($item['service_item_code']);
            if (!empty($SERVICE_ITEM->item_code)) {
                $item_code_display = $SERVICE_ITEM->item_code;
            }
        }

        $result[] = [
            'id' => $item['id'],
            'item_id' => $item_id,
            'item_code' => $item_code_display,
            'item_name' => $item['item_name'],
            'quantity' => $item['available_quantity'], // Use available quantity instead of original quantity
            'original_quantity' => $item['quantity'], // Keep original for reference
            'returned_quantity' => $item['returned_quantity'], // Include returned quantity for reference
            'unit_price' => isset($item['price']) ? $item['price'] : $item['customer_price'],
            'customer_price' => $item['customer_price'],
            'dealer_price' => $item['dealer_price'],
            'discount' => isset($item['discount']) ? $item['discount'] : '0.00',
            'total' => $item['total']
        ];
    }

    echo json_encode(['items' => $result]);
    exit();
}

// Get next sales return number (for display on form)
if (isset($_POST['action']) && $_POST['action'] == 'get_next_return_no') {
    $db = Database::getInstance();
    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(return_no, '/', -1) AS UNSIGNED)) as max_num FROM sales_return";
    $result = $db->readQuery($query);
    $row = mysqli_fetch_array($result);

    $nextNumber = ($row && $row['max_num']) ? $row['max_num'] + 1 : 1;
    $return_no = 'WT/SR/00/' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

    echo json_encode([
        'status' => 'success',
        'return_no' => $return_no
    ]);
    exit();
}

// Save sales return
if (isset($_POST['action']) && $_POST['action'] == 'save_sales_return') {
    $return_data = json_decode($_POST['return_data'], true);
    $return_items = json_decode($_POST['return_items'], true);

    // Validate return_date
    if (empty($return_data['return_date'])) {
        echo json_encode(['status' => 'error', 'type' => 'error', 'message' => 'Return date is required']);
        exit();
    }

    // Check if sales_return table exists
    $db = Database::getInstance();
    $table_check = $db->readQuery("SHOW TABLES LIKE 'sales_return'");
    if (mysqli_num_rows($table_check) == 0) {
        echo json_encode(['status' => 'error', 'type' => 'error', 'message' => 'Database table sales_return does not exist']);
        exit();
    }

    // Get the next return number by finding the highest existing return number
    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(return_no, '/', -1) AS UNSIGNED)) as max_num FROM sales_return";
    $result = $db->readQuery($query);
    $row = mysqli_fetch_array($result);
    
    $nextNumber = ($row && $row['max_num']) ? $row['max_num'] + 1 : 1;
    $return_no = 'WT/SR/00/' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

    error_log("Generated return number: " . $return_no . " (next after: " . ($row['max_num'] ?? 'none') . ")");

    // Validate return quantities against available quantities
    $SALES_INVOICE = new SalesInvoice(NULL);
    $invoice = $SALES_INVOICE->getInvoiceByNo($return_data['invoice_no']);
    
    if ($invoice) {
        $SALES_INVOICE_ITEM = new SalesInvoiceItem(NULL);
        $available_items = $SALES_INVOICE_ITEM->getByInvoiceIdWithReturns($invoice['id']);
        
        // Create a map of available quantities
        $available_map = [];
        foreach ($available_items as $avail_item) {
            $available_map[$avail_item['item_code']] = floatval($avail_item['available_quantity']);
        }
        
        // Validate each return item
        foreach ($return_items as $item) {
            $item_id = $item['item_id'];
            $return_qty = floatval($item['quantity']);
            
            if (!isset($available_map[$item_id])) {
                echo json_encode(['status' => 'error', 'type' => 'error', 'message' => 'Item ' . $item_id . ' not found in invoice']);
                exit();
            }
            
            if ($return_qty > $available_map[$item_id]) {
                echo json_encode(['status' => 'error', 'type' => 'error', 'message' => 'Return quantity (' . $return_qty . ') exceeds available quantity (' . $available_map[$item_id] . ') for item ' . $item_id]);
                exit();
            }
        }
    }

    // Create sales return
    $SALES_RETURN = new SalesReturn();
    $SALES_RETURN->return_no = $return_no;
    $SALES_RETURN->return_date = $return_data['return_date'];
    $SALES_RETURN->invoice_no = $return_data['invoice_no'];
    $SALES_RETURN->invoice_id = $invoice['id']; // Add invoice ID from fetched invoice
    $SALES_RETURN->customer_id = $return_data['customer_id'] ?: 1; // Default to customer ID 1 if empty
    $SALES_RETURN->total_amount = $return_data['total_amount'];
    $SALES_RETURN->return_reason = $return_data['return_reason'];
    $SALES_RETURN->remarks = $return_data['remarks'];
    $SALES_RETURN->is_damaged = isset($return_data['is_damaged']) ? (int)$return_data['is_damaged'] : 0;
    $SALES_RETURN->created_by = $_SESSION['user']['id'] ?? 1;

    $return_id = $SALES_RETURN->create();

    if ($return_id) {
        // Save return items
        foreach ($return_items as $item) {
            $RETURN_ITEM = new SalesReturnItem();
            $RETURN_ITEM->return_id = $return_id;
            $RETURN_ITEM->item_id = $item['item_id'];
            $RETURN_ITEM->quantity = $item['quantity'];
            $RETURN_ITEM->unit_price = $item['unit_price'];
            $RETURN_ITEM->discount = $item['discount'];
            $RETURN_ITEM->tax = $item['tax'];
            $RETURN_ITEM->net_amount = $item['net_amount'];
            $RETURN_ITEM->remarks = $item['remarks'] ?? '';
            
            $item_result = $RETURN_ITEM->create();
            error_log("Item created for return $return_id: " . $item_result);
            
            if (!$item_result) {
                error_log("Failed to create item: " . print_r($item, true));
            }
        }

        // Handle stock management based on damage flag
        $is_damaged = $SALES_RETURN->is_damaged;
        
        if ($is_damaged == 0) {
            // NOT damaged - return items to stock
            $STOCK_ITEM_TMP = new StockItemTmp();
            $DEPARTMENT_ID = $invoice['department_id'] ?? 1; // Use department from invoice or default
            
            foreach ($return_items as $item) {
                if ($item['quantity'] > 0) {
                    $stock_result = $STOCK_ITEM_TMP->addBackQuantity($item['item_id'], $DEPARTMENT_ID, $item['quantity']);
                    if (!$stock_result) {
                        error_log("Failed to return item " . $item['item_id'] . " to stock");
                    }
                }
            }
        } else {
            // DAMAGED items - do not return to stock, could log as damaged if needed
            error_log("Damaged items return - not returning to stock: " . print_r($return_items, true));
            // TODO: Could implement damaged items logging here if needed
        }

        echo json_encode(['status' => 'success', 'type' => 'success', 'return_id' => $return_id, 'return_no' => $return_no]);
    } else {
        error_log("Failed to create sales return");
        echo json_encode(['status' => 'error', 'type' => 'error', 'message' => 'Failed to create sales return']);
    }
    exit();
}

// Get sales returns
if (isset($_POST['action']) && $_POST['action'] == 'get_sales_returns') {
    $SALES_RETURN = new SalesReturn();
    $returns = $SALES_RETURN->all();

    $result = [];
    foreach ($returns as $return) {
        $CUSTOMER = new CustomerMaster($return['customer_id']);
        $result[] = [
            'id' => $return['id'],
            'return_no' => $return['return_no'],
            'return_date' => $return['return_date'],
            'invoice_no' => $return['invoice_no'],
            'customer_name' => $CUSTOMER->name,
            'total_amount' => $return['total_amount'],
            'return_reason' => $return['return_reason']
        ];
    }

    echo json_encode(['returns' => $result]);
    exit();
}

// Get sales return details
if (isset($_POST['action']) && $_POST['action'] == 'get_sales_return_details') {
    $return_id = $_POST['return_id'];

    $SALES_RETURN = new SalesReturn($return_id);
    $SALES_RETURN_ITEM = new SalesReturnItem();
    $items = $SALES_RETURN_ITEM->getByReturnId($return_id);

    $CUSTOMER = new CustomerMaster($SALES_RETURN->customer_id);
    $db = Database::getInstance();

    // Get department from the original invoice
    $invoice_query = "SELECT * FROM `sales_invoice` WHERE `invoice_no` = '" . $SALES_RETURN->invoice_no . "' LIMIT 1";
    $invoice_result = mysqli_fetch_array($db->readQuery($invoice_query));
    $DEPARTMENT = new DepartmentMaster($invoice_result ? $invoice_result['department_id'] : null);
    $invoice_date = $invoice_result ? $invoice_result['invoice_date'] : null;
    $payment_type = $invoice_result ? $invoice_result['payment_type'] : null;

    $return_data = [
        'id' => $SALES_RETURN->id,
        'return_no' => $SALES_RETURN->return_no,
        'return_date' => $SALES_RETURN->return_date,
        'invoice_no' => $SALES_RETURN->invoice_no,
        'customer' => [
            'id' => $CUSTOMER->id,
            'code' => $CUSTOMER->code,
            'name' => $CUSTOMER->name,
            'address' => $CUSTOMER->address,
            'mobile_number' => $CUSTOMER->mobile_number
        ],
        'department' => [
            'id' => $DEPARTMENT->id ?? null,
            'name' => $DEPARTMENT->name ?? ''
        ],
        'total_amount' => $SALES_RETURN->total_amount,
        'return_reason' => $SALES_RETURN->return_reason,
        'remarks' => $SALES_RETURN->remarks,
        'is_damaged' => $SALES_RETURN->is_damaged,
        'invoice_date' => $invoice_date,
        'payment_type' => $payment_type
    ];

    error_log("Return data: " . print_r($return_data, true));
    error_log("Items count: " . count($items));

    $item_data = [];
    foreach ($items as $item) {
        $ITEM_MASTER = new ItemMaster($item['item_id']);
        
        // Get original invoice quantity by joining with sales_invoice_item
        $invoice_item_query = "SELECT quantity FROM `sales_invoice_items` WHERE `item_code` = '" . $item['item_id'] . "' AND `invoice_id` = (SELECT id FROM `sales_invoice` WHERE `invoice_no` = '" . $SALES_RETURN->invoice_no . "' LIMIT 1)";
        $invoice_result = mysqli_fetch_array($db->readQuery($invoice_item_query));
        $original_quantity = $invoice_result ? $invoice_result['quantity'] : $item['quantity'];
        
        $item_data[] = [
            'id' => $item['id'],
            'item_id' => $item['item_id'],
            'item_code' => $ITEM_MASTER->code,
            'item_name' => $ITEM_MASTER->name,
            'quantity' => $item['quantity'], // Return quantity
            'original_quantity' => $original_quantity, // Original invoice quantity
            'unit_price' => $item['unit_price'],
            'discount' => $item['discount'],
            'tax' => $item['tax'],
            'net_amount' => $item['net_amount'],
            'remarks' => $item['remarks']
        ];
    }

    error_log("Item data: " . print_r($item_data, true));

    echo json_encode([
        'return' => $return_data,
        'items' => $item_data
    ]);
    exit();
}

// Fetch all invoices for modal
if (isset($_POST['action']) && $_POST['action'] == 'fetch_invoices') {
    $SALES_INVOICE = new SalesInvoice(NULL);
    $invoices = $SALES_INVOICE->getInvoicesWithAvailableItems();

    $result = [];
    foreach ($invoices as $invoice) {
        $CUSTOMER = new CustomerMaster($invoice['customer_id']);
        $result[] = [
            'id' => $invoice['id'],
            'invoice_no' => $invoice['invoice_no'],
            'invoice_date' => $invoice['invoice_date'],
            'customer_name' => $CUSTOMER->name,
            'customer_id' => $CUSTOMER->id,
            'grand_total' => $invoice['grand_total'],
            'payment_type' => $invoice['payment_type'] == 1 ? 'Cash' : 'Credit'
        ];
    }

    echo json_encode(['status' => 'success', 'invoices' => $result]);
    exit();
}

// Delete sales return
if (isset($_POST['action']) && $_POST['action'] == 'delete_sales_return') {
    $return_id = $_POST['return_id'];

    // Get return details before deleting to check if items were returned to stock
    $SALES_RETURN = new SalesReturn($return_id);
    $SALES_RETURN_ITEM = new SalesReturnItem();
    $return_items = $SALES_RETURN_ITEM->getByReturnId($return_id);

    // Delete return items first
    $SALES_RETURN_ITEM->deleteByReturnId($return_id);

    // Delete the return
    $result = $SALES_RETURN->delete();

    if ($result) {
        // Reverse stock changes only if items were previously returned to stock (not damaged)
        if ($SALES_RETURN->is_damaged == 0) {
            $STOCK_ITEM_TMP = new StockItemTmp();

            // Get invoice details to determine department
            $invoice_query = "SELECT department_id FROM `sales_invoice` WHERE `invoice_no` = '" . $SALES_RETURN->invoice_no . "' LIMIT 1";
            $invoice_result = mysqli_fetch_array($db->readQuery($invoice_query));
            $DEPARTMENT_ID = $invoice_result ? $invoice_result['department_id'] : 1;

            foreach ($return_items as $item) {
                if ($item['quantity'] > 0) {
                    // Deduct the returned quantity from stock since we're deleting the return
                    $deduct_result = $STOCK_ITEM_TMP->deductFromLatestArnLots($item['item_id'], $DEPARTMENT_ID, $item['quantity']);
                    if (!$deduct_result['success']) {
                        error_log("Failed to deduct item " . $item['item_id'] . " from stock during return deletion");
                    }
                }
            }
        } else {
            // Items were damaged, so they were never added back to stock - no reversal needed
            error_log("Damaged return deletion - no stock reversal needed");
        }

        echo json_encode(['status' => 'success', 'type' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'type' => 'error', 'message' => 'Failed to delete sales return']);
    }
    exit();
}

?>
