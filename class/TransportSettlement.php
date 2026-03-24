<?php

class TransportSettlement
{
    public $id;
    public $transport_id;
    public $settlement_date;
    public $amount;
    public $remark;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `transport_settlements` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->transport_id = $result['transport_id'];
                $this->settlement_date = $result['settlement_date'];
                $this->amount = $result['amount'];
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO `transport_settlements` (
            `transport_id`, `settlement_date`, `amount`, `remark`, `created_at`
        ) VALUES (
            '" . (int) $this->transport_id . "',
            '" . addslashes($this->settlement_date) . "',
            '" . floatval($this->amount) . "',
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
        
        $query = "UPDATE `transport_settlements` SET 
            `settlement_date` = '" . addslashes($this->settlement_date) . "',
            `amount` = '" . floatval($this->amount) . "',
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
        
        $query = "DELETE FROM `transport_settlements` WHERE `id` = '" . (int) $this->id . "'";
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
        $query = "SELECT * FROM `transport_settlements` 
                  WHERE `transport_id` = " . (int) $transport_id . "
                  ORDER BY `settlement_date` DESC, `id` DESC";
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
                  FROM `transport_settlements` 
                  WHERE `transport_id` = " . (int) $transport_id;
        $result = mysqli_fetch_assoc($db->readQuery($query));
        
        return floatval($result['total'] ?? 0);
    }

    private function updateTransportSettledStatus($transport_id)
    {
        $db = Database::getInstance();
        
        // Get transport total and total settlements
        $query = "SELECT 
                    td.total_amount,
                    COALESCE(SUM(ts.amount), 0) AS total_settled
                  FROM transport_details td
                  LEFT JOIN transport_settlements ts ON td.id = ts.transport_id
                  WHERE td.id = " . (int) $transport_id . "
                  GROUP BY td.id";
        
        $result = mysqli_fetch_assoc($db->readQuery($query));
        
        if ($result) {
            $totalAmount = floatval($result['total_amount']);
            $totalSettled = floatval($result['total_settled']);
            
            // Mark as settled if total settlements >= total amount
            $isSettled = ($totalSettled >= $totalAmount) ? 1 : 0;
            
            $updateQuery = "UPDATE `transport_details` 
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
                    td.id,
                    td.total_amount,
                    td.payment_method,
                    td.is_settled,
                    COALESCE(SUM(ts.amount), 0) AS total_settled,
                    (td.total_amount - COALESCE(SUM(ts.amount), 0)) AS remaining_amount
                  FROM transport_details td
                  LEFT JOIN transport_settlements ts ON td.id = ts.transport_id
                  WHERE td.id = " . (int) $transport_id . "
                  GROUP BY td.id";
        
        return mysqli_fetch_assoc($db->readQuery($query));
    }
}

?>
