<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

$data = json_decode(file_get_contents("php://input"), true);

// ---------- Check BL No Exists ----------
if (isset($_POST['check_bl_no'])) {
    $bl_no = trim($_POST['check_bl_no']);
    if (empty($bl_no)) {
        echo json_encode(['status' => 'not_exists']);
        exit();
    }

    $ARN = new ArnMaster(NULL);
    $exists = $ARN->isBlNoExists($bl_no);

    echo json_encode(['status' => $exists ? 'exists' : 'not_exists']);
    exit();
}

// ---------- Create ARN ----------
if (isset($data['create'])) {
    // Check if this is a company ARN adjust
    $isCompanyArnAdjust = isset($data['company_arn_adjust']) && $data['company_arn_adjust'] === true;
    
    // 1. Collect master data
    $ARN = new ArnMaster(NULL);
    
    // Handle ARN number for company ARN adjust
    if ($isCompanyArnAdjust) {
        // For company ARN adjust, append company name from session to ARN number
        $companyName = isset($_SESSION['company_name']) ? $_SESSION['company_name'] : 'COMPANY';
        $ARN->arn_no = $data['arn_no'] . '/' . $companyName;
    } else {
        $ARN->arn_no = $data['arn_no'];
    }
    
    // Handle special company ARN adjust supplier
    if ($data['supplier'] === 'COMPANY_ARN_ADJUST') {
        // For company ARN adjust, use a special supplier handling
        // The supplier fields contain SM/1/1/companyName format
        $supplierName = isset($data['supplier_name']) ? $data['supplier_name'] : 'SM/1/1/' . (isset($_SESSION['company_name']) ? $_SESSION['company_name'] : 'COMPANY');
        $ARN->supplier_id = 0; // Use 0 or a special ID for company ARN adjust
    } else {
        $ARN->supplier_id = $data['supplier'];
    }
    $ARN->lc_tt_no = $data['lc_no'] ?? '';
    $ARN->ci_no = $data['ci_no'] ?? '';
    $ARN->bl_no = $data['bl_no'] ?? '';
    $ARN->pi_no = $data['pi_no'] ?? '';
    $ARN->brand = $data['brand'] ?? '';
    $ARN->category = $data['category'] ?? '';
    $ARN->country = $data['country'] ?? '';
    $ARN->order_by = $data['order_by'] ?? '';
    $ARN->purchase_type = $data['purchase_type'] ?? '';
    $ARN->arn_status = $data['arn_status'];
    $ARN->credit_note_amount = $data['credit_note_amount'];
    $ARN->delivery_date = $data['delivery_date'];
    $ARN->invoice_date = $data['invoice_date'];
    $ARN->entry_date = $data['entry_date'];
    $ARN->total_arn_value = $data['total_arn'];
    $ARN->total_discount = $data['total_discount'];
    $ARN->total_received_qty = $data['total_received_qty'];
    $ARN->total_order_qty = $data['total_order_qty'];
    $ARN->department = $data['department_id'];
    // Save the human-readable PO number (code) in arn_master.po_no
    $ARN->po_no = isset($data['po_no']) ? $data['po_no'] : '';
    $ARN->po_date = $data['purchase_date'];
    $ARN->payment_type = $data['payment_type'];
    
    // Set paid amount to 0 for company ARN adjust (no payment)
    $ARN->paid_amount = '0';
    $CUSTOMER = new CustomerMaster($ARN->supplier_id);
    $CUSTOMER->outstanding = $ARN->total_arn_value;
    $CUSTOMER->update();

    // 2. Update Purchase Order Status using numeric PO ID
    $purchaseOrderId = isset($data['purchase_order_id']) ? (int)$data['purchase_order_id'] : 0;
    if ($purchaseOrderId > 0) {
        $PURCHASE_ORDER = new PurchaseOrder($purchaseOrderId);
        $PURCHASE_ORDER->status = 1;
        $PURCHASE_ORDER->update();
    }

    // 3. Create ARN master
    $arn_id = $ARN->create();

    if ($arn_id) {
        // 4. Log audit
        $AUDIT_LOG = new AuditLog(NULL);
        $AUDIT_LOG->ref_id = $arn_id;
        $AUDIT_LOG->ref_code = $ARN->arn_no;
        $AUDIT_LOG->action = 'CREATE';
        $description = 'CREATE ARN NO #' . $ARN->arn_no;
        if ($isCompanyArnAdjust) {
            $description .= ' (Company ARN Adjust - No Payment Required)';
        }
        $AUDIT_LOG->description = $description;
        $AUDIT_LOG->user_id = $_SESSION['id'];
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        // Document Tracking ID update
        (new DocumentTracking(null))->incrementDocumentId('arn');

        // 5. Process ARN items
        foreach ($data['items'] as $item) {
            $recQty = isset($item['rec_qty']) ? (float)$item['rec_qty'] : 0;
            $itemId = (int)$item['item_id'];
            $departmentId = (int)$data['department_id'];

            // ARN Item
            $ARN_ITEM = new ArnItem(NULL);
            $ARN_ITEM->arn_id = $arn_id;
            $ARN_ITEM->item_code = $itemId;
            $ARN_ITEM->order_qty = $item['order_qty'];
            $ARN_ITEM->received_qty = $recQty;
            $ARN_ITEM->discount_2 = $item['dis2'];
            $ARN_ITEM->discount_3 = $item['dis3'];
            $ARN_ITEM->discount_4 = $item['dis4'];
            $ARN_ITEM->discount_5 = $item['dis5'];
            $ARN_ITEM->discount_6 = $item['dis6'];
            $ARN_ITEM->discount_7 = $item['dis7'];
            $ARN_ITEM->discount_8 = $item['dis8'];
            $ARN_ITEM->final_cost = $item['actual_cost'];
            $ARN_ITEM->unit_total = $item['unit_total'];
            $ARN_ITEM->list_price = $item['list_price'];
            $ARN_ITEM->invoice_price = $item['invoice_price'];
            $ARN_ITEM->created_at = date("Y-m-d H:i:s");
            $ARN_ITEM->create();

            $stockMaster = new StockMaster();

            if ($recQty >= 0) {
                // Stock Item Temporary (additions)
                $STOCK_ITEM_TMP = new StockItemTmp();
                $STOCK_ITEM_TMP->arn_id = $arn_id;
                $STOCK_ITEM_TMP->item_id = $itemId;
                $STOCK_ITEM_TMP->qty = $recQty;
                $STOCK_ITEM_TMP->cost = $item['actual_cost'];
                $STOCK_ITEM_TMP->list_price = $item['list_price'];
                $STOCK_ITEM_TMP->invoice_price = $item['invoice_price'];
                $STOCK_ITEM_TMP->department_id = $departmentId;
                $STOCK_ITEM_TMP->status = 1;
                $STOCK_ITEM_TMP->create();

                // Stock Master update for additions
                $existingStock = $stockMaster->getAvailableQuantity($ARN->department, $itemId);
                if ($existingStock > 0) {
                    $newQty = $existingStock + $recQty;
                    $stockMaster->updateQtyByItemAndDepartment($ARN->department, $itemId, $newQty);
                } else {
                    $stockMaster->item_id = $itemId;
                    $stockMaster->department_id = $ARN->department;
                    $stockMaster->quantity = $recQty;
                    $stockMaster->created_at = date("Y-m-d H:i:s");
                    $stockMaster->is_active = 1;
                    $stockMaster->create();
                }

                // Item Master update
                $ITEM_master = new ItemMaster($itemId);
                $ITEM_master->list_price = $item['list_price'];
                $ITEM_master->invoice_price = $item['invoice_price'];
                $ITEM_master->update();

                // Stock Transaction log for additions
                $stockTransaction = new StockTransaction(NULL);
                $stockTransaction->item_id = $itemId;
                $stockTransaction->type = 2; // Stock In
                $stockTransaction->date = date("Y-m-d");
                $stockTransaction->qty_in = $recQty;
                $stockTransaction->qty_out = 0;
                $stockTransaction->remark = "ARN #{$ARN->arn_no} received";
                $stockTransaction->created_at = date("Y-m-d H:i:s");
                $stockTransaction->create();
            } else {
                // Handle deductions for negative received quantities
                $deductQty = abs($recQty);
                $stockItemTmpManager = new StockItemTmp();
                $deductResult = $stockItemTmpManager->deductFromLatestArnLots($itemId, $departmentId, $deductQty);

                if (!$deductResult['success']) {
                    $available = isset($deductResult['available']) ? (float)$deductResult['available'] : 0;
                    $message = isset($deductResult['message']) ? $deductResult['message'] : 'Unable to deduct quantity from previous ARNs.';
                    $message .= " Requested {$deductQty}, available {$available}.";
                    echo json_encode(["status" => 'error', "message" => $message]);
                    exit();
                }

                $adjustResponse = $stockMaster->adjustQuantity($itemId, $departmentId, $deductQty, 'deductions', "Company ARN adjust #{$ARN->arn_no}");
                if (!is_array($adjustResponse) || $adjustResponse['status'] !== 'success') {
                    $errorMsg = (is_array($adjustResponse) && isset($adjustResponse['message'])) ? $adjustResponse['message'] : 'Failed to adjust stock.';
                    echo json_encode(["status" => 'error', "message" => $errorMsg]);
                    exit();
                }
            }
        }

        echo json_encode(["status" => 'success' , "arn_id" => $arn_id,"supplier_id" => $ARN->supplier_id ]);
    } else {
        echo json_encode(["status" => 'error', "message" => "Failed to create ARN master."]);
    }

    exit();
}

