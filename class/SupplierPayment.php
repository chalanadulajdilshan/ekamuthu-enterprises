<?php

class SupplierPayment
{
    public $id;
    public $arn_id;
    public $supplier_id;
    public $payment_type_id;
    public $amount;
    public $cheq_no;
    public $bank_id;
    public $branch_id;
    public $cheq_date;
    public $is_settle;

    // Constructor to initialize the SupplierPayment object with an ID (fetch data from DB)
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT `id`, `arn_id`, `supplier_id`, `payment_type_id`, `amount`, 
                             `cheq_no`, `bank_id`, `branch_id`, `cheq_date`, `is_settle`
                      FROM `supplier_payment` 
                      WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->arn_id = $result['arn_id'];
                $this->supplier_id = $result['supplier_id'];
                $this->payment_type_id = $result['payment_type_id'];
                $this->amount = $result['amount'];
                $this->cheq_no = $result['cheq_no'];
                $this->bank_id = $result['bank_id'];
                $this->branch_id = $result['branch_id'];
                $this->cheq_date = $result['cheq_date'];
                $this->is_settle = $result['is_settle'];
            }
        }
    }

    // Create a new supplier payment record
    public function create()
    {
        $query = "INSERT INTO `supplier_payment` 
                  (`arn_id`, `supplier_id`, `payment_type_id`, `amount`, `cheq_no`, `bank_id`, `branch_id`, `cheq_date`, `is_settle`) 
                  VALUES (
                      '" . $this->arn_id . "', 
                      '" . $this->supplier_id . "', 
                      '" . $this->payment_type_id . "', 
                      '" . $this->amount . "', 
                      '" . $this->cheq_no . "', 
                      '" . $this->bank_id . "', 
                      '" . $this->branch_id . "', 
                      '" . $this->cheq_date . "', 
                      '" . $this->is_settle . "')";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON); // Return the ID of the newly inserted record
        } else {
            return false;
        }
    }

    // Update an existing supplier payment record
    public function update()
    {
        $query = "UPDATE `supplier_payment` SET 
                    `arn_id` = '" . $this->arn_id . "',
                    `supplier_id` = '" . $this->supplier_id . "',
                    `payment_type_id` = '" . $this->payment_type_id . "',
                    `amount` = '" . $this->amount . "',
                    `cheq_no` = '" . $this->cheq_no . "',
                    `bank_id` = '" . $this->bank_id . "',
                    `branch_id` = '" . $this->branch_id . "',
                    `cheq_date` = '" . $this->cheq_date . "',
                    `is_settle` = '" . $this->is_settle . "'
                  WHERE `id` = '" . $this->id . "'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        return $result ? true : false;
    }

    // Delete a supplier payment record by ID
    public function delete()
    {
        $query = "DELETE FROM `supplier_payment` WHERE `id` = '" . $this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Get all supplier payments
    public function all()
    {
        $query = "SELECT `id`, `arn_id`, `supplier_id`, `payment_type_id`, `amount`, `cheq_no`, `bank_id`, `branch_id`, `cheq_date`, `is_settle`
                  FROM `supplier_payment` 
                  ORDER BY `id` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get all supplier payments for a specific invoice
    public function getByInvoiceId($arn_id)
    {
        $query = "SELECT * FROM `supplier_payment` 
                  WHERE `arn_id` = '" . (int) $arn_id . "' 
                  ORDER BY `id` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get supplier payments by settlement status
    public function getBySettlementStatus($status)
    {
        $query = "SELECT * FROM `supplier_payment` 
                  WHERE `is_settle` = '" . (int) $status . "' 
                  ORDER BY `id` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }
}
