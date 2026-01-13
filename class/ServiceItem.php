<?php

class ServiceItem
{
    public $id;
    public $item_code;
    public $item_name;
    public $cost;
    public $selling_price;
    public $qty; 

    // Constructor: Fetch a service item by ID
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `service_item` WHERE `id` = " . (int)$id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->item_code = $result['item_code'];
                $this->item_name = $result['item_name'];
                $this->cost = $result['cost'];
                $this->selling_price = $result['selling_price']; 
                $this->qty = $result['qty'];
            }
        }
    }
    public function create()
    {
        $query = "INSERT INTO `service_item` (`item_code`, `item_name`, `cost`, `selling_price`, `qty`) 
                  VALUES ('" . $this->item_code . "', '" . $this->item_name . "', '" . $this->cost . "', '" . $this->selling_price . "', '" . $this->qty . "')";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    // Update an existing service item
    public function update()
    {
        $query = "UPDATE `service_item` SET 
                    `item_code` = '" . $this->item_code . "',
                    `item_name` = '" . $this->item_name . "',
                    `cost` = '" . $this->cost . "',
                    `selling_price` = '" . $this->selling_price . "',
                    `qty` = '" . $this->qty . "'
                  WHERE `id` = '" . $this->id . "'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    // Delete a service item by ID
    public function delete()
    {
        $query = "DELETE FROM `service_item` WHERE `id` = '" . $this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Get all service items
    public function all()
    {
        $query = "SELECT * FROM `service_item` ORDER BY `item_name` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get service items by item_code
    public function getByCode($item_code)
    {

        $query = "SELECT * FROM `service_item` WHERE `id` = '" . $item_code . "'";
        
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
        $query = "SELECT * FROM `service_item` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'];
    }
}
?>
