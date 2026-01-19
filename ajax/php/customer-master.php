<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Add Old Outstanding Detail
if (isset($_POST['action']) && $_POST['action'] == 'add_old_outstanding_detail') {
    
    $customerId = $_POST['detail_customer_id'] ?? 0;
    $invoiceNo = $_POST['detail_invoice_no'] ?? '';
    $date = $_POST['detail_date'] ?? '';
    $amount = $_POST['detail_amount'] ?? 0;
    $status = $_POST['detail_status'] ?? 'Not Paid';
    
    if(!$customerId || !$invoiceNo || !$date || !$amount) {
        echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
        exit;
    }
    
    $db = Database::getInstance();
    
    $query = "INSERT INTO customer_old_outstanding (customer_id, invoice_no, date, amount, status) 
              VALUES ('$customerId', '$invoiceNo', '$date', '$amount', '$status')";
              
    $result = $db->readQuery($query);
    
    if($result) {
        // If status is Not Paid, update customer master total
        if($status === 'Not Paid') {
            $updateQuery = "UPDATE customer_master SET old_outstanding = old_outstanding + $amount WHERE id = $customerId";
            $db->readQuery($updateQuery);
        }
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    
    exit;
}


// Get Old Outstanding Details
if (isset($_POST['action']) && $_POST['action'] == 'get_old_outstanding_details') {
    $customerId = $_POST['customer_id'] ?? 0;
    
    if(!$customerId) {
        echo json_encode([]);
        exit;
    }
    
    $db = Database::getInstance();
    $query = "SELECT * FROM customer_old_outstanding WHERE customer_id = $customerId ORDER BY date DESC";
    $result = $db->readQuery($query);
    
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    echo json_encode($data);
    exit;
}

// Delete Old Outstanding Detail
if (isset($_POST['action']) && $_POST['action'] == 'delete_old_outstanding_detail') {
    $id = $_POST['id'] ?? 0;
    
    if(!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // First get the detail to check status and amount
    $checkQuery = "SELECT * FROM customer_old_outstanding WHERE id = $id";
    $detail = mysqli_fetch_assoc($db->readQuery($checkQuery));
    
    if($detail) {
        $deleteQuery = "DELETE FROM customer_old_outstanding WHERE id = $id";
        if($db->readQuery($deleteQuery)) {
            
            // If it was Not Paid, deduct from total
            if($detail['status'] === 'Not Paid') {
                $customerId = $detail['customer_id'];
                $amount = $detail['amount'];
                $updateQuery = "UPDATE customer_master SET old_outstanding = old_outstanding - $amount WHERE id = $customerId";
                $db->readQuery($updateQuery);
            }
            
            echo json_encode(['status' => 'success']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Record not found']);
    }
    exit;
}

// Create a new customer
if (isset($_POST['create'])) {

    // Check if mobile number already exists (check both mobile fields)
    $db = Database::getInstance();
    $conditions = [];

    // Check primary mobile number
    $conditions[] = "mobile_number = '{$_POST['mobile_number']}'";
    $conditions[] = "mobile_number_2 = '{$_POST['mobile_number']}'";

    // Check secondary mobile number if provided
    if (!empty($_POST['mobile_number_2'])) {
        $conditions[] = "mobile_number = '{$_POST['mobile_number_2']}'";
        $conditions[] = "mobile_number_2 = '{$_POST['mobile_number_2']}'";
    }

    $conditionString = implode(' OR ', $conditions);
    $mobileCheck = "SELECT id FROM customer_master WHERE ($conditionString)";
    $existingCustomer = mysqli_fetch_assoc($db->readQuery($mobileCheck));

    if ($existingCustomer) {
        echo json_encode(["status" => "duplicate", "message" => "Mobile number of this customer/supplier is already exist in the system"]);
        exit();
    }

    $CUSTOMER = new CustomerMaster(NULL); // New customer object

    $CUSTOMER->code = $_POST['code'];
    $CUSTOMER->name = strtoupper($_POST['name']);
    $CUSTOMER->mobile_number = $_POST['mobile_number'];
    $CUSTOMER->mobile_number_2 = $_POST['mobile_number_2'] ?? '';
    $CUSTOMER->old_outstanding = $_POST['old_outstanding'] ?? 0;
    $CUSTOMER->address = strtoupper($_POST['address'] ?? '');
    $CUSTOMER->remark = $_POST['remark'] ?? '';
    $CUSTOMER->nic = $_POST['nic'] ?? '';
    $CUSTOMER->utility_bill_no = $_POST['water_bill_no'] ?? $_POST['utility_bill_no'] ?? '';
    $CUSTOMER->workplace_address = strtoupper($_POST['workplace_address'] ?? '');
    $CUSTOMER->guarantor_name = strtoupper($_POST['guarantor_name'] ?? '');
    $CUSTOMER->guarantor_nic = $_POST['guarantor_nic'] ?? '';
    $CUSTOMER->guarantor_address = strtoupper($_POST['guarantor_address'] ?? '');
    
    // Document image fields
    $CUSTOMER->nic_image_1 = $_POST['nic_image_1'] ?? '';
    $CUSTOMER->nic_image_2 = $_POST['nic_image_2'] ?? '';
    $CUSTOMER->utility_bill_image = $_POST['water_bill_image_1'] ?? $_POST['utility_bill_image_1'] ?? '';
    $CUSTOMER->guarantor_nic_image_1 = $_POST['guarantor_nic_image_1'] ?? '';
    $CUSTOMER->guarantor_nic_image_2 = $_POST['guarantor_nic_image_2'] ?? '';
    $CUSTOMER->guarantor_photo_image = $_POST['guarantor_photo_image_1'] ?? '';
    
    // Company fields
    $CUSTOMER->is_company = isset($_POST['is_company']) ? 1 : 0;
    $CUSTOMER->company_document = $_POST['po_document_image_1'] ?? $_POST['company_document_image_1'] ?? '';
    
    $res = $CUSTOMER->create();

    //audit log
    $AUDIT_LOG = new AuditLog(NUll);
    $AUDIT_LOG->ref_id = $_POST['code'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'CREATE';
    $AUDIT_LOG->description = 'CREATE CUSTOMER NO #' . $_POST['code'];
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

if (isset($_POST['create-invoice-customer'])) {

    // Check if mobile number already exists (check both mobile fields)
    $db = Database::getInstance();
    $conditions = [];

    // Check primary mobile number
    $conditions[] = "mobile_number = '{$_POST['mobile_number']}'";
    $conditions[] = "mobile_number_2 = '{$_POST['mobile_number']}'";

    // Check secondary mobile number if provided
    if (!empty($_POST['mobile_number_2'])) {
        $conditions[] = "mobile_number = '{$_POST['mobile_number_2']}'";
        $conditions[] = "mobile_number_2 = '{$_POST['mobile_number_2']}'";
    }

    $conditionString = implode(' OR ', $conditions);
    $mobileCheck = "SELECT id FROM customer_master WHERE ($conditionString)";
    $existingCustomer = mysqli_fetch_assoc($db->readQuery($mobileCheck));

    if ($existingCustomer) {
        echo json_encode(["status" => "duplicate", "message" => "Mobile number of this customer/supplier is already exist in the system"]);
        exit();
    }

    $CUSTOMER = new CustomerMaster(NULL); // New customer object

    $CUSTOMER->code = $_POST['code'];
    $CUSTOMER->name = strtoupper($_POST['name']);
    $CUSTOMER->mobile_number = $_POST['mobile_number'];
    $CUSTOMER->address = strtoupper($_POST['address']);
    $res = $CUSTOMER->createInvoiceCustomer();

    //audit log
    $AUDIT_LOG = new AuditLog(NUll);
    $AUDIT_LOG->ref_id = $res;
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'CREATE';
    $AUDIT_LOG->description = 'CREATE CUSTOMER NO #' . $_POST['code'];
    $AUDIT_LOG->user_id = $_SESSION['id'];
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();


    if ($res) {

        $CUSTOMER = new CustomerMaster($res);
        echo json_encode(["status" => "success", "customer_id" => $CUSTOMER->id, "customer_code" => $CUSTOMER->code, "customer_name" => $CUSTOMER->name, "customer_address" => $CUSTOMER->address, "customer_mobile_number" => $CUSTOMER->mobile_number]);
        exit();
    } else {
        echo json_encode(["status" => "error"]);
        exit();
    }
}

// Update customer
if (isset($_POST['update'])) {

    // Check if mobile numbers already exist (excluding current customer, check both mobile fields)
    $db = Database::getInstance();
    $conditions = [];

    // Check primary mobile number
    $conditions[] = "mobile_number = '{$_POST['mobile_number']}'";
    $conditions[] = "mobile_number_2 = '{$_POST['mobile_number']}'";

    // Check secondary mobile number if provided
    if (!empty($_POST['mobile_number_2'])) {
        $conditions[] = "mobile_number = '{$_POST['mobile_number_2']}'";
        $conditions[] = "mobile_number_2 = '{$_POST['mobile_number_2']}'";
    }

    $conditionString = implode(' OR ', $conditions);
    $mobileCheck = "SELECT id FROM customer_master WHERE ($conditionString) AND id != '{$_POST['customer_id']}'";
    $existingCustomer = mysqli_fetch_assoc($db->readQuery($mobileCheck));

    if ($existingCustomer) {
        echo json_encode(["status" => "duplicate", "message" => "Mobile number of this customer/supplier is already exist in the system"]);
        exit();
    }

    $CUSTOMER = new CustomerMaster($_POST['customer_id']); // Load customer by ID

    $CUSTOMER->code = $_POST['code'];
    $CUSTOMER->name = strtoupper($_POST['name']);
    $CUSTOMER->mobile_number = $_POST['mobile_number'];
    $CUSTOMER->mobile_number_2 = $_POST['mobile_number_2'];
    $CUSTOMER->old_outstanding = $_POST['old_outstanding'];
    $CUSTOMER->address = strtoupper($_POST['address']);
    $CUSTOMER->remark = $_POST['remark'];
    $CUSTOMER->nic = $_POST['nic'] ?? '';
    $CUSTOMER->utility_bill_no = $_POST['water_bill_no'] ?? $_POST['utility_bill_no'] ?? '';
    $CUSTOMER->workplace_address = strtoupper($_POST['workplace_address'] ?? '');
    $CUSTOMER->guarantor_name = strtoupper($_POST['guarantor_name'] ?? '');
    $CUSTOMER->guarantor_nic = $_POST['guarantor_nic'] ?? '';
    $CUSTOMER->guarantor_address = strtoupper($_POST['guarantor_address'] ?? '');
    
    // Document image fields - only update if new data is provided
    if (!empty($_POST['nic_image_1'])) {
        $CUSTOMER->nic_image_1 = $_POST['nic_image_1'];
    }
    if (!empty($_POST['nic_image_2'])) {
        $CUSTOMER->nic_image_2 = $_POST['nic_image_2'];
    }
    if (!empty($_POST['water_bill_image_1']) || !empty($_POST['utility_bill_image_1'])) {
        $CUSTOMER->utility_bill_image = $_POST['water_bill_image_1'] ?? $_POST['utility_bill_image_1'];
    }
    if (!empty($_POST['guarantor_nic_image_1'])) {
        $CUSTOMER->guarantor_nic_image_1 = $_POST['guarantor_nic_image_1'];
    }
    if (!empty($_POST['guarantor_nic_image_2'])) {
        $CUSTOMER->guarantor_nic_image_2 = $_POST['guarantor_nic_image_2'];
    }
    if (!empty($_POST['guarantor_photo_image_1'])) {
        $CUSTOMER->guarantor_photo_image = $_POST['guarantor_photo_image_1'];
    }
    
    // Company fields
    $CUSTOMER->is_company = isset($_POST['is_company']) ? 1 : 0;
    if (!empty($_POST['po_document_image_1']) || !empty($_POST['company_document_image_1'])) {
        $CUSTOMER->company_document = $_POST['po_document_image_1'] ?? $_POST['company_document_image_1'];
    }

    $res = $CUSTOMER->update();

    //audit log
    $AUDIT_LOG = new AuditLog(NUll);
    $AUDIT_LOG->ref_id = $_POST['customer_id'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE CUSTOMER NO #' . $_POST['code'];
    $AUDIT_LOG->user_id = $_SESSION['id'];
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

// Delete customer
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $CUSTOMER = new CustomerMaster($_POST['id']);
    $res = $CUSTOMER->delete();

    //audit log
    $AUDIT_LOG = new AuditLog(NUll);
    $AUDIT_LOG->ref_id = $_POST['id'];
    $AUDIT_LOG->ref_code = $CUSTOMER->code;
    $AUDIT_LOG->action = 'DELETE';
    $AUDIT_LOG->description = 'DELETE CUSTOMER NO #' . $CUSTOMER->code;
    $AUDIT_LOG->user_id = $_SESSION['id'];
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit; // Add exit here to prevent further execution
}

if (isset($_POST['filter'])) {

    $CUSTOMER_MASTER = new CustomerMaster();
    $response = $CUSTOMER_MASTER->fetchForDataTable($_REQUEST, []);



    echo json_encode($response);
    exit;
}


// search by customer
if (isset($_POST['query'])) {
    $search = $_POST['query'];

    $CUSTOMER_MASTER = new CustomerMaster();
    $customers = $CUSTOMER_MASTER->searchCustomers($search);

    if ($customers) {
        echo json_encode($customers);  // Return the customers as a JSON string
    } else {
        echo json_encode([]);  // Return an empty array if no customers are found
    }
    exit;
}



// Make sure to use isset() before accessing $_POST['action']
if (isset($_POST['action']) && $_POST['action'] == 'get_first_customer') {
    $CUSTOMER = new CustomerMaster(1); // Fetch customer with ID 1

    $response = [
        "status" => "success",
        "customer_id" => $CUSTOMER->id,
        "customer_name" => $CUSTOMER->name,
        "customer_code" => $CUSTOMER->code ?? '',
        "mobile_number" => $CUSTOMER->mobile_number,
        "customer_address" => $CUSTOMER->address
    ];

    echo json_encode($response);
    exit;
}