// ---------- Delete ARN ----------
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $ARN = new ArnMaster($_POST['id']);
    $result = $ARN->delete();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete ARN.']);
    }
    exit();
}

if (isset($_POST['arn_id'])) {
    $arn_id = intval($_POST['arn_id']);
    $ARN = new ArnMaster(null);
    $items = $ARN->getByArnId($arn_id);
    echo json_encode($items);
}

if (isset($_POST['arn_id_cancel'])) {
    $arn_id = intval($_POST['arn_id_cancel']);
    $ARN = new ArnMaster(null);

    $result = $ARN->cancelArn($arn_id);

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to cancel ARN. Please try again.'
        ]);
    }
    exit;
}

if (isset($_POST['brand_id'], $_POST['category_id'])) {
    $brandId = (int)$_POST['brand_id'];
    $categoryId = (int)$_POST['category_id'];

    $brandWiseDis = new BrandWiseDis();
    $discounts = $brandWiseDis->getByBrand($brandId, $categoryId);
    
    $discount_01 = 0;
    $discount_02 = 0;
    $discount_03 = 0;
    
    if (!empty($discounts)) {
        $row = $discounts[0]; // first matching record
        $discount_01 = isset($row['discount_percent_01']) ? (float)$row['discount_percent_01'] : 0;
        $discount_02 = isset($row['discount_percent_02']) ? (float)$row['discount_percent_02'] : 0;
        $discount_03 = isset($row['discount_percent_03']) ? (float)$row['discount_percent_03'] : 0;
    }
    
    $total_discount = $discount_01 + $discount_02 + $discount_03;
  
    echo json_encode(['discount_01' => $discount_01, 'discount_02' => $discount_02, 'discount_03' => $discount_03, 'total_discount' => $total_discount]);
    exit();
}

