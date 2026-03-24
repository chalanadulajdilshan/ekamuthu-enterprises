<?php

class TransportDetail
{
    public $id;
    public $rent_id;
    public $transport_date;
    public $employee_id;
    public $vehicle_id;
    public $start_location;
    public $end_location;
    public $deliver_amount;
    public $pickup_amount;
    public $total_amount;
    public $remark;
    public $payment_method;
    public $is_settled;
    public $settled_date;
    public $settlement_amount;
    public $settlement_remark;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `transport_details` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->rent_id = $result['rent_id'];
                $this->transport_date = $result['transport_date'];
                $this->employee_id = $result['employee_id'];
                $this->vehicle_id = $result['vehicle_id'];
                $this->start_location = $result['start_location'];
                $this->end_location = $result['end_location'];
                $this->deliver_amount = $result['deliver_amount'];
                $this->pickup_amount = $result['pickup_amount'];
                $this->total_amount = $result['total_amount'] ?? 0;
                $this->remark = $result['remark'];
                $this->payment_method = $result['payment_method'] ?? 'credit';
                $this->is_settled = $result['is_settled'] ?? 0;
                $this->settled_date = $result['settled_date'];
                $this->settlement_amount = $result['settlement_amount'] ?? 0;
                $this->settlement_remark = $result['settlement_remark'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        
        $this->total_amount = floatval($this->deliver_amount) + floatval($this->pickup_amount);

        $payment_method = in_array($this->payment_method, ['cash', 'credit']) ? $this->payment_method : 'credit';
        
        $query = "INSERT INTO `transport_details` (
            `rent_id`, `transport_date`, `employee_id`, `vehicle_id`, 
            `start_location`, `end_location`, `deliver_amount`, `pickup_amount`, `total_amount`,
            `payment_method`, `is_settled`, `settled_date`, `settlement_amount`, `settlement_remark`,
            `remark`, `created_at`
        ) VALUES (
            '" . (int) $this->rent_id . "',
            " . ($this->transport_date ? "'" . addslashes($this->transport_date) . "'" : "NULL") . ",
            " . ($this->employee_id ? "'" . (int) $this->employee_id . "'" : "NULL") . ",
            " . ($this->vehicle_id ? "'" . (int) $this->vehicle_id . "'" : "NULL") . ",
            " . ($this->start_location ? "'" . addslashes($this->start_location) . "'" : "NULL") . ",
            " . ($this->end_location ? "'" . addslashes($this->end_location) . "'" : "NULL") . ",
            '" . floatval($this->deliver_amount) . "',
            '" . floatval($this->pickup_amount) . "',
            '" . floatval($this->total_amount) . "',
            '" . $payment_method . "',
            " . (int)($this->is_settled ?? 0) . ",
            " . ($this->settled_date ? "'" . addslashes($this->settled_date) . "'" : "NULL") . ",
            '" . floatval($this->settlement_amount ?? 0) . "',
            " . ($this->settlement_remark ? "'" . addslashes($this->settlement_remark) . "'" : "NULL") . ",
            " . ($this->remark ? "'" . addslashes($this->remark) . "'" : "NULL") . ",
            '$now'
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
        $this->total_amount = floatval($this->deliver_amount) + floatval($this->pickup_amount);
        
        $payment_method = in_array($this->payment_method, ['cash', 'credit']) ? $this->payment_method : 'credit';
        
        $query = "UPDATE `transport_details` SET 
            `transport_date` = " . ($this->transport_date ? "'" . addslashes($this->transport_date) . "'" : "NULL") . ",
            `employee_id` = " . ($this->employee_id ? "'" . (int) $this->employee_id . "'" : "NULL") . ",
            `vehicle_id` = " . ($this->vehicle_id ? "'" . (int) $this->vehicle_id . "'" : "NULL") . ",
            `start_location` = " . ($this->start_location ? "'" . addslashes($this->start_location) . "'" : "NULL") . ",
            `end_location` = " . ($this->end_location ? "'" . addslashes($this->end_location) . "'" : "NULL") . ",
            `deliver_amount` = '" . floatval($this->deliver_amount) . "',
            `pickup_amount` = '" . floatval($this->pickup_amount) . "',
            `total_amount` = '" . floatval($this->total_amount) . "',
            `payment_method` = '" . $payment_method . "',
            `is_settled` = " . (int)($this->is_settled ?? 0) . ",
            `settled_date` = " . ($this->settled_date ? "'" . addslashes($this->settled_date) . "'" : "NULL") . ",
            `settlement_amount` = '" . floatval($this->settlement_amount ?? 0) . "',
            `settlement_remark` = " . ($this->settlement_remark ? "'" . addslashes($this->settlement_remark) . "'" : "NULL") . ",
            `remark` = " . ($this->remark ? "'" . addslashes($this->remark) . "'" : "NULL") . "
            WHERE `id` = " . (int) $this->id;

        return $db->readQuery($query);
    }

