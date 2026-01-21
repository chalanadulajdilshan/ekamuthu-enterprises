<?php

class RepairJobItem
{
    public $id;
    public $job_id;
    public $item_name;
    public $quantity;
    public $unit_price;
    public $total_price;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `repair_job_items` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->job_id = $result['job_id'];
                $this->item_name = $result['item_name'];
                $this->quantity = $result['quantity'];
                $this->unit_price = $result['unit_price'];
                $this->total_price = $result['total_price'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `repair_job_items` (
            `job_id`, `item_name`, `quantity`, `unit_price`, `total_price`
        ) VALUES (
            " . (int) $this->job_id . ",
            '" . $db->escapeString($this->item_name) . "',
            " . (int) $this->quantity . ",
            " . floatval($this->unit_price) . ",
            " . floatval($this->total_price) . "
        )";

        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);
            return $this->id;
        }
        return false;
    }

    public function update()
    {
        $db = Database::getInstance();
        $query = "UPDATE `repair_job_items` SET
            `item_name` = '" . $db->escapeString($this->item_name) . "',
            `quantity` = " . (int) $this->quantity . ",
            `unit_price` = " . floatval($this->unit_price) . ",
            `total_price` = " . floatval($this->total_price) . "
            WHERE `id` = " . (int) $this->id;

        return $db->readQuery($query) ? true : false;
    }

    public function delete()
    {
        $query = "DELETE FROM `repair_job_items` WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getByJobId($job_id)
    {
        $query = "SELECT * FROM `repair_job_items` WHERE `job_id` = " . (int) $job_id . " ORDER BY `id` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
        return $items;
    }

    public function deleteByJobId($job_id)
    {
        $query = "DELETE FROM `repair_job_items` WHERE `job_id` = " . (int) $job_id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }
}
