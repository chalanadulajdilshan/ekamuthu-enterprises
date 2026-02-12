<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/equipment-rent-return-debug.log');

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Calculate settlement for a return (preview calculation)
if (isset($_POST['action']) && $_POST['action'] === 'calculate_settlement') {
    $rent_item_id = $_POST['rent_item_id'] ?? 0;
    $return_qty = $_POST['return_qty'] ?? 0;
    $damage_amount = $_POST['damage_amount'] ?? 0;
    $return_date = $_POST['return_date'] ?? date('Y-m-d');
    $return_time = $_POST['return_time'] ?? null;
    $after_9am_extra_day = $_POST['after_9am_extra_day'] ?? 0;
    $extra_day_amount = $_POST['extra_day_amount'] ?? 0;
    $penalty_percentage = $_POST['penalty_percentage'] ?? 0;
    
    if (!$rent_item_id) {
        echo json_encode(["status" => "error", "message" => "Rent item ID required"]);
        exit;
    }
    
    $calculation = EquipmentRentReturn::calculateSettlement($rent_item_id, $return_qty, $damage_amount, $return_date, $return_time, $after_9am_extra_day, $extra_day_amount, $penalty_percentage);
    
    if ($calculation['error']) {
        echo json_encode(["status" => "error", "message" => $calculation['message']]);
    } else {
        echo json_encode(["status" => "success", "data" => $calculation]);
    }
    exit;
}

