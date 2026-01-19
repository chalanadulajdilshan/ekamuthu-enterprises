<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new sub equipment
if (isset($_POST['create'])) {

    // Check if code already exists
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM sub_equipment WHERE code = '{$_POST['code']}'";
    $existingSub = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingSub) {
        echo json_encode(["status" => "duplicate", "message" => "Sub equipment code already exists in the system"]);
        exit();
    }

    $SUB_EQUIPMENT = new SubEquipment(NULL);

    $SUB_EQUIPMENT->equipment_id = $_POST['equipment_id'] ?? '';
    $SUB_EQUIPMENT->code = $_POST['code'];

    $res = $SUB_EQUIPMENT->create();

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['code'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'CREATE';
    $AUDIT_LOG->description = 'CREATE SUB EQUIPMENT NO #' . $_POST['code'];
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

// Update sub equipment
if (isset($_POST['update'])) {

    // Check if code already exists (excluding current record)
    $db = Database::getInstance();
    $codeCheck = "SELECT id FROM sub_equipment WHERE code = '{$_POST['code']}' AND id != '{$_POST['sub_equipment_id']}'";
    $existingSub = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existingSub) {
        echo json_encode(["status" => "duplicate", "message" => "Sub equipment code already exists in the system"]);
        exit();
    }

    $SUB_EQUIPMENT = new SubEquipment($_POST['sub_equipment_id']);

    $SUB_EQUIPMENT->equipment_id = $_POST['equipment_id'] ?? '';
    $SUB_EQUIPMENT->code = $_POST['code'];

    $res = $SUB_EQUIPMENT->update();

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['sub_equipment_id'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE SUB EQUIPMENT NO #' . $_POST['code'];
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

// Delete sub equipment
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $SUB_EQUIPMENT = new SubEquipment($_POST['id']);

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $SUB_EQUIPMENT->code;
    $AUDIT_LOG->action = 'DELETE';
    $AUDIT_LOG->description = 'DELETE SUB EQUIPMENT NO #' . $SUB_EQUIPMENT->code;
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    $res = $SUB_EQUIPMENT->delete();

    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Filter for DataTable
if (isset($_POST['filter'])) {
    $equipment_id = isset($_POST['equipment_id']) ? $_POST['equipment_id'] : null;
    $SUB_EQUIPMENT = new SubEquipment(NULL);
    $result = $SUB_EQUIPMENT->fetchForDataTable($_REQUEST, $equipment_id);
    echo json_encode($result);
    exit;
}

// Get new code
if (isset($_POST['action']) && $_POST['action'] === 'get_new_code') {
    $SUB_EQUIPMENT = new SubEquipment(NULL);
    $lastId = $SUB_EQUIPMENT->getLastID();
    $equipment_id = $_POST['equipment_id'] ?? '0';
    $newCode = 'SE/' . $equipment_id . '/0' . ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit;
}

// Get parent equipment info
if (isset($_POST['action']) && $_POST['action'] === 'get_equipment_info') {
    $equipment_id = $_POST['equipment_id'] ?? 0;

    if ($equipment_id) {
        $EQUIPMENT = new Equipment($equipment_id);
        echo json_encode([
            "status" => "success",
            "id" => $EQUIPMENT->id,
            "code" => $EQUIPMENT->code,
            "item_name" => $EQUIPMENT->item_name
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Equipment ID required"
        ]);
    }
    exit;
}
