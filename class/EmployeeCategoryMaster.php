<?php

class EmployeeCategoryMaster
{
    public $id;
    public $code;
    public $name;
    public $remark;
    public $is_active;

    // Constructor to load employee category by ID
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `employee_category_master` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->code = $result['code'];
                $this->name = $result['name'];
                $this->remark = $result['remark'];
                $this->is_active = $result['is_active'];
            }
        }
    }

    // Create a new employee category
    public function create()
    {
        $query = "INSERT INTO `employee_category_master` (`code`, `name`, `remark`, `is_active`) 
                  VALUES ('" . $this->code . "', '" . $this->name . "', '" . $this->remark . "', '" . $this->is_active . "')";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        return $result ? mysqli_insert_id($db->DB_CON) : false;
    }

    // Update existing employee category
    public function update()
    {
        $query = "UPDATE `employee_category_master` SET 
                  `code` = '" . $this->code . "', 
                  `name` = '" . $this->name . "', 
                  `remark` = '" . $this->remark . "', 
                  `is_active` = '" . $this->is_active . "' 
                  WHERE `id` = '" . $this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Delete employee category
    public function delete()
    {
        $query = "DELETE FROM `employee_category_master` WHERE `id` = '" . $this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Fetch all employee categories
    public function all()
    {
        $query = "SELECT * FROM `employee_category_master` ORDER BY `name` ASC";
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
        $query = "SELECT * FROM `employee_category_master` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'];
    }

    public function getActiveEmployeeCategory()
    {
        $query = "SELECT * FROM `employee_category_master` WHERE `is_active` = 1 ORDER BY `id` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }
}
?>
