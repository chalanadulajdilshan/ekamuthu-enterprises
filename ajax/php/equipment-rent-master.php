<?php

// Enable logging but keep output clean for JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new equipment rent with items
if (isset($_POST['create'])) {

    if (empty($_POST['payment_type_id'])) {
        echo json_encode(["status" => "error", "message" => "Please select a payment method"]);
        exit();
    }

    // Check if bill number already exists
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM equipment_rent WHERE bill_number = '{$_POST['code']}'";
    $existingRent = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingRent) {
        echo json_encode(["status" => "duplicate", "message" => "Bill number already exists in the system"]);
        exit();
    }

    $bill_number = $_POST['code'];

    // Parse items from JSON
    $items = json_decode($_POST['items'] ?? '[]', true);

    if (empty($items)) {
        echo json_encode(["status" => "error", "message" => "Please add at least one equipment item"]);
        exit();
    }

    // Validate all sub-equipment availability before creating
    foreach ($items as $item) {
        $EQUIP_CHECK = new Equipment($item['equipment_id']);
        if ($EQUIP_CHECK->no_sub_items == 1) {
            continue;
        }

        if (!EquipmentRentItem::isSubEquipmentAvailable($item['sub_equipment_id'])) {
            $SUB_EQ = new SubEquipment($item['sub_equipment_id']);
            echo json_encode([
                "status" => "error",
                "message" => "Sub equipment '{$SUB_EQ->code}' is already rented out"
            ]);
            exit();
        }
    }

    // Create master rent record
    $EQUIPMENT_RENT = new EquipmentRent(NULL);
    $EQUIPMENT_RENT->bill_number = $bill_number;
    $EQUIPMENT_RENT->customer_id = $_POST['customer_id'] ?? '';
    $EQUIPMENT_RENT->rental_date = $_POST['rental_date'] ?? date('Y-m-d');
    // Received date is system-controlled; ignore manual input and set when all items returned
    // Keep previously stored received_date if already set and all items remain returned
    $existingReceivedDate = $EQUIPMENT_RENT->received_date;
    $EQUIPMENT_RENT->status = 'rented';
    $EQUIPMENT_RENT->remark = $_POST['remark'] ?? '';
    $EQUIPMENT_RENT->total_items = count($items);
    $EQUIPMENT_RENT->transport_cost = $_POST['transport_cost'] ?? 0;
    $EQUIPMENT_RENT->deposit_total = $_POST['custom_deposit'] ?? 0;
    $EQUIPMENT_RENT->payment_type_id = !empty($_POST['payment_type_id']) ? (int) $_POST['payment_type_id'] : null;
    $EQUIPMENT_RENT->created_by = isset($_SESSION['id']) ? $_SESSION['id'] : null;

    $rent_id = $EQUIPMENT_RENT->create();

    if ($rent_id) {
        // Create rent items
        foreach ($items as $item) {
            // Deposit sent from UI as per-day value; multiply by qty to store total for the item
            $itemDeposit = $item['deposit_one_day'] ?? ($item['deposit'] ?? 0);
            $itemDepositTotal = ($item['quantity'] ?? 1) * $itemDeposit;
            $RENT_ITEM = new EquipmentRentItem(NULL);
            $RENT_ITEM->rent_id = $rent_id;
            $RENT_ITEM->equipment_id = $item['equipment_id'];
            $RENT_ITEM->sub_equipment_id = $item['sub_equipment_id'];
            $RENT_ITEM->rental_date = $item['rental_date'] ?? $_POST['rental_date'];
            $RENT_ITEM->quantity = $item['quantity'] ?? 1;
            $RENT_ITEM->rent_type = $item['rent_type'] ?? 'day';
            $RENT_ITEM->duration = $item['duration'] ?? 0;
            $RENT_ITEM->amount = $item['amount'] ?? 0;
            $RENT_ITEM->deposit_amount = $itemDepositTotal;
            $RENT_ITEM->status = 'rented';
            $RENT_ITEM->remark = $item['remark'] ?? '';
            $RENT_ITEM->create();
        }

        // Update total items count
        $EQUIPMENT_RENT->id = $rent_id;
        $EQUIPMENT_RENT->updateTotalItems();

        // Audit log
        $AUDIT_LOG = new AuditLog(NULL);
        $AUDIT_LOG->ref_id = $rent_id;
        $AUDIT_LOG->ref_code = $_POST['code'];
        $AUDIT_LOG->action = 'CREATE';
        $AUDIT_LOG->description = 'CREATE EQUIPMENT BILL NO #' . $_POST['code'] . ' with ' . count($items) . ' items';
        $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        // Update document tracking ID if the used bill number is greater than current tracking
        $DOC_TRACKING = new DocumentTracking(1);
        if ((int) $DOC_TRACKING->equipment_rent_id < (int) $bill_number) {
            $db->readQuery("UPDATE `document_tracking` SET `equipment_rent_id` = '" . (int) $bill_number . "', `updated_at` = NOW() WHERE `id` = 1");
        }

        echo json_encode(["status" => "success", "rent_id" => $rent_id, "bill_number" => $bill_number]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create rent record"]);
    }
    exit();
}

