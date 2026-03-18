<?php

class VehicleRepair
{
    public $id;
    public $ref_no;
    public $vehicle_id;
    public $repair_date;
    public $amount;
    public $remark;
    public $created_by;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $db = Database::getInstance();
            $id = (int)$id;
            $query = "SELECT id, ref_no, vehicle_id, repair_date, amount, remark, created_by, created_at FROM vehicle_repairs WHERE id = {$id}";
            $result = mysqli_fetch_array($db->readQuery($query));
            if ($result) {
                $this->id = $result['id'];
                $this->ref_no = $result['ref_no'];
                $this->vehicle_id = $result['vehicle_id'];
                $this->repair_date = $result['repair_date'];
                $this->amount = $result['amount'];
                $this->remark = $result['remark'];
                $this->created_by = $result['created_by'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();

        $ref_no = mysqli_real_escape_string($db->DB_CON, $this->ref_no);
        $vehicle_id = (int)$this->vehicle_id;
        $repair_date = mysqli_real_escape_string($db->DB_CON, $this->repair_date);
        $amount = (float)$this->amount;
        $remark = mysqli_real_escape_string($db->DB_CON, $this->remark);
        $created_by = (int)$this->created_by;

        $query = "INSERT INTO vehicle_repairs (ref_no, vehicle_id, repair_date, amount, remark, created_by, created_at) VALUES (" .
            "'{$ref_no}', " .
            "{$vehicle_id}, " .
            "'{$repair_date}', " .
            "{$amount}, " .
            "'{$remark}', " .
            "{$created_by}, NOW())";

        $result = $db->readQuery($query);
        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        }
        return false;
    }

    public function all()
    {
        $db = Database::getInstance();
        $query = "SELECT vr.*, v.vehicle_no, v.ref_no AS vehicle_ref_no FROM vehicle_repairs vr INNER JOIN vehicles v ON vr.vehicle_id = v.id ORDER BY vr.id DESC";
        $result = $db->readQuery($query);
        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            $array_res[] = $row;
        }
        return $array_res;
    }

    public function getLastID()
    {
        $db = Database::getInstance();
        $query = "SELECT id FROM vehicle_repairs ORDER BY id DESC LIMIT 1";
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result ? (int)$result['id'] : 0;
    }

    public function update()
    {
        if (!$this->id) {
            return false;
        }
        $db = Database::getInstance();
        $id = (int)$this->id;
        $ref_no = mysqli_real_escape_string($db->DB_CON, $this->ref_no);
        $vehicle_id = (int)$this->vehicle_id;
        $repair_date = mysqli_real_escape_string($db->DB_CON, $this->repair_date);
        $amount = (float)$this->amount;
        $remark = mysqli_real_escape_string($db->DB_CON, $this->remark);

        $query = "UPDATE vehicle_repairs SET " .
            "ref_no='{$ref_no}', " .
            "vehicle_id={$vehicle_id}, " .
            "repair_date='{$repair_date}', " .
            "amount={$amount}, " .
            "remark='{$remark}' " .
            "WHERE id={$id}";

        return $db->readQuery($query) ? true : false;
    }

    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $db = Database::getInstance();
        $id = (int)$this->id;
        $query = "DELETE FROM vehicle_repairs WHERE id={$id}";
        return $db->readQuery($query) ? true : false;
    }

    public function getById($id)
    {
        $db = Database::getInstance();
        $id = (int)$id;
        $query = "SELECT vr.*, v.vehicle_no, v.ref_no AS vehicle_ref_no FROM vehicle_repairs vr INNER JOIN vehicles v ON vr.vehicle_id = v.id WHERE vr.id={$id} LIMIT 1";
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result ?: null;
    }
}

?>
