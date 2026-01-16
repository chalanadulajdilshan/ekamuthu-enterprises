<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new equipment
if (isset($_POST['create'])) {

    // Check if code already exists
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM equipment WHERE code = '{$_POST['code']}'";
    $existingEquipment = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingEquipment) {
        echo json_encode(["status" => "duplicate", "message" => "Equipment code already exists in the system"]);
        exit();
    }

    $EQUIPMENT = new Equipment(NULL);

    $EQUIPMENT->code = $_POST['code'];
    $EQUIPMENT->item_name = strtoupper($_POST['item_name'] ?? '');
    $EQUIPMENT->category = $_POST['category'] ?? '';
    $EQUIPMENT->serial_number = $_POST['serial_number'] ?? '';
    $EQUIPMENT->damage = $_POST['damage'] ?? '';
    $EQUIPMENT->size = $_POST['size'] ?? '';
    $EQUIPMENT->rent_one_day = $_POST['rent_one_day'] ?? 0;
    $EQUIPMENT->deposit_one_day = $_POST['deposit_one_day'] ?? 0;
    $EQUIPMENT->rent_one_month = $_POST['rent_one_month'] ?? 0;
    $EQUIPMENT->value = $_POST['value'] ?? 0;
    $EQUIPMENT->quantity = $_POST['quantity'] ?? 0;

    $res = $EQUIPMENT->create();

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['code'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'CREATE';
    $AUDIT_LOG->description = 'CREATE EQUIPMENT NO #' . $_POST['code'];
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

// Update equipment
if (isset($_POST['update'])) {

    // Check if code already exists (excluding current equipment)
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM equipment WHERE code = '{$_POST['code']}' AND id != '{$_POST['equipment_id']}'";
    $existingEquipment = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingEquipment) {
        echo json_encode(["status" => "duplicate", "message" => "Equipment code already exists in the system"]);
        exit();
    }

    $EQUIPMENT = new Equipment($_POST['equipment_id']);

    $EQUIPMENT->code = $_POST['code'];
    $EQUIPMENT->item_name = strtoupper($_POST['item_name'] ?? '');
    $EQUIPMENT->category = $_POST['category'] ?? '';
    $EQUIPMENT->serial_number = $_POST['serial_number'] ?? '';
    $EQUIPMENT->damage = $_POST['damage'] ?? '';
    $EQUIPMENT->size = $_POST['size'] ?? '';
    $EQUIPMENT->rent_one_day = $_POST['rent_one_day'] ?? 0;
    $EQUIPMENT->deposit_one_day = $_POST['deposit_one_day'] ?? 0;
    $EQUIPMENT->rent_one_month = $_POST['rent_one_month'] ?? 0;
    $EQUIPMENT->value = $_POST['value'] ?? 0;
    $EQUIPMENT->quantity = $_POST['quantity'] ?? 0;

    $res = $EQUIPMENT->update();

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['equipment_id'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE EQUIPMENT NO #' . $_POST['code'];
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

// Delete equipment
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $EQUIPMENT = new Equipment($_POST['id']);

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $EQUIPMENT->code;
    $AUDIT_LOG->action = 'DELETE';
    $AUDIT_LOG->description = 'DELETE EQUIPMENT NO #' . $EQUIPMENT->code;
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    $res = $EQUIPMENT->delete();

    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Filter for DataTable
if (isset($_POST['filter'])) {
    $db = Database::getInstance();

    $start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    $length = isset($_REQUEST['length']) ? (int) $_REQUEST['length'] : 100;
    $search = $_REQUEST['search']['value'] ?? '';

    // Total records
    $totalSql = "SELECT COUNT(*) as total FROM equipment";
    $totalQuery = $db->readQuery($totalSql);
    $totalData = mysqli_fetch_assoc($totalQuery)['total'];

    // Search filter
    $where = "WHERE 1=1";
    if (!empty($search)) {
        $where .= " AND (item_name LIKE '%$search%' OR code LIKE '%$search%' OR serial_number LIKE '%$search%' OR category LIKE '%$search%' OR damage LIKE '%$search%' OR size LIKE '%$search%')";
    }

    // Filtered records
    $filteredSql = "SELECT COUNT(*) as filtered FROM equipment $where";
    $filteredQuery = $db->readQuery($filteredSql);
    $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

    // Paginated query
    $sql = "SELECT * FROM equipment $where ORDER BY id DESC LIMIT $start, $length";
    $dataQuery = $db->readQuery($sql);

    $data = [];
    $key = 1;

    while ($row = mysqli_fetch_assoc($dataQuery)) {
        // Get category name from equipment_category table
        $categoryLabel = $row['category'];
        if (!empty($row['category'])) {
            $catQuery = "SELECT name FROM equipment_category WHERE id = " . (int) $row['category'];
            $catResult = $db->readQuery($catQuery);
            if ($catResult && $catRow = mysqli_fetch_assoc($catResult)) {
                $categoryLabel = $catRow['name'];
            }
        }

        $nestedData = [
            "key" => $key,
            "id" => $row['id'],
            "code" => $row['code'],
            "item_name" => $row['item_name'],
            "category" => $row['category'],
            "category_label" => $categoryLabel,
            "serial_number" => $row['serial_number'],
            "damage" => $row['damage'],
            "size" => $row['size'],
            "rent_one_day" => $row['rent_one_day'],
            "deposit_one_day" => $row['deposit_one_day'],
            "rent_one_month" => $row['rent_one_month'],
            "value" => $row['value'],
            "quantity" => $row['quantity']
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

// Get new code
if (isset($_POST['action']) && $_POST['action'] === 'get_new_code') {
    $EQUIPMENT = new Equipment(NULL);
    $lastId = $EQUIPMENT->getLastID();
    $nextNumber = $lastId + 1;
    // Pad with leading zeros for minimum 3 digits (001, 010, 100, 1000+)
    $newCode = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit;
}

// Get sub-equipment by equipment_id
if (isset($_POST['action']) && $_POST['action'] === 'get_sub_equipment') {
    $equipment_id = isset($_POST['equipment_id']) ? (int) $_POST['equipment_id'] : 0;

    if ($equipment_id > 0) {
        $db = Database::getInstance();
        $sql = "SELECT id, equipment_id, code, name FROM sub_equipment WHERE equipment_id = $equipment_id ORDER BY id ASC";
        $result = $db->readQuery($sql);

        $subEquipments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $subEquipments[] = [
                'id' => $row['id'],
                'equipment_id' => $row['equipment_id'],
                'code' => $row['code'],
                'name' => $row['name']
            ];
        }

        echo json_encode([
            "status" => "success",
            "data" => $subEquipments
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Equipment ID required"
        ]);
    }
    exit;
}

// Get equipment totals for summary cards
if (isset($_POST['action']) && $_POST['action'] === 'get_equipment_totals') {
    $db = Database::getInstance();

    // Total equipment count
    $totalSql = "SELECT COUNT(*) as total FROM equipment";
    $totalResult = $db->readQuery($totalSql);
    $total = mysqli_fetch_assoc($totalResult)['total'] ?? 0;

    echo json_encode([
        "status" => "success",
        "data" => [
            "total" => (int) $total
        ]
    ]);
    exit;
}
