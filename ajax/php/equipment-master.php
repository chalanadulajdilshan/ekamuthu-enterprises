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
    $EQUIPMENT->no_sub_items = $_POST['no_sub_items'] ?? 0;
    $EQUIPMENT->change_value = $_POST['change_value'] ?? 0;
    $EQUIPMENT->remark = $_POST['remark'] ?? '';

    // Handle image upload
    if (isset($_FILES['equipment_image']) && !empty($_FILES['equipment_image']['name'])) {
        $handle = new Upload($_FILES['equipment_image']);
        if ($handle->uploaded) {
            $handle->image_resize = true;
            $handle->file_new_name_ext = 'jpg';
            $handle->image_ratio_crop = 'C';
            $handle->file_new_name_body = 'EQUIP-' . time();
            $handle->image_x = 600;
            $handle->image_y = 600;
            $handle->Process('../../uploads/equipment/');

            if ($handle->processed) {
                $EQUIPMENT->image_name = $handle->file_dst_name;
            }
        }
    }

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
        echo json_encode(["status" => "success","equipment_id" => $res]  ) ;  

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
    $EQUIPMENT->no_sub_items = $_POST['no_sub_items'] ?? 0;
    $EQUIPMENT->change_value = $_POST['change_value'] ?? 0;
    $EQUIPMENT->remark = $_POST['remark'] ?? '';
    $EQUIPMENT->image_name = $_POST['old_image_name'] ?? '';

    // Handle image upload
    if (isset($_FILES['equipment_image']) && !empty($_FILES['equipment_image']['name'])) {
        $handle = new Upload($_FILES['equipment_image']);
        if ($handle->uploaded) {
            // Delete old image if exists
            if (!empty($EQUIPMENT->image_name) && file_exists('../../uploads/equipment/' . $EQUIPMENT->image_name)) {
                unlink('../../uploads/equipment/' . $EQUIPMENT->image_name);
            }

            $handle->image_resize = true;
            $handle->file_new_name_ext = 'jpg';
            $handle->image_ratio_crop = 'C';
            $handle->file_new_name_body = 'EQUIP-' . time();
            $handle->image_x = 600;
            $handle->image_y = 600;
            $handle->Process('../../uploads/equipment/');

            if ($handle->processed) {
                $EQUIPMENT->image_name = $handle->file_dst_name;
            }
        }
    }

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
        $where .= " AND (item_name LIKE '%$search%' OR code LIKE '%$search%' OR serial_number LIKE '%$search%' OR category LIKE '%$search%' OR damage LIKE '%$search%' OR size LIKE '%$search%' OR EXISTS (SELECT 1 FROM sub_equipment se WHERE se.equipment_id = equipment.id AND se.code LIKE '%$search%'))";
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
            "quantity" => $row['quantity'],
            "no_sub_items" => $row['no_sub_items'],
            "change_value" => $row['change_value'] ?? 0,
            "image_name" => $row['image_name'],
            "remark" => $row['remark'],
            "has_sub_match" => false, 
            "search_term" => $search
        ];

        // Check for sub-equipment match if search is active
        if (!empty($search)) {
            $subMatchQuery = "SELECT 1 FROM sub_equipment WHERE equipment_id = " . (int)$row['id'] . " AND code LIKE '%$search%' LIMIT 1";
            $subMatchResult = $db->readQuery($subMatchQuery);
            if ($subMatchResult && mysqli_num_rows($subMatchResult) > 0) {
                $nestedData['has_sub_match'] = true;
            }
        }

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

// List codes for autocomplete
if (isset($_POST['action']) && $_POST['action'] === 'list_codes') {
    $db = Database::getInstance();
    $sql = "SELECT id, code, item_name FROM equipment ORDER BY id DESC";
    $result = $db->readQuery($sql);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'label' => $row['code'] . ' - ' . $row['item_name'],
            'value' => $row['code'],
            'code' => $row['code'],
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
    exit;
}