// Update equipment rent
if (isset($_POST['update'])) {

    if (empty($_POST['payment_type_id'])) {
        echo json_encode(["status" => "error", "message" => "Please select a payment method"]);
        exit();
    }

    // Check if bill number already exists (excluding current record)
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM equipment_rent WHERE bill_number = '{$_POST['code']}' AND id != '{$_POST['rent_id']}'";
    $existingRent = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingRent) {
        echo json_encode(["status" => "duplicate", "message" => "Bill number already exists in the system"]);
        exit();
    }

    $EQUIPMENT_RENT = new EquipmentRent($_POST['rent_id']);

    // Parse items from JSON
    $items = json_decode($_POST['items'] ?? '[]', true);

    if (empty($items)) {
        echo json_encode(["status" => "error", "message" => "Please add at least one equipment item"]);
        exit();
    }

    // Get existing item IDs for this rent
    $existingItems = $EQUIPMENT_RENT->getItems();
    $existingItemIds = array_column($existingItems, 'id');
    $newItemIds = array_filter(array_column($items, 'id'));

    // Find items to delete (exist in DB but not in new list)
    $itemsToDelete = array_diff($existingItemIds, $newItemIds);

    // Delete removed items
    foreach ($itemsToDelete as $itemId) {
        $RENT_ITEM = new EquipmentRentItem($itemId);
        $RENT_ITEM->delete();
    }

    // Validate and update/create items
    foreach ($items as $item) {
        // Deposit sent from UI as per-day value; multiply by qty to store total for the item
        $itemDeposit = $item['deposit_one_day'] ?? ($item['deposit'] ?? 0);
        $itemDepositTotal = ($item['quantity'] ?? 1) * $itemDeposit;
        $subEquipmentId = $item['sub_equipment_id'];
        $itemId = $item['id'] ?? null;

        // Check availability (exclude current item if updating)
        $shouldCheck = true;
        $isReturning = ($item['status'] ?? 'rented') === 'returned';

        if ($itemId) {
            $existingItemForCheck = new EquipmentRentItem($itemId);
            // If sub_equipment hasn't changed, and we are not changing status from returned to rented (which is rare here),
            // then we don't need to check availability because we already hold it.
            if ($existingItemForCheck->sub_equipment_id == $subEquipmentId && $existingItemForCheck->status !== 'returned') {
                $shouldCheck = false;
            }
        }

        if ($shouldCheck && !$isReturning) {
            $EQUIP_CHECK = new Equipment($item['equipment_id']);
            if ($EQUIP_CHECK->no_sub_items != 1) {
                if (!EquipmentRentItem::isSubEquipmentAvailable($subEquipmentId, $itemId)) {
                    $SUB_EQ = new SubEquipment($subEquipmentId);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Sub equipment '{$SUB_EQ->code}' is already rented out"
                    ]);
                    exit();
                }
            }
        }

        if ($itemId) {
            // Update existing item
            $RENT_ITEM = new EquipmentRentItem($itemId);
            $RENT_ITEM->equipment_id = $item['equipment_id'];
            $RENT_ITEM->sub_equipment_id = $subEquipmentId;
            $RENT_ITEM->rental_date = $item['rental_date'] ?? $_POST['rental_date'];
            $RENT_ITEM->quantity = $item['quantity'] ?? 1;
            $RENT_ITEM->rent_type = $item['rent_type'] ?? 'day';
            $RENT_ITEM->duration = $item['duration'] ?? 0;
            $RENT_ITEM->amount = $item['amount'] ?? 0;
            $RENT_ITEM->deposit_amount = $itemDepositTotal;
            $status = $item['status'] ?? 'rented';
            // Validate status to prevent empty or invalid values
            if ($status !== 'returned' && $status !== 'rented') {
                $status = 'rented';
            }
            $RENT_ITEM->status = $status;
            $RENT_ITEM->remark = $item['remark'] ?? '';
            $RENT_ITEM->update();
        } else {
            // Create new item
            $RENT_ITEM = new EquipmentRentItem(NULL);
            $RENT_ITEM->rent_id = $_POST['rent_id'];
            $RENT_ITEM->equipment_id = $item['equipment_id'];
            $RENT_ITEM->sub_equipment_id = $subEquipmentId;
            $RENT_ITEM->rental_date = $item['rental_date'] ?? $_POST['rental_date'];
            $RENT_ITEM->quantity = $item['quantity'] ?? 1;
            $RENT_ITEM->rent_type = $item['rent_type'] ?? 'day';
            $RENT_ITEM->duration = $item['duration'] ?? 0;
            $RENT_ITEM->amount = $item['amount'] ?? 0;
            $RENT_ITEM->deposit_amount = $itemDepositTotal;
            $RENT_ITEM->status = 'rented';
            $RENT_ITEM->remark = $item['remark'] ?? '';
            $RENT_ITEM->create();
        }
    }

    // Update master record
    $EQUIPMENT_RENT->bill_number = $_POST['code'];
    $EQUIPMENT_RENT->customer_id = $_POST['customer_id'] ?? '';
    $EQUIPMENT_RENT->rental_date = $_POST['rental_date'] ?? date('Y-m-d');
    $EQUIPMENT_RENT->remark = $_POST['remark'] ?? '';
    $EQUIPMENT_RENT->transport_cost = $_POST['transport_cost'] ?? 0;
    $EQUIPMENT_RENT->deposit_total = $_POST['custom_deposit'] ?? 0;
    $EQUIPMENT_RENT->payment_type_id = !empty($_POST['payment_type_id']) ? (int) $_POST['payment_type_id'] : null;

    // Check if all items are returned
    $allReturned = true;
    foreach ($items as $item) {
        if (($item['status'] ?? 'rented') === 'rented') {
            $allReturned = false;
            break;
        }
    }
    $EQUIPMENT_RENT->status = $allReturned ? 'returned' : 'rented';

    if ($allReturned) {
        // Set received date/time only once when all items are returned
        $EQUIPMENT_RENT->received_date = $existingReceivedDate ?: date('Y-m-d H:i');
    } else {
        // Clear received date if items are still pending
        $EQUIPMENT_RENT->received_date = null;
    }

    $res = $EQUIPMENT_RENT->update();
    $EQUIPMENT_RENT->updateTotalItems();

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['rent_id'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE EQUIPMENT BILL NO #' . $_POST['code'];
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    if ($res) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    exit();
}

