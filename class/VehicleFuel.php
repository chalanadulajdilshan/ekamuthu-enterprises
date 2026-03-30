<?php

class VehicleFuel
{
    public $id;
    public $vehicle_id;
    public $fuel_amount;
    public $liters;
    public $date;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `vehicle_fuel` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->vehicle_id = $result['vehicle_id'];
                $this->fuel_amount = $result['fuel_amount'];
                $this->liters = $result['liters'];
                $this->date = $result['date'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `vehicle_fuel` (`vehicle_id`, `fuel_amount`, `liters`, `date`, `created_at`) VALUES ('" .
            mysqli_real_escape_string($db->DB_CON, $this->vehicle_id) . "', '" . 
            mysqli_real_escape_string($db->DB_CON, $this->fuel_amount) . "', '" . 
            mysqli_real_escape_string($db->DB_CON, $this->liters) . "', '" . 
            mysqli_real_escape_string($db->DB_CON, $this->date) . "', NOW())";
        
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
        $query = "UPDATE `vehicle_fuel` SET 
            `vehicle_id` = '" . mysqli_real_escape_string($db->DB_CON, $this->vehicle_id) . "',
            `fuel_amount` = '" . mysqli_real_escape_string($db->DB_CON, $this->fuel_amount) . "',
            `liters` = '" . mysqli_real_escape_string($db->DB_CON, $this->liters) . "',
            `date` = '" . mysqli_real_escape_string($db->DB_CON, $this->date) . "'
            WHERE `id` = '" . (int)$this->id . "'";

        $result = $db->readQuery($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function delete()
    {
        $query = "DELETE FROM `vehicle_fuel` WHERE `id` = '" . (int)$this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT f.*, v.vehicle_no FROM `vehicle_fuel` f JOIN `vehicles` v ON f.vehicle_id = v.id ORDER BY f.date DESC, f.id DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getFuelByVehicle($vehicle_id)
    {
        $query = "SELECT * FROM `vehicle_fuel` WHERE `vehicle_id` = " . (int)$vehicle_id . " ORDER BY date DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }
}
?>
