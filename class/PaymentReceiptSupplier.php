<?php

class PaymentReceiptSupplier
{
    public $id;
    public $receipt_no;
    public $customer_id;
    public $entry_date;
    public $amount_paid;
    public $remark;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT `id`, `receipt_no`, `customer_id`, `entry_date`, `amount_paid`, `remark`, `created_at`
                      FROM `payment_receipt_supplier`
                      WHERE `id` = " . (int) $id;

            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->receipt_no = $result['receipt_no'];
                $this->customer_id = $result['customer_id'];
                $this->entry_date = $result['entry_date'];
                $this->amount_paid = $result['amount_paid'];
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `payment_receipt_supplier` (`receipt_no`, `customer_id`, `entry_date`, `amount_paid`, `remark`, `created_at`) 
                  VALUES (
                    '{$this->receipt_no}', 
                    '{$this->customer_id}', 
                    '{$this->entry_date}', 
                    '{$this->amount_paid}', 
                    '{$this->remark}', 
                    NOW()
                  )";

        $db = Database::getInstance();
        return $db->readQuery($query) ? mysqli_insert_id($db->DB_CON) : false;
    }

    public function update()
    {
        $query = "UPDATE `payment_receipt_supplier` 
                  SET 
                    `receipt_no` = '{$this->receipt_no}', 
                    `customer_id` = '{$this->customer_id}', 
                    `entry_date` = '{$this->entry_date}', 
                    `amount_paid` = '{$this->amount_paid}', 
                    `remark` = '{$this->remark}'
                  WHERE `id` = '{$this->id}'";

        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function delete()
    {
        $query = "DELETE FROM `payment_receipt_supplier` WHERE `id` = '{$this->id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT `id`, `receipt_no`, `customer_id`, `entry_date`, `amount_paid`, `remark`, `created_at`
                  FROM `payment_receipt_supplier`
                  ORDER BY `entry_date` DESC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }

    public function getByCustomer($customerId)
    {
        $query = "SELECT `id`, `receipt_no`, `customer_id`, `entry_date`, `amount_paid`, `remark`, `created_at`
                  FROM `payment_receipt_supplier`
                  WHERE `customer_id` = '" . (int)$customerId . "'
                  ORDER BY `entry_date` DESC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }
    
}