// Get rent details with items
if (isset($_POST['action']) && $_POST['action'] === 'get_rent_details') {
    $rent_id = $_POST['rent_id'] ?? 0;

    if ($rent_id) {
        $EQUIPMENT_RENT = new EquipmentRent($rent_id);

        $paymentTypeName = null;
        if (!empty($EQUIPMENT_RENT->payment_type_id)) {
            $PAYMENT_TYPE = new PaymentType((int) $EQUIPMENT_RENT->payment_type_id);
            $paymentTypeName = $PAYMENT_TYPE->name ?? null;
        }

        // Get items with equipment deposit info
        $db = Database::getInstance();
        $itemsQuery = "SELECT ri.*, 
                       e.code as equipment_code, e.item_name as equipment_name, 
                       e.deposit_one_day as equipment_deposit, e.no_sub_items, e.change_value,
                       se.code as sub_equipment_code,
                       -- latest return info per item
                       (SELECT err.return_date FROM equipment_rent_returns err 
                            WHERE err.rent_item_id = ri.id 
                            ORDER BY err.return_date DESC, err.id DESC LIMIT 1) AS latest_return_date,
                       (SELECT err.return_time FROM equipment_rent_returns err 
                            WHERE err.rent_item_id = ri.id 
                            ORDER BY err.return_date DESC, err.id DESC LIMIT 1) AS latest_return_time,
                       (SELECT err.after_9am_extra_day FROM equipment_rent_returns err 
                            WHERE err.rent_item_id = ri.id 
                            ORDER BY err.return_date DESC, err.id DESC LIMIT 1) AS latest_after_9am_flag,
                       (SELECT GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, ri.rental_date, err.return_date) / 86400))
                            FROM equipment_rent_returns err 
                            WHERE err.rent_item_id = ri.id 
                            ORDER BY err.return_date DESC, err.id DESC LIMIT 1) AS latest_used_days
                       FROM equipment_rent_items ri 
                       LEFT JOIN equipment e ON ri.equipment_id = e.id
                       LEFT JOIN sub_equipment se ON ri.sub_equipment_id = se.id
                       WHERE ri.rent_id = $rent_id";
        $itemsResult = $db->readQuery($itemsQuery);
        $items = [];
        while ($row = mysqli_fetch_assoc($itemsResult)) {
            $items[] = $row;
        }

        // Calculate refund balance from returns
        // Refund Balance = Customer Deposit - Total Charges (rental + extra day + damage + penalty)
        $customerDeposit = floatval($EQUIPMENT_RENT->deposit_total ?? 0);
        
        // Get total charges from all returns: rental amounts + extra day + damage + penalty
        // For monthly rentals, divide by 30 to get daily rate
        $chargesQuery = "SELECT COALESCE(SUM(
                            GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                            * ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END))
                            * err.return_qty
                            + COALESCE(err.extra_day_amount, 0)
                            + COALESCE(err.damage_amount, 0)
                            + COALESCE(err.penalty_amount, 0)
                        ), 0) as total_charges
                        FROM equipment_rent_returns err
                        INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                        WHERE eri.rent_id = $rent_id";
        $chargesResult = $db->readQuery($chargesQuery);
        $chargesData = mysqli_fetch_assoc($chargesResult);
        $totalCharges = floatval($chargesData['total_charges'] ?? 0);
        $refundBalance = $customerDeposit - $totalCharges;

        // Get customer details
        $CUSTOMER = new CustomerMaster($EQUIPMENT_RENT->customer_id);

        echo json_encode([
            "status" => "success",
            "rent" => [
                "id" => $EQUIPMENT_RENT->id,
                "bill_number" => $EQUIPMENT_RENT->bill_number,
                "customer_id" => $EQUIPMENT_RENT->customer_id,
                "customer_name" => $CUSTOMER->code . ' - ' . $CUSTOMER->name,
                "rental_date" => $EQUIPMENT_RENT->rental_date,
                "received_date" => $EQUIPMENT_RENT->received_date,
                "status" => $EQUIPMENT_RENT->status,
                "remark" => $EQUIPMENT_RENT->remark,
                "transport_cost" => $EQUIPMENT_RENT->transport_cost,
                "deposit_total" => $EQUIPMENT_RENT->deposit_total,
                "refund_balance" => $refundBalance,
                "payment_type_id" => $EQUIPMENT_RENT->payment_type_id,
                "payment_type_name" => $paymentTypeName,
                "total_items" => $EQUIPMENT_RENT->total_items
            ],
            "items" => $items
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Rent ID required"]);
    }
    exit;
}

