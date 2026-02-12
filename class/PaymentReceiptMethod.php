<?php

class PaymentReceiptMethod
{
    public $id;
    public $receipt_id;
    public $invoice_id;
    public $payment_type_id;
    public $amount;
    public $payment_date;
    public $cheq_no;
    public $ref_no;
    public $bank_id;
    public $branch_id;
    public $cheq_date;
    public $transfer_date;
    public $is_settle;
    public $account_no;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT `id`, `receipt_id`, `invoice_id`, `payment_type_id`, `amount`, `payment_date`,
                             `cheq_no`, `ref_no`, `bank_id`, `branch_id`, `cheq_date`, `transfer_date`, `is_settle`, `account_no`
                      FROM `payment_receipt_method`
                      WHERE `id` = " . (int)$id;

            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->receipt_id = $result['receipt_id'];
                $this->invoice_id = $result['invoice_id'];
                $this->payment_type_id = $result['payment_type_id'];
                $this->amount = $result['amount'];
                $this->payment_date = $result['payment_date'];
                $this->cheq_no = $result['cheq_no'];
                $this->ref_no = $result['ref_no'];
                $this->bank_id = $result['bank_id'];
                $this->branch_id = $result['branch_id'];
                $this->cheq_date = $result['cheq_date'];
                $this->transfer_date = $result['transfer_date'];
                $this->is_settle = $result['is_settle'];
                $this->account_no = $result['account_no'];
            }
        }
    }

    public function getByReceiptId($receipt_id) {
        $query = "SELECT `id`, `receipt_id`, `invoice_id`, `payment_type_id`, `amount`, 
                        `cheq_no`, `bank_id`, `branch_id`, `cheq_date`, `is_settle`
                 FROM `payment_receipt_method` 
                 WHERE `receipt_id` = " . (int)$receipt_id;

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $methods = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $methods[] = $row;
        }

        return $methods;
    }

    public function create()
    {
        $cheq_date = empty($this->cheq_date) ? "NULL" : "'{$this->cheq_date}'";
        $transfer_date = empty($this->transfer_date) ? "NULL" : "'{$this->transfer_date}'";

        $query = "INSERT INTO `payment_receipt_method` 
                    (`receipt_id`, `invoice_id`, `payment_type_id`, `amount`, `payment_date`, `cheq_no`, `ref_no`, `bank_id`, `branch_id`, `cheq_date`, `transfer_date`, `is_settle`, `account_no`) 
                  VALUES (
                    '{$this->receipt_id}',
                    '{$this->invoice_id}',
                    '{$this->payment_type_id}',
                    '{$this->amount}',
                    '{$this->payment_date}',
                    '{$this->cheq_no}',
                    '{$this->ref_no}',
                    '{$this->bank_id}',
                    '{$this->branch_id}',
                    $cheq_date,
                    $transfer_date,
                    '{$this->is_settle}',
                    '{$this->account_no}'
                  )";

        $db = Database::getInstance();
        return $db->readQuery($query) ? mysqli_insert_id($db->DB_CON) : false;
    }

    public function update()
    {
        $query = "UPDATE `payment_receipt_method`
                  SET 
                    `receipt_id` = '{$this->receipt_id}',
                    `invoice_id` = '{$this->invoice_id}',
                    `payment_type_id` = '{$this->payment_type_id}',
                    `amount` = '{$this->amount}',
                    `cheq_no` = '{$this->cheq_no}',
                    `bank_id` = '{$this->bank_id}',
                    `branch_id` = '{$this->branch_id}',
                    `cheq_date` = '{$this->cheq_date}',
                    `is_settle` = '{$this->is_settle}'
                  WHERE `id` = '{$this->id}'";

        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function delete()
    {
        $query = "DELETE FROM `payment_receipt_method` WHERE `id` = '{$this->id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT `id`, `receipt_id`, `invoice_id`, `payment_type_id`, `amount`, 
                         `cheq_no`, `bank_id`, `branch_id`, `cheq_date`, `is_settle`
                  FROM `payment_receipt_method`
                  ORDER BY `id` DESC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }

    public function getByReceipt($receiptId)
    {
        $query = "SELECT `id`, `receipt_id`, `invoice_id`, `payment_type_id`, `amount`, 
                         `cheq_no`, `bank_id`, `branch_id`, `cheq_date`, `is_settle`
                  FROM `payment_receipt_method`
                  WHERE `receipt_id` = '" . (int)$receiptId . "'
                  ORDER BY `id` ASC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }

    public function updateIsSettle($id)
    {
        $query = "UPDATE `payment_receipt_method`
                  SET 
                    `is_settle` = 1
                  WHERE `id` = '{$id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }
      public function getByDateRange($date, $dateTo)
{
    if (empty($date) || empty($dateTo)) {
        return [];
    }

    $db = Database::getInstance();
    $date = $db->escapeString($date);
    $dateTo = $db->escapeString($dateTo);

    $query = "SELECT 
                prm.id,
                prm.receipt_id,
                prm.invoice_id,
                prm.payment_type_id,
                prm.amount,
                prm.cheq_no,
                prm.bank_id,
                prm.branch_id,
                prm.cheq_date,
                prm.is_settle,
                prm.cheq_date as entry_date,
                b.name AS bank_name,
                br.name AS branch_name
            FROM `payment_receipt_method` prm
            LEFT JOIN `banks` b ON prm.bank_id = b.id
            LEFT JOIN `branches` br ON prm.branch_id = br.id
            WHERE prm.payment_type_id = 2
              AND prm.cheq_date BETWEEN '{$date}' AND '{$dateTo}'
            ORDER BY prm.id ASC";

    $result = $db->readQuery($query);
    $array = [];

    while ($row = mysqli_fetch_array($result)) {

        $BANK = new Bank($row['bank_id']);
        $BRANCH = new Branch($row['branch_id']);
        $row['bank_name'] = $BANK->name;
        $row['branch_name'] = $BRANCH->name;
        
        $array[] = $row;
    }

    return $array;
}
}
