<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(1);
ini_set('display_errors', 1);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new stock
if (isset($_POST['create'])) {

    $SUB_EQUIPMENT = new SubEquipment(NULL);

    $SUB_EQUIPMENT->equipment_id = $_POST['equipment_id'];
    $SUB_EQUIPMENT->department_id = $_POST['department_id'];
    $SUB_EQUIPMENT->qty = $_POST['qty'];
    // We don't use 'code' or 'rental_status' for simple stock tracking, or we can generate a dummy code
    // Assuming this table structure is still sub_equipment, we need a code.
    // Let's generate a unique code for this stock entry
    $lastId = $SUB_EQUIPMENT->getLastID();
    $SUB_EQUIPMENT->code = 'DS/' . $_POST['equipment_id'] . '/' . $_POST['department_id'] . '/' . ($lastId + 1);
    $SUB_EQUIPMENT->rental_status = 'available'; // Default

    // Check for duplicate
    if ($SUB_EQUIPMENT->checkDuplicate($_POST['equipment_id'], $_POST['department_id'])) {
        echo json_encode(["status" => "error", "message" => "Stock for this department already exists."]);
        exit();
    }

    $res = $SUB_EQUIPMENT->create();

    // Sync equipment quantity
    if ($res) {
        SubEquipment::syncEquipmentQuantity($_POST['equipment_id']);
    }

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $res;
    $AUDIT_LOG->ref_code = $SUB_EQUIPMENT->code;
    $AUDIT_LOG->action = 'CREATE';
    $AUDIT_LOG->description = 'ADD DEPARTMENT STOCK FOR EQ ID #' . $_POST['equipment_id'];
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

// Update stock
if (isset($_POST['update'])) {
    
    $SUB_EQUIPMENT = new SubEquipment($_POST['id']);
    
    // Keep existing values for fields not in the form
    $SUB_EQUIPMENT->department_id = $_POST['department_id'];
    $SUB_EQUIPMENT->qty = $_POST['qty'];
    
    $res = $SUB_EQUIPMENT->update();

    // Sync equipment quantity
    if ($res) {
        SubEquipment::syncEquipmentQuantity($SUB_EQUIPMENT->equipment_id);
    }

    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $SUB_EQUIPMENT->code;
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE DEPARTMENT STOCK ID #' . $_POST['id'];
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

// Delete stock
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $SUB_EQUIPMENT = new SubEquipment($_POST['id']);
    $equipment_id = $SUB_EQUIPMENT->equipment_id;
    
    $res = $SUB_EQUIPMENT->delete();
    
    // Sync equipment quantity
    if ($res) {
        SubEquipment::syncEquipmentQuantity($equipment_id);
    }
    
    // Audit log
    $AUDIT_LOG = new AuditLog(NULL);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $SUB_EQUIPMENT->code;
    $AUDIT_LOG->action = 'DELETE';
    $AUDIT_LOG->description = 'DELETE DEPARTMENT STOCK ID #' . $_POST['id'];
    $AUDIT_LOG->user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Fetch for DataTable
if (isset($_POST['fetch'])) {
    $equipment_id = isset($_POST['equipment_id']) ? $_POST['equipment_id'] : null;
    $SUB_EQUIPMENT = new SubEquipment(NULL);
    $result = $SUB_EQUIPMENT->fetchForDataTable($_REQUEST, $equipment_id);
    
    echo json_encode($result);
    exit;
}
