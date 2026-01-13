<?php

class Service
{
    public $id;
    public $service_code;
    public $service_name;
    public $service_price;

    // Constructor: Fetch a service by ID
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `service` WHERE `id` = " . (int)$id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->service_code = $result['service_code'];
                $this->service_name = $result['service_name'];
                $this->service_price = $result['service_price'];
            }
        }
    }

    // Create a new service
    public function create()
    {
        $query = "INSERT INTO `service` (`service_code`, `service_name`, `service_price`) 
                  VALUES ('" . $this->service_code . "', '" . $this->service_name . "', '" . $this->service_price . "')";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    // Update an existing service
    public function update()
    {
        $query = "UPDATE `service` SET 
                    `service_name` = '" . $this->service_name . "',
                    `service_price` = '" . $this->service_price . "'
                  WHERE `id` = '" . $this->id . "'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        return $result ? true : false;
    }

    // Delete a service by ID
    public function delete()
    {
        $query = "DELETE FROM `service` WHERE `id` = '" . $this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Get all services
    public function all()
    {
        $query = "SELECT * FROM `service` ORDER BY `service_name` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get services by name (if you want search functionality)
    public function getByName($name)
    {
        $query = "SELECT * FROM `service` WHERE `service_name` LIKE '%" . $name . "%'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get the last inserted ID
    public function getLastID()
    {
        $query = "SELECT `id` FROM `service` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        return $result ? $result['id'] : 0;
    }
}
?>