// Get new code
if (isset($_POST['action']) && $_POST['action'] === 'get_new_code') {
    // Get bill number from document tracking table
    $DOCUMENT_TRACKING = new DocumentTracking(1);
    $lastId = $DOCUMENT_TRACKING->equipment_rent_id;
    $newCode = $lastId + 1;

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit;
}

// List bill numbers for autocomplete
if (isset($_POST['action']) && $_POST['action'] === 'list_bill_numbers') {
    $db = Database::getInstance();
    $sql = "SELECT er.id, er.bill_number, c.name AS customer_name FROM equipment_rent er LEFT JOIN customer_master c ON er.customer_id = c.id ORDER BY er.id DESC";
    $result = $db->readQuery($sql);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $labelName = trim(($row['customer_name'] ?? ''));
        $data[] = [
            'label' => $row['bill_number'] . ($labelName ? (' - ' . $labelName) : ''),
            'value' => $row['bill_number'],
            'bill_number' => $row['bill_number'],
            'rent_id' => $row['id'],
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
    exit;
}

// Get rent id by bill number
if (isset($_POST['action']) && $_POST['action'] === 'get_rent_by_bill' && isset($_POST['bill_number'])) {
    $db = Database::getInstance();
    $bill = mysqli_real_escape_string($db->DB_CON, $_POST['bill_number']);
    $sql = "SELECT id FROM equipment_rent WHERE bill_number = '$bill' LIMIT 1";
    $result = $db->readQuery($sql);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        echo json_encode([
            'status' => 'success',
            'rent_id' => $row['id']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Bill number not found'
        ]);
    }
    exit;
}

// Get available sub-equipment for an equipment
if (isset($_POST['action']) && $_POST['action'] === 'get_available_sub_equipment') {
    $equipment_id = $_POST['equipment_id'] ?? 0;

    if ($equipment_id) {
        $available = EquipmentRentItem::getAvailableSubEquipment($equipment_id);
        echo json_encode([
            "status" => "success",
            "sub_equipment" => $available
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Equipment ID required"
        ]);
    }
    exit;
}

