<?php

class CustomerMaster
{
    public $id;
    public $code;
    public $name;
    public $address;
    public $mobile_number;
    public $mobile_number_2;
    public $email;
    public $contact_person;
    public $contact_person_number;
    public $credit_limit;
    public $outstanding;
    public $vat_no;
    public $old_outstanding;
    public $category;
    public $remark;
    public $is_active;
    public $nic;
    public $water_bill_no;
    public $electricity_bill_no;
    public $workplace_address;
    public $guarantor_name;
    public $guarantor_nic;
    public $guarantor_address;
    
    // Document image fields
    public $nic_image_1;
    public $nic_image_2;
    public $water_bill_image;
    public $electricity_bill_image;
    public $guarantor_nic_image_1;
    public $guarantor_nic_image_2;
    
    // Company fields
    public $is_company;
    public $po_document;
    public $letterhead_document;

    // Constructor
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `customer_master` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                foreach ($result as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
    }

    // Create new customer
    public function create()
    {
        $db = Database::getInstance();
        $is_company = $this->is_company ?? 0;
        
        // First insert without images to get the customer ID
        $query = "INSERT INTO `customer_master` (
                    `code`, `name`, `address`, `mobile_number`, `mobile_number_2`, `email`, 
                    `contact_person`, `contact_person_number`, `credit_limit`, `outstanding`, `old_outstanding`, `category`, `remark`, `is_active`, `vat_no`, `nic`, `water_bill_no`, `electricity_bill_no`,
                    `workplace_address`, `guarantor_name`, `guarantor_nic`, `guarantor_address`, `is_company`
                ) VALUES (
                    '{$this->code}', '{$this->name}', '{$this->address}', '{$this->mobile_number}', '{$this->mobile_number_2}', '{$this->email}',
                    '{$this->contact_person}', '{$this->contact_person_number}', '{$this->credit_limit}', '{$this->outstanding}', '{$this->old_outstanding}', '{$this->category}', '{$this->remark}', '{$this->is_active}', '{$this->vat_no}', '{$this->nic}', '{$this->water_bill_no}', '{$this->electricity_bill_no}',
                    '{$this->workplace_address}', '{$this->guarantor_name}', '{$this->guarantor_nic}', '{$this->guarantor_address}', '$is_company'
                )";
        $result = $db->readQuery($query);

