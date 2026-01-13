<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

if (isset($_POST['action']) && $_POST['action'] === 'get_available_qty') {

    $department_id = isset($_POST['department_id']) ? (int) $_POST['department_id'] : 0;
    $item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;

    if ($department_id > 0 && $item_id > 0) {
        $STOCK_MASTER = new StockMaster(NUll);


        $available_qty = $STOCK_MASTER->getAvailableQuantity($department_id, $item_id);

        echo json_encode([
            'status' => 'success',
            'available_qty' => $available_qty
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid department or item ID'
        ]);
    }

    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'get_available_qty_by_dates') {

    $item_id = (int) $_POST['item_id'];
    $department_id = (int) $_POST['department_id'];
    $days = isset($_POST['days']) ? (int) $_POST['days'] : 0;
    $date_from = $_POST['date_from'] ?? null;
    $date_to = $_POST['date_to'] ?? null;
    $show_all = $_POST['show_all'] ?? null;

    if ($item_id > 0 && $department_id > 0) {

        $STOCK = new StockTransaction();
        $available_qty = $STOCK->getAvailableQuantityByDepartment(
            $department_id,
            $item_id,
            $days,
            $date_from,
            $date_to,

        );

        echo json_encode([
            'status' => 'success',
            'available_qty' => $available_qty
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid input values.'
        ]);
    }

    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'get_transaction_records') {

    $item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
    $department_id = isset($_POST['department_id']) ? (int) $_POST['department_id'] : 0;
    $date_from = $_POST['date_from'] ?? null;
    $date_to = $_POST['date_to'] ?? null;


    if ($item_id > 0 && $department_id > 0 && $date_from && $date_to) {

        $STOCK = new StockTransaction();

        // Assuming your class has a method like this:
        $transactions = $STOCK->getTransactionRecords(
            $department_id,
            $item_id,
            $date_from,
            $date_to
        );

        echo json_encode([
            'status' => 'success',
            'transactions' => $transactions
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or missing input values.'
        ]);
    }

    exit();
}
if (isset($_POST['action']) && $_POST['action'] === 'get_department_stock_status') {

    $item_id = (int) $_POST['item_id'];

    if ($item_id > 0) {
        $STOCK_MASTER = new StockMaster();
        $DEPARTMENT_MASTER = new DepartmentMaster();


        $results = [];

        foreach ($DEPARTMENT_MASTER->all() as $dept) {
            $available_qty = $STOCK_MASTER->getAvailableQuantity($dept['id'], $item_id);
            $pending_orders = 10;

            $results[] = [
                'department_name' => $dept['name'],
                'available_qty' => $available_qty,
                'pending_orders' => $pending_orders,
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $results
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid item ID']);
    }

    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'get_department_stock') {
    $item_id = (int)$_POST['item_id'];

    if ($item_id > 0) {
        $departments = StockMaster::getDepartmentWiseStock($item_id);
        echo json_encode(['status' => 'success', 'data' => $departments]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid item ID']);
    }
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'create_stock_transfer') {

    $from = $_POST['department_id'];
    $to = $_POST['to_department_id'];
    $date = $_POST['transfer_date'];
    $codes = $_POST['item_codes'];
    $names = $_POST['item_names'];
    $qtys = $_POST['item_qtys'];

    if (!$from || !$to || !$date || empty($codes)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    $STOCK_MASTER = new StockMaster(null);

    //audit log
    $AUDIT_LOG = new AuditLog(NUll);
    $AUDIT_LOG->ref_id = 01;
    $AUDIT_LOG->ref_code = 'REF/STK/TRN/01';
    $AUDIT_LOG->action = 'TRN';
    $AUDIT_LOG->description = 'TRN STOCK NO # REF/TRN/ADJ/01';
    $AUDIT_LOG->user_id = $_SESSION['id'];
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    foreach ($codes as $index => $code) {
        $item_id = $code;
        $qty = isset($qtys[$index]) ? (int) $qtys[$index] : 0;

        if ($item_id && $qty > 0) {
            $result = $STOCK_MASTER->transferQuantity($item_id, $from, $to, $qty, "Transfer on $date");

            if ($result['status'] !== 'success') {
                echo json_encode([
                    'status' => 'error',
                    'message' => "Failed to transfer item code $code: " . $result['message']
                ]);
                exit;
            }

            // ARN Transfer Logic - Create new ARN entries for transferred items
            transferArnForItem($item_id, $from, $to, $qty, $date);
            
            // Update stock_item_tmp with new ARN IDs for transferred quantities
            updateStockItemTmpArnIds($item_id, $to, $qty);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => "Invalid item code or quantity for item: $code"
            ]);
            exit;
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Stock and ARN transfer completed successfully.']);
    exit;
}

/**
 * Transfer ARN entries for a specific item from source to target department
 */
function transferArnForItem($item_id, $from_dept, $to_dept, $transfer_qty, $date) {
    $db = Database::getInstance();
    
    // Get ARN items from source department that have this item
    $query = "
        SELECT ai.*, am.* 
        FROM arn_items ai 
        JOIN arn_master am ON ai.arn_id = am.id 
        WHERE ai.item_code = '{$item_id}' 
        AND am.department = '{$from_dept}' 
        AND ai.received_qty > 0 
        AND (am.is_cancelled IS NULL OR am.is_cancelled = 0)
        ORDER BY am.created_at ASC
    ";
    
    $result = $db->readQuery($query);
    $remaining_qty = $transfer_qty;
    
    // Check if query was successful and has results
    if (!$result || mysqli_num_rows($result) == 0) {
        return; // No ARN records found for this item in source department
    }
    
    while (($row = mysqli_fetch_assoc($result)) && $remaining_qty > 0) {
        if (!$row) break; // Safety check
        $available_qty = min($row['received_qty'], $remaining_qty);
        
        if ($available_qty > 0) {
            // Create new ARN Master for target department
            $ARN_MASTER = new ArnMaster();
            
            // Generate new ARN number with next sequential number
            $original_arn = $row['arn_no'];
            $transfer_suffix = "TRF-" . date('Ymd-His');
            
            // Parse the original ARN to get the base format and current number
            if (preg_match('/^(.+\/)(\d+)$/', $original_arn, $matches)) {
                $base_format = $matches[1]; // e.g., "DS/ARN/00/"
                $current_number = (int)$matches[2]; // e.g., 56
                
                // Get the highest ARN number with the same base format
                $db_temp = Database::getInstance();
                $max_query = "SELECT MAX(CAST(SUBSTRING_INDEX(arn_no, '/', -1) AS UNSIGNED)) as max_num 
                             FROM arn_master 
                             WHERE arn_no LIKE '" . $db_temp->escapeString($base_format) . "%' 
                             AND arn_no NOT LIKE '%/TRF-%'";
                $max_result = $db_temp->readQuery($max_query);
                $max_row = mysqli_fetch_assoc($max_result);
                $max_number = $max_row['max_num'] ? (int)$max_row['max_num'] : $current_number;
                
                $next_number = $max_number + 1;
                $new_arn_no = $base_format . str_pad($next_number, 2, '0', STR_PAD_LEFT) . "/" . $transfer_suffix;
            } else {
                // Fallback if pattern doesn't match
                $new_arn_no = $original_arn . "/" . $transfer_suffix;
            }
            
            // Copy ARN master data to new department
            $ARN_MASTER->arn_no = $new_arn_no;
            $ARN_MASTER->lc_tt_no = $row['lc_tt_no'];
            $ARN_MASTER->pi_no = $row['pi_no'];
            $ARN_MASTER->po_date = $row['po_date'];
            $ARN_MASTER->supplier_id = $row['supplier_id'];
            $ARN_MASTER->ci_no = $row['ci_no'];
            $ARN_MASTER->bl_no = $row['bl_no'];
            $ARN_MASTER->container_size = $row['container_size'];
            $ARN_MASTER->category = $row['category'];
            $ARN_MASTER->brand = $row['brand'];
            $ARN_MASTER->department = $to_dept; // New department
            $ARN_MASTER->po_no = $row['po_no'];
            $ARN_MASTER->country = $row['country'];
            $ARN_MASTER->order_by = $row['order_by'];
            $ARN_MASTER->purchase_type = $row['purchase_type'];
            $ARN_MASTER->arn_status = $row['arn_status'];
            $ARN_MASTER->remark = "Transferred from Dept {$from_dept} on {$date}";
            $ARN_MASTER->invoice_date = $row['invoice_date'];
            $ARN_MASTER->entry_date = date('Y-m-d');
            $ARN_MASTER->delivery_date = $row['delivery_date'];
            $ARN_MASTER->credit_note_amount = 0;
            $ARN_MASTER->sub_arn_value = isset($row['sub_arn_value']) && $row['total_received_qty'] > 0 ? 
                ($row['sub_arn_value'] / $row['total_received_qty']) * $available_qty : 0;
            $ARN_MASTER->total_discount = isset($row['total_discount']) && $row['total_received_qty'] > 0 ? 
                ($row['total_discount'] / $row['total_received_qty']) * $available_qty : 0;
            $ARN_MASTER->total_arn_value = isset($row['total_arn_value']) && $row['total_received_qty'] > 0 ? 
                ($row['total_arn_value'] / $row['total_received_qty']) * $available_qty : 0;
            $ARN_MASTER->total_received_qty = $available_qty;
            $ARN_MASTER->total_order_qty = $available_qty;
            $ARN_MASTER->paid_amount = isset($row['paid_amount']) && $row['total_received_qty'] > 0 ? 
                ($row['paid_amount'] / $row['total_received_qty']) * $available_qty : 0;
            
            $new_arn_id = $ARN_MASTER->create();
            
            if ($new_arn_id) {
                // Create new ARN Item
                $ARN_ITEM = new ArnItem();
                $ARN_ITEM->arn_id = $new_arn_id;
                $ARN_ITEM->item_code = $item_id;
                $ARN_ITEM->order_qty = $available_qty;
                $ARN_ITEM->received_qty = $available_qty;
                $ARN_ITEM->discount_1 = $row['discount_1'] ?? 0;
                $ARN_ITEM->discount_2 = $row['discount_2'] ?? 0;
                $ARN_ITEM->discount_3 = $row['discount_3'] ?? 0;
                $ARN_ITEM->discount_4 = $row['discount_4'] ?? 0;
                $ARN_ITEM->discount_5 = $row['discount_5'] ?? 0;
                $ARN_ITEM->discount_6 = $row['discount_6'] ?? 0;
                $ARN_ITEM->discount_7 = $row['discount_7'] ?? 0;
                $ARN_ITEM->discount_8 = $row['discount_8'] ?? 0;
                $ARN_ITEM->final_cost = $row['final_cost'] ?? 0;
                $ARN_ITEM->unit_total = isset($row['unit_total']) && $row['received_qty'] > 0 ? 
                    ($row['unit_total'] / $row['received_qty']) * $available_qty : 0;
                $ARN_ITEM->list_price = $row['list_price'] ?? 0;
                $ARN_ITEM->invoice_price = $row['invoice_price'] ?? 0;
                $ARN_ITEM->margin_percent = $row['margin_percent'] ?? 0;
                
                $ARN_ITEM->create();
                
                // Update original ARN item quantity (reduce by transferred amount)
                $new_received_qty = $row['received_qty'] - $available_qty;
                $update_query = "
                    UPDATE arn_items 
                    SET received_qty = '{$new_received_qty}',
                        unit_total = (unit_total / {$row['received_qty']}) * {$new_received_qty},
                        updated_at = NOW()
                    WHERE id = '{$row['id']}'
                ";
                $db->readQuery($update_query);
                
                // Update original ARN master totals
                $update_master_query = "
                    UPDATE arn_master 
                    SET total_received_qty = total_received_qty - {$available_qty},
                        total_arn_value = total_arn_value - " . (($row['total_arn_value'] / $row['total_received_qty']) * $available_qty) . ",
                        sub_arn_value = sub_arn_value - " . (($row['sub_arn_value'] / $row['total_received_qty']) * $available_qty) . ",
                        paid_amount = paid_amount - " . (($row['paid_amount'] / $row['total_received_qty']) * $available_qty) . "
                    WHERE id = '{$row['arn_id']}'
                ";
                $db->readQuery($update_master_query);
                
                // Create audit log for ARN transfer
                $AUDIT_LOG = new AuditLog();
                $AUDIT_LOG->ref_id = $new_arn_id;
                $AUDIT_LOG->ref_code = $new_arn_no;
                $AUDIT_LOG->action = 'ARN_TRANSFER';
                $AUDIT_LOG->description = "ARN transferred from Dept {$from_dept} to Dept {$to_dept} - Qty: {$available_qty}";
                $AUDIT_LOG->user_id = $_SESSION['id'];
                $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
                $AUDIT_LOG->create();
                
                $remaining_qty -= $available_qty;
            }
        }
    }
}

/**
 * Update stock_item_tmp records with new ARN IDs for transferred quantities
 */
function updateStockItemTmpArnIds($item_id, $to_dept, $transfer_qty) {
    $db = Database::getInstance();
    
    // Get the latest ARN ID for this item in the target department
    $query = "
        SELECT am.id as arn_id, am.arn_no
        FROM arn_master am
        WHERE am.department = '{$to_dept}'
        AND am.arn_no LIKE '%/TRF-%'
        ORDER BY am.id DESC
        LIMIT 1
    ";
    
    $result = $db->readQuery($query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $new_arn_id = $row['arn_id'];
        
        // Update stock_item_tmp records in target department with new ARN ID and set status to 1
        $update_query = "
            UPDATE stock_item_tmp 
            SET arn_id = '{$new_arn_id}', status = 1
            WHERE item_id = '{$item_id}' 
            AND department_id = '{$to_dept}'
            AND qty = '{$transfer_qty}'
            AND (arn_id IS NULL OR arn_id = 0 OR status = 0)
            ORDER BY id DESC
            LIMIT 1
        ";
        $db->readQuery($update_query);
        
        // Remove any duplicate records with status 0
        $cleanup_query = "
            DELETE FROM stock_item_tmp 
            WHERE item_id = '{$item_id}' 
            AND department_id = '{$to_dept}'
            AND status = 0
            AND arn_id != '{$new_arn_id}'
        ";
        $db->readQuery($cleanup_query);
    }
}

?>