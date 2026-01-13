<?php

class ArnMaster
{
    public $id;
    public $arn_no;
    public $lc_tt_no;
    public $pi_no;
    public $po_date;
    public $supplier_id;
    public $ci_no;
    public $bl_no;
    public $container_size;
    public $category;
    public $brand;
    public $department;
    public $po_no;
    public $country;
    public $order_by;
    public $purchase_type;
    public $arn_status;
    public $remark;
    public $invoice_date;
    public $entry_date;
    public $delivery_date;
    public $credit_note_amount;
    public $sub_arn_value;
    public $total_discount;
    public $total_arn_value;
    public $total_received_qty;
    public $total_order_qty;
    public $created_at;
    public $is_cancelled;
    public $paid_amount;
    public $payment_type;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `arn_master` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));
            if ($result) {
                foreach ($result as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
    }


    public function getArnIdByArnNo($arn_no)
    {
        if (empty($arn_no)) {
            return false;
        }

        $db = Database::getInstance();
        $arn_no = $db->escapeString($arn_no);

        $query = "SELECT `id` FROM `arn_master` WHERE `arn_no` = '{$arn_no}' LIMIT 1";
        $result = $db->readQuery($query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return (int)$row['id'];
        }

        return false;
    }

    public function isBlNoExists($bl_no, $exclude_id = null)
    {
        if (empty($bl_no)) {
            return false;
        }

        $db = Database::getInstance();
        $bl_no = $db->escapeString($bl_no);

        $query = "SELECT `id` FROM `arn_master` WHERE `bl_no` = '{$bl_no}'";
        if ($exclude_id) {
            $query .= " AND `id` != '{$exclude_id}'";
        }
        $query .= " LIMIT 1";

        $result = $db->readQuery($query);

        return ($result && mysqli_num_rows($result) > 0);
    }

    public function create()
    {
        // Check if bl_no already exists
        if ($this->isBlNoExists($this->bl_no)) {
            return false; // Duplicate bl_no
        }

        $query = "INSERT INTO `arn_master` (
            `arn_no`, `lc_tt_no`, `pi_no`, `po_date`, `supplier_id`, `ci_no`, `bl_no`,
            `container_size`, `category`, `brand`, `department`, `po_no`, `country`, `order_by`,
            `purchase_type`, `arn_status`, `remark`, `invoice_date`, `entry_date`, `delivery_date`,
            `credit_note_amount`, `sub_arn_value`, `total_discount`, `total_arn_value`, `paid_amount`,
            `total_received_qty`, `total_order_qty`, `created_at`
        ) VALUES (
            '{$this->arn_no}', '{$this->lc_tt_no}', '{$this->pi_no}', '{$this->po_date}', '{$this->supplier_id}',
            '{$this->ci_no}', '{$this->bl_no}', '{$this->container_size}', '{$this->category}', '{$this->brand}',
            '{$this->department}', '{$this->po_no}', '{$this->country}', '{$this->order_by}', '{$this->payment_type}',
            '{$this->arn_status}', '{$this->remark}', '{$this->invoice_date}', '{$this->entry_date}', '{$this->delivery_date}',
            '{$this->credit_note_amount}', '{$this->sub_arn_value}', '{$this->total_discount}', '{$this->total_arn_value}',
            '{$this->paid_amount}', '{$this->total_received_qty}', '{$this->total_order_qty}', NOW()
        )";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        return $result ? mysqli_insert_id($db->DB_CON) : false;
    }

    public function update()
    {
        // Check if bl_no is being changed and if it already exists
        if ($this->isBlNoExists($this->bl_no, $this->id)) {
            return false; // Duplicate bl_no
        }

        $query = "UPDATE `arn_master` SET
            `arn_no` = '{$this->arn_no}',
            `lc_tt_no` = '{$this->lc_tt_no}',
            `pi_no` = '{$this->pi_no}',
            `po_date` = '{$this->po_date}',
            `supplier_id` = '{$this->supplier_id}',
            `ci_no` = '{$this->ci_no}',
            `bl_no` = '{$this->bl_no}',
            `container_size` = '{$this->container_size}',
            `category` = '{$this->category}',
            `brand` = '{$this->brand}',
            `department` = '{$this->department}',
            `po_no` = '{$this->po_no}',
            `country` = '{$this->country}',
            `order_by` = '{$this->order_by}',
            `purchase_type` = '{$this->purchase_type}',
            `arn_status` = '{$this->arn_status}',
            `remark` = '{$this->remark}',
            `invoice_date` = '{$this->invoice_date}',
            `entry_date` = '{$this->entry_date}',
            `delivery_date` = '{$this->delivery_date}',
            `credit_note_amount` = '{$this->credit_note_amount}',
            `sub_arn_value` = '{$this->sub_arn_value}',
            `total_discount` = '{$this->total_discount}',
            `total_arn_value` = '{$this->total_arn_value}',
            `paid_amount` = '{$this->paid_amount}',
            `total_received_qty` = '{$this->total_received_qty}',
            `total_order_qty` = '{$this->total_order_qty}'
        WHERE `id` = '{$this->id}'";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        return $result ? $this->__construct($this->id) : false;
    }

    public function delete()
    {
        $query = "DELETE FROM `arn_master` WHERE `id` = '{$this->id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `arn_master` ORDER BY `id` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = [];

        while ($row = mysqli_fetch_array($result)) {
            $array_res[] = $row;
        }

        return $array_res;
    }

    public function getByArnId($arn_id)
    {
        $query = "
        SELECT ai.*,im.*, im.code AS item_code, im.name AS item_name, im.id AS item_id
        FROM arn_items ai
        LEFT JOIN item_master im ON ai.item_code = im.id
        WHERE ai.arn_id = '{$arn_id}'
        ORDER BY ai.id ASC
    ";


        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = [];

        while ($row = mysqli_fetch_assoc($result)) {

            $ARN = new ArnMaster($row['arn_id']);
            $row['brand'] = $ARN->brand;
            $row['category'] = $ARN->category;
            $array_res[] = $row;
        }

        return $array_res;
    }



    public function getLastID()
    {
        $query = "SELECT * FROM `arn_master` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result ? $result['id'] : null;
    }

    public function cancelArn($arn_id)
    {
        $db = Database::getInstance();

        // Fetch ARN details to get department
        $arn = new ArnMaster($arn_id);
        $department_id = $arn->department;

        // Mark as cancelled in related tables
        $db->readQuery("UPDATE arn_master SET is_cancelled = 1 WHERE id = '{$arn_id}'");
        $db->readQuery("UPDATE arn_items SET is_cancelled = 1 WHERE arn_id = '{$arn_id}'");

        // For every department, deduct quantities from stock_master that are tied to this ARN via stock_item_tmp, then zero those tmp rows
        $tmpQuery = "SELECT department_id, item_id, SUM(qty) AS qty_sum FROM stock_item_tmp WHERE arn_id = '" . (int)$arn_id . "' GROUP BY department_id, item_id";
        $tmpRes = $db->readQuery($tmpQuery);
        while ($row = mysqli_fetch_assoc($tmpRes)) {
            $deptId = (int)$row['department_id'];
            $itemId = (int)$row['item_id'];
            $qtySum = (float)$row['qty_sum'];
            if ($qtySum > 0) {
                $STOCK_MASTER = new StockMaster();
                $STOCK_MASTER->adjustQuantity($itemId, $deptId, $qtySum, 'deductions', 'ARN cancellation adjustment');
            }
        }

        // Zero out the tmp quantities for this ARN so they won’t appear in listings
        $db->readQuery("UPDATE stock_item_tmp SET qty = 0 WHERE arn_id = '" . (int)$arn_id . "'");

        return true;
    }


    public function reactivateArn($arn_id)
    {
        $db = Database::getInstance();

        $query1 = "UPDATE arn_master SET is_cancelled = 0 WHERE id = '{$arn_id}'";
        $db->readQuery($query1);

        $query2 = "UPDATE arn_items SET is_cancelled = 0 WHERE arn_id = '{$arn_id}'";
        $db->readQuery($query2);

        $query3 = "UPDATE stock_item_tmp SET is_cancelled = 0 WHERE arn_id = '{$arn_id}'";
        $db->readQuery($query3);

        // Also reactivate stock transactions as needed
        // Assuming you track with arn_id in remark or separate field
        $query4 = "UPDATE stock_transaction SET is_cancelled = 0 WHERE remark LIKE '%ARN #{$arn_id}%'";
        $db->readQuery($query4);

        return true;
    }

    public function updateInvoiceOutstanding($invoice_id, $amount)
    {
        $query = "UPDATE `arn_master` SET `paid_amount` = `paid_amount` + $amount WHERE `id` = $invoice_id";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        return ($result) ? true : false;
    }

    public function getCreditInvoicesByCustomerAndStatus($status, $customer_id)
    {

        $query = "SELECT * FROM `arn_master` 
                 WHERE `supplier_id` = $customer_id 
                 AND purchase_type = $status
                 AND `total_arn_value` > `paid_amount`      
                 AND `is_cancelled`='0'
                 ORDER BY `invoice_date` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            $array_res[] = $row;
        }

        return $array_res;
    }
}