        if ($result) {
            $customerId = mysqli_insert_id($db->DB_CON);
            
            // Save images to files and update paths
            $this->saveCustomerImages($customerId);
            
            return $customerId;
        } else {
            return false;
        }
    }
    
    // Save base64 images to files and update database with paths
    private function saveCustomerImages($customerId)
    {
        $db = Database::getInstance();
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ekamuthu-enterprises/uploads/customers/' . $customerId . '/';
        
        // Create customer directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $updates = [];
        
        // Save each image if it exists
        if (!empty($this->nic_image_1)) {
            $path = $this->saveBase64ToFile($this->nic_image_1, $uploadDir, 'nic_front');
            if ($path) $updates[] = "`nic_image_1` = '$path'";
        }
        if (!empty($this->nic_image_2)) {
            $path = $this->saveBase64ToFile($this->nic_image_2, $uploadDir, 'nic_back');
            if ($path) $updates[] = "`nic_image_2` = '$path'";
        }
        if (!empty($this->water_bill_image)) {
            $path = $this->saveBase64ToFile($this->water_bill_image, $uploadDir, 'water_bill');
            if ($path) $updates[] = "`water_bill_image` = '$path'";
        }
        if (!empty($this->electricity_bill_image)) {
            $path = $this->saveBase64ToFile($this->electricity_bill_image, $uploadDir, 'electricity_bill');
            if ($path) $updates[] = "`electricity_bill_image` = '$path'";
        }
        if (!empty($this->guarantor_nic_image_1)) {
            $path = $this->saveBase64ToFile($this->guarantor_nic_image_1, $uploadDir, 'guarantor_nic_front');
            if ($path) $updates[] = "`guarantor_nic_image_1` = '$path'";
        }
        if (!empty($this->guarantor_nic_image_2)) {
            $path = $this->saveBase64ToFile($this->guarantor_nic_image_2, $uploadDir, 'guarantor_nic_back');
            if ($path) $updates[] = "`guarantor_nic_image_2` = '$path'";
        }
        if (!empty($this->po_document)) {
            $path = $this->saveBase64ToFile($this->po_document, $uploadDir, 'po_document');
            if ($path) $updates[] = "`po_document` = '$path'";
        }
        if (!empty($this->letterhead_document)) {
            $path = $this->saveBase64ToFile($this->letterhead_document, $uploadDir, 'letterhead');
            if ($path) $updates[] = "`letterhead_document` = '$path'";
        }
        
        // Update database with file paths
        if (!empty($updates)) {
            $updateQuery = "UPDATE `customer_master` SET " . implode(', ', $updates) . " WHERE `id` = '$customerId'";
            $db->readQuery($updateQuery);
        }
    }
    
    // Convert base64 to file and return relative path
    private function saveBase64ToFile($base64Data, $uploadDir, $filename)
    {
        if (empty($base64Data) || strpos($base64Data, 'data:') !== 0) {
            return null;
        }
        
        // Extract MIME type and data
        preg_match('/data:([^;]+);base64,/', $base64Data, $matches);
        $mimeType = $matches[1] ?? 'image/jpeg';
        
        // Determine file extension
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf'
        ];
        $ext = $extensions[$mimeType] ?? 'jpg';
        
        // Remove data URL prefix
        $base64Data = preg_replace('/data:[^;]+;base64,/', '', $base64Data);
        $binaryData = base64_decode($base64Data);
        
        if ($binaryData === false) {
            return null;
        }
        
        // Generate unique filename
        $fullFilename = $filename . '_' . time() . '.' . $ext;
        $fullPath = $uploadDir . $fullFilename;
        
        // Save file
        if (file_put_contents($fullPath, $binaryData)) {
            // Return relative path for database
            $customerId = basename(dirname($fullPath));
            return 'uploads/customers/' . $customerId . '/' . $fullFilename;
        }
        
        return null;
    }
    public function createInvoiceCustomer()
    {
        $mobile_number_2 = $this->mobile_number_2 ? $this->mobile_number_2 : '0';
        $email = $this->email ? $this->email : '0';
        $contact_person = $this->contact_person ? $this->contact_person : '0';
        $contact_person_number = $this->contact_person_number ? $this->contact_person_number : '0';

        $query = "INSERT INTO `customer_master` (
                    `code`, `name`, `address`, `mobile_number`, `mobile_number_2`, `email`, 
                    `contact_person`, `contact_person_number`, `credit_limit`, `outstanding`, `old_outstanding`, `category`, `remark`, `is_active`
                ) VALUES (
                    '{$this->code}', '{$this->name}', '{$this->address}', '{$this->mobile_number}', '{$mobile_number_2}', '{$email}',
                    '{$contact_person}', '{$contact_person_number}', '0', '0', '0', '1', '0', '1'
                )";


        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    // Update existing customer
    public function update()
    {
        $db = Database::getInstance();
        $is_company = $this->is_company ?? 0;
        
        $query = "UPDATE `customer_master` SET 
                    `code` = '{$this->code}', 
                    `name` = '{$this->name}', 
                    `address` = '{$this->address}', 
                    `mobile_number` = '{$this->mobile_number}', 
                    `mobile_number_2` = '{$this->mobile_number_2}', 
                    `email` = '{$this->email}', 
                    `contact_person` = '{$this->contact_person}', 
                    `contact_person_number` = '{$this->contact_person_number}', 
                    `credit_limit` = '{$this->credit_limit}', 
                    `outstanding` = '{$this->outstanding}', 
                    `old_outstanding` = '{$this->old_outstanding}', 
                    `category` = '{$this->category}', 
                    `remark` = '{$this->remark}', 
                    `is_active` = '{$this->is_active}', 
                    `vat_no` = '{$this->vat_no}',
                    `nic` = '{$this->nic}',
                    `water_bill_no` = '{$this->water_bill_no}',
                    `electricity_bill_no` = '{$this->electricity_bill_no}',
                    `workplace_address` = '{$this->workplace_address}',
                    `guarantor_name` = '{$this->guarantor_name}',
                    `guarantor_nic` = '{$this->guarantor_nic}',
                    `guarantor_address` = '{$this->guarantor_address}',
                    `is_company` = '$is_company'
                WHERE `id` = '{$this->id}'";

        $result = $db->readQuery($query);
        
        // Save new images if provided
        if ($result) {
            $this->saveCustomerImages($this->id);
        }

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    // Delete customer
    public function delete()
    {
        $query = "DELETE FROM `customer_master` WHERE `id` = '{$this->id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Get all customers
    public function all()
    {
        $query = "SELECT * FROM `customer_master` ORDER BY name ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getLastID()
    {
        $query = "SELECT * FROM `customer_master` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'];
    }

    public function fetchForDataTable($request, $category)
    {
        $db = Database::getInstance();

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 100;
        $search = $request['search']['value'];
        $oldOutstandingOnly = isset($request['old_outstanding_only']) && $request['old_outstanding_only'] === 'true';

        // Total records
        $totalSql = "SELECT * FROM customer_master";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_num_rows($totalQuery);

        // Search filter
        $sql = "SELECT * FROM customer_master WHERE id != 1 ";

        if (!empty($category)) {
            if (is_array($category)) {
                // Sanitize values to integers
                $category = array_map('intval', $category);
                $categoryList = implode(',', $category);
                $sql .= " AND category IN ($categoryList)   ";
            } else {
                // Single category value
                $category = intval($category); // sanitize
                $sql .= " AND category = $category    ";
            }
        }

        // Add old outstanding filter if requested
        if ($oldOutstandingOnly) {
            $sql .= " AND old_outstanding > 0 ";
        }


        if (!empty($search)) {
            $sql .= "AND  name LIKE '%$search%' OR code LIKE '%$search%' OR mobile_number LIKE '%$search%'  and is_active !=0";
        }

        $filteredQuery = $db->readQuery($sql);
        $filteredData = mysqli_num_rows($filteredQuery);

        // Add pagination
        $sql .= " LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];

        $key = 1;
        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $CATEGORY = new CustomerCategory($row['category']);
            $PROVINCE = new Province($row['province']);
            $DISTRICT = new District($row['district']);

            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "code" => $row['code'],
                "name" => $row['name'], // First name
                "name_2" => $row['name_2'], // Last name
                "display_name" => trim(($row['name'] ?? '') . ' ' . ($row['name_2'] ?? '')), // Combined name for display
                "address" => $row['address'],
                "mobile_number" => $row['mobile_number'],
                "mobile_number_2" => $row['mobile_number_2'],
                "email" => $row['email'],
                "contact_person" => $row['contact_person'],
                "contact_person_number" => $row['contact_person_number'],
                "credit_limit" => number_format($row['credit_limit'], 2),
                "outstanding" => number_format($row['outstanding'], 2),
                "old_outstanding" => number_format($row['old_outstanding'], 2),
                "category_id" => $row['category'],
                "category" => $CATEGORY->name,
                "remark" => $row['remark'],
                "status" => $row['is_active'],
                "status_label" => $row['is_active'] == 1
                    ? '<span class="badge bg-soft-success font-size-12">Active</span>'
                    : '<span class="badge bg-soft-danger font-size-12">Inactive</span>',
                "province" => $PROVINCE->name,
                "district" => $DISTRICT->name,
                "vat_no" => $row['vat_no'],
                "nic" => $row['nic'],
                "water_bill_no" => $row['water_bill_no'],
                "electricity_bill_no" => $row['electricity_bill_no'],
                "workplace_address" => $row['workplace_address'] ?? '',
                "guarantor_name" => $row['guarantor_name'] ?? '',
                "guarantor_nic" => $row['guarantor_nic'] ?? '',
                "guarantor_address" => $row['guarantor_address'] ?? '',
                "nic_image_1" => $row['nic_image_1'] ?? '',
                "nic_image_2" => $row['nic_image_2'] ?? '',
                "water_bill_image" => $row['water_bill_image'] ?? '',
                "electricity_bill_image" => $row['electricity_bill_image'] ?? '',
                "guarantor_nic_image_1" => $row['guarantor_nic_image_1'] ?? '',
                "guarantor_nic_image_2" => $row['guarantor_nic_image_2'] ?? '',
                "is_company" => $row['is_company'] ?? 0,
                "po_document" => $row['po_document'] ?? '',
                "letterhead_document" => $row['letterhead_document'] ?? ''
            ];

            $data[] = $nestedData;
            $key++;
        }

        return [
            "draw" => intval($request['draw']),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($filteredData),
            "data" => $data
        ];
    }


    public static function searchCustomers($search)
    {
        $db = Database::getInstance();
        $query = "SELECT *
                FROM customer_master 
                WHERE (code LIKE '%$search%' OR name LIKE '%$search%') 
                AND is_active = 1 ";


        $result = $db->readQuery($query);

        $customers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Combine name and name_2 for display
            $row['display_name'] = trim(($row['name'] ?? '') . ' ' . ($row['name_2'] ?? ''));
            $customers[] = $row;
        }

        return $customers;
    }

    // Update customer outstanding balance
    public function updateCustomerOutstanding($customerId, $amount, $isCredit = false)
    {
        $db = Database::getInstance();

        // Determine whether to add or subtract the amount based on credit/debit
        $operator = $isCredit ? '+' : '-';

        $query = "UPDATE `customer_master` 
                 SET `outstanding` = GREATEST(0, `outstanding` $operator $amount)
                 WHERE `id` = '{$customerId}'";

        $result = $db->readQuery($query);

        return $result ? true : false;
    }
}
