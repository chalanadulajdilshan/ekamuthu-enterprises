<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// ✅ Check if invoice number exists
if (isset($_POST['action']) && $_POST['action'] == 'check_invoice_id') {

    $invoice_no = trim($_POST['invoice_no']);
    $SALES_INVOICE = new SalesInvoice(NULL);
    $res = $SALES_INVOICE->checkInvoiceIdExist($invoice_no);

    echo json_encode(['exists' => $res]);
    exit();
}

if (isset($_POST['create'])) {

    $invoice_no = $_POST['invoice_no'];
    $items = json_decode($_POST['items'], true);
    $paid = $_POST['paid'];
    $payment_type = $_POST['payment_type'];
    
    // Debug: Check if items are properly decoded
    if (!$items || !is_array($items)) {
        echo json_encode(["status" => 'error', "message" => "Invalid items data"]);
        exit();
    }

    // Validate required fields
    if (empty($_POST['customer_id'])) {
        echo json_encode(["status" => 'error', "message" => "Customer ID is required"]);
        exit();
    }

    if (empty($_POST['customer_name'])) {
        echo json_encode(["status" => 'error', "message" => "Customer name is required"]);
        exit();
    }

    if (empty($_POST['dag_id'])) {
        echo json_encode(["status" => 'error', "message" => "DAG ID is required"]);
        exit();
    }

    $total_sub_total = 0;
    $total_discount = 0;
    $final_cost = 0;

    foreach ($items as $item) {

        // Check if it's the new format (with 'price') or old format (with 'payment')
        if (isset($item['price'])) {
            $price = floatval($item['price']); // This is the selling price
            $cost = isset($item['cost']) ? floatval($item['cost']) : 0; // This is the cost
            $qty = 1; // Each row is one item
        } else {
            $price = floatval($item['payment']);
            $cost = floatval($item['payment']); // Fallback for old format
            $qty = floatval($item['qty']);
        }

        // Validate that cost doesn't exceed price
        if ($cost > $price) {
            echo json_encode(["status" => 'error', "message" => "Cost cannot exceed the selling price for item: " . (isset($item['vehicle_no']) ? $item['vehicle_no'] : 'Unknown')]);
            exit();
        }
        
        $item_total = $price * $qty; // Use price for invoice total
        $cost_total = $cost * $qty; // Use cost for cost calculations

        $total_sub_total += $item_total; // Price goes to invoice totals
        $final_cost += $cost_total; // Cost goes to final_cost

        // Only update DagItem if dag_item_id is present
        if (isset($item['dag_item_id']) && !empty($item['dag_item_id'])) {
            $DAG_ITEM = new DagItem($item['dag_item_id']);
            if ($DAG_ITEM->id) { // Make sure the item exists
                $DAG_ITEM->total_amount = $price; // Price goes to total_amount
                $DAG_ITEM->casing_cost = $cost; // Cost goes to casing_cost
                $DAG_ITEM->is_invoiced = 1; // Mark this item as invoiced
                $DAG_ITEM->update();
            }
        }
    }

    // Check if all DAG items are invoiced before setting is_print = 1
    $DAG_ITEM_CHECK = new DagItem(null);
    $allItemsInvoiced = $DAG_ITEM_CHECK->areAllDagItemsInvoiced($_POST['dag_id']);
    
    if ($allItemsInvoiced) {
        // Only set is_print = 1 if all items are invoiced
        $DAG = new Dag($_POST['dag_id']);
        $DAG->is_print = 1;
        $DAG->update();
    }

    $net_total = $total_sub_total;

    $USER = new User($_SESSION['id']);
    $COMPANY_PROFILE = new CompanyProfile($USER->company_id);

    $vat = 0; // Initialize VAT
    if ($COMPANY_PROFILE->is_vat == 1) {
        $grand_total = $net_total + $vat;
    } else {
        $grand_total = $net_total;
    }

    $SALES_INVOICE = new SalesInvoice(null);
    $SALES_INVOICE->invoice_no = $invoice_no;
    $SALES_INVOICE->invoice_date = date('Y-m-d');
    $SALES_INVOICE->company_id = $USER->company_id; // Add missing company_id
    $SALES_INVOICE->customer_id = $_POST['customer_id'];
    $SALES_INVOICE->customer_name = $_POST['customer_name'];
    $SALES_INVOICE->customer_mobile = $_POST['customer_mobile'];
    $SALES_INVOICE->customer_address = $_POST['customer_address'];
    $SALES_INVOICE->recommended_person = $_POST['recommended_person'];
    $SALES_INVOICE->department_id = $_POST['department_id'];
    $SALES_INVOICE->sale_type = $_POST['payment_type'];
    $SALES_INVOICE->invoice_type = 'DAG'; // Set invoice type as DAG
    $SALES_INVOICE->ref_id = $_POST['dag_id']; // Reference to DAG ID
    $SALES_INVOICE->discount_type = 'percentage';
    $SALES_INVOICE->final_cost = $final_cost;
    $SALES_INVOICE->payment_type = $payment_type;
    $SALES_INVOICE->sub_total = $total_sub_total;
    $SALES_INVOICE->discount = $total_discount;
    $SALES_INVOICE->tax = $vat;
    $SALES_INVOICE->grand_total = $grand_total;
    $SALES_INVOICE->outstanding_settle_amount = 0; // Add missing field
    $SALES_INVOICE->remark = !empty($_POST['remark']) ? $_POST['remark'] : null;

    try {
        $invoice_id = $SALES_INVOICE->create();
    } catch (Exception $e) {
        echo json_encode(["status" => 'error', "message" => "Invoice creation failed: " . $e->getMessage()]);
        exit();
    }

    // If invoice creation successful
    if ($invoice_id) {
        // Document tracking update
        $DOCUMENT_TRACKING = new DocumentTracking(null);
        if ($payment_type == '1') {
            $DOCUMENT_TRACKING->incrementDocumentId('cash');
        } else if ($payment_type == '2') {
            $DOCUMENT_TRACKING->incrementDocumentId('credit');
        } else {
            $DOCUMENT_TRACKING->incrementDocumentId('invoice');
        }

        // Create invoice items for each DAG item
        foreach ($items as $item) {
            if (isset($item['is_dag']) && $item['is_dag']) {
                $SALES_INVOICE_ITEM = new SalesInvoiceItem(NULL);
                $SALES_INVOICE_ITEM->invoice_id = $invoice_id;
                $SALES_INVOICE_ITEM->item_code = 'DAG-' . $item['dag_item_id'];
                $SALES_INVOICE_ITEM->service_item_code = 'DAG-' . $_POST['dag_id'];
                $SALES_INVOICE_ITEM->item_name = $item['vehicle_no'] . ' - ' . $item['belt_design'] . ' - ' . (isset($item['size']) ? $item['size'] : '') . ' - ' . $item['serial_no'];
                $SALES_INVOICE_ITEM->price = $item['price'];
                $SALES_INVOICE_ITEM->quantity = 1;
                $SALES_INVOICE_ITEM->discount = 0;
                $SALES_INVOICE_ITEM->total = $item['price'];
                $SALES_INVOICE_ITEM->cost = $item['cost'];
                $SALES_INVOICE_ITEM->create();

                // Log audit
                $AUDIT_LOG = new AuditLog(NULL);
                $AUDIT_LOG->ref_id = $invoice_id;
                $AUDIT_LOG->ref_code = $invoice_no;
                $AUDIT_LOG->action = 'CREATE';
                $AUDIT_LOG->description = 'CREATE DAG INVOICE #' . $invoice_no . ' - Item: ' . $item['serial_no'];
                $AUDIT_LOG->user_id = $_SESSION['id'];
                $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
                $AUDIT_LOG->create();
            }
        }

        echo json_encode([
            "status" => 'success',
            "invoice_id" => $invoice_id,
            "sub_total" => $total_sub_total,
            "discount" => $total_discount,
            "vat" => $vat,
            "grand_total" => $grand_total
        ]);
        exit();
    } else {
        echo json_encode(["status" => 'error', "message" => "Failed to create invoice record"]);
        exit();
    }
}

// 🔄 Future updates / filtering / deletion can be added here if needed

?>