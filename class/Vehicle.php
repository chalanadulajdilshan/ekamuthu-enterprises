<?php

class Vehicle
{

    public $id;
    public $ref_no;
    public $vehicle_no;
    public $brand;
    public $model;
    public $type;
    public $chassis_no;
    public $engine_no;
    public $start_meter;
    public $created_at;

    // Constructor to initialize the vehicle object with an ID (fetch data from the DB)
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT `id`, `ref_no`, `vehicle_no`, `brand`, `model`, `type`, `start_meter`, `created_at` FROM `vehicles` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->ref_no = $result['ref_no'];
                $this->vehicle_no = $result['vehicle_no'];
                $this->brand = $result['brand'];
                $this->model = $result['model'];
                $this->type = $result['type'];
                $this->start_meter = $result['start_meter'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    // Create a new vehicle record in the database
    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `vehicles` (`ref_no`, `vehicle_no`, `brand`, `model`, `type`, `start_meter`, `created_at`) VALUES ('" .
            mysqli_real_escape_string($db->DB_CON, $this->ref_no) . "', '" . 
            mysqli_real_escape_string($db->DB_CON, $this->vehicle_no) . "', '" . 
            mysqli_real_escape_string($db->DB_CON, $this->brand) . "', '" . 
            mysqli_real_escape_string($db->DB_CON, $this->model) . "', '" . 
            mysqli_real_escape_string($db->DB_CON, $this->type) . "', '" . 
            mysqli_real_escape_string($db->DB_CON, $this->start_meter) . "', NOW())";
        
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON); // Return the ID of the newly inserted record
        } else {
            return false; // Return false if the insertion fails
        }
    }

    // Update an existing vehicle record
    public function update()
    {
        $db = Database::getInstance();
        $query = "UPDATE `vehicles` SET 
            `ref_no` = '" . mysqli_real_escape_string($db->DB_CON, $this->ref_no) . "',
            `vehicle_no` = '" . mysqli_real_escape_string($db->DB_CON, $this->vehicle_no) . "',
            `brand` = '" . mysqli_real_escape_string($db->DB_CON, $this->brand) . "',
            `model` = '" . mysqli_real_escape_string($db->DB_CON, $this->model) . "',
            `type` = '" . mysqli_real_escape_string($db->DB_CON, $this->type) . "',
            `start_meter` = '" . mysqli_real_escape_string($db->DB_CON, $this->start_meter) . "'
            WHERE `id` = '" . (int)$this->id . "'";

        $result = $db->readQuery($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    // Delete a vehicle record by ID
    public function delete()
    {
        $query = "DELETE FROM `vehicles` WHERE `id` = '" . (int)$this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `vehicles` ORDER BY vehicle_no ASC";
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
        $query = "SELECT `id` FROM `vehicles` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        if ($result) {
            return $result['id'];
        } else {
            return 0;
        }
    }
}
?>