    public function delete()
    {
        $query = "DELETE FROM `transport_details` WHERE `id` = '" . (int) $this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public static function getByRentId($rent_id)
    {
        $db = Database::getInstance();
        $query = "SELECT td.*, 
                         em.name AS employee_name, em.code AS employee_code,
                         v.vehicle_no, v.brand AS vehicle_brand, v.model AS vehicle_model
                  FROM `transport_details` td
                  LEFT JOIN `employee_master` em ON td.employee_id = em.id
                  LEFT JOIN `vehicles` v ON td.vehicle_id = v.id
                  WHERE td.rent_id = " . (int) $rent_id . "
                  ORDER BY td.id DESC";
        $result = $db->readQuery($query);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public static function getTotalByRentId($rent_id)
    {
        $db = Database::getInstance();
        $query = "SELECT COALESCE(SUM(deliver_amount + pickup_amount), 0) AS total 
                  FROM `transport_details` 
                  WHERE `rent_id` = " . (int) $rent_id;
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return floatval($result['total'] ?? 0);
    }
    
    public static function getNextId()
    {
        $db = Database::getInstance();
        $query = "SELECT MAX(id) as max_id FROM `transport_details`";
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return ($result['max_id'] ?? 0) + 1;
    }

    public static function getByDateRange($from, $to, $employee_id = null)
    {
        $db = Database::getInstance();
        $query = "SELECT td.*, 
                         em.name AS employee_name, em.code AS employee_code,
                         v.vehicle_no, v.brand AS vehicle_brand, v.model AS vehicle_model,
                         r.bill_number
                  FROM `transport_details` td
                  LEFT JOIN `employee_master` em ON td.employee_id = em.id
                  LEFT JOIN `vehicles` v ON td.vehicle_id = v.id
                  LEFT JOIN `equipment_rent` r ON td.rent_id = r.id
                  WHERE DATE(td.transport_date) BETWEEN '" . $db->DB_CON->real_escape_string($from) . "' AND '" . $db->DB_CON->real_escape_string($to) . "'";

        if ($employee_id) {
            $query .= " AND td.employee_id = " . (int) $employee_id;
        }

        $query .= " ORDER BY td.transport_date ASC, td.id ASC";
        $result = $db->readQuery($query);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public static function formatId($id)
    {
        return 'TRN-' . str_pad($id, 4, '0', STR_PAD_LEFT);
    }

    public static function getUnsettledCreditByRentId($rent_id)
    {
        $db = Database::getInstance();
        $query = "SELECT COALESCE(SUM(total_amount), 0) AS unsettled_amount 
                  FROM `transport_details` 
                  WHERE `rent_id` = " . (int) $rent_id . "
                  AND `payment_method` = 'credit'
                  AND `is_settled` = 0";
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return floatval($result['unsettled_amount'] ?? 0);
    }

    public function settle($settled_date, $settlement_amount, $settlement_remark = null)
    {
        $db = Database::getInstance();
        $query = "UPDATE `transport_details` SET 
            `is_settled` = 1,
            `settled_date` = '" . addslashes($settled_date) . "',
            `settlement_amount` = '" . floatval($settlement_amount) . "',
            `settlement_remark` = " . ($settlement_remark ? "'" . addslashes($settlement_remark) . "'" : "NULL") . "
            WHERE `id` = " . (int) $this->id;
        return $db->readQuery($query);
    }
}

?>
