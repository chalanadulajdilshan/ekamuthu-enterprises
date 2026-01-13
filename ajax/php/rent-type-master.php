<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new rent type
if (isset($_POST['create'])) {

    // Check if name already exists for this equipment
    $db = Database::getInstance();
    $nameCheck = "SELECT id FROM rent_type WHERE name = '{$_POST['name']}' AND equipment_id = '{$_POST['equipment_id']}'";
    $existingRentType = mysqli_fetch_assoc($db->readQuery($nameCheck));

    if ($existingRentType) {
        echo json_encode(["status" => "duplicate", "message" => "Rent type name already exists for this equipment"]);
        exit();
    }

    $RENT_TYPE = new RentType(NULL);

    $RENT_TYPE->equipment_id = $_POST['equipment_id'];
    $RENT_TYPE->name = strtoupper($_POST['name'] ?? '');
    $RENT_TYPE->price = $_POST['price'] ?? 0;
    $RENT_TYPE->deposit_amount = $_POST['deposit_amount'] ?? 0;

    $res = $RENT_TYPE->create();

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $res;
    $AUDIT_LOG->ref_code = $_POST['name'];
    $AUDIT_LOG->action = 'CREATE';
    $AUDIT_LOG->description = 'CREATE RENT TYPE #' . $_POST['name'];
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

// Update rent type
if (isset($_POST['update'])) {

    // Check if name already exists for this equipment (excluding current rent type)
    $db = Database::getInstance();
    $nameCheck = "SELECT id FROM rent_type WHERE name = '{$_POST['name']}' AND equipment_id = '{$_POST['equipment_id']}' AND id != '{$_POST['rent_type_id']}'";
    $existingRentType = mysqli_fetch_assoc($db->readQuery($nameCheck));

    if ($existingRentType) {
        echo json_encode(["status" => "duplicate", "message" => "Rent type name already exists for this equipment"]);
        exit();
    }

    $RENT_TYPE = new RentType($_POST['rent_type_id']);

    $RENT_TYPE->equipment_id = $_POST['equipment_id'];
    $RENT_TYPE->name = strtoupper($_POST['name'] ?? '');
    $RENT_TYPE->price = $_POST['price'] ?? 0;
    $RENT_TYPE->deposit_amount = $_POST['deposit_amount'] ?? 0;

    $res = $RENT_TYPE->update();

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['rent_type_id'];
    $AUDIT_LOG->ref_code = $_POST['name'];
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE RENT TYPE #' . $_POST['name'];
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

// Delete rent type
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $RENT_TYPE = new RentType($_POST['id']);

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $RENT_TYPE->name;
    $AUDIT_LOG->action = 'DELETE';
    $AUDIT_LOG->description = 'DELETE RENT TYPE #' . $RENT_TYPE->name;
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    $res = $RENT_TYPE->delete();

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
    $totalSql = "SELECT COUNT(*) as total FROM rent_type";
    $totalQuery = $db->readQuery($totalSql);
    $totalData = mysqli_fetch_assoc($totalQuery)['total'];

    // Search filter
    $where = "WHERE 1=1";
    if (!empty($search)) {
        $where .= " AND (rt.name LIKE '%$search%' OR e.item_name LIKE '%$search%' OR e.code LIKE '%$search%')";
    }

    // Filtered records
    $filteredSql = "SELECT COUNT(*) as filtered FROM rent_type rt LEFT JOIN equipment e ON rt.equipment_id = e.id $where";
    $filteredQuery = $db->readQuery($filteredSql);
    $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

    // Paginated query
    $sql = "SELECT rt.*, e.item_name as equipment_name, e.code as equipment_code 
            FROM rent_type rt 
            LEFT JOIN equipment e ON rt.equipment_id = e.id 
            $where ORDER BY rt.id DESC LIMIT $start, $length";
    $dataQuery = $db->readQuery($sql);

    $data = [];
    $key = 1;

    while ($row = mysqli_fetch_assoc($dataQuery)) {
        $nestedData = [
            "key" => $key,
            "id" => $row['id'],
            "equipment_id" => $row['equipment_id'],
            "equipment_name" => ($row['equipment_code'] ?? '') . ' - ' . ($row['equipment_name'] ?? ''),
            "name" => $row['name'],
            "price" => number_format($row['price'], 2),
            "price_raw" => $row['price'],
            "deposit_amount" => number_format($row['deposit_amount'], 2),
            "deposit_amount_raw" => $row['deposit_amount']
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
    $RENT_TYPE = new RentType(NULL);
    $lastId = $RENT_TYPE->getLastID();
    $newCode = 'RT/' . $_SESSION['id'] . '/0' . ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit;
}
