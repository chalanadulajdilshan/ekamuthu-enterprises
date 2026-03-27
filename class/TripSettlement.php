<?php

class TripSettlement
{
    public $id;
    public $trip_id;
    public $settlement_date;
    public $amount;
    public $remark;
    public $payment_method;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `trip_settlements` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->trip_id = $result['trip_id'];
                $this->settlement_date = $result['settlement_date'];
                $this->amount = $result['amount'];
                $this->payment_method = $result['payment_method'] ?? 'cash';
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO `trip_settlements` (
            `trip_id`, `settlement_date`, `amount`, `payment_method`, `remark`, `created_at`
        ) VALUES (
            '" . (int) $this->trip_id . "',
            '" . addslashes($this->settlement_date) . "',
            '" . floatval($this->amount) . "',
            '" . addslashes($this->payment_method ?: 'cash') . "',
            " . ($this->remark ? "'" . addslashes($this->remark) . "'" : "NULL") . ",
            '$now'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            $settlementId = mysqli_insert_id($db->DB_CON);
            
            // Update trip settlement status
            $this->updateTripSettledStatus($this->trip_id);
            
            return $settlementId;
        }
        
        return false;
    }

    public function delete()
    {
        $tripId = $this->trip_id;
        
        $query = "DELETE FROM `trip_settlements` WHERE `id` = '" . (int) $this->id . "'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        
        if ($result) {
            // Update trip settlement status
            $this->updateTripSettledStatus($tripId);
        }
        
        return $result;
    }

    public static function getByTripId($trip_id)
    {
        $db = Database::getInstance();
        $query = "SELECT * FROM `trip_settlements` 
                  WHERE `trip_id` = " . (int) $trip_id . "
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

    public static function getTotalByTripId($trip_id)
    {
        $db = Database::getInstance();
        $query = "SELECT COALESCE(SUM(amount), 0) AS total 
                  FROM `trip_settlements` 
                  WHERE `trip_id` = " . (int) $trip_id;
        $result = mysqli_fetch_assoc($db->readQuery($query));
        
        return floatval($result['total'] ?? 0);
    }

    private function updateTripSettledStatus($trip_id)
    {
        $db = Database::getInstance();
        
        // Get trip total_cost and total settlements
        $query = "SELECT 
                    tm.total_cost,
                    COALESCE(SUM(ts.amount), 0) AS total_settled
                  FROM trip_management tm
                  LEFT JOIN trip_settlements ts ON tm.id = ts.trip_id
                  WHERE tm.id = " . (int) $trip_id . "
                  GROUP BY tm.id";
        
        $result = mysqli_fetch_assoc($db->readQuery($query));
        
        if ($result) {
            $totalAmount = floatval($result['total_cost']);
            $totalSettled = floatval($result['total_settled']);
            
            // Mark as settled if total settlements >= total amount
            $isSettled = ($totalSettled >= $totalAmount) ? 1 : 0;
            
            $updateQuery = "UPDATE `trip_management` 
                           SET `is_settled` = $isSettled,
                               `settlement_amount` = $totalSettled
                           WHERE `id` = " . (int) $trip_id;
            
            $db->readQuery($updateQuery);
        }
    }

    public static function getSettlementStatus($trip_id)
    {
        $db = Database::getInstance();
        $query = "SELECT 
                    tm.id,
                    tm.total_cost,
                    tm.payment_method,
                    tm.is_settled,
                    COALESCE(SUM(ts.amount), 0) AS total_settled,
                    (tm.total_cost - COALESCE(SUM(ts.amount), 0)) AS remaining_amount
                  FROM trip_management tm
                  LEFT JOIN trip_settlements ts ON tm.id = ts.trip_id
                  WHERE tm.id = " . (int) $trip_id . "
                  GROUP BY tm.id";
        
        return mysqli_fetch_assoc($db->readQuery($query));
    }
}

?>
