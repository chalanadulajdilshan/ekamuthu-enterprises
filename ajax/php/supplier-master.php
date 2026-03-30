<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Get next supplier code
if (isset($_POST['get_next_code'])) {
    $SUPPLIER_MASTER = new SupplierMaster(NULL);
    $lastId = $SUPPLIER_MASTER->getLastID();
    $nextCode = 'SM/' . ($_SESSION['id'] ?? '0') . '/0' . ($lastId + 1);

    echo json_encode(["status" => "success", "code" => $nextCode]);
    exit();
}

// Create a new supplier
if (isset($_POST['create'])) {
    $db = Database::getInstance();
    
    // Check if mobile number already exists
    $mobile = $db->escapeString($_POST['mobile_number']);
    $mobileCheck = "SELECT id FROM supplier_master WHERE mobile_number = '$mobile'";
    $existingSupplier = mysqli_fetch_assoc($db->readQuery($mobileCheck));

    if ($existingSupplier) {
        echo json_encode(["status" => "duplicate", "message" => "Mobile number of this supplier already exists in the system"]);
        exit();
    }

    $SUPPLIER = new SupplierMaster(NULL);
    $SUPPLIER->code = $_POST['code'];
    $SUPPLIER->name = strtoupper($_POST['name']);
    $SUPPLIER->address = strtoupper($_POST['address'] ?? '');
    $SUPPLIER->mobile_number = $_POST['mobile_number'];
    $SUPPLIER->mobile_number_2 = $_POST['mobile_number_2'] ?? '';
    $SUPPLIER->email = $_POST['email'] ?? '';
    $SUPPLIER->contact_person = strtoupper($_POST['contact_person'] ?? '');
    $SUPPLIER->contact_person_number = $_POST['contact_person_number'] ?? '';
    $SUPPLIER->credit_limit = $_POST['credit_limit'] ?? 0;
    $SUPPLIER->outstanding = $_POST['outstanding'] ?? 0;
    $SUPPLIER->is_active = isset($_POST['is_active']) ? 1 : 0;
    $SUPPLIER->remark = $_POST['remark'] ?? '';

    $res = $SUPPLIER->create();

    if ($res) {
        echo json_encode(["status" => "success", "id" => $res]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    exit();
}

// Update supplier
if (isset($_POST['update'])) {
    $db = Database::getInstance();
    $id = $_POST['supplier_id'];
    
    // Check if mobile number already exists (excluding current supplier)
    $mobile = $db->escapeString($_POST['mobile_number']);
    $mobileCheck = "SELECT id FROM supplier_master WHERE mobile_number = '$mobile' AND id != '$id'";
    $existingSupplier = mysqli_fetch_assoc($db->readQuery($mobileCheck));

    if ($existingSupplier) {
        echo json_encode(["status" => "duplicate", "message" => "Mobile number of this supplier already exists in the system"]);
        exit();
    }

    $SUPPLIER = new SupplierMaster($id);
    $SUPPLIER->code = $_POST['code'];
    $SUPPLIER->name = strtoupper($_POST['name']);
    $SUPPLIER->address = strtoupper($_POST['address'] ?? '');
    $SUPPLIER->mobile_number = $_POST['mobile_number'];
    $SUPPLIER->mobile_number_2 = $_POST['mobile_number_2'] ?? '';
    $SUPPLIER->email = $_POST['email'] ?? '';
    $SUPPLIER->contact_person = strtoupper($_POST['contact_person'] ?? '');
    $SUPPLIER->contact_person_number = $_POST['contact_person_number'] ?? '';
    $SUPPLIER->credit_limit = $_POST['credit_limit'] ?? 0;
    $SUPPLIER->outstanding = $_POST['outstanding'] ?? 0;
    $SUPPLIER->is_active = isset($_POST['is_active']) ? 1 : 0;
    $SUPPLIER->remark = $_POST['remark'] ?? '';

    if ($SUPPLIER->update()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    exit();
}

// Delete supplier
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $SUPPLIER = new SupplierMaster($_POST['id']);
    if ($SUPPLIER->delete()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// Get suppliers for DataTable
if (isset($_POST['filter'])) {
    $SUPPLIER_MASTER = new SupplierMaster();
    $response = $SUPPLIER_MASTER->fetchForDataTable($_REQUEST);
    echo json_encode($response);
    exit();
}

// Search suppliers
if (isset($_POST['query'])) {
    $search = $_POST['query'];
    $suppliers = SupplierMaster::searchSuppliers($search);
    echo json_encode($suppliers);
    exit();
}
