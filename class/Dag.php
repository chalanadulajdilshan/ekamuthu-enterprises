<?php

class Dag
{
    public $id;
    public $ref_no;
    public $customer_id;

    public $department_id;
    public $received_date;
    public $delivery_date;
    public $customer_request_date;
    public $vehicle_no;
    public $remark;

    public $is_print;

    // Constructor: Fetch by ID
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT  * FROM `dag` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->ref_no = $result['ref_no'];
                $this->department_id = $result['department_id'];
                $this->customer_id = $result['customer_id'];

                $this->received_date = $result['received_date'];
                $this->delivery_date = $result['delivery_date'];
                $this->customer_request_date = $result['customer_request_date'];
                $this->vehicle_no = $result['vehicle_no'];
                $this->remark = $result['remark'];
                $this->is_print = $result['is_print'];
            }
        }
    }

    // Create
    public function create()
    {
        $db = Database::getInstance();
        $this->remark = mysqli_real_escape_string($db->DB_CON, $this->remark);

        $query = "INSERT INTO `dag` (
            `ref_no`, `department_id`,`customer_id`, `received_date`, `delivery_date`, `customer_request_date`,
            `vehicle_no`, `remark`
        ) VALUES (
            '{$this->ref_no}', '{$this->department_id}','{$this->customer_id}', '{$this->received_date}', '{$this->delivery_date}', '{$this->customer_request_date}',
            '{$this->vehicle_no}', '{$this->remark}'
        )";

        $result = $db->readQuery($query);
        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    // Update
    public function update()
    {
        $db = Database::getInstance();
        $this->remark = mysqli_real_escape_string($db->DB_CON, $this->remark);

        $query = "UPDATE `dag` SET 
            `ref_no` = '{$this->ref_no}',
            `department_id` = '{$this->department_id}',
            `received_date` = '{$this->received_date}',
            `delivery_date` = '{$this->delivery_date}',
            `customer_request_date` = '{$this->customer_request_date}',
            `vehicle_no` = '{$this->vehicle_no}',
            `remark` = '{$this->remark}',
            `is_print` = '{$this->is_print}'
            WHERE `id` = '{$this->id}'";

        return $db->readQuery($query);
    }

    // Delete
    public function delete()
    {
        $db = Database::getInstance();

        if (!$this->id) {
            return false;
        }

        // Remove all related dag_item rows first
        $queryDeleteItems = "DELETE FROM `dag_item` WHERE `dag_id` = '{$this->id}'";
        $db->readQuery($queryDeleteItems);

        $query = "DELETE FROM `dag` WHERE `id` = '{$this->id}'";
        return $db->readQuery($query);
    }

    // Get all
    public function all()
    {
        $query = "SELECT * FROM `dag` ORDER BY `id` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    //get by print status
    public function printStatus($status)
    {
        $query = "SELECT * FROM `dag` WHERE `is_print` =$status ORDER BY `id` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get last inserted ID
    public function getLastID()
    {
        $query = "SELECT `id` FROM `dag` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result ? $result['id'] : null;
    }


    public function getByCompany($companyId)
    {
        $query = "SELECT * FROM `dag` WHERE `dag_company_id` = {$companyId} ORDER BY `received_date` DESC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getFilteredReports($from_date, $to_date, $status = '', $dag_no = '')
    {
        $query = "SELECT 
                     d.*, 
                     c.name as customer_name,
                     dept.name as department_name,
                     dc.name as company_name,
                     b.name as belt_design,
                     di.barcode as barcode,
                     di.vehicle_no as item_vehicle_no,
                     di.qty as qty,
                     di.total_amount as total_amount
              FROM dag d
              LEFT JOIN customer_master c ON d.customer_id = c.id
              LEFT JOIN department_master dept ON d.department_id = dept.id
              LEFT JOIN dag_company dc ON d.dag_company_id = dc.id
              LEFT JOIN dag_item di ON d.id = di.dag_id
              LEFT JOIN belt_master b ON di.belt_id = b.id
              WHERE d.received_date BETWEEN '$from_date' AND '$to_date'";

        if (!empty($status)) {
            $query .= " AND d.status = '$status'";
        }
        if (!empty($dag_no)) {
            $query .= " AND d.ref_no LIKE '%$dag_no%'";
        }

        $query .= " ORDER BY d.received_date DESC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $reports = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $reports[] = $row;
        }

        return $reports;
    }

    // Search DAGs by job_number or serial_number in dag_item
    public function searchByItemFields($search_term)
    {
        $query = "SELECT DISTINCT d.*
                  FROM dag d
                  INNER JOIN dag_item di ON d.id = di.dag_id
                  WHERE di.job_number LIKE '%$search_term%' OR di.serial_number LIKE '%$search_term%'
                  ORDER BY d.id DESC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }
}
