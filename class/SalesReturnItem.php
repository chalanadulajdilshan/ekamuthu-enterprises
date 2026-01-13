<?php

class SalesReturnItem
{
    public $id;
    public $return_id;
    public $item_id;
    public $quantity;
    public $unit_price;
    public $discount;
    public $tax;
    public $net_amount;
    public $remarks;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `sales_return_items` WHERE `id` = " . (int)$id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->return_id = $result['return_id'];
                $this->item_id = $result['item_id'];
                $this->quantity = $result['quantity'];
                $this->unit_price = $result['unit_price'];
                $this->discount = $result['discount'];
                $this->tax = $result['tax'];
                $this->net_amount = $result['net_amount'];
                $this->remarks = $result['remarks'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {

        $db = Database::getInstance();

        // Escape all values to prevent SQL injection and handle special characters
        $return_id = (int)$this->return_id;
        $item_id = $db->escapeString($this->item_id);
        $quantity = $db->escapeString($this->quantity);
        $unit_price = $db->escapeString($this->unit_price);
        $discount = $db->escapeString($this->discount);
        $tax = $db->escapeString($this->tax);
        $net_amount = $db->escapeString($this->net_amount);
        $remarks = $db->escapeString($this->remarks);

        $query = "INSERT INTO `sales_return_items` (
            `return_id`, `item_id`, `quantity`, `unit_price`, `discount`, `tax`, `net_amount`, `remarks`, `created_at`
        ) VALUES (
            '$return_id', '$item_id', '$quantity', '$unit_price', '$discount', '$tax', '$net_amount', '$remarks', NOW()
        )";

        $result = mysqli_query($db->DB_CON, $query);

        if (!$result) {
            error_log("Sales Return Item Create Error: " . mysqli_error($db->DB_CON));
            return false;
        }

        if ($result) {
            $insert_id = mysqli_insert_id($db->DB_CON);
            error_log("Sales Return Item Created with ID: " . $insert_id);
            return $insert_id;
        } else {
            error_log("Sales Return Item Create Error: " . mysqli_error($db->DB_CON));
            return false;
        }
    }

    public function update()
    {
        $query = "UPDATE `sales_return_items` SET
            `return_id` = '$this->return_id',
            `item_id` = '$this->item_id',
            `quantity` = '$this->quantity',
            `unit_price` = '$this->unit_price',
            `discount` = '$this->discount',
            `tax` = '$this->tax',
            `net_amount` = '$this->net_amount',
            `remarks` = '$this->remarks'
            WHERE `id` = '$this->id'";

        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function delete()
    {
        $query = "DELETE FROM `sales_return_items` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getByReturnId($return_id)
    {
        $query = "SELECT * FROM `sales_return_items` WHERE `return_id` = '$return_id' ORDER BY `created_at` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function deleteByReturnId($return_id)
    {
        $query = "DELETE FROM `sales_return_items` WHERE `return_id` = '$return_id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `sales_return_items` ORDER BY `created_at` DESC";
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
