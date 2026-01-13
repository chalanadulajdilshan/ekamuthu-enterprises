<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

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
    $CUSTOMER->email = $_POST['email'] ?? '';
    $CUSTOMER->contact_person = strtoupper($_POST['contact_person'] ?? '');
    $CUSTOMER->contact_person_number = $_POST['contact_person_number'] ?? '';
    $CUSTOMER->credit_limit = $_POST['credit_limit'] ?? 0;
    $CUSTOMER->outstanding = $_POST['outstanding'] ?? 0;
    $CUSTOMER->old_outstanding = $_POST['old_outstanding'] ?? 0;
    $CUSTOMER->address = strtoupper($_POST['address'] ?? '');
    $CUSTOMER->remark = $_POST['remark'] ?? '';
    $CUSTOMER->vat_no = $_POST['vat_no'] ?? '';
    $CUSTOMER->category = $_POST['category'] ?? 1;
    $CUSTOMER->is_active = isset($_POST['is_active']) ? 1 : 0;
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
        echo json_encode(["status" => "success", "customer_id" => $CUSTOMER->id, "customer_code" => $CUSTOMER->code, "customer_name" => trim(($CUSTOMER->name ?? '') . ' ' . ($CUSTOMER->name_2 ?? '')), "customer_address" => $CUSTOMER->address, "customer_mobile_number" => $CUSTOMER->mobile_number]);
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
    $CUSTOMER->email = $_POST['email'];
    $CUSTOMER->contact_person = strtoupper($_POST['contact_person']);
    $CUSTOMER->contact_person_number = $_POST['contact_person_number'];
    $CUSTOMER->credit_limit = $_POST['credit_limit'];
    $CUSTOMER->vat_no = $_POST['vat_no'];
    $CUSTOMER->outstanding = $_POST['outstanding'];
    $CUSTOMER->old_outstanding = $_POST['old_outstanding'];
    $CUSTOMER->address = strtoupper($_POST['address']);
    $CUSTOMER->remark = $_POST['remark'];
    $CUSTOMER->category = $_POST['category'];
    $CUSTOMER->is_active = isset($_POST['is_active']) ? 1 : 0;

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

    // Make sure category is always an array
    $categories = isset($_POST['category']) ? (array) $_POST['category'] : [];

    // Sanitize to integers
    $categories = array_map('intval', $categories);

    $CUSTOMER_MASTER = new CustomerMaster();
    $response = $CUSTOMER_MASTER->fetchForDataTable($_REQUEST, $categories);

    // Inject raw is_vat value from customer_master for each row (batched for performance)
    if (isset($response['data']) && is_array($response['data']) && count($response['data']) > 0) {
        $ids = [];
        foreach ($response['data'] as $r) {
            if (isset($r['id'])) {
                $ids[] = (int) $r['id'];
            }
        }

        if (!empty($ids)) {
            $db = Database::getInstance();
            $idList = implode(',', $ids);

            $sql = "SELECT id, is_vat FROM customer_master WHERE id IN ($idList)";
            $res = $db->readQuery($sql);

            $vatMap = [];
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $vatMap[(int) $row['id']] = (int) $row['is_vat'];
                }
            }

            foreach ($response['data'] as &$row) {
                $id = isset($row['id']) ? (int) $row['id'] : 0;
                $row['is_vat'] = isset($vatMap[$id]) ? $vatMap[$id] : 0; // raw 0/1 value
            }
            unset($row);
        }
    }

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

// Fetch customers for DataTable
if (isset($_POST['action']) && $_POST['action'] === 'fetch_customers') {
    $db = Database::getInstance();

    // Get request parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $searchValue = isset($_POST['search']['value']) ? $db->escapeString($_POST['search']['value']) : '';
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 2; // Default sort by name
    $orderDir = isset($_POST['order'][0]['dir']) ? $db->escapeString($_POST['order'][0]['dir']) : 'asc';

    // Column mapping
    $columns = [
        0 => 'id',
        1 => 'code',
        2 => 'name',
        3 => 'mobile_number',
        4 => 'email',
        5 => 'category_name',
        6 => 'credit_limit',
        7 => 'outstanding',
        8 => 'vat_no',
        9 => 'status_label'
    ];

    $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'name';

    try {
        // Base query - Only fetch customers with category = 1
        $query = "SELECT SQL_CALC_FOUND_ROWS cm.id, cm.code, cm.name, cm.mobile_number, cm.email, 
                  cm.credit_limit, cm.outstanding, cm.is_vat, cc.name as category_name, cm.province,
                  CASE WHEN cm.is_active = 1 THEN 'Active' ELSE 'Inactive' END as status_label 
                  FROM customer_master cm
                  LEFT JOIN customer_category cc ON cm.category = cc.id
                  WHERE cm.category = 1";

        // Add search condition
        if (!empty($searchValue)) {
            $query .= " AND (cm.name LIKE '%$searchValue%' OR cm.code LIKE '%$searchValue%' OR cm.mobile_number LIKE '%$searchValue%' OR cm.email LIKE '%$searchValue%')";
        }

        // Add status filter if provided
        if (isset($_POST['status']) && $_POST['status'] === 'active') {
            $query .= " AND cm.is_active = 1";
        }

        // Add ordering
        $query .= " ORDER BY $orderBy $orderDir";

        // Add pagination
        $query .= " LIMIT $start, $length";

        // Execute query
        $result = $db->readQuery($query);
        $data = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            // Get total records
            $totalResult = $db->readQuery("SELECT FOUND_ROWS() as total");
            $totalRow = mysqli_fetch_assoc($totalResult);
            $totalRecords = $totalRow['total'];

            // Prepare response
            $response = [
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data
            ];

            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            throw new Exception('Database query failed: ' . mysqli_error($db->DB_CON));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

// Make sure to use isset() before accessing $_POST['action']
if (isset($_POST['action']) && $_POST['action'] == 'get_first_customer') {
    $CUSTOMER = new CustomerMaster(1); // Fetch customer with ID 1

    $response = [
        "status" => "success",
        "customer_id" => $CUSTOMER->id,
        "customer_name" => trim(($CUSTOMER->name ?? '') . ' ' . ($CUSTOMER->name_2 ?? '')),
        "customer_code" => $CUSTOMER->code ?? '',
        "vat_no" => $CUSTOMER->vat_no,
        "mobile_number" => $CUSTOMER->mobile_number,
        "customer_address" => $CUSTOMER->address,
        "email" => $CUSTOMER->email ?? ''
    ];

    echo json_encode($response);
    exit;
}
