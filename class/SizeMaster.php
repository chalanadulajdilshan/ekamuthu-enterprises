<?php

class Sizes
{
    public $id;
    public $code;
    public $name;
    public $is_active;

    // Constructor to initialize the Page object with an ID (fetch data from the DB)
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `size_master` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->code = $result['code'];
                $this->name = $result['name'];
                $this->is_active = $result['is_active'];
            }
        }
    }

    // Create a new page record in the database
    public function create()
    {
        $query = "INSERT INTO `size_master` (`code`, `name`, `is_active`) VALUES (
            '" . $this->code . "',
            '" . $this->name . "',
            '" . $this->is_active . "')";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON); // Return the ID of the newly inserted record
        } else {
            return false; // Return false if the insertion fails
        }
    }

    // Update an existing page record
    public function update()
    {
        $query = "UPDATE `size_master` SET 
            `code` = '" . $this->code . "',
            `name` = '" . $this->name . "',
            `is_active` = '" . $this->is_active . "'
            WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return true; // Return true if the update is successful
        } else {
            return false; // Return false if the update fails
        }
    }

    // Delete a page record by ID
    public function delete()
    {
        $query = "DELETE FROM `size_master` WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Retrieve all page records
    public function all()
    {
        $query = "SELECT * FROM `size_master` ORDER BY `code` ASC";
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
        $query = "SELECT * FROM `size_master` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'];
    }
    

}
?>
