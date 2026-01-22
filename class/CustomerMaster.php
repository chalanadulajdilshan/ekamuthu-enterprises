<?php

class CustomerMaster
{
    public $id;
    public $code;
    public $name;
    public $address;
    public $mobile_number;
    public $mobile_number_2;
    public $old_outstanding;
    public $remark;
    public $nic;
    public $utility_bill_no;
    public $workplace_address;
    public $guarantor_name;
    public $guarantor_nic;
    public $guarantor_address;  
    
    // Document image fields
    public $nic_image_1;
    public $nic_image_2;
    public $utility_bill_image;
    public $guarantor_nic_image_1;
    public $guarantor_nic_image_2;
    public $guarantor_photo_image;
    public $customer_photo_image;
    
    // Company fields
    public $is_company;
    public $company_document;

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
                    `code`, `name`, `address`, `mobile_number`, `mobile_number_2`, `old_outstanding`, `remark`, `nic`, `utility_bill_no`,
                    `workplace_address`, `guarantor_name`, `guarantor_nic`, `guarantor_address`, `is_company`, `company_document`
                ) VALUES (
                    '{$this->code}', '{$this->name}', '{$this->address}', '{$this->mobile_number}', '{$this->mobile_number_2}', '{$this->old_outstanding}', '{$this->remark}', '{$this->nic}', '{$this->utility_bill_no}',
                    '{$this->workplace_address}', '{$this->guarantor_name}', '{$this->guarantor_nic}', '{$this->guarantor_address}', '$is_company', '{$this->company_document}'
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
        if (!empty($this->utility_bill_image)) {
            $path = $this->saveBase64ToFile($this->utility_bill_image, $uploadDir, 'utility_bill');
            if ($path) $updates[] = "`utility_bill_image` = '$path'";
        }
        if (!empty($this->guarantor_nic_image_1)) {
            $path = $this->saveBase64ToFile($this->guarantor_nic_image_1, $uploadDir, 'guarantor_nic_front');
            if ($path) $updates[] = "`guarantor_nic_image_1` = '$path'";
        }
        if (!empty($this->guarantor_nic_image_2)) {
            $path = $this->saveBase64ToFile($this->guarantor_nic_image_2, $uploadDir, 'guarantor_nic_back');
            if ($path) $updates[] = "`guarantor_nic_image_2` = '$path'";
        }
        if (!empty($this->guarantor_photo_image)) {
            $path = $this->saveBase64ToFile($this->guarantor_photo_image, $uploadDir, 'guarantor_photo');
            if ($path) $updates[] = "`guarantor_photo` = '$path'";
        }
        if (!empty($this->customer_photo_image)) {
            $path = $this->saveBase64ToFile($this->customer_photo_image, $uploadDir, 'customer_photo');
            if ($path) $updates[] = "`customer_photo` = '$path'";
        }
        if (!empty($this->company_document)) {
            $path = $this->saveBase64ToFile($this->company_document, $uploadDir, 'company_document');
            if ($path) $updates[] = "`company_document` = '$path'";
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

        $query = "INSERT INTO `customer_master` (
                    `code`, `name`, `address`, `mobile_number`, `mobile_number_2`, `old_outstanding`, `remark`
                ) VALUES (
                    '{$this->code}', '{$this->name}', '{$this->address}', '{$this->mobile_number}', '{$mobile_number_2}', '0', '0'
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
        $company_name = $this->company_name ?? ''; // Default to empty string if not set
        
        $query = "UPDATE `customer_master` SET 
                    `code` = '{$this->code}', 
                    `name` = '{$this->name}', 
                    `address` = '{$this->address}', 
                    `mobile_number` = '{$this->mobile_number}', 
                    `mobile_number_2` = '{$this->mobile_number_2}', 
                    `old_outstanding` = '{$this->old_outstanding}', 
                    `remark` = '{$this->remark}', 
                    `nic` = '{$this->nic}',
                    `utility_bill_no` = '{$this->utility_bill_no}',
                    `workplace_address` = '{$this->workplace_address}',
                    `guarantor_name` = '{$this->guarantor_name}',
                    `guarantor_nic` = '{$this->guarantor_nic}',
                    `guarantor_address` = '{$this->guarantor_address}',
                    `is_company` = '$is_company',
                    `company_name` = '{$company_name}',
                    `company_document` = '{$this->company_document}'
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

        // Add old outstanding filter if requested
        if ($oldOutstandingOnly) {
            $sql .= " AND old_outstanding > 0 ";
        }


        if (!empty($search)) {
            $sql .= " AND (name LIKE '%$search%' OR code LIKE '%$search%' OR mobile_number LIKE '%$search%') ";
        }

        $filteredQuery = $db->readQuery($sql);
        $filteredData = mysqli_num_rows($filteredQuery);

        // Add pagination
        $sql .= " LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];

        $key = 1;
        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "code" => $row['code'],
                "name" => $row['name'],
                "address" => $row['address'],
                "mobile_number" => $row['mobile_number'],
                "mobile_number_2" => $row['mobile_number_2'],
                "old_outstanding" => number_format($row['old_outstanding'] ?? 0, 2),
                "remark" => $row['remark'],
                "nic" => $row['nic'],
                "utility_bill_no" => $row['utility_bill_no'] ?? '',
                "workplace_address" => $row['workplace_address'] ?? '',
                "guarantor_name" => $row['guarantor_name'] ?? '',
                "guarantor_nic" => $row['guarantor_nic'] ?? '',
                "guarantor_address" => $row['guarantor_address'] ?? '',
                "guarantor_photo" => $row['guarantor_photo'] ?? '',
                "nic_image_1" => $row['nic_image_1'] ?? '',
                "nic_image_2" => $row['nic_image_2'] ?? '',
                "utility_bill_image" => $row['utility_bill_image'] ?? '',
                "guarantor_nic_image_1" => $row['guarantor_nic_image_1'] ?? '',
                "guarantor_nic_image_2" => $row['guarantor_nic_image_2'] ?? '',
                "customer_photo" => $row['customer_photo'] ?? '',
                "is_company" => $row['is_company'] ?? 0,
                "company_name" => $row['company_name'] ?? '', // Added company_name to fetchForDataTable
                "company_document" => $row['company_document'] ?? ''
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
                WHERE (code LIKE '%$search%' OR name LIKE '%$search%')";


        $result = $db->readQuery($query);

        $customers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['display_name'] = $row['name'] ?? '';
            $customers[] = $row;
        }

        return $customers;
    }

    public function updateCustomerOutstanding($customerId, $amount, $isCredit = false)
    {
        return true;
    }

    // Helper to sync customer_master old_outstanding with sum of unpaid invoices
    private function updateOldOutstandingBalance($customerId)
    {
        $db = Database::getInstance();
        $query = "SELECT SUM(amount) as total FROM customer_old_outstanding WHERE customer_id = $customerId AND status = 'Not Paid'";
        $result = mysqli_fetch_assoc($db->readQuery($query));
        $total = $result['total'] ?? 0;
        
        $updateQuery = "UPDATE customer_master SET old_outstanding = $total WHERE id = $customerId";
        return $db->readQuery($updateQuery);
    }

    // --- Old Outstanding Management ---

    public function addOldOutstandingDetail($customerId, $invoiceNo, $date, $amount, $status)
    {
        $db = Database::getInstance();
        $query = "INSERT INTO customer_old_outstanding (customer_id, invoice_no, date, amount, status) 
                  VALUES ('$customerId', '$invoiceNo', '$date', '$amount', '$status')";
                  
        $result = $db->readQuery($query);
        
        if($result) {
            $this->updateOldOutstandingBalance($customerId);
            return true;
        }
        return false;
    }

    public function getOldOutstandingDetails($customerId)
    {
        $db = Database::getInstance();
        // Filter out Paid invoices as requested
        $query = "SELECT * FROM customer_old_outstanding WHERE customer_id = $customerId AND status = 'Not Paid' ORDER BY date DESC";
        $result = $db->readQuery($query);
        
        $data = [];
        while($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    public function getOldOutstandingSummary($customerId)
    {
        $db = Database::getInstance();
        
        // Total
        $qTotal = "SELECT SUM(amount) as total FROM customer_old_outstanding WHERE customer_id = $customerId";
        $rTotal = mysqli_fetch_assoc($db->readQuery($qTotal));
        $total = $rTotal['total'] ?? 0;
        
        // Paid
        $qPaid = "SELECT SUM(amount) as paid FROM customer_old_outstanding WHERE customer_id = $customerId AND status = 'Paid'";
        $rPaid = mysqli_fetch_assoc($db->readQuery($qPaid));
        $paid = $rPaid['paid'] ?? 0;
        
        // Payable
        $qPayable = "SELECT SUM(amount) as payable FROM customer_old_outstanding WHERE customer_id = $customerId AND status = 'Not Paid'";
        $rPayable = mysqli_fetch_assoc($db->readQuery($qPayable));
        $payable = $rPayable['payable'] ?? 0;
        
        return [
            'total' => $total,
            'paid' => $paid,
            'payable' => $payable
        ];
    }

    public function getPendingInvoices($customerId)
    {
        $db = Database::getInstance();
        $query = "SELECT * FROM customer_old_outstanding WHERE customer_id = $customerId AND status = 'Not Paid' ORDER BY date ASC";
        $result = $db->readQuery($query);
        
        $data = [];
        while($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    public function saveOldOutstandingPayment($customerId, $invoiceId, $date, $amount, $remark)
    {
        $db = Database::getInstance();
        
        $query = "INSERT INTO `old-outstanding-collection` (`outstanding_id`, `customer_id`, `amount`, `collect-date`, `remark`) 
                  VALUES ('$invoiceId', '$customerId', '$amount', '$date', '$remark')";
        
        if($db->readQuery($query)) {
            
            // Reduce amount from customer_old_outstanding
            $qCheck = "SELECT amount FROM customer_old_outstanding WHERE id = $invoiceId";
            $rCheck = mysqli_fetch_assoc($db->readQuery($qCheck));
            $currentAmount = $rCheck['amount'];
            
            $newAmount = $currentAmount - $amount;
            $status = ($newAmount <= 0) ? 'Paid' : 'Not Paid';
            if($newAmount < 0) $newAmount = 0; 
            
            $qUpdate = "UPDATE customer_old_outstanding SET amount = $newAmount, status = '$status' WHERE id = $invoiceId";
            $db->readQuery($qUpdate);
            
            // Recalculate total balance strictly
            $this->updateOldOutstandingBalance($customerId);
            
            return true;
        }
        return false;
    }

    public function getOldOutstandingPayments($customerId)
    {
        $db = Database::getInstance();
        $query = "SELECT p.*, o.invoice_no 
                  FROM `old-outstanding-collection` p 
                  LEFT JOIN customer_old_outstanding o ON p.outstanding_id = o.id 
                  WHERE p.customer_id = $customerId 
                  ORDER BY p.`collect-date` DESC";
                  
        $result = $db->readQuery($query);
        
        $data = [];
        while($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    public function deleteOldOutstandingDetail($id)
    {
        $db = Database::getInstance();
        
        $checkQuery = "SELECT * FROM customer_old_outstanding WHERE id = $id";
        $detail = mysqli_fetch_assoc($db->readQuery($checkQuery));
        
        if($detail) {
            $deleteQuery = "DELETE FROM customer_old_outstanding WHERE id = $id";
            if($db->readQuery($deleteQuery)) {
                $this->updateOldOutstandingBalance($detail['customer_id']);
                return true;
            }
        }
        return false;
    }
}
