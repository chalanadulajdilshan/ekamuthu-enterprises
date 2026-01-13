<?php

class SalesReturn
{
    public $id;
    public $return_no;
    public $return_date;
    public $invoice_no;
    public $invoice_id;
    public $customer_id;
    public $total_amount;
    public $return_reason;
    public $remarks;
    public $is_damaged;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `sales_return` WHERE `id` = " . (int)$id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->return_no = $result['return_no'];
                $this->return_date = $result['return_date'];
                $this->invoice_no = $result['invoice_no'];
                $this->invoice_id = $result['invoice_id'];
                $this->customer_id = $result['customer_id'];
                $this->total_amount = $result['total_amount'];
                $this->return_reason = $result['return_reason'];
                $this->remarks = $result['remarks'];
                $this->is_damaged = $result['is_damaged'];
                $this->created_by = $result['created_by'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();

        // Escape all values to prevent SQL injection and handle special characters
        $return_no = $db->escapeString($this->return_no);
        $return_date = $db->escapeString($this->return_date);
        $invoice_no = $db->escapeString($this->invoice_no);
        $invoice_id = (int)$this->invoice_id;
        $customer_id = (int)$this->customer_id;
        $total_amount = $db->escapeString($this->total_amount);
        $return_reason = $db->escapeString($this->return_reason);
        $remarks = $db->escapeString($this->remarks);
        $is_damaged = (int)$this->is_damaged;
        $created_by = (int)$this->created_by;

        $query = "INSERT INTO `sales_return` (
            `return_no`, `return_date`, `invoice_no`, `invoice_id`, `customer_id`, `total_amount`, `return_reason`, `remarks`, `is_damaged`, `created_by`, `created_at`, `updated_at`
        ) VALUES (
            '$return_no', '$return_date', '$invoice_no', '$invoice_id', '$customer_id', '$total_amount', '$return_reason', '$remarks', '$is_damaged', '$created_by', NOW(), NOW()
        )";

        $result = mysqli_query($db->DB_CON, $query);

        if (!$result) {
            error_log("Sales Return Create Error: " . mysqli_error($db->DB_CON));
            return false;
        }

        if ($result) {
            $insert_id = mysqli_insert_id($db->DB_CON);
            // Update the invoice's is_return flag
            $invoice = new SalesInvoice($this->invoice_id);
            $invoice->updateIsReturnFlag();
            return $insert_id;
        } else {
            return false;
        }
    }

    public function update()
    {
        $query = "UPDATE `sales_return` SET
            `return_no` = '$this->return_no',
            `return_date` = '$this->return_date',
            `invoice_no` = '$this->invoice_no',
            `invoice_id` = '$this->invoice_id',
            `customer_id` = '$this->customer_id',
            `total_amount` = '$this->total_amount',
            `return_reason` = '$this->return_reason',
            `remarks` = '$this->remarks',
            `is_damaged` = '$this->is_damaged',
            `created_by` = '$this->created_by',
            `updated_at` = NOW()
            WHERE `id` = '$this->id'";

        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function delete()
    {
        // Get invoice_id before deleting
        $invoice_id = $this->invoice_id;
        
        $query = "DELETE FROM `sales_return` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        
        if ($result) {
            // Update the invoice's is_return flag after deletion
            $invoice = new SalesInvoice($invoice_id);
            $invoice->updateIsReturnFlag();
        }
        
        return $result;
    }

    public function all()
    {
        $query = "SELECT * FROM `sales_return` ORDER BY `created_at` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getByCustomerId($customer_id)
    {
        $query = "SELECT * FROM `sales_return` WHERE `customer_id` = '$customer_id' ORDER BY `created_at` DESC";
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
        $query = "SELECT `id` FROM `sales_return` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'] ?? null;
    }

    public function checkReturnNoExist($return_no, $exclude_id = null)
    {
        $query = "SELECT COUNT(*) as count FROM `sales_return` WHERE `return_no` = '$return_no'";
        if ($exclude_id) {
            $query .= " AND `id` != '$exclude_id'";
        }
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['count'] > 0;
    }

    public function getTotalReturnsByDateRange($from_date, $to_date)
    {
        $query = "SELECT SUM(sri.net_amount) as total_returns FROM `sales_return` sr 
                  LEFT JOIN `sales_return_items` sri ON sr.id = sri.return_id 
                  WHERE sr.return_date BETWEEN '$from_date' AND '$to_date'";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return (float) ($result['total_returns'] ?? 0);
    }
}

?>
