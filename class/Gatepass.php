<?php

class Gatepass
{
    public $id;
    public $gatepass_code;
    public $invoice_id;
    public $name;
    public $address;
    public $id_number;
    public $equipment_type;
    public $serial_no;
    public $issued_by;
    public $gatepass_date;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `gatepass` WHERE `id` = " . (int)$id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->gatepass_code = $result['gatepass_code'];
                $this->invoice_id = $result['invoice_id'];
                $this->name = $result['name'];
                $this->address = $result['address'];
                $this->id_number = $result['id_number'];
                $this->equipment_type = $result['equipment_type'];
                $this->serial_no = $result['serial_no'];
                $this->issued_by = $result['issued_by'];
                $this->gatepass_date = $result['gatepass_date'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `gatepass` (
            `gatepass_code`, `invoice_id`, `name`, `address`, `id_number`, `equipment_type`, `serial_no`, `issued_by`, `gatepass_date`
        ) VALUES (
            '" . $db->escapeString($this->gatepass_code) . "', 
            '" . $db->escapeString($this->invoice_id) . "', 
            '" . $db->escapeString($this->name) . "', 
            '" . $db->escapeString($this->address) . "', 
            '" . $db->escapeString($this->id_number) . "', 
            '" . $db->escapeString($this->equipment_type) . "', 
            '" . $db->escapeString($this->serial_no) . "', 
            '" . $db->escapeString($this->issued_by) . "', 
            '" . $db->escapeString($this->gatepass_date) . "'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    public function update()
    {
        $db = Database::getInstance();
        $query = "UPDATE `gatepass` SET 
            `gatepass_code` = '" . $db->escapeString($this->gatepass_code) . "', 
            `invoice_id` = '" . $db->escapeString($this->invoice_id) . "', 
            `name` = '" . $db->escapeString($this->name) . "', 
            `address` = '" . $db->escapeString($this->address) . "', 
            `id_number` = '" . $db->escapeString($this->id_number) . "', 
            `equipment_type` = '" . $db->escapeString($this->equipment_type) . "', 
            `serial_no` = '" . $db->escapeString($this->serial_no) . "', 
            `issued_by` = '" . $db->escapeString($this->issued_by) . "', 
            `gatepass_date` = '" . $db->escapeString($this->gatepass_date) . "'
            WHERE `id` = '" . (int)$this->id . "'";

        $result = $db->readQuery($query);

        if ($result) {
            return $this->__construct($this->id);
        } else {
            return false;
        }
    }

    public function delete()
    {
        $query = "DELETE FROM `gatepass` WHERE `id` = '" . (int)$this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `gatepass` ORDER BY `id` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getByInvoiceId($invoice_id)
    {
        $query = "SELECT * FROM `gatepass` WHERE `invoice_id` = '" . (int)$invoice_id . "' ORDER BY `id` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function filter($searchTerm = '')
    {
        $db = Database::getInstance();
        $query = "SELECT g.*, er.bill_number 
                  FROM `gatepass` g 
                  LEFT JOIN `equipment_rent` er ON g.invoice_id = er.id 
                  WHERE 1";

        if (!empty($searchTerm)) {
            $search = $db->escapeString($searchTerm);
            $query .= " AND (g.gatepass_code LIKE '%$search%' OR g.name LIKE '%$search%' OR er.bill_number LIKE '%$search%' OR g.invoice_id LIKE '%$search%')";
        }

        $query .= " ORDER BY g.id DESC";
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getItems()
    {
        $GATEPASS_ITEM = new GatepassItem(null);
        return $GATEPASS_ITEM->getByGatepassId($this->id);
    }
}
