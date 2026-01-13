<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new equipment rent
if (isset($_POST['create'])) {

    // Check if code already exists
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM equipment_rent WHERE code = '{$_POST['code']}'";
    $existingRent = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingRent) {
        echo json_encode(["status" => "duplicate", "message" => "Equipment rent code already exists in the system"]);
        exit();
    }

    $rentQuantity = (int) ($_POST['quantity'] ?? 1);
    $equipmentId = $_POST['equipment_id'] ?? '';
    $rentStatus = $_POST['rent_status'] ?? 'rented';

    // Check if enough quantity is available
    if ($equipmentId && $rentStatus === 'rented') {
        $EQUIPMENT = new Equipment($equipmentId);
        if ($EQUIPMENT->quantity == 0) {
            echo json_encode(["status" => "error", "message" => "Cannot rent this equipment. Available quantity is 0."]);
            exit();
        }
        if ($EQUIPMENT->quantity < $rentQuantity) {
            echo json_encode(["status" => "error", "message" => "Not enough quantity available. Available: " . $EQUIPMENT->quantity]);
            exit();
        }
    }

    $EQUIPMENT_RENT = new EquipmentRent(NULL);

    $EQUIPMENT_RENT->code = $_POST['code'];
    $EQUIPMENT_RENT->customer_id = $_POST['customer_id'] ?? '';
    $EQUIPMENT_RENT->equipment_id = $equipmentId;
    $EQUIPMENT_RENT->rental_date = $_POST['rental_date'] ?? date('Y-m-d');
    $EQUIPMENT_RENT->received_date = !empty($_POST['received_date']) ? $_POST['received_date'] : null;
    $EQUIPMENT_RENT->status = $rentStatus;
    $EQUIPMENT_RENT->quantity = $rentQuantity;
    $EQUIPMENT_RENT->remark = $_POST['remark'] ?? '';

    $res = $EQUIPMENT_RENT->create();

    // Update equipment available quantity (decrease when rented)
    if ($res && $equipmentId && $rentStatus === 'rented') {
        $EQUIPMENT = new Equipment($equipmentId);
        $EQUIPMENT->quantity = max(0, $EQUIPMENT->quantity - $rentQuantity);
        $EQUIPMENT->update();
    }

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['code'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'CREATE';
    $AUDIT_LOG->description = 'CREATE EQUIPMENT RENT NO #' . $_POST['code'];
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    if ($res) {
        echo json_encode(["status" => "success"]);
        exit();
    } else {
        echo json_encode(["status" => "error"]);
        exit();
    }
}

// Update equipment rent
if (isset($_POST['update'])) {

    // Check if code already exists (excluding current record)
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM equipment_rent WHERE code = '{$_POST['code']}' AND id != '{$_POST['rent_id']}'";
    $existingRent = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingRent) {
        echo json_encode(["status" => "duplicate", "message" => "Equipment rent code already exists in the system"]);
        exit();
    }

    // Get the existing rent record to compare changes
    $EQUIPMENT_RENT = new EquipmentRent($_POST['rent_id']);
    $oldStatus = $EQUIPMENT_RENT->status;
    $oldQuantity = (int) $EQUIPMENT_RENT->quantity;
    $oldEquipmentId = $EQUIPMENT_RENT->equipment_id;

    $newStatus = $_POST['rent_status'] ?? 'rented';
    $newQuantity = (int) ($_POST['quantity'] ?? 1);
    $newEquipmentId = $_POST['equipment_id'] ?? '';

    // Handle equipment quantity adjustments based on status changes
    // Restore quantity to old equipment if equipment changed or status changed to returned/available
    if ($oldEquipmentId && ($oldStatus === 'rented')) {
        // If equipment changed, or status changed from rented to returned/available
        if ($oldEquipmentId != $newEquipmentId || ($oldStatus === 'rented' && $newStatus !== 'rented')) {
            $OLD_EQUIPMENT = new Equipment($oldEquipmentId);
            $OLD_EQUIPMENT->quantity = $OLD_EQUIPMENT->quantity + $oldQuantity;
            $OLD_EQUIPMENT->update();
        } elseif ($oldEquipmentId == $newEquipmentId && $oldStatus === 'rented' && $newStatus === 'rented' && $oldQuantity != $newQuantity) {
            // Same equipment, same status, but quantity changed - adjust the difference
            $OLD_EQUIPMENT = new Equipment($oldEquipmentId);
            $quantityDiff = $newQuantity - $oldQuantity;
            if ($OLD_EQUIPMENT->quantity < $quantityDiff) {
                echo json_encode(["status" => "error", "message" => "Not enough quantity available. Available: " . $OLD_EQUIPMENT->quantity]);
                exit();
            }
            $OLD_EQUIPMENT->quantity = $OLD_EQUIPMENT->quantity - $quantityDiff;
            $OLD_EQUIPMENT->update();
        }
    }

    // Decrease quantity from new equipment if status is rented
    if ($newEquipmentId && $newStatus === 'rented') {
        // Only decrease if equipment changed or status changed to rented
        if ($oldEquipmentId != $newEquipmentId || ($oldStatus !== 'rented' && $newStatus === 'rented')) {
            $NEW_EQUIPMENT = new Equipment($newEquipmentId);
            if ($NEW_EQUIPMENT->quantity < $newQuantity) {
                echo json_encode(["status" => "error", "message" => "Not enough quantity available. Available: " . $NEW_EQUIPMENT->quantity]);
                exit();
            }
            $NEW_EQUIPMENT->quantity = $NEW_EQUIPMENT->quantity - $newQuantity;
            $NEW_EQUIPMENT->update();
        }
    }

    $EQUIPMENT_RENT->code = $_POST['code'];
    $EQUIPMENT_RENT->customer_id = $_POST['customer_id'] ?? '';
    $EQUIPMENT_RENT->equipment_id = $newEquipmentId;
    $EQUIPMENT_RENT->rental_date = $_POST['rental_date'] ?? date('Y-m-d');
    $EQUIPMENT_RENT->received_date = !empty($_POST['received_date']) ? $_POST['received_date'] : null;
    $EQUIPMENT_RENT->status = $newStatus;
    $EQUIPMENT_RENT->quantity = $newQuantity;
    $EQUIPMENT_RENT->remark = $_POST['remark'] ?? '';

    $res = $EQUIPMENT_RENT->update();

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['rent_id'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE EQUIPMENT RENT NO #' . $_POST['code'];
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    if ($res) {
        echo json_encode(["status" => "success"]);
        exit();
    } else {
        echo json_encode(["status" => "error"]);
        exit();
    }
}

