<?php

class SupplierInvoiceItem
{
    public $id;
    public $supplier_invoice_id;
    public $item_id;
    public $item_code;
    public $item_name;
    public $unit;
    public $quantity;
    public $rate;
    public $discount_percentage;
    public $amount;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `supplier_invoice_items` WHERE `id` = " . (int) $id;
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
        $query = "INSERT INTO `supplier_invoice_items` 
                  (`supplier_invoice_id`, `item_id`, `item_code`, `item_name`, `unit`, `quantity`, `rate`, `discount_percentage`, `amount`) 
                  VALUES 
                  ('" . (int) $this->supplier_invoice_id . "', '" . (int) $this->item_id . "', 
                   '" . $db->escapeString($this->item_code ?? '') . "', '" . $db->escapeString($this->item_name ?? '') . "',
                   '" . $db->escapeString($this->unit ?? '') . "', '" . floatval($this->quantity) . "', 
                   '" . floatval($this->rate) . "', '" . floatval($this->discount_percentage) . "', 
                   '" . floatval($this->amount) . "')";

        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    public function delete()
    {
        $query = "DELETE FROM `supplier_invoice_items` WHERE `id` = '" . (int) $this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public static function deleteByInvoiceId($invoiceId)
    {
        $query = "DELETE FROM `supplier_invoice_items` WHERE `supplier_invoice_id` = '" . (int) $invoiceId . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public static function getByInvoiceId($invoiceId)
    {
        $query = "SELECT * FROM `supplier_invoice_items` WHERE `supplier_invoice_id` = '" . (int) $invoiceId . "'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }
}
