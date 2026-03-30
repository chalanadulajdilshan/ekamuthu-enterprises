<?php

class SupplierInvoice
{
    public $id;
    public $grn_number;
    public $order_no;
    public $supplier_id;
    public $invoice_no;
    public $invoice_date;
    public $delivery_date;
    public $grand_total;
    public $payment_type;
    public $cheque_no;
    public $cheque_date;
    public $bank_name;
    public $branch_name;
    public $cheque_image;
    public $credit_period;
    public $status;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `supplier_invoices` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_assoc($db->readQuery($query));

            if ($result) {
                foreach ($result as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $grn_number = $db->escapeString($this->grn_number ?? '');
        $order_no = $db->escapeString($this->order_no ?? '');
        $supplier_id = (int) ($this->supplier_id ?? 0);
        $invoice_no = $db->escapeString($this->invoice_no ?? '');
        $invoice_date = $db->escapeString($this->invoice_date ?? '');
        $delivery_date = $db->escapeString($this->delivery_date ?? '');
        $grand_total = is_numeric($this->grand_total) ? $this->grand_total : 0;
        $payment_type = $db->escapeString($this->payment_type ?? 'cash');
        $cheque_no = $db->escapeString($this->cheque_no ?? '');
        $cheque_date = $db->escapeString($this->cheque_date ?? '');
        $bank_name = $db->escapeString($this->bank_name ?? '');
        $branch_name = $db->escapeString($this->branch_name ?? '');
        $cheque_image = $db->escapeString($this->cheque_image ?? '');
        $credit_period = (int) ($this->credit_period ?? 0);
        $created_by = (int) ($this->created_by ?? 0);
        $created_at = $db->escapeString($this->created_at ?? date('Y-m-d H:i:s'));

        $query = "INSERT INTO `supplier_invoices` (
            `grn_number`, `order_no`, `supplier_id`, `invoice_no`, `invoice_date`, `delivery_date`,
            `grand_total`, `payment_type`, `cheque_no`, `cheque_date`, `bank_name`,
            `branch_name`, `cheque_image`, `credit_period`, `status`, `created_by`, `created_at`
        ) VALUES (
            '{$grn_number}', '{$order_no}', '{$supplier_id}', '{$invoice_no}', '{$invoice_date}', '{$delivery_date}',
            '{$grand_total}', '{$payment_type}', '{$cheque_no}', '{$cheque_date}', '{$bank_name}',
            '{$branch_name}', '{$cheque_image}', '{$credit_period}', 0, '{$created_by}', '{$created_at}'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    public function update()
    {
        $db = Database::getInstance();
        $grn_number = $db->escapeString($this->grn_number ?? '');
        $order_no = $db->escapeString($this->order_no ?? '');
        $supplier_id = (int) ($this->supplier_id ?? 0);
        $invoice_no = $db->escapeString($this->invoice_no ?? '');
        $invoice_date = $db->escapeString($this->invoice_date ?? '');
        $delivery_date = $db->escapeString($this->delivery_date ?? '');
        $grand_total = is_numeric($this->grand_total) ? $this->grand_total : 0;
        $payment_type = $db->escapeString($this->payment_type ?? 'cash');
        $cheque_no = $db->escapeString($this->cheque_no ?? '');
        $cheque_date = $db->escapeString($this->cheque_date ?? '');
        $bank_name = $db->escapeString($this->bank_name ?? '');
        $branch_name = $db->escapeString($this->branch_name ?? '');
        $cheque_image = $db->escapeString($this->cheque_image ?? '');
        $credit_period = (int) ($this->credit_period ?? 0);

        $query = "UPDATE `supplier_invoices` SET 
            `grn_number` = '{$grn_number}',
            `order_no` = '{$order_no}',
            `supplier_id` = '{$supplier_id}',
            `invoice_no` = '{$invoice_no}',
            `invoice_date` = '{$invoice_date}',
            `delivery_date` = '{$delivery_date}',
            `grand_total` = '{$grand_total}',
            `payment_type` = '{$payment_type}',
            `cheque_no` = '{$cheque_no}',
            `cheque_date` = '{$cheque_date}',
            `bank_name` = '{$bank_name}',
            `branch_name` = '{$branch_name}',
            `cheque_image` = '{$cheque_image}',
            `credit_period` = '{$credit_period}',
            `status` = '{$this->status}',
            `updated_at` = NOW()
        WHERE `id` = '{$this->id}'";

        $result = $db->readQuery($query);
        return $result ? true : false;
    }

    public function delete()
    {
        SupplierInvoiceItem::deleteByInvoiceId($this->id);

        $query = "DELETE FROM `supplier_invoices` WHERE `id` = '{$this->id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `supplier_invoices` ORDER BY `id` DESC";
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
        $query = "SELECT * FROM `supplier_invoices` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result ? $result['id'] : 0;
    }

    public function checkGrnNumberExist($grn_no)
    {
        $db = Database::getInstance();
        $grn_no = $db->escapeString($grn_no);
        $query = "SELECT * FROM `supplier_invoices` WHERE `grn_number` = '$grn_no'";
        $result = mysqli_fetch_array($db->readQuery($query));
        return ($result) ? true : false;
    }
}
