<?php

class DepositPayment
{
    public $id;
    public $rent_id;
    public $amount;
    public $payment_date;
    public $remark;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `deposit_payments` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->rent_id = $result['rent_id'];
                $this->amount = $result['amount'];
                $this->payment_date = $result['payment_date'];
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $amount = floatval($this->amount);
        $rent_id = (int) $this->rent_id;
        $payment_date = mysqli_real_escape_string($db->DB_CON, $this->payment_date);
        $remark = mysqli_real_escape_string($db->DB_CON, $this->remark ?? '');

        $query = "INSERT INTO `deposit_payments` (`rent_id`, `amount`, `payment_date`, `remark`)
                  VALUES ('$rent_id', '$amount', '$payment_date', '$remark')";

        $result = $db->readQuery($query);
        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        }
        return false;
    }

    public function delete()
    {
        if (!$this->id) return false;
        $db = Database::getInstance();
        $query = "DELETE FROM `deposit_payments` WHERE `id` = " . (int) $this->id;
        return $db->readQuery($query);
    }

    /**
     * Get all deposit payments for a rent
     */
    public static function getByRentId($rent_id)
    {
        $db = Database::getInstance();
        $rent_id = (int) $rent_id;
        $query = "SELECT * FROM `deposit_payments` WHERE `rent_id` = $rent_id ORDER BY `payment_date` ASC, `id` ASC";
        $result = $db->readQuery($query);
        $payments = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $payments[] = $row;
            }
        }
        return $payments;
    }

    /**
     * Get total deposit amount for a rent
     */
    public static function getTotalByRentId($rent_id)
    {
        $db = Database::getInstance();
        $rent_id = (int) $rent_id;
        $query = "SELECT COALESCE(SUM(`amount`), 0) as total FROM `deposit_payments` WHERE `rent_id` = $rent_id";
        $result = $db->readQuery($query);
        $row = mysqli_fetch_assoc($result);
        return floatval($row['total'] ?? 0);
    }

    /**
     * Update deposit_total on equipment_rent to match sum of payments
     */
    public static function syncDepositTotal($rent_id)
    {
        $total = self::getTotalByRentId($rent_id);
        $db = Database::getInstance();
        $rent_id = (int) $rent_id;
        $query = "UPDATE `equipment_rent` SET `deposit_total` = '$total' WHERE `id` = $rent_id";
        $db->readQuery($query);
        return $total;
    }
}
