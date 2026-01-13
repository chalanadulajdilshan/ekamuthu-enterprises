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
    $EQUIPMENT->is_condition = $_POST['is_condition'] ?? 1;
    $EQUIPMENT->availability_status = $_POST['availability_status'] ?? 1;
    $EQUIPMENT->queue = $_POST['queue'] ?? 0;

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
    $EQUIPMENT->is_condition = $_POST['is_condition'] ?? 1;
    $EQUIPMENT->availability_status = $_POST['availability_status'] ?? 1;
    $EQUIPMENT->queue = $_POST['queue'] ?? 0;

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
        $where .= " AND (item_name LIKE '%$search%' OR code LIKE '%$search%' OR serial_number LIKE '%$search%' OR category LIKE '%$search%')";
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
        $nestedData = [
            "key" => $key,
            "id" => $row['id'],
            "code" => $row['code'],
            "item_name" => $row['item_name'],
            "category" => $row['category'],
            "serial_number" => $row['serial_number'],
            "is_condition" => $row['is_condition'],
            "condition_label" => $row['is_condition'] == 1
                ? '<span class="badge bg-soft-success font-size-12">Good</span>'
                : '<span class="badge bg-soft-danger font-size-12">Bad</span>',
            "availability_status" => $row['availability_status'],
            "status_label" => $row['availability_status'] == 1
                ? '<span class="badge bg-soft-success font-size-12">Available</span>'
                : '<span class="badge bg-soft-danger font-size-12">Unavailable</span>',
            "queue" => $row['queue']
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
    $newCode = 'EQ/' . $_SESSION['id'] . '/0' . ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit;
}
