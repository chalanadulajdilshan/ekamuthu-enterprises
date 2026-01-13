<?php

class BrandWiseDis
{
    public $dis_id;
    public $brand_id;
    public $category_id;
    public $discount_percent_01;
    public $discount_percent_02;
    public $discount_percent_03;

    public function __construct($dis_id = null)
    {
        if ($dis_id) {
            $query = "SELECT * FROM `brand_wise_dis` WHERE `id` = " . (int) $dis_id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->dis_id = $result['id'];
                $this->brand_id = $result['brand_id'];
                $this->category_id = $result['category_id'];
                $this->discount_percent_01 = $result['discount_percent_01'];
                $this->discount_percent_02 = $result['discount_percent_02'];
                $this->discount_percent_03 = $result['discount_percent_03'];
            }
        }
    }

    // Create new record
    public function create()
    {
        $query = "INSERT INTO `brand_wise_dis` (`brand_id`, `category_id`, `discount_percent_01`, `discount_percent_02`, `discount_percent_03`) 
                  VALUES (
                    '{$this->brand_id}', 
                    '{$this->category_id}', 
                    '{$this->discount_percent_01}',
                    '{$this->discount_percent_02}',
                    '{$this->discount_percent_03}'
                  )";
        $db = Database::getInstance();
        return $db->readQuery($query) ? mysqli_insert_id($db->DB_CON) : false;
    }

    // Update existing record
    public function update()
    {
        $query = "UPDATE `brand_wise_dis` 
                  SET 
                    `brand_id` = '{$this->brand_id}', 
                    `category_id` = '{$this->category_id}', 
                    `discount_percent_01` = '{$this->discount_percent_01}',
                    `discount_percent_02` = '{$this->discount_percent_02}',
                    `discount_percent_03` = '{$this->discount_percent_03}'
                  WHERE `id` = '{$this->dis_id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Delete record
    public function delete()
    {
        $query = "DELETE FROM `brand_wise_dis` WHERE `id` = '{$this->dis_id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Get all records
    public function all()
    {
        $query = "SELECT * FROM `brand_wise_dis` ORDER BY id ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }

    // Get all records for a specific brand & category
    public function getByBrand($brand_id, $category_id)
    {
        $query = "SELECT * FROM `brand_wise_dis` 
              WHERE brand_id = " . (int)$brand_id . " 
              AND category_id = " . (int)$category_id . " 
              ORDER BY id ASC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $array[] = $row;
        }

        return $array;
    }
}