// Delete equipment rent
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $EQUIPMENT_RENT = new EquipmentRent($_POST['id']);

    // Restore equipment quantity if the rent was in 'rented' status
    if ($EQUIPMENT_RENT->equipment_id && $EQUIPMENT_RENT->status === 'rented') {
        $EQUIPMENT = new Equipment($EQUIPMENT_RENT->equipment_id);
        $EQUIPMENT->quantity = $EQUIPMENT->quantity + (int) $EQUIPMENT_RENT->quantity;
        $EQUIPMENT->update();
    }

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $EQUIPMENT_RENT->code;
    $AUDIT_LOG->action = 'DELETE';
    $AUDIT_LOG->description = 'DELETE EQUIPMENT RENT NO #' . $EQUIPMENT_RENT->code;
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    $res = $EQUIPMENT_RENT->delete();

    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Filter for DataTable
if (isset($_POST['filter'])) {
    $EQUIPMENT_RENT = new EquipmentRent(NULL);
    $result = $EQUIPMENT_RENT->fetchForDataTable($_REQUEST);
    echo json_encode($result);
    exit;
}

// Get new code
if (isset($_POST['action']) && $_POST['action'] === 'get_new_code') {
    $EQUIPMENT_RENT = new EquipmentRent(NULL);
    $lastId = $EQUIPMENT_RENT->getLastID();
    $newCode = 'ER/' . $_SESSION['id'] . '/0' . ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit;
}

// Get equipment available quantity
if (isset($_POST['action']) && $_POST['action'] === 'get_equipment_quantity') {
    $equipment_id = $_POST['equipment_id'] ?? 0;

    if ($equipment_id) {
        $EQUIPMENT = new Equipment($equipment_id);
        echo json_encode([
            "status" => "success",
            "quantity" => $EQUIPMENT->quantity ?? 0,
            "item_name" => $EQUIPMENT->item_name ?? ''
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
    $totalSql = "SELECT COUNT(*) as total FROM customer_master WHERE is_active = 1";
    $totalQuery = $db->readQuery($totalSql);
    $totalData = mysqli_fetch_assoc($totalQuery)['total'];

    // Search filter
    $where = "WHERE is_active = 1";
    if (!empty($search)) {
        $where .= " AND (name LIKE '%$search%' OR code LIKE '%$search%' OR mobile_number LIKE '%$search%')";
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
            "address" => $row['address']
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

// Filter equipment for DataTable
if (isset($_POST['filter_equipment'])) {
    $db = Database::getInstance();

    $start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    $length = isset($_REQUEST['length']) ? (int) $_REQUEST['length'] : 100;
    $search = $_REQUEST['search']['value'] ?? '';

    // Total records
    $totalSql = "SELECT COUNT(*) as total FROM equipment WHERE availability_status = 1";
    $totalQuery = $db->readQuery($totalSql);
    $totalData = mysqli_fetch_assoc($totalQuery)['total'];

    // Search filter
    $where = "WHERE availability_status = 1";
    if (!empty($search)) {
        $where .= " AND (item_name LIKE '%$search%' OR code LIKE '%$search%' OR serial_number LIKE '%$search%')";
    }

    // Filtered records
    $filteredSql = "SELECT COUNT(*) as filtered FROM equipment $where";
    $filteredQuery = $db->readQuery($filteredSql);
    $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

    // Paginated query
    $sql = "SELECT * FROM equipment $where ORDER BY item_name ASC LIMIT $start, $length";
    $dataQuery = $db->readQuery($sql);

    $data = [];
    $key = 1;

    // Category name mapping
    $categoryNames = [
        '1' => 'Power Tools',
        '2' => 'Hand Tools',
        '3' => 'Safety Equipment',
        '4' => 'Measuring Instruments',
        '5' => 'Electrical Equipment'
    ];

    while ($row = mysqli_fetch_assoc($dataQuery)) {
        $categoryLabel = isset($categoryNames[$row['category']]) ? $categoryNames[$row['category']] : $row['category'];

        $nestedData = [
            "key" => $key,
            "id" => $row['id'],
            "code" => $row['code'],
            "item_name" => $row['item_name'],
            "category" => $row['category'],
            "category_label" => $categoryLabel,
            "serial_number" => $row['serial_number'],
            "quantity" => $row['quantity'],
            "condition_label" => $row['is_condition'] == 1
                ? '<span class="badge bg-soft-success font-size-12">Good</span>'
                : '<span class="badge bg-soft-danger font-size-12">Bad</span>'
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
