<?php

class TransportSettlement
{
    public $id;
    public $transport_id;
    public $settlement_date;
    public $amount;
    public $payment_type;
    public $bank_id;
    public $branch_id;
    public $cheque_no;
    public $cheque_date;
    public $transfer_date;
    public $account_no;
    public $reference_no;
    public $remark;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `trip_settlements` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->transport_id = $result['trip_id'];
                $this->settlement_date = $result['settlement_date'];
                $this->amount = $result['amount'];
                $this->payment_type = $result['payment_type'] ?? 'Cash';
                $this->bank_id = $result['bank_id'];
                $this->branch_id = $result['branch_id'];
                $this->cheque_no = $result['cheque_no'];
                $this->cheque_date = $result['cheque_date'];
                $this->transfer_date = $result['transfer_date'];
                $this->account_no = $result['account_no'];
                $this->reference_no = $result['reference_no'];
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        
        $paymentType = $this->payment_type ?? 'Cash';
        $bankId = $this->bank_id ? (int)$this->bank_id : 'NULL';
        $branchId = $this->branch_id ? (int)$this->branch_id : 'NULL';
        $chequeNo = $this->cheque_no ? "'" . addslashes($this->cheque_no) . "'" : 'NULL';
        $chequeDate = $this->cheque_date ? "'" . addslashes($this->cheque_date) . "'" : 'NULL';
        $transferDate = $this->transfer_date ? "'" . addslashes($this->transfer_date) . "'" : 'NULL';
        $accountNo = $this->account_no ? "'" . addslashes($this->account_no) . "'" : 'NULL';
        $referenceNo = $this->reference_no ? "'" . addslashes($this->reference_no) . "'" : 'NULL';

        $query = "INSERT INTO `trip_settlements` (
            `trip_id`, `settlement_date`, `amount`, `payment_type`, 
            `bank_id`, `branch_id`, `cheque_no`, `cheque_date`, 
            `transfer_date`, `account_no`, `reference_no`, `remark`, `created_at`
        ) VALUES (
            '" . (int) $this->transport_id . "',
            '" . addslashes($this->settlement_date) . "',
            '" . floatval($this->amount) . "',
            '" . addslashes($paymentType) . "',
            $bankId, $branchId, $chequeNo, $chequeDate,
            $transferDate, $accountNo, $referenceNo,
            " . ($this->remark ? "'" . addslashes($this->remark) . "'" : "NULL") . ",
            '$now'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            $settlementId = mysqli_insert_id($db->DB_CON);
            
            // Update transport is_settled status
            $this->updateTransportSettledStatus($this->transport_id);
            
            return $settlementId;
        }
        
        return false;
    }

    public function update()
    {
        $db = Database::getInstance();
        
        $paymentType = $this->payment_type ?? 'Cash';
        $bankId = $this->bank_id ? (int)$this->bank_id : 'NULL';
        $branchId = $this->branch_id ? (int)$this->branch_id : 'NULL';
        $chequeNo = $this->cheque_no ? "'" . addslashes($this->cheque_no) . "'" : 'NULL';
        $chequeDate = $this->cheque_date ? "'" . addslashes($this->cheque_date) . "'" : 'NULL';
        $transferDate = $this->transfer_date ? "'" . addslashes($this->transfer_date) . "'" : 'NULL';
        $accountNo = $this->account_no ? "'" . addslashes($this->account_no) . "'" : 'NULL';
        $referenceNo = $this->reference_no ? "'" . addslashes($this->reference_no) . "'" : 'NULL';

        $query = "UPDATE `trip_settlements` SET 
            `settlement_date` = '" . addslashes($this->settlement_date) . "',
            `amount` = '" . floatval($this->amount) . "',
            `payment_type` = '" . addslashes($paymentType) . "',
            `bank_id` = $bankId,
            `branch_id` = $branchId,
            `cheque_no` = $chequeNo,
            `cheque_date` = $chequeDate,
            `transfer_date` = $transferDate,
            `account_no` = $accountNo,
            `reference_no` = $referenceNo,
            `remark` = " . ($this->remark ? "'" . addslashes($this->remark) . "'" : "NULL") . "
            WHERE `id` = " . (int) $this->id;

        $result = $db->readQuery($query);
        
        if ($result) {
            // Update transport is_settled status
            $this->updateTransportSettledStatus($this->transport_id);
        }
        
        return $result;
    }

    public function delete()
    {
        $transportId = $this->transport_id;
        
        $query = "DELETE FROM `trip_settlements` WHERE `id` = '" . (int) $this->id . "'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        
        if ($result) {
            // Update transport is_settled status
            $this->updateTransportSettledStatus($transportId);
        }
        
        return $result;
    }

    public static function getByTransportId($transport_id)
    {
        $db = Database::getInstance();
        $query = "SELECT ts.*, 
                    b.name AS bank_name, 
                    br.name AS branch_name
                  FROM `trip_settlements` ts
                  LEFT JOIN `banks` b ON ts.bank_id = b.id
                  LEFT JOIN `branches` br ON ts.branch_id = br.id
                  WHERE ts.`trip_id` = " . (int) $transport_id . "
                  ORDER BY ts.`settlement_date` DESC, ts.`id` DESC";
        $result = $db->readQuery($query);
        $data = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        
        return $data;
    }

    public static function getTotalByTransportId($transport_id)
    {
        $db = Database::getInstance();
        $query = "SELECT COALESCE(SUM(amount), 0) AS total 
                  FROM `trip_settlements` 
                  WHERE `trip_id` = " . (int) $transport_id;
        $result = mysqli_fetch_assoc($db->readQuery($query));
        
        return floatval($result['total'] ?? 0);
    }

    private function updateTransportSettledStatus($transport_id)
    {
        $db = Database::getInstance();
        
        // Get transport total and total settlements
        $query = "SELECT 
                    tm.total_cost AS total_amount,
                    COALESCE(SUM(ts.amount), 0) AS total_settled
                  FROM trip_management tm
                  LEFT JOIN trip_settlements ts ON tm.id = ts.trip_id
                  WHERE tm.id = " . (int) $transport_id . "
                  GROUP BY tm.id";
        
        $result = mysqli_fetch_assoc($db->readQuery($query));
        
        if ($result) {
            $totalAmount = floatval($result['total_amount']);
            $totalSettled = floatval($result['total_settled']);
            
            // Mark as settled if total settlements >= total amount
            $isSettled = ($totalSettled >= $totalAmount) ? 1 : 0;
            
            $updateQuery = "UPDATE `trip_management` 
                           SET `is_settled` = $isSettled,
                               `settlement_amount` = $totalSettled
                           WHERE `id` = " . (int) $transport_id;
            
            $db->readQuery($updateQuery);
        }
    }

    public static function getSettlementStatus($transport_id)
    {
        $db = Database::getInstance();
        $query = "SELECT 
                    tm.id,
                    tm.total_cost AS total_amount,
                    tm.payment_method,
                    tm.is_settled,
                    COALESCE(SUM(ts.amount), 0) AS total_settled,
                    (tm.total_cost - COALESCE(SUM(ts.amount), 0)) AS remaining_amount
                  FROM trip_management tm
                  LEFT JOIN trip_settlements ts ON tm.id = ts.trip_id
                  WHERE tm.id = " . (int) $transport_id . "
                  GROUP BY tm.id";
        
        return mysqli_fetch_assoc($db->readQuery($query));
    }
}

?>