// Get equipment by code
if (isset($_POST['action']) && $_POST['action'] === 'get_by_code' && isset($_POST['code'])) {
    $db = Database::getInstance();
    $code = mysqli_real_escape_string($db->DB_CON, $_POST['code']);
    $sql = "SELECT * FROM equipment WHERE code = '$code' LIMIT 1";
    $result = $db->readQuery($sql);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        echo json_encode([
            'status' => 'success',
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Equipment not found'
        ]);
    }
    exit;
}

// Get sub-equipment by equipment_id (and availability summary for no-sub-items)
if (isset($_POST['action']) && $_POST['action'] === 'get_sub_equipment') {
    $equipment_id = isset($_POST['equipment_id']) ? (int) $_POST['equipment_id'] : 0;

    if ($equipment_id > 0) {
        $db = Database::getInstance();

        // Check if equipment has no sub items
        $equipRow = mysqli_fetch_assoc($db->readQuery("SELECT quantity, no_sub_items FROM equipment WHERE id = $equipment_id"));
        $isNoSub = ($equipRow['no_sub_items'] ?? 0) == 1;

        $subEquipments = [];
        $meta = [];

        if ($isNoSub) {
            $totalQty = (float) ($equipRow['quantity'] ?? 0);
            $rentSql = "SELECT COALESCE(SUM(quantity),0) AS rented FROM equipment_rent_items WHERE equipment_id = $equipment_id AND status = 'rented' AND (sub_equipment_id IS NULL OR sub_equipment_id = 0)";
            $rentRow = mysqli_fetch_assoc($db->readQuery($rentSql));
            $rentedQty = (float) ($rentRow['rented'] ?? 0);
            $availableQty = max(0, $totalQty - $rentedQty);

            $meta = [
                'no_sub_items' => 1,
                'available_qty' => $availableQty,
                'rented_qty' => $rentedQty,
                'total_qty' => $totalQty,
            ];
        } else {
            $sql = "SELECT 
                        se.id, 
                        se.equipment_id, 
                        se.code, 
                        se.rental_status,
                        (SELECT eri.rent_id FROM equipment_rent_items eri WHERE eri.sub_equipment_id = se.id AND eri.status = 'rented' ORDER BY eri.id DESC LIMIT 1) AS active_rent_id,
                        (SELECT er.bill_number FROM equipment_rent er WHERE er.id = (SELECT eri2.rent_id FROM equipment_rent_items eri2 WHERE eri2.sub_equipment_id = se.id AND eri2.status = 'rented' ORDER BY eri2.id DESC LIMIT 1)) AS active_bill_number,
                        (SELECT cm.name FROM equipment_rent er2 LEFT JOIN customer_master cm ON er2.customer_id = cm.id WHERE er2.id = (SELECT eri3.rent_id FROM equipment_rent_items eri3 WHERE eri3.sub_equipment_id = se.id AND eri3.status = 'rented' ORDER BY eri3.id DESC LIMIT 1)) AS active_customer_name
                    FROM sub_equipment se
                    WHERE se.equipment_id = $equipment_id";
            
            // Apply search filter to sub-equipment if provided
            if (isset($_POST['search']) && !empty($_POST['search'])) {
                $search = mysqli_real_escape_string($db->DB_CON, $_POST['search']);
                $sql .= " AND se.code LIKE '%$search%'";
            }
            
            $sql .= " ORDER BY se.id ASC";
            $result = $db->readQuery($sql);
            while ($row = mysqli_fetch_assoc($result)) {
                $subEquipments[] = [
                    'id' => $row['id'],
                    'equipment_id' => $row['equipment_id'],
                    'code' => $row['code'],
                    'rental_status' => $row['rental_status'],
                    'active_rent_id' => $row['active_rent_id'],
                    'active_bill_number' => $row['active_bill_number'],
                    'active_customer_name' => $row['active_customer_name']
                ];
            }
        }

        echo json_encode([
            "status" => "success",
            "data" => $subEquipments,
            "meta" => $meta
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
