<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new equipment rent quotation with items
if (isset($_POST['create'])) {

    // Check if quotation number already exists
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM equipment_rent_quotation WHERE quotation_number = '{$_POST['quotation_number']}'";
    $existingQuotation = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingQuotation) {
        echo json_encode(["status" => "duplicate", "message" => "Quotation number already exists in the system"]);
        exit();
    }

    // Parse items from JSON
    $items = json_decode($_POST['items'] ?? '[]', true);

    if (empty($items)) {
        echo json_encode(["status" => "error", "message" => "Please add at least one equipment item"]);
        exit();
    }

    // Create master quotation record
    $QUOTATION = new EquipmentRentQuotation(NULL);
    $QUOTATION->quotation_number = $_POST['quotation_number'];
    $QUOTATION->customer_id = $_POST['customer_id'] ?? '';
    $QUOTATION->customer_name = $_POST['customer_name'] ?? '';
    $QUOTATION->rental_date = $_POST['rental_date'] ?? date('Y-m-d');
    $QUOTATION->received_date = !empty($_POST['received_date']) ? $_POST['received_date'] : null;
    $QUOTATION->status = 'pending';
    $QUOTATION->remark = $_POST['remark'] ?? '';
    $QUOTATION->transport_cost = $_POST['transport_cost'] ?? 0;
    $QUOTATION->deposit_total = $_POST['deposit_total'] ?? 0;
    $QUOTATION->total_items = count($items);

    $quotation_id = $QUOTATION->create();

    if ($quotation_id) {
        // Create quotation items
        foreach ($items as $item) {
            $QUOTATION_ITEM = new EquipmentRentQuotationItem(NULL);
            $QUOTATION_ITEM->quotation_id = $quotation_id;
            $QUOTATION_ITEM->equipment_id = $item['equipment_id'];
            $QUOTATION_ITEM->sub_equipment_id = $item['sub_equipment_id'];
            $QUOTATION_ITEM->rental_date = $item['rental_date'] ?? $_POST['rental_date'];
            $QUOTATION_ITEM->return_date = !empty($item['return_date']) ? $item['return_date'] : null;
            $QUOTATION_ITEM->quantity = $item['quantity'] ?? 1;
            $QUOTATION_ITEM->rent_type = $item['rent_type'] ?? 'day';
            $QUOTATION_ITEM->duration = $item['duration'] ?? 0;
            $QUOTATION_ITEM->unit_price = $item['unit_price'] ?? 0;
            $QUOTATION_ITEM->amount = $item['amount'] ?? 0;
            $QUOTATION_ITEM->status = 'pending';
            $QUOTATION_ITEM->remark = $item['remark'] ?? '';
            $QUOTATION_ITEM->create();
        }

        // Update total items count
        $QUOTATION->id = $quotation_id;
        $QUOTATION->updateTotalItems();

        // Audit log
        $AUDIT_LOG = new AuditLog(NULL);
        $AUDIT_LOG->ref_id = $quotation_id;
        $AUDIT_LOG->ref_code = $_POST['quotation_number'];
        $AUDIT_LOG->action = 'CREATE';
        $AUDIT_LOG->description = 'CREATE EQUIPMENT QUOTATION NO #' . $_POST['quotation_number'] . ' with ' . count($items) . ' items';
        $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        // Increment document tracking ID for equipment rent quotation
        (new DocumentTracking(null))->incrementDocumentId('equipment_rent_quotation');

        echo json_encode(["status" => "success", "quotation_id" => $quotation_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create quotation record"]);
    }
    exit();
}

// Update equipment rent quotation
if (isset($_POST['update'])) {

    // Check if quotation number already exists (excluding current record)
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM equipment_rent_quotation WHERE quotation_number = '{$_POST['quotation_number']}' AND id != '{$_POST['quotation_id']}'";
    $existingQuotation = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingQuotation) {
        echo json_encode(["status" => "duplicate", "message" => "Quotation number already exists in the system"]);
        exit();
    }

    $QUOTATION = new EquipmentRentQuotation($_POST['quotation_id']);

    // Parse items from JSON
    $items = json_decode($_POST['items'] ?? '[]', true);

    if (empty($items)) {
        echo json_encode(["status" => "error", "message" => "Please add at least one equipment item"]);
        exit();
    }

    // Get existing item IDs for this quotation
    $existingItems = $QUOTATION->getItems();
    $existingItemIds = array_column($existingItems, 'id');
    $newItemIds = array_filter(array_column($items, 'id'));

    // Find items to delete (exist in DB but not in new list)
    $itemsToDelete = array_diff($existingItemIds, $newItemIds);

    // Delete removed items
    foreach ($itemsToDelete as $itemId) {
        $QUOTATION_ITEM = new EquipmentRentQuotationItem($itemId);
        $QUOTATION_ITEM->delete();
    }

    // Validate and update/create items
    foreach ($items as $item) {
        $subEquipmentId = $item['sub_equipment_id'];
        $itemId = $item['id'] ?? null;

        if ($itemId) {
            // Update existing item
            $QUOTATION_ITEM = new EquipmentRentQuotationItem($itemId);
            $QUOTATION_ITEM->equipment_id = $item['equipment_id'];
            $QUOTATION_ITEM->sub_equipment_id = $subEquipmentId;
            $QUOTATION_ITEM->rental_date = $item['rental_date'] ?? $_POST['rental_date'];
            $QUOTATION_ITEM->return_date = !empty($item['return_date']) ? $item['return_date'] : null;
            $QUOTATION_ITEM->quantity = $item['quantity'] ?? 1;
            $QUOTATION_ITEM->rent_type = $item['rent_type'] ?? 'day';
            $QUOTATION_ITEM->duration = $item['duration'] ?? 0;
            $QUOTATION_ITEM->unit_price = $item['unit_price'] ?? 0;
            $QUOTATION_ITEM->amount = $item['amount'] ?? 0;
            $QUOTATION_ITEM->status = $item['status'] ?? 'pending';
            $QUOTATION_ITEM->remark = $item['remark'] ?? '';
            $QUOTATION_ITEM->update();
        } else {
            // Create new item
            $QUOTATION_ITEM = new EquipmentRentQuotationItem(NULL);
            $QUOTATION_ITEM->quotation_id = $_POST['quotation_id'];
            $QUOTATION_ITEM->equipment_id = $item['equipment_id'];
            $QUOTATION_ITEM->sub_equipment_id = $subEquipmentId;
            $QUOTATION_ITEM->rental_date = $item['rental_date'] ?? $_POST['rental_date'];
            $QUOTATION_ITEM->return_date = !empty($item['return_date']) ? $item['return_date'] : null;
            $QUOTATION_ITEM->quantity = $item['quantity'] ?? 1;
            $QUOTATION_ITEM->rent_type = $item['rent_type'] ?? 'day';
            $QUOTATION_ITEM->duration = $item['duration'] ?? 0;
            $QUOTATION_ITEM->unit_price = $item['unit_price'] ?? 0;
            $QUOTATION_ITEM->amount = $item['amount'] ?? 0;
            $QUOTATION_ITEM->status = 'pending';
            $QUOTATION_ITEM->remark = $item['remark'] ?? '';
            $QUOTATION_ITEM->create();
        }
    }

    // Update master record
    $QUOTATION->quotation_number = $_POST['quotation_number'];
    $QUOTATION->customer_id = $_POST['customer_id'] ?? '';
    $QUOTATION->customer_name = $_POST['customer_name'] ?? '';
    $QUOTATION->rental_date = $_POST['rental_date'] ?? date('Y-m-d');
    $QUOTATION->received_date = !empty($_POST['received_date']) ? $_POST['received_date'] : null;
    $QUOTATION->remark = $_POST['remark'] ?? '';
    $QUOTATION->transport_cost = $_POST['transport_cost'] ?? 0;
    $QUOTATION->deposit_total = $_POST['deposit_total'] ?? 0;

    $res = $QUOTATION->update();
    $QUOTATION->updateTotalItems();

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['quotation_id'];
    $AUDIT_LOG->ref_code = $_POST['quotation_number'];
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE EQUIPMENT QUOTATION NO #' . $_POST['quotation_number'];
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

// Delete equipment rent quotation
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $QUOTATION = new EquipmentRentQuotation($_POST['id']);

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $QUOTATION->quotation_number;
    $AUDIT_LOG->action = 'DELETE';
    $AUDIT_LOG->description = 'DELETE EQUIPMENT QUOTATION NO #' . $QUOTATION->quotation_number;
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    $res = $QUOTATION->delete();

    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Filter for DataTable
if (isset($_POST['filter'])) {
    $QUOTATION = new EquipmentRentQuotation(NULL);
    $result = $QUOTATION->fetchForDataTable($_REQUEST);
    echo json_encode($result);
    exit;
}

// Get quotation details with items
if (isset($_POST['action']) && $_POST['action'] === 'get_quotation_details') {
    $quotation_id = $_POST['quotation_id'] ?? 0;

    if ($quotation_id) {
        $QUOTATION = new EquipmentRentQuotation($quotation_id);
        $items = $QUOTATION->getItems();

        // Get customer details (use stored manual name when no linked customer)
        $customerDisplay = $QUOTATION->customer_name;
        if (!empty($QUOTATION->customer_id)) {
            $CUSTOMER = new CustomerMaster($QUOTATION->customer_id);
            $customerDisplay = $CUSTOMER->code . ' - ' . $CUSTOMER->name;
        }

        echo json_encode([
            "status" => "success",
            "quotation" => [
                "id" => $QUOTATION->id,
                "quotation_number" => $QUOTATION->quotation_number,
                "customer_id" => $QUOTATION->customer_id,
                "customer_name" => $customerDisplay,
                "rental_date" => $QUOTATION->rental_date,
                "received_date" => $QUOTATION->received_date,
                "status" => $QUOTATION->status,
                "remark" => $QUOTATION->remark,
                "transport_cost" => $QUOTATION->transport_cost,
                "deposit_total" => $QUOTATION->deposit_total,
                "total_items" => $QUOTATION->total_items
            ],
            "items" => $items
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Quotation ID required"]);
    }
    exit;
}

// Get new code
if (isset($_POST['action']) && $_POST['action'] === 'get_new_code') {
    // Get quotation number from document tracking table
    $DOCUMENT_TRACKING = new DocumentTracking(1);
    $lastId = $DOCUMENT_TRACKING->equipment_rent_quotation_id;
    $newCode = 'ERQ/' . $_SESSION['id'] . '/0' . ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
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

// Filter equipment for DataTable (include ones without sub-equipment to allow direct rentals)
if (isset($_POST['filter_equipment'])) {
    $db = Database::getInstance();

    $start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    $length = isset($_REQUEST['length']) ? (int) $_REQUEST['length'] : 100;
    $search = $_REQUEST['search']['value'] ?? '';

    // Include all equipment; sub-equipment availability is handled per row
    $baseWhere = "WHERE 1=1";

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
            "image_name" => $row['image_name'],
            "category" => $row['category'],
            "category_label" => $categoryLabel,
            "serial_number" => $row['serial_number'],
            "size" => $row['size'],
            "value" => $row['value'],
            "total_sub" => $row['total_sub'],
            "available_sub" => $row['available_sub'],
            "no_sub_items" => $row['no_sub_items'] ?? 0,
            "rent_one_day" => $row['rent_one_day'],
            "deposit_one_day" => $row['deposit_one_day'],
            "rent_one_month" => $row['rent_one_month'],
            "availability_label" => ($row['total_sub'] ?? 0) > 0
                ? ($row['available_sub'] > 0
                    ? '<span class="badge bg-soft-success font-size-12">' . $row['available_sub'] . ' / ' . $row['total_sub'] . ' Available</span>'
                    : '<span class="badge bg-soft-danger font-size-12">All Rented</span>')
                : '<span class="badge bg-soft-secondary font-size-12">No Sub Items</span>'
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

    // Base where - we show all "available" sub-equipment even though quotes don't reserve them.
    // However, users might want to quote on currently rented items too?
    // The requirement was "same like invoice" (meaning equipment rent), which only shows available.
    // So distinct to equipment rent: Let's show available for now to keep it simpler.
    // If they want to quote booked items, they can't selection them here.
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