// Get all sub-equipment with status for an equipment
if (isset($_POST['action']) && $_POST['action'] === 'get_all_sub_equipment') {
    $equipment_id = $_POST['equipment_id'] ?? 0;

    if ($equipment_id) {
        $all = EquipmentRentItem::getAllSubEquipmentWithStatus($equipment_id);
        echo json_encode([
            "status" => "success",
            "sub_equipment" => $all
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Equipment ID required"
        ]);
    }
    exit;
}

// Get equipment info with sub-equipment count
if (isset($_POST['action']) && $_POST['action'] === 'get_equipment_info') {
    $equipment_id = $_POST['equipment_id'] ?? 0;

    if ($equipment_id) {
        $EQUIPMENT = new Equipment($equipment_id);
        $available = EquipmentRentItem::getAvailableSubEquipment($equipment_id);
        $all = EquipmentRentItem::getAllSubEquipmentWithStatus($equipment_id);

        echo json_encode([
            "status" => "success",
            "equipment" => [
                "id" => $EQUIPMENT->id,
                "code" => $EQUIPMENT->code,
                "item_name" => $EQUIPMENT->item_name,
                "rent_one_day" => $EQUIPMENT->rent_one_day,
                "rent_one_month" => $EQUIPMENT->rent_one_month,
                "deposit_one_day" => $EQUIPMENT->deposit_one_day,
                "no_sub_items" => $EQUIPMENT->no_sub_items,
                "quantity" => $EQUIPMENT->quantity
            ],
            "total_sub_equipment" => count($all),
            "available_count" => count($available)
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Equipment ID required"
        ]);
    }
    exit;
}

