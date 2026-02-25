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

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/sub_equipment/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid('sub_eq_') . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = 'uploads/sub_equipment/' . $fileName;
            }
        }
    }

    $SUB_EQUIPMENT = new SubEquipment(NULL);

    $SUB_EQUIPMENT->equipment_id = $_POST['equipment_id'] ?? '';
    $SUB_EQUIPMENT->department_id = $_POST['department'] ?? '';
    $SUB_EQUIPMENT->code = $_POST['code'];
    $SUB_EQUIPMENT->rental_status = $_POST['rental_status'] ?? 'available';
    $SUB_EQUIPMENT->qty = $_POST['qty'] ?? 0;
    $SUB_EQUIPMENT->purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $SUB_EQUIPMENT->value = !empty($_POST['value']) ? $_POST['value'] : 0.00;
    $SUB_EQUIPMENT->image = $imagePath;
    $SUB_EQUIPMENT->brand = $_POST['brand'] ?? null;
    $SUB_EQUIPMENT->company_customer_name = $_POST['company_customer_name'] ?? null;
    $SUB_EQUIPMENT->condition_type = $_POST['condition_type'] ?? 'new';

    $res = $SUB_EQUIPMENT->create();

    // Sync parent equipment quantity with latest sub-equipment totals
    SubEquipment::syncEquipmentQuantity($SUB_EQUIPMENT->equipment_id);

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
    
    // Handle image upload
    $imagePath = $_POST['existing_image'] ?? $SUB_EQUIPMENT->image;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/sub_equipment/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Delete old image if exists
            if ($SUB_EQUIPMENT->image && file_exists('../../' . $SUB_EQUIPMENT->image)) {
                unlink('../../' . $SUB_EQUIPMENT->image);
            }
            
            $fileName = uniqid('sub_eq_') . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = 'uploads/sub_equipment/' . $fileName;
            }
        }
    }

    $SUB_EQUIPMENT->equipment_id = $_POST['equipment_id'] ?? '';
    $SUB_EQUIPMENT->department_id = $_POST['department'] ?? '';
    $SUB_EQUIPMENT->code = $_POST['code'];
    $SUB_EQUIPMENT->rental_status = $_POST['rental_status'] ?? 'available';
    $SUB_EQUIPMENT->qty = $_POST['qty'] ?? 0;
    $SUB_EQUIPMENT->purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $SUB_EQUIPMENT->value = !empty($_POST['value']) ? $_POST['value'] : 0.00;
    $SUB_EQUIPMENT->image = $imagePath;
    $SUB_EQUIPMENT->brand = $_POST['brand'] ?? null;
    $SUB_EQUIPMENT->company_customer_name = $_POST['company_customer_name'] ?? null;
    $SUB_EQUIPMENT->condition_type = $_POST['condition_type'] ?? 'new';

    $res = $SUB_EQUIPMENT->update();

    // Sync parent equipment quantity with latest sub-equipment totals
    SubEquipment::syncEquipmentQuantity($SUB_EQUIPMENT->equipment_id);

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

    // Sync parent equipment quantity with latest sub-equipment totals
    SubEquipment::syncEquipmentQuantity($SUB_EQUIPMENT->equipment_id);

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
    
    // Debug info (only if needed, but let's keep it safe for now)
    $result['debug_equipment_id'] = $equipment_id;
    
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