// Create a new return
if (isset($_POST['action']) && $_POST['action'] === 'create_return') {
    $rent_item_id = $_POST['rent_item_id'] ?? 0;
    $return_date = $_POST['return_date'] ?? date('Y-m-d');
    $return_time = $_POST['return_time'] ?? null;
    $return_qty = $_POST['return_qty'] ?? 0;
    $damage_amount = $_POST['damage_amount'] ?? 0;
    $after_9am_extra_day = $_POST['after_9am_extra_day'] ?? 0;
    $extra_day_amount = $_POST['extra_day_amount'] ?? 0;
    $penalty_percentage = $_POST['penalty_percentage'] ?? 0;
    $remark = $_POST['remark'] ?? '';
    
    if (!$rent_item_id) {
        echo json_encode(["status" => "error", "message" => "Rent item ID required"]);
        exit;
    }
    
    if ($return_qty <= 0) {
        echo json_encode(["status" => "error", "message" => "Return quantity must be greater than 0"]);
        exit;
    }
    
    // Calculate settlement
    $calculation = EquipmentRentReturn::calculateSettlement($rent_item_id, $return_qty, $damage_amount, $return_date, $return_time, $after_9am_extra_day, $extra_day_amount, $penalty_percentage);
    
    if ($calculation['error']) {
        echo json_encode(["status" => "error", "message" => $calculation['message']]);
        exit;
    }
    
    // Create return record
    $RETURN = new EquipmentRentReturn(NULL);
    $RETURN->rent_item_id = $rent_item_id;
    $RETURN->return_date = $return_date;
    $RETURN->return_time = $return_time;
    $RETURN->return_qty = $return_qty;
    $RETURN->damage_amount = $damage_amount;
    $RETURN->after_9am_extra_day = intval($after_9am_extra_day);
    $RETURN->extra_day_amount = floatval($calculation['extra_day_amount'] ?? 0);
    $RETURN->penalty_percentage = floatval($calculation['penalty_percentage'] ?? 0);
    $RETURN->penalty_amount = floatval($calculation['penalty_amount'] ?? 0);
    $RETURN->settle_amount = $calculation['settle_amount'];
    $RETURN->refund_amount = $calculation['refund_amount'];
    $RETURN->additional_payment = $calculation['additional_payment'];
    $RETURN->remark = $remark;
    
    // Outstanding tracking
    $customer_paid = floatval($_POST['customer_paid'] ?? 0);
    $additional_payment = floatval($calculation['additional_payment']);
    if ($additional_payment > 0) {
        $RETURN->customer_paid = $customer_paid;
        $RETURN->outstanding_amount = max(0, $additional_payment - $customer_paid);
    } else {
        $RETURN->customer_paid = 0;
        $RETURN->outstanding_amount = 0;
    }
    
    $return_id = $RETURN->create();
    
    if ($return_id) {
        // Get rent item to check if all items returned
        $RENT_ITEM = new EquipmentRentItem($rent_item_id);
        
        // Check if all items for this rent are returned
        $EQUIPMENT_RENT = new EquipmentRent($RENT_ITEM->rent_id);
        if (!$EQUIPMENT_RENT->hasActiveRentals()) {
            $EQUIPMENT_RENT->status = 'returned';
            // Use latest return date/time across all items for this rent
            $latestReturn = EquipmentRentReturn::getLatestReturnDateTimeByRentId($RENT_ITEM->rent_id);
            $EQUIPMENT_RENT->received_date = $latestReturn ?: date('Y-m-d H:i');
            $EQUIPMENT_RENT->update();
        }
        
        // Audit log
        $AUDIT_LOG = new AuditLog(NULL);
        $AUDIT_LOG->ref_id = $RENT_ITEM->rent_id;
        $AUDIT_LOG->ref_code = $EQUIPMENT_RENT->bill_number;
        $AUDIT_LOG->action = 'RETURN';
        $AUDIT_LOG->description = "EQUIPMENT RETURN - Bill #{$EQUIPMENT_RENT->bill_number}, Qty: {$return_qty}, Damage: {$damage_amount}, Settlement: {$calculation['settle_amount']}";
        $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();
        
        echo json_encode([
            "status" => "success", 
            "return_id" => $return_id,
            "calculation" => $calculation,
            "message" => "Return processed successfully"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create return record"]);
    }
    exit;
}

// Get returns for a specific rent item
if (isset($_POST['action']) && $_POST['action'] === 'get_returns') {
    $rent_item_id = $_POST['rent_item_id'] ?? 0;
    
    if (!$rent_item_id) {
        echo json_encode(["status" => "error", "message" => "Rent item ID required"]);
        exit;
    }
    
    $returns = EquipmentRentReturn::getByRentItemId($rent_item_id);
    $settlement = EquipmentRentReturn::getTotalSettlementAmount($rent_item_id);
    
    echo json_encode([
        "status" => "success",
        "returns" => $returns,
        "settlement" => $settlement
    ]);
    exit;
}

// Get return summary for a rent
if (isset($_POST['action']) && $_POST['action'] === 'get_rent_summary') {
    $rent_id = $_POST['rent_id'] ?? 0;
    
    if (!$rent_id) {
        echo json_encode(["status" => "error", "message" => "Rent ID required"]);
        exit;
    }
    
    $summary = EquipmentRentReturn::getReturnSummaryByRentId($rent_id);
    
    echo json_encode([
        "status" => "success",
        "summary" => $summary
    ]);
    exit;
}

// Update a return
if (isset($_POST['action']) && $_POST['action'] === 'update_return') {
    $return_id = $_POST['return_id'] ?? 0;
    $return_date = $_POST['return_date'] ?? date('Y-m-d');
    $return_qty = $_POST['return_qty'] ?? 0;
    $damage_amount = $_POST['damage_amount'] ?? 0;
    $remark = $_POST['remark'] ?? '';
    
    if (!$return_id) {
        echo json_encode(["status" => "error", "message" => "Return ID required"]);
        exit;
    }
    
    if ($return_qty <= 0) {
        echo json_encode(["status" => "error", "message" => "Return quantity must be greater than 0"]);
        exit;
    }
    
    $RETURN = new EquipmentRentReturn($return_id);
    
    if (!$RETURN->id) {
        echo json_encode(["status" => "error", "message" => "Return record not found"]);
        exit;
    }
    
    // Calculate settlement
    $calculation = EquipmentRentReturn::calculateSettlement($RETURN->rent_item_id, $return_qty, $damage_amount, $return_date);
    
    if ($calculation['error']) {
        echo json_encode(["status" => "error", "message" => $calculation['message']]);
        exit;
    }
    
    // Update return record
    $RETURN->return_date = $return_date;
    $RETURN->return_qty = $return_qty;
    $RETURN->damage_amount = $damage_amount;
    $RETURN->settle_amount = $calculation['settle_amount'];
    $RETURN->refund_amount = $calculation['refund_amount'];
    $RETURN->additional_payment = $calculation['additional_payment'];
    $RETURN->remark = $remark;
    
    if ($RETURN->update()) {
        echo json_encode([
            "status" => "success",
            "calculation" => $calculation,
            "message" => "Return updated successfully"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update return record"]);
    }
    exit;
}

// Delete a return
if (isset($_POST['action']) && $_POST['action'] === 'delete_return') {
    $return_id = $_POST['return_id'] ?? 0;
    
    if (!$return_id) {
        echo json_encode(["status" => "error", "message" => "Return ID required"]);
        exit;
    }
    
    $RETURN = new EquipmentRentReturn($return_id);
    
    if (!$RETURN->id) {
        echo json_encode(["status" => "error", "message" => "Return record not found"]);
        exit;
    }
    
    $rent_item_id = $RETURN->rent_item_id;
    
    if ($RETURN->delete()) {
        // Get rent item to update status if needed
        $RENT_ITEM = new EquipmentRentItem($rent_item_id);
        $EQUIPMENT_RENT = new EquipmentRent($RENT_ITEM->rent_id);
        
        // Check if there are still active rentals
        if ($EQUIPMENT_RENT->hasActiveRentals()) {
            $EQUIPMENT_RENT->status = 'rented';
            $EQUIPMENT_RENT->update();
        }
        
        echo json_encode(["status" => "success", "message" => "Return deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete return record"]);
    }
    exit;
}

// Get rent item details for return form
if (isset($_POST['action']) && $_POST['action'] === 'get_item_details') {
    $rent_item_id = $_POST['rent_item_id'] ?? 0;
    
    if (!$rent_item_id) {
        echo json_encode(["status" => "error", "message" => "Rent item ID required"]);
        exit;
    }
    
    $db = Database::getInstance();
    $query = "SELECT eri.*, 
              e.code as equipment_code, 
              e.item_name as equipment_name,
              e.deposit_one_day as deposit_per_item,
              e.damage as equipment_damage,
              se.code as sub_equipment_code,
              er.bill_number,
              er.deposit_total as customer_deposit,
              cm.name as customer_name
              FROM equipment_rent_items eri
              LEFT JOIN equipment e ON eri.equipment_id = e.id
              LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
              LEFT JOIN equipment_rent er ON eri.rent_id = er.id
              LEFT JOIN customer_master cm ON er.customer_id = cm.id
              WHERE eri.id = " . (int) $rent_item_id;
    
    $result = mysqli_fetch_assoc($db->readQuery($query));
    
    if ($result) {
        $returns = EquipmentRentReturn::getByRentItemId($rent_item_id);
        $result['returns'] = $returns;
        $result['total_returned'] = EquipmentRentReturn::getTotalReturnedQty($rent_item_id);
        $result['pending_qty'] = $result['quantity'] - $result['total_returned'];
        
        echo json_encode(["status" => "success", "data" => $result]);
    } else {
        echo json_encode(["status" => "error", "message" => "Item not found"]);
    }
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
