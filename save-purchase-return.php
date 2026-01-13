<?php
error_reporting(0);
header('Content-Type: application/json');

require_once 'class/include.php';

// Session already started in include.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $arn_id = (int) $_POST['arn_id'];
    $ref_no = $_POST['ref_no'];
    $return_reason = $_POST['return_reason'];
    $return_date = $_POST['return_date'] ?? date('Y-m-d');
    $department_id = (int) ($_POST['department_id'] ?? 0);
    $return_items = json_decode($_POST['return_items'], true);

    if (empty($return_items)) {
        echo json_encode(['status' => 'error', 'message' => 'No items to return.']);
        exit;
    }

    $db = Database::getInstance();

    // Get ARN details
    $arn = mysqli_fetch_assoc($db->readQuery("SELECT * FROM arn_master WHERE id = $arn_id"));
    if (!$arn) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ARN.']);
        exit;
    }

    $supplier_id = (int) $arn['supplier_id'];
    // Use department from POST if provided, otherwise use ARN's department
    if ($department_id <= 0) {
        $department_id = (int) $arn['department'];
    }
    $created_by = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 1;

    $DEPARTMENT = new DepartmentMaster($department_id);

    // Validate return quantities against available stock
    $errors = [];
    foreach ($return_items as $item) {
        $item_id = (int) $item['item_id'];
        $qty = (float) $item['quantity'];

        if ($qty <= 0) continue;

        // Check available qty in stock_item_tmp for this ARN
        $stockCheck = mysqli_fetch_assoc($db->readQuery(
            "SELECT IFNULL(SUM(qty), 0) AS available 
             FROM stock_item_tmp 
             WHERE arn_id = $arn_id 
               AND item_id = $item_id 
               AND department_id = $department_id"
        ));

        $available = (float) $stockCheck['available'];
        
        // If no stock_item_tmp data, check arn_items received_qty as fallback
        if ($available <= 0) {
            $arnItemCheck = mysqli_fetch_assoc($db->readQuery(
                "SELECT IFNULL(SUM(received_qty), 0) AS received 
                 FROM arn_items 
                 WHERE arn_id = $arn_id AND item_code = $item_id"
            ));
            $available = (float) $arnItemCheck['received'];
        }

        if ($qty > $available) {
            $errors[] = "Item ID $item_id: Return qty ($qty) exceeds available stock ($available).";
        }
    }

    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
        exit;
    }

    // Insert into purchase_return table
    $ref_no_escaped = mysqli_real_escape_string($db->DB_CON, $ref_no);
    $return_reason_escaped = mysqli_real_escape_string($db->DB_CON, $return_reason);

    $insert_return = "INSERT INTO purchase_return (ref_no, department_id, return_date, arn_id, supplier_id, total_amount, return_reason, created_by, created_at)
                      VALUES ('$ref_no_escaped', '$department_id', '$return_date', '$arn_id', '$supplier_id', 0, '$return_reason_escaped', '$created_by', NOW())";
    $db->readQuery($insert_return);
    $return_id = mysqli_insert_id($db->DB_CON);

    if (!$return_id) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create purchase return record.']);
        exit;
    }

    $total_amount = 0;
    $total_return_qty = 0;
    $total_value_reduced = 0;
    $total_discount_reduced = 0;

    foreach ($return_items as $item) {
        $item_id = (int) $item['item_id'];
        $qty = (float) $item['quantity'];

        if ($qty <= 0) continue;

        // Get item cost from arn_items for this specific ARN
        $arn_item = mysqli_fetch_assoc($db->readQuery(
            "SELECT final_cost, list_price, received_qty 
             FROM arn_items 
             WHERE arn_id = $arn_id AND item_code = $item_id 
             LIMIT 1"
        ));

        $unit_price = $arn_item ? (float) $arn_item['final_cost'] : 0;
        $list_price = $arn_item ? (float) $arn_item['list_price'] : 0;
        $net_amount = $unit_price * $qty;
        $total_amount += $net_amount;
        $total_return_qty += $qty;

        // Calculate discount reduction
        $discount_per_unit = max(0, $list_price - $unit_price);
        $total_value_reduced += $net_amount;
        $total_discount_reduced += ($discount_per_unit * $qty);

        // Insert into purchase_return_items
        $db->readQuery("INSERT INTO purchase_return_items (return_id, item_id, quantity, unit_price, net_amount, created_at)
                        VALUES ('$return_id', '$item_id', '$qty', '$unit_price', '$net_amount', NOW())");

        // 1. Reduce stock from stock_item_tmp (FIFO from this specific ARN)
        $remainingQty = $qty;
        $tmpLots = $db->readQuery(
            "SELECT id, qty FROM stock_item_tmp 
             WHERE arn_id = $arn_id 
               AND item_id = $item_id 
               AND department_id = $department_id 
               AND qty > 0 
             ORDER BY created_at ASC, id ASC"
        );

        while ($remainingQty > 0 && ($lot = mysqli_fetch_assoc($tmpLots))) {
            $lotQty = (float) $lot['qty'];
            $deduct = min($remainingQty, $lotQty);
            $newQty = $lotQty - $deduct;

            $db->readQuery("UPDATE stock_item_tmp SET qty = '$newQty' WHERE id = " . (int) $lot['id']);
            $remainingQty -= $deduct;
        }

        // 2. Reduce stock from stock_master (reduce from total quantity for this item+department)
        $remainingMasterQty = $qty;
        $stockMasterRows = $db->readQuery(
            "SELECT id, quantity FROM stock_master 
             WHERE item_id = $item_id 
               AND department_id = $department_id 
               AND quantity > 0
             ORDER BY created_at ASC, id ASC"
        );

        while ($remainingMasterQty > 0 && ($smRow = mysqli_fetch_assoc($stockMasterRows))) {
            $smQty = (float) $smRow['quantity'];
            $deductMaster = min($remainingMasterQty, $smQty);
            $newMasterQty = $smQty - $deductMaster;
            
            $db->readQuery("UPDATE stock_master SET quantity = '$newMasterQty' WHERE id = " . (int) $smRow['id']);
            $remainingMasterQty -= $deductMaster;
        }
        
        // If no stock_master records found, try to update any existing record directly
        if ($remainingMasterQty == $qty) {
            $db->readQuery("UPDATE stock_master 
                            SET quantity = GREATEST(quantity - $qty, 0) 
                            WHERE item_id = $item_id AND department_id = $department_id");
        }

        // 3. Reduce received_qty in arn_items
        if (false) {
            $remainingArnQty = $qty;
            $arnItemRows = $db->readQuery(
                "SELECT id, received_qty, final_cost, unit_total 
                 FROM arn_items 
                 WHERE arn_id = $arn_id AND item_code = $item_id 
                 ORDER BY id DESC"
            );

            while ($remainingArnQty > 0 && ($arnRow = mysqli_fetch_assoc($arnItemRows))) {
                $rowReceivedQty = (float) $arnRow['received_qty'];
                if ($rowReceivedQty <= 0) continue;

                $consume = min($remainingArnQty, $rowReceivedQty);
                $newReceivedQty = $rowReceivedQty - $consume;
                $rowFinalCost = (float) $arnRow['final_cost'];
                $newUnitTotal = $rowFinalCost * $newReceivedQty;

                $db->readQuery("UPDATE arn_items 
                                SET received_qty = '$newReceivedQty', unit_total = '$newUnitTotal' 
                                WHERE id = " . (int) $arnRow['id']);
                $remainingArnQty -= $consume;
            }
        }

        // 4. Record stock transaction for this return
        $STOCK_TRANSACTION = new StockTransaction(null);
        $STOCK_TRANSACTION->item_id = $item_id;
        $STOCK_TRANSACTION->type = 10; // Purchase Return type (you may need to add this to stock_adjustment_type table)
        $STOCK_TRANSACTION->date = $return_date;
        $STOCK_TRANSACTION->qty_in = 0;
        $STOCK_TRANSACTION->qty_out = $qty;
        $STOCK_TRANSACTION->remark = 'Purchase Return #' . $ref_no . ' - ' . $DEPARTMENT->name;
        $STOCK_TRANSACTION->create();
    }

    // 5. Update arn_master totals
    if (false) {
        $db->readQuery("UPDATE arn_master SET 
                        total_received_qty = GREATEST(total_received_qty - $total_return_qty, 0),
                        total_arn_value = GREATEST(total_arn_value - $total_value_reduced, 0),
                        total_discount = GREATEST(total_discount - $total_discount_reduced, 0)
                        WHERE id = $arn_id");
    }

    // 6. Update purchase_return with total amount
    $db->readQuery("UPDATE purchase_return SET total_amount = '$total_amount' WHERE id = '$return_id'");

    echo json_encode([
        'status' => 'success',
        'message' => 'Purchase return saved successfully.',
        'return_id' => $return_id,
        'total_amount' => $total_amount
    ]);
}
