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
            $query = "SELECT * FROM `trip_management` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->rent_id = $result['bill_id'];
                $this->transport_date = $result['transport_date'];
                $this->employee_id = $result['employee_id'];
                $this->vehicle_id = $result['vehicle_id'];
                $this->start_location = $result['start_location'];
                $this->end_location = $result['end_location'];
                $this->deliver_amount = $result['transport_amount']; // Mapping transport_amount to deliver_amount
                $this->pickup_amount = 0;
                $this->total_amount = $result['total_cost'] ?? 0;
                $this->remark = $result['remark'];
                $this->payment_method = $result['payment_method'] ?? 'credit';
                $this->is_settled = $result['is_settled'] ?? 0;
                $this->settlement_amount = $result['settlement_amount'] ?? 0;
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        // Redirect to TripManagement create if needed, but for now just prevent error
        return false;
    }

    public function update()
    {
        // Redirect to TripManagement update if needed
        return false;
    }

    public function delete()
    {
        $query = "DELETE FROM `trip_management` WHERE `id` = '" . (int) $this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public static function getByRentId($rent_id)
    {
        $db = Database::getInstance();
        $query = "SELECT tm.*, 
                         tm.id AS id,
                         tm.bill_id AS rent_id,
                         tm.transport_amount AS deliver_amount,
                         0 AS pickup_amount,
                         tm.total_cost AS total_amount,
                         em.name AS employee_name, em.code AS employee_code,
                         v.vehicle_no, v.brand AS vehicle_brand, v.model AS vehicle_model
                  FROM `trip_management` tm
                  LEFT JOIN `employee_master` em ON tm.employee_id = em.id
                  LEFT JOIN `vehicles` v ON tm.vehicle_id = v.id
                  WHERE tm.bill_id = " . (int) $rent_id . "
                  ORDER BY tm.id DESC";
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
        $query = "SELECT COALESCE(SUM(total_cost), 0) AS total 
                  FROM `trip_management` 
                  WHERE `bill_id` = " . (int) $rent_id;
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return floatval($result['total'] ?? 0);
    }
    
    public static function getNextId()
    {
        $db = Database::getInstance();
        $query = "SELECT MAX(id) as max_id FROM `trip_management`";
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return ($result['max_id'] ?? 0) + 1;
    }

    public static function getByDateRange($from, $to, $employee_id = null)
    {
        $db = Database::getInstance();
        $query = "SELECT tm.*, 
                         tm.id AS id,
                         tm.bill_id AS rent_id,
                         tm.transport_amount AS deliver_amount,
                         0 AS pickup_amount,
                         tm.total_cost AS total_amount,
                         em.name AS employee_name, em.code AS employee_code,
                         v.vehicle_no, v.brand AS vehicle_brand, v.model AS vehicle_model,
                         r.bill_number
                  FROM `trip_management` tm
                  LEFT JOIN `employee_master` em ON tm.employee_id = em.id
                  LEFT JOIN `vehicles` v ON tm.vehicle_id = v.id
                  LEFT JOIN `equipment_rent` r ON tm.bill_id = r.id
                  WHERE DATE(tm.transport_date) BETWEEN '" . $db->DB_CON->real_escape_string($from) . "' AND '" . $db->DB_CON->real_escape_string($to) . "'";

        if ($employee_id) {
            $query .= " AND tm.employee_id = " . (int) $employee_id;
        }

        $query .= " ORDER BY tm.transport_date ASC, tm.id ASC";
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
        return 'TRP-' . str_pad($id, 4, '0', STR_PAD_LEFT);
    }

    public static function getUnsettledCreditByRentId($rent_id)
    {
        $db = Database::getInstance();
        $query = "SELECT COALESCE(SUM(total_cost), 0) AS unsettled_amount 
                  FROM `trip_management` 
                  WHERE `bill_id` = " . (int) $rent_id . "
                  AND `payment_method` = 'credit'
                  AND `is_settled` = 0";
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return floatval($result['unsettled_amount'] ?? 0);
    }

    public function settle($settled_date, $settlement_amount, $settlement_remark = null)
    {
        // This should probably be handled by TripSettlement
        return false;
    }
}

?>
