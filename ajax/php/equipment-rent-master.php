<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new equipment rent with items
if (isset($_POST['create'])) {

    // Check if bill number already exists
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM equipment_rent WHERE bill_number = '{$_POST['code']}'";
    $existingRent = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingRent) {
        echo json_encode(["status" => "duplicate", "message" => "Bill number already exists in the system"]);
        exit();
    }

    // Parse items from JSON
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    if (empty($items)) {
        echo json_encode(["status" => "error", "message" => "Please add at least one equipment item"]);
        exit();
    }

    // Validate all sub-equipment availability before creating
    foreach ($items as $item) {
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
    $EQUIPMENT_RENT->bill_number = $_POST['code'];
    $EQUIPMENT_RENT->customer_id = $_POST['customer_id'] ?? '';
    $EQUIPMENT_RENT->rental_date = $_POST['rental_date'] ?? date('Y-m-d');
    $EQUIPMENT_RENT->received_date = !empty($_POST['received_date']) ? $_POST['received_date'] : null;
    $EQUIPMENT_RENT->status = 'rented';
    $EQUIPMENT_RENT->remark = $_POST['remark'] ?? '';
    $EQUIPMENT_RENT->total_items = count($items);
    $EQUIPMENT_RENT->transport_cost = $_POST['transport_cost'] ?? 0;
    $EQUIPMENT_RENT->deposit_total = $_POST['custom_deposit'] ?? 0;

    $rent_id = $EQUIPMENT_RENT->create();

    if ($rent_id) {
        // Create rent items
        foreach ($items as $item) {
            $RENT_ITEM = new EquipmentRentItem(NULL);
            $RENT_ITEM->rent_id = $rent_id;
            $RENT_ITEM->equipment_id = $item['equipment_id'];
            $RENT_ITEM->sub_equipment_id = $item['sub_equipment_id'];
            $RENT_ITEM->rental_date = $item['rental_date'] ?? $_POST['rental_date'];
            $RENT_ITEM->return_date = !empty($item['return_date']) ? $item['return_date'] : null;
            $RENT_ITEM->quantity = $item['quantity'] ?? 1;
            $RENT_ITEM->rent_type = $item['rent_type'] ?? 'day';
            $RENT_ITEM->duration = $item['duration'] ?? 0;
            $RENT_ITEM->amount = $item['amount'] ?? 0;
            $RENT_ITEM->deposit_amount = $item['deposit'] ?? 0;
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

        // Increment document tracking ID for equipment rent
        (new DocumentTracking(null))->incrementDocumentId('equipment_rent');

        echo json_encode(["status" => "success", "rent_id" => $rent_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create rent record"]);
    }
    exit();
}

// Update equipment rent
if (isset($_POST['update'])) {

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
        $subEquipmentId = $item['sub_equipment_id'];
        $itemId = $item['id'] ?? null;

        // Check availability (exclude current item if updating)
        if (!EquipmentRentItem::isSubEquipmentAvailable($subEquipmentId, $itemId)) {
            $SUB_EQ = new SubEquipment($subEquipmentId);
            echo json_encode([
                "status" => "error", 
                "message" => "Sub equipment '{$SUB_EQ->code}' is already rented out"
            ]);
            exit();
        }

        if ($itemId) {
            // Update existing item
            $RENT_ITEM = new EquipmentRentItem($itemId);
            $RENT_ITEM->equipment_id = $item['equipment_id'];
            $RENT_ITEM->sub_equipment_id = $subEquipmentId;
            $RENT_ITEM->rental_date = $item['rental_date'] ?? $_POST['rental_date'];
            $RENT_ITEM->return_date = !empty($item['return_date']) ? $item['return_date'] : null;
            $RENT_ITEM->quantity = $item['quantity'] ?? 1;
            $RENT_ITEM->rent_type = $item['rent_type'] ?? 'day';
            $RENT_ITEM->duration = $item['duration'] ?? 0;
            $RENT_ITEM->duration = $item['duration'] ?? 0;
            $RENT_ITEM->amount = $item['amount'] ?? 0;
            $RENT_ITEM->deposit_amount = $item['deposit'] ?? 0;
            $RENT_ITEM->status = $item['status'] ?? 'rented';
            $RENT_ITEM->remark = $item['remark'] ?? '';
            $RENT_ITEM->update();
        } else {
            // Create new item
            $RENT_ITEM = new EquipmentRentItem(NULL);
            $RENT_ITEM->rent_id = $_POST['rent_id'];
            $RENT_ITEM->equipment_id = $item['equipment_id'];
            $RENT_ITEM->sub_equipment_id = $subEquipmentId;
            $RENT_ITEM->rental_date = $item['rental_date'] ?? $_POST['rental_date'];
            $RENT_ITEM->return_date = !empty($item['return_date']) ? $item['return_date'] : null;
            $RENT_ITEM->quantity = $item['quantity'] ?? 1;
            $RENT_ITEM->rent_type = $item['rent_type'] ?? 'day';
            $RENT_ITEM->duration = $item['duration'] ?? 0;
            $RENT_ITEM->amount = $item['amount'] ?? 0;
            $RENT_ITEM->deposit_amount = $item['deposit'] ?? 0;
            $RENT_ITEM->status = 'rented';
            $RENT_ITEM->remark = $item['remark'] ?? '';
            $RENT_ITEM->create();
        }
    }

    // Update master record
    $EQUIPMENT_RENT->bill_number = $_POST['code'];
    $EQUIPMENT_RENT->customer_id = $_POST['customer_id'] ?? '';
    $EQUIPMENT_RENT->rental_date = $_POST['rental_date'] ?? date('Y-m-d');
    $EQUIPMENT_RENT->received_date = !empty($_POST['received_date']) ? $_POST['received_date'] : null;
    $EQUIPMENT_RENT->remark = $_POST['remark'] ?? '';
    $EQUIPMENT_RENT->transport_cost = $_POST['transport_cost'] ?? 0;
    $EQUIPMENT_RENT->deposit_total = $_POST['custom_deposit'] ?? 0;
    
    // Check if all items are returned
    $allReturned = true;
    foreach ($items as $item) {
        if (($item['status'] ?? 'rented') === 'rented') {
            $allReturned = false;
            break;
        }
    }
    $EQUIPMENT_RENT->status = $allReturned ? 'returned' : 'rented';
    
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

// Delete equipment rent
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $EQUIPMENT_RENT = new EquipmentRent($_POST['id']);

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $EQUIPMENT_RENT->bill_number;
    $AUDIT_LOG->action = 'DELETE';
    $AUDIT_LOG->description = 'DELETE EQUIPMENT RENT NO #' . $EQUIPMENT_RENT->bill_number;
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    // Delete will also release all sub_equipment
    $res = $EQUIPMENT_RENT->delete();

    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Mark single item as returned
if (isset($_POST['action']) && $_POST['action'] === 'return_item') {
    $item_id = $_POST['item_id'] ?? 0;
    
    if ($item_id) {
        $RENT_ITEM = new EquipmentRentItem($item_id);
        if ($RENT_ITEM->markAsReturned()) {
            // Check if all items for this rent are returned
            $EQUIPMENT_RENT = new EquipmentRent($RENT_ITEM->rent_id);
            if (!$EQUIPMENT_RENT->hasActiveRentals()) {
                $EQUIPMENT_RENT->status = 'returned';
                $EQUIPMENT_RENT->received_date = date('Y-m-d');
                $EQUIPMENT_RENT->update();
            }
            
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark item as returned"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Item ID required"]);
    }
    exit;
}

// Mark all items as returned
if (isset($_POST['action']) && $_POST['action'] === 'return_all') {
    $rent_id = $_POST['rent_id'] ?? 0;
    
    if ($rent_id) {
        $EQUIPMENT_RENT = new EquipmentRent($rent_id);
        if ($EQUIPMENT_RENT->markAllReturned()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark items as returned"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Rent ID required"]);
    }
    exit;
}

// Filter for DataTable
if (isset($_POST['filter'])) {
    $EQUIPMENT_RENT = new EquipmentRent(NULL);
    
    // Add custom filter for Issue Note page (exclude already issued invoices)
    if (isset($_POST['exclude_issued']) && $_POST['exclude_issued'] == 'true') {
        $_REQUEST['custom_where'] = "AND er.id NOT IN (SELECT rent_invoice_id FROM issue_notes WHERE issue_status != 'cancelled')";
    }
    
    $result = $EQUIPMENT_RENT->fetchForDataTable($_REQUEST);
    echo json_encode($result);
    exit;
}

// Get rent details with items
if (isset($_POST['action']) && $_POST['action'] === 'get_rent_details') {
    $rent_id = $_POST['rent_id'] ?? 0;
    
    if ($rent_id) {
        $EQUIPMENT_RENT = new EquipmentRent($rent_id);
        
        // Get items with equipment deposit info
        $db = Database::getInstance();
        $itemsQuery = "SELECT ri.*, e.code as equipment_code, e.item_name as equipment_name, 
                       e.deposit_one_day as equipment_deposit,
                       se.code as sub_equipment_code 
                       FROM equipment_rent_items ri 
                       LEFT JOIN equipment e ON ri.equipment_id = e.id
                       LEFT JOIN sub_equipment se ON ri.sub_equipment_id = se.id
                       WHERE ri.rent_id = $rent_id";
        $itemsResult = $db->readQuery($itemsQuery);
        $items = [];
        while ($row = mysqli_fetch_assoc($itemsResult)) {
            $items[] = $row;
        }
        
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
    $newCode = 'ER/' . $_SESSION['id'] . '/0' . ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
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
                "deposit_one_day" => $EQUIPMENT->deposit_one_day
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
        $where .= " WHERE (name LIKE '%$search%' OR code LIKE '%$search%' OR mobile_number LIKE '%$search%')";
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
            "outstanding" => number_format($row['old_outstanding'] ?? 0, 2)
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

// Filter equipment for DataTable (only equipment that has sub-equipment)
if (isset($_POST['filter_equipment'])) {
    $db = Database::getInstance();

    $start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    $length = isset($_REQUEST['length']) ? (int) $_REQUEST['length'] : 100;
    $search = $_REQUEST['search']['value'] ?? '';

    // Only get equipment that has sub-equipment
    $baseWhere = "WHERE EXISTS (SELECT 1 FROM sub_equipment se WHERE se.equipment_id = e.id)";

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

    // Paginated query with sub-equipment counts
    $sql = "SELECT e.*, ec.name as category_name,
            (SELECT COUNT(*) FROM sub_equipment se WHERE se.equipment_id = e.id) as total_sub,
            (SELECT COUNT(*) FROM sub_equipment se WHERE se.equipment_id = e.id AND se.rental_status = 'available') as available_sub
            FROM equipment e 
            LEFT JOIN equipment_category ec ON e.category = ec.id
            $where ORDER BY e.item_name ASC LIMIT $start, $length";
    $dataQuery = $db->readQuery($sql);

    $data = [];
    $key = 1;

    while ($row = mysqli_fetch_assoc($dataQuery)) {
        $categoryLabel = $row['category_name'] ?: ($row['category'] ?: '-');

        $nestedData = [
            "key" => $key,
            "id" => $row['id'],
            "code" => $row['code'],
            "item_name" => $row['item_name'],
            "category" => $row['category'],
            "category_label" => $categoryLabel,
            "serial_number" => $row['serial_number'],
            "total_sub" => $row['total_sub'],
            "available_sub" => $row['available_sub'],
            "rent_one_day" => $row['rent_one_day'],
            "deposit_one_day" => $row['deposit_one_day'],
            "rent_one_month" => $row['rent_one_month'],
            "availability_label" => $row['available_sub'] > 0
                ? '<span class="badge bg-soft-success font-size-12">' . $row['available_sub'] . ' / ' . $row['total_sub'] . ' Available</span>'
                : '<span class="badge bg-soft-danger font-size-12">All Rented</span>'
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