if (isset($_POST['filter'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $db = Database::getInstance();
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $searchValue = isset($_POST['search']['value']) ? $db->escapeString($_POST['search']['value']) : '';
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
    $orderDir = isset($_POST['order'][0]['dir']) ? $db->escapeString($_POST['order'][0]['dir']) : 'asc';

    $columns = [
        0 => 'id',
        1 => 'arn_no',
        2 => 'invoice_date',
        3 => 'supplier_name',
        4 => 'payment_type',
        5 => 'total_arn_value',
        6 => 'paid_amount',
        7 => 'status'
    ];

    $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'arn_no';

    $query = "SELECT SQL_CALC_FOUND_ROWS am.id, am.arn_no, am.invoice_date, cm.code, cm.name, am.purchase_type, am.total_arn_value, am.paid_amount,
              CASE WHEN am.is_cancelled = 1 THEN 'Cancelled' ELSE 'Active' END as status
              FROM arn_master am
              LEFT JOIN customer_master cm ON am.supplier_id = cm.id
              WHERE 1=1";

    if (!empty($searchValue)) {
        $query .= " AND (am.arn_no LIKE '%$searchValue%' OR CONCAT(cm.code, ' - ', cm.name) LIKE '%$searchValue%')";
    }

    $query .= " ORDER BY $orderBy $orderDir LIMIT $start, $length";

    $result = $db->readQuery($query);

    $data = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $supplierName = (!empty($row['code']) && !empty($row['name'])) ? $row['code'] . ' - ' . $row['name'] : 'N/A';
            $data[] = [
                'id' => $row['id'],
                'arn_no' => $row['arn_no'],
                'invoice_date' => $row['invoice_date'],
                'supplier_name' => $supplierName,
                'payment_type' => $row['purchase_type'],
                'total_arn_value' => number_format($row['total_arn_value'], 2),
                'paid_amount' => number_format($row['paid_amount'], 2),
                'status' => $row['status']
            ];
        }

        $totalResult = $db->readQuery("SELECT FOUND_ROWS() as total");
        $totalRow = mysqli_fetch_assoc($totalResult);
        $totalRecords = $totalRow['total'];

        $response = [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ];

        echo json_encode($response);
        exit();
    }
}