// Delete equipment rent
if (isset($_POST['delete'])) {
    $rent_id = $_POST['id'] ?? 0;
    
    if ($rent_id) {
        $EQUIPMENT_RENT = new EquipmentRent($rent_id);
        $bill_number = $EQUIPMENT_RENT->bill_number;
        
        if ($EQUIPMENT_RENT->delete()) {
            $AUDIT_LOG = new AuditLog(NULL);
            $AUDIT_LOG->ref_id = $rent_id;
            $AUDIT_LOG->ref_code = $bill_number;
            $AUDIT_LOG->action = 'DELETE';
            $AUDIT_LOG->description = 'DELETE EQUIPMENT BILL NO #' . $bill_number;
            $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
            $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
            $AUDIT_LOG->create();
            
            echo json_encode(["status" => "success", "message" => "Equipment rent deleted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to delete equipment rent"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Rent ID required"]);
    }
    exit;
}

// Return all items
if (isset($_POST['action']) && $_POST['action'] === 'return_all') {
    $rent_id = $_POST['rent_id'] ?? 0;
    
    if ($rent_id) {
        $EQUIPMENT_RENT = new EquipmentRent($rent_id);
        $items = $EQUIPMENT_RENT->getItems();

        if (!$items) {
            echo json_encode(["status" => "error", "message" => "No items found for this rent"]);
            exit;
        }

        // Prefer client-provided date/time (local to user); fallback to server time
        $nowDate = !empty($_POST['return_date']) ? $_POST['return_date'] : date('Y-m-d');
        $nowTime = !empty($_POST['return_time']) ? $_POST['return_time'] : date('H:i');
        $after9Flag = isset($_POST['after_9am_extra_day']) ? (int) $_POST['after_9am_extra_day'] : 0;
        // If user wants to count an extra day for all items, ensure return time is after 9:00 AM
        if ($after9Flag === 1 && $nowTime < '09:00') {
            $nowTime = '09:01';
        }

        foreach ($items as $item) {
            // Only process items that still have pending quantity
            $pendingQty = max(0, ($item['quantity'] ?? 0) - EquipmentRentReturn::getTotalReturnedQty($item['id']));
            if ($pendingQty <= 0) {
                continue;
            }

            // Calculate settlement for full pending quantity
            $calculation = EquipmentRentReturn::calculateSettlement($item['id'], $pendingQty, 0, $nowDate, $nowTime, $after9Flag, 0, 0);

            // If after-9 flag is set but extra day amount did not compute, force one extra-day charge
            if ($after9Flag === 1 && floatval($calculation['extra_day_amount'] ?? 0) <= 0) {
                $extraOverride = floatval($calculation['per_unit_daily'] ?? 0) * $pendingQty;
                $calculation = EquipmentRentReturn::calculateSettlement($item['id'], $pendingQty, 0, $nowDate, $nowTime, $after9Flag, $extraOverride, 0);
            }

            if ($calculation['error']) {
                echo json_encode(["status" => "error", "message" => $calculation['message']]);
                exit;
            }

            // Create return record for the item
            $RETURN = new EquipmentRentReturn(NULL);
            $RETURN->rent_item_id = $item['id'];
            $RETURN->return_date = $nowDate;
            $RETURN->return_time = $nowTime;
            $RETURN->return_qty = $pendingQty;
            $RETURN->damage_amount = 0;
            $RETURN->after_9am_extra_day = $after9Flag;
            $RETURN->extra_day_amount = floatval($calculation['extra_day_amount'] ?? 0);
            $RETURN->penalty_percentage = floatval($calculation['penalty_percentage'] ?? 0);
            $RETURN->penalty_amount = floatval($calculation['penalty_amount'] ?? 0);
            $RETURN->settle_amount = $calculation['settle_amount'];
            $RETURN->refund_amount = $calculation['refund_amount'];
            $RETURN->additional_payment = $calculation['additional_payment'];
            $RETURN->remark = 'Returned via Return All';

            $return_id = $RETURN->create();

            if ($return_id) {
                // Mark item as returned
                $RENT_ITEM = new EquipmentRentItem($item['id']);
                $RENT_ITEM->markAsReturned();
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to create return record for item #" . ($item['id'] ?? '')]);
                exit;
            }
        }

        // Update master rent status
        $EQUIPMENT_RENT->status = 'returned';
        // Store full datetime for received_date when all items returned via Return All
        $EQUIPMENT_RENT->received_date = date('Y-m-d H:i');
        $EQUIPMENT_RENT->update();

        // Audit log
        $AUDIT_LOG = new AuditLog(NULL);
        $AUDIT_LOG->ref_id = $rent_id;
        $AUDIT_LOG->ref_code = $EQUIPMENT_RENT->bill_number;
        $AUDIT_LOG->action = 'RETURN_ALL';
        $AUDIT_LOG->description = 'RETURN ALL ITEMS FOR EQUIPMENT BILL NO #' . $EQUIPMENT_RENT->bill_number;
        $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();
        
        echo json_encode(["status" => "success", "message" => "All items returned successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Rent ID required"]);
    }
    exit;
}

// Filter equipment rent master list for DataTable
if (isset($_POST['filter'])) {
    $EQUIPMENT_RENT = new EquipmentRent(NULL);
    echo json_encode($EQUIPMENT_RENT->fetchForDataTable($_REQUEST));
    exit;
}

// Filter customers for DataTable
if (isset($_POST['filter_customers'])) {
    $db = Database::getInstance();

    $start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    $length = isset($_REQUEST['length']) ? (int) $_REQUEST['length'] : 100;
    $search = $_REQUEST['search']['value'] ?? '';

    // Total records
    $totalSql = "SELECT COUNT(*) as total FROM customer_master";
    $totalQuery = $db->readQuery($totalSql);
    $totalData = mysqli_fetch_assoc($totalQuery)['total'];

    // Search filter
    $where = "";
    if (!empty($search)) {
        $where .= " WHERE (name LIKE '%$search%' OR code LIKE '%$search%' OR mobile_number LIKE '%$search%' OR nic LIKE '%$search%')";
    }

    // Filtered records
    $filteredSql = "SELECT COUNT(*) as filtered FROM customer_master $where";
    $filteredQuery = $db->readQuery($filteredSql);
    $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

    // Paginated query
    $sql = "SELECT * FROM customer_master $where ORDER BY name ASC LIMIT $start, $length";
    $dataQuery = $db->readQuery($sql);

    $data = [];
    $key = 1;

    while ($row = mysqli_fetch_assoc($dataQuery)) {
        $nestedData = [
            "key" => $key,
            "id" => $row['id'],
            "code" => $row['code'],
            "name" => $row['name'],
            "mobile_number" => $row['mobile_number'],
            "address" => $row['address'],
            "nic" => $row['nic'],
            "outstanding" => number_format($row['old_outstanding'] ?? 0, 2),
            "is_blacklisted" => $row['is_blacklisted'] ?? 0
        ];

        $data[] = $nestedData;
        $key++;
    }

    echo json_encode([
        "draw" => intval($_REQUEST['draw'] ?? 1),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($filteredData),
        "data" => $data
    ]);
    exit;
}

// Simple customer search (non-DataTable) for modal
if (isset($_POST['action']) && $_POST['action'] === 'search_customers_simple') {
    $db = Database::getInstance();
    $search = trim($_POST['search'] ?? '');

    $where = "WHERE id != 1"; // skip default/system customer if any
    if ($search !== '') {
        $safeSearch = mysqli_real_escape_string($db->DB_CON, $search);
        $where .= " AND (name LIKE '%$safeSearch%' OR code LIKE '%$safeSearch%' OR mobile_number LIKE '%$safeSearch%' OR nic LIKE '%$safeSearch%')";
    }

    // If no search term, only load a small initial set to keep modal light
    $limitClause = ($search === '') ? 'LIMIT 5' : 'LIMIT 500';

    $sql = "SELECT id, code, name, mobile_number, address, nic, old_outstanding, is_blacklisted FROM customer_master $where ORDER BY name ASC $limitClause";
    $result = $db->readQuery($sql);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'id' => $row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'mobile_number' => $row['mobile_number'],
            'address' => $row['address'],
            'nic' => $row['nic'],
            'outstanding' => number_format($row['old_outstanding'] ?? 0, 2),
            'is_blacklisted' => $row['is_blacklisted'] ?? 0,
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
    exit;
}

// Filter equipment for DataTable (only equipment that has sub-equipment)
if (isset($_POST['filter_equipment'])) {
    $db = Database::getInstance();

    $start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    $length = isset($_REQUEST['length']) ? (int) $_REQUEST['length'] : 100;
    $search = $_REQUEST['search']['value'] ?? '';

    // Only get equipment that has sub-equipment OR has no_sub_items enabled
    $baseWhere = "WHERE (e.no_sub_items = 1 OR EXISTS (SELECT 1 FROM sub_equipment se WHERE se.equipment_id = e.id))";

    // Total records
    $totalSql = "SELECT COUNT(*) as total FROM equipment e $baseWhere";
    $totalQuery = $db->readQuery($totalSql);
    $totalData = mysqli_fetch_assoc($totalQuery)['total'];

    // Search filter
    $where = $baseWhere;
    if (!empty($search)) {
        $where .= " AND (e.item_name LIKE '%$search%' OR e.code LIKE '%$search%' OR e.serial_number LIKE '%$search%')";
    }

    // Filtered records
    $filteredSql = "SELECT COUNT(*) as filtered FROM equipment e $where";
    $filteredQuery = $db->readQuery($filteredSql);
    $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

    // Paginated query with sub-equipment counts and rented counts
    $sql = "SELECT e.*, ec.name as category_name,
            (SELECT COUNT(*) FROM sub_equipment se WHERE se.equipment_id = e.id) as total_sub,
            (SELECT COUNT(*) FROM sub_equipment se WHERE se.equipment_id = e.id AND se.rental_status = 'available') as available_sub,
            (SELECT COALESCE(SUM(quantity), 0) FROM equipment_rent_items eri WHERE eri.equipment_id = e.id AND eri.status = 'rented') as rented_qty
            FROM equipment e 
            LEFT JOIN equipment_category ec ON e.category = ec.id
            $where ORDER BY e.item_name ASC LIMIT $start, $length";
    $dataQuery = $db->readQuery($sql);

    $data = [];
    $key = 1;

    while ($row = mysqli_fetch_assoc($dataQuery)) {
        $categoryLabel = $row['category_name'] ?: ($row['category'] ?: '-');

        $statusLabel = '';
        if ($row['no_sub_items'] == 1) {
            $available = max(0, $row['quantity'] - $row['rented_qty']);
            $statusLabel = $available > 0
                ? '<span class="badge bg-soft-success font-size-12">' . $available . ' / ' . $row['quantity'] . ' Available</span>'
                : '<span class="badge bg-soft-danger font-size-12">Out of Stock</span>';
        } else {
            $statusLabel = $row['available_sub'] > 0
                ? '<span class="badge bg-soft-success font-size-12">' . $row['available_sub'] . ' / ' . $row['total_sub'] . ' Available</span>'
                : '<span class="badge bg-soft-danger font-size-12">All Rented</span>';
        }

        $nestedData = [
            "key" => $key,
            "id" => $row['id'],
            "code" => $row['code'],
            "item_name" => $row['item_name'],
            "image_name" => $row['image_name'],
            "category" => $row['category'],
            "category_label" => $categoryLabel,
            "serial_number" => $row['serial_number'],
            "size" => $row['size'],
            "value" => $row['value'],
            "total_sub" => $row['total_sub'],
            "available_sub" => $row['available_sub'],
            "rented_qty" => $row['rented_qty'],
            "rent_one_day" => $row['rent_one_day'],
            "deposit_one_day" => $row['deposit_one_day'],
            "rent_one_month" => $row['rent_one_month'],
            "no_sub_items" => $row['no_sub_items'],
            "change_value" => $row['change_value'] ?? 0,
            "total_quantity" => $row['quantity'],
            "availability_label" => $statusLabel
        ];

        $data[] = $nestedData;
        $key++;
    }

    echo json_encode([
        "draw" => intval($_REQUEST['draw'] ?? 1),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($filteredData),
        "data" => $data
    ]);
    exit;
}

// Update equipment amount (rent_one_day or rent_one_month)
if (isset($_POST['action']) && $_POST['action'] === 'update_equipment_amount') {
    $equipment_id = $_POST['equipment_id'] ?? 0;
    $rent_type = $_POST['rent_type'] ?? 'day';
    $amount = $_POST['amount'] ?? 0;

    if (!$equipment_id || $amount <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid equipment ID or amount'
        ]);
        exit;
    }

    $db = Database::getInstance();
    $equipment_id = (int) $equipment_id;
    $amount = (float) $amount;

    // Determine which field to update based on rent type
    $field = ($rent_type === 'month') ? 'rent_one_month' : 'rent_one_day';

    // Update the equipment table
    $updateQuery = "UPDATE equipment SET `$field` = '$amount' WHERE id = $equipment_id";
    $result = $db->readQuery($updateQuery);

    if ($result) {
        // Create audit log
        $EQUIPMENT = new Equipment($equipment_id);
        $AUDIT_LOG = new AuditLog(NULL);
        $AUDIT_LOG->ref_id = $equipment_id;
        $AUDIT_LOG->ref_code = $EQUIPMENT->code;
        $AUDIT_LOG->action = 'UPDATE';
        $AUDIT_LOG->description = 'UPDATE EQUIPMENT ' . $EQUIPMENT->code . ' - ' . strtoupper($field) . ' to ' . $amount;
        $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        echo json_encode([
            'status' => 'success',
            'message' => 'Equipment amount updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update equipment amount'
        ]);
    }
    exit;
}

// Filter sub-equipment for selection (only available ones)
if (isset($_POST['filter_sub_equipment'])) {
    $db = Database::getInstance();
    $equipment_id = $_POST['equipment_id'] ?? 0;

    if (!$equipment_id) {
        echo json_encode([
            "draw" => 1,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => []
        ]);
        exit;
    }

    $start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    $length = isset($_REQUEST['length']) ? (int) $_REQUEST['length'] : 100;
    $search = $_REQUEST['search']['value'] ?? '';

    // Base where - only available sub-equipment for this equipment
    $baseWhere = "WHERE se.equipment_id = $equipment_id AND se.rental_status = 'available'";

    // Total records
    $totalSql = "SELECT COUNT(*) as total FROM sub_equipment se $baseWhere";
    $totalQuery = $db->readQuery($totalSql);
    $totalData = mysqli_fetch_assoc($totalQuery)['total'];

    // Search filter
    $where = $baseWhere;
    if (!empty($search)) {
        $where .= " AND (se.code LIKE '%$search%' OR se.name LIKE '%$search%')";
    }

    // Filtered records
    $filteredSql = "SELECT COUNT(*) as filtered FROM sub_equipment se $where";
    $filteredQuery = $db->readQuery($filteredSql);
    $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

    // Paginated query
    $sql = "SELECT se.*, e.code as equipment_code, e.item_name as equipment_name 
            FROM sub_equipment se 
            LEFT JOIN equipment e ON se.equipment_id = e.id
            $where ORDER BY se.code ASC LIMIT $start, $length";
    $dataQuery = $db->readQuery($sql);

    $data = [];
    $key = 1;

    while ($row = mysqli_fetch_assoc($dataQuery)) {
        $nestedData = [
            "key" => $key,
            "id" => $row['id'],
            "code" => $row['code'],
            "equipment_id" => $row['equipment_id'],
            "equipment_code" => $row['equipment_code'],
            "equipment_name" => $row['equipment_name'],
            "rental_status" => $row['rental_status'],
            "status_label" => '<span class="badge bg-soft-success font-size-12">Available</span>'
        ];

        $data[] = $nestedData;
        $key++;
    }

    echo json_encode([
        "draw" => intval($_REQUEST['draw'] ?? 1),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($filteredData),
        "data" => $data
    ]);
    exit;
}
