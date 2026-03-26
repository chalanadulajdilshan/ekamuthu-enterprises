<?php

class TripManagement
{
    public $id;
    public $trip_number;
    public $trip_category;
    public $invoice_type;
    public $bill_id;
    public $customer_id;
    public $vehicle_id;
    public $employee_id;
    public $start_location;
    public $end_location;
    public $start_meter;
    public $end_meter;
    public $trip_type;
    public $customer_fuel_cost;
    public $toll;
    public $helper_payment;
    public $transport_amount;
    public $total_cost;
    public $pay_amount;
    public $payment_method;
    public $is_settled;
    public $settlement_amount;
    public $remark;
    public $status;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `trip_management` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->trip_number = $result['trip_number'];
                $this->trip_category = $result['trip_category'];
                $this->invoice_type = $result['invoice_type'];
                $this->bill_id = $result['bill_id'];
                $this->customer_id = $result['customer_id'];
                $this->vehicle_id = $result['vehicle_id'];
                $this->employee_id = $result['employee_id'];
                $this->start_location = $result['start_location'];
                $this->end_location = $result['end_location'];
                $this->start_meter = $result['start_meter'];
                $this->end_meter = $result['end_meter'];
                $this->trip_type = $result['trip_type'];
                $this->customer_fuel_cost = $result['customer_fuel_cost'] ?? 0;
                $this->toll = $result['toll'] ?? 0;
                $this->helper_payment = $result['helper_payment'] ?? 0;
                $this->transport_amount = $result['transport_amount'] ?? 0;
                $this->total_cost = $result['total_cost'] ?? 0;
                $this->pay_amount = $result['pay_amount'] ?? 0;
                $this->payment_method = $result['payment_method'] ?? 'cash';
                $this->is_settled = $result['is_settled'] ?? 0;
                $this->settlement_amount = $result['settlement_amount'] ?? 0;
                $this->remark = $result['remark'];
                $this->status = $result['status'];
                $this->created_by = $result['created_by'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');

        // Auto-calculate total cost
        $this->total_cost = floatval($this->customer_fuel_cost) + floatval($this->toll) + 
                           floatval($this->helper_payment) + floatval($this->transport_amount) + floatval($this->pay_amount);

        $query = "INSERT INTO `trip_management` (
            `trip_number`, `trip_category`, `invoice_type`, `bill_id`, `customer_id`,
            `vehicle_id`, `employee_id`, `start_location`, `end_location`,
            `start_meter`, `end_meter`, `trip_type`,
            `customer_fuel_cost`, `toll`, `helper_payment`, `transport_amount`,
            `total_cost`, `pay_amount`, `payment_method`, `is_settled`, `settlement_amount`,
            `remark`, `status`, `created_by`, `created_at`
        ) VALUES (
            '" . addslashes($this->trip_number) . "',
            '" . addslashes($this->trip_category) . "',
            " . ($this->invoice_type ? "'" . addslashes($this->invoice_type) . "'" : "NULL") . ",
            " . ($this->bill_id ? (int) $this->bill_id : "NULL") . ",
            " . ($this->customer_id ? (int) $this->customer_id : "NULL") . ",
            " . ($this->vehicle_id ? (int) $this->vehicle_id : "NULL") . ",
            " . ($this->employee_id ? (int) $this->employee_id : "NULL") . ",
            " . ($this->start_location ? "'" . addslashes($this->start_location) . "'" : "NULL") . ",
            " . ($this->end_location ? "'" . addslashes($this->end_location) . "'" : "NULL") . ",
            '" . floatval($this->start_meter) . "',
            " . ($this->end_meter !== null && $this->end_meter !== '' ? "'" . floatval($this->end_meter) . "'" : "NULL") . ",
            " . ($this->trip_type ? "'" . addslashes($this->trip_type) . "'" : "NULL") . ",
            '" . floatval($this->customer_fuel_cost) . "',
            '" . floatval($this->toll) . "',
            '" . floatval($this->helper_payment) . "',
            '" . floatval($this->transport_amount) . "',
            '" . floatval($this->total_cost) . "',
            '" . floatval($this->pay_amount) . "',
            '" . addslashes($this->payment_method ?: 'cash') . "',
            " . (int)($this->is_settled ?? 0) . ",
            '" . floatval($this->settlement_amount ?? 0) . "',
            " . ($this->remark ? "'" . addslashes($this->remark) . "'" : "NULL") . ",
            '" . ($this->status ?: 'started') . "',
            " . ($this->created_by ? (int) $this->created_by : "NULL") . ",
            '$now'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            error_log("TripManagement create error: " . mysqli_error($db->DB_CON));
            error_log("Query: " . $query);
            return false;
        }
    }

    public function update()
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');

        // Auto-calculate total cost
        $this->total_cost = floatval($this->customer_fuel_cost) + floatval($this->toll) + 
                           floatval($this->helper_payment) + floatval($this->transport_amount) + floatval($this->pay_amount);

        $query = "UPDATE `trip_management` SET
            `trip_category` = '" . addslashes($this->trip_category) . "',
            `invoice_type` = " . ($this->invoice_type ? "'" . addslashes($this->invoice_type) . "'" : "NULL") . ",
            `bill_id` = " . ($this->bill_id ? (int) $this->bill_id : "NULL") . ",
            `customer_id` = " . ($this->customer_id ? (int) $this->customer_id : "NULL") . ",
            `vehicle_id` = " . ($this->vehicle_id ? (int) $this->vehicle_id : "NULL") . ",
            `employee_id` = " . ($this->employee_id ? (int) $this->employee_id : "NULL") . ",
            `start_location` = " . ($this->start_location ? "'" . addslashes($this->start_location) . "'" : "NULL") . ",
            `end_location` = " . ($this->end_location ? "'" . addslashes($this->end_location) . "'" : "NULL") . ",
            `start_meter` = '" . floatval($this->start_meter) . "',
            `end_meter` = " . ($this->end_meter !== null && $this->end_meter !== '' ? "'" . floatval($this->end_meter) . "'" : "NULL") . ",
            `trip_type` = " . ($this->trip_type ? "'" . addslashes($this->trip_type) . "'" : "NULL") . ",
            `customer_fuel_cost` = '" . floatval($this->customer_fuel_cost) . "',
            `toll` = '" . floatval($this->toll) . "',
            `helper_payment` = '" . floatval($this->helper_payment) . "',
            `transport_amount` = '" . floatval($this->transport_amount) . "',
            `total_cost` = '" . floatval($this->total_cost) . "',
            `pay_amount` = '" . floatval($this->pay_amount) . "',
            `payment_method` = '" . addslashes($this->payment_method ?: 'cash') . "',
            `is_settled` = " . (int)($this->is_settled ?? 0) . ",
            `settlement_amount` = '" . floatval($this->settlement_amount ?? 0) . "',
            `remark` = " . ($this->remark ? "'" . addslashes($this->remark) . "'" : "NULL") . ",
            `status` = '" . addslashes($this->status) . "',
            `updated_at` = '$now'
            WHERE `id` = " . (int) $this->id;

        $result = $db->readQuery($query);

        if (!$result) {
            error_log("TripManagement update error: " . mysqli_error($db->DB_CON));
            error_log("Query: " . $query);
        }

        return $result;
    }

    public function delete()
    {
        $query = "DELETE FROM `trip_management` WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT tm.*,
                         cm.name AS customer_name, cm.code AS customer_code,
                         v.vehicle_no, v.brand AS vehicle_brand, v.model AS vehicle_model,
                         em.name AS employee_name, em.code AS employee_code,
                         er.bill_number
                  FROM `trip_management` tm
                  LEFT JOIN `customer_master` cm ON tm.customer_id = cm.id
                  LEFT JOIN `vehicles` v ON tm.vehicle_id = v.id
                  LEFT JOIN `employee_master` em ON tm.employee_id = em.id
                  LEFT JOIN `equipment_rent` er ON tm.bill_id = er.id
                  ORDER BY tm.id DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getLastID()
    {
        $query = "SELECT `id` FROM `trip_management` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        if ($result) {
            return $result['id'];
        }
        return 0;
    }

    public static function formatTripNumber($id)
    {
        return 'TRP-' . str_pad($id, 4, '0', STR_PAD_LEFT);
    }

    public static function getByBillId($billId)
    {
        $db = Database::getInstance();
        $query = "SELECT tm.*,
                         v.vehicle_no, em.name AS employee_name
                  FROM `trip_management` tm
                  LEFT JOIN `vehicles` v ON tm.vehicle_id = v.id
                  LEFT JOIN `employee_master` em ON tm.employee_id = em.id
                  WHERE tm.bill_id = " . (int) $billId . "
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

    public static function getTotalCostByBillId($billId)
    {
        $db = Database::getInstance();
        $query = "SELECT COALESCE(SUM(total_cost), 0) AS total 
                  FROM `trip_management` 
                  WHERE `bill_id` = " . (int) $billId;
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return floatval($result['total'] ?? 0);
    }
}

?>
