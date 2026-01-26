<?php

class LoyaltyPoints
{
    public $id;
    public $customer_id;
    public $points;
    public $type;
    public $reference_id;
    public $description;
    public $created_at;
    public $created_by;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `loyalty_points` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_assoc($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->customer_id = $result['customer_id'];
                $this->points = $result['points'];
                $this->type = $result['type'];
                $this->reference_id = $result['reference_id'];
                $this->description = $result['description'];
                $this->created_at = $result['created_at'];
                $this->created_by = $result['created_by'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $this->created_by = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
        
        $query = "INSERT INTO `loyalty_points` (
            `customer_id`, `points`, `type`, `reference_id`, `description`, `created_at`, `created_by`
        ) VALUES (
            '$this->customer_id', 
            '$this->points', 
            '$this->type', 
            " . ($this->reference_id ? "'$this->reference_id'" : "NULL") . ", 
            '$this->description', 
            NOW(), 
            '$this->created_by'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    public static function getBalance($customerId)
    {
        $db = Database::getInstance();
        
        // Sum additions (earn, adjustment +) vs subtractions (redeem, adjustment -)
        // Usually, we just store signed values or handle by type.
        // Let's assume 'earn' and positive 'adjustment' are +, 'redeem' is -.
        // Cleaner: Store signed values in DB? Or map types.
        // Let's map types: earn (+), redeem (-), adjustment (+/- handled by value or explicit?)
        // Let's assume points column is always positive and type dictates sign.
        
        $query = "SELECT SUM(
                    CASE 
                        WHEN type = 'earn' THEN points 
                        WHEN type = 'adjustment' THEN points -- Assumes adjustment can be stored as -ve if deduction, or handle logic here
                        WHEN type = 'redeem' THEN -points
                        ELSE 0 
                    END
                  ) as balance 
                  FROM `loyalty_points` 
                  WHERE `customer_id` = " . (int) $customerId;
                  
        // If adjustment needs to be negative, user enters negative?
        // Let's rely on stored value being correctly signed if it's an adjustment, 
        // OR simply enforce: 'earn' = +, 'redeem' = -. 'adjustment' = value as is (signed).
        // Let's stick to: points are magnitude. 
        // type='earn' => +, type='redeem' => -. 
        // type='adjustment' => let's say we adding points manually.
        
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return $result['balance'] ?? 0;
    }
    
    // Get transaction history
    public static function getHistory($customerId)
    {
        $db = Database::getInstance();
        $query = "SELECT lp.*, u.name as user_name 
                  FROM `loyalty_points` lp 
                  LEFT JOIN user u ON lp.created_by = u.id 
                  WHERE lp.customer_id = " . (int) $customerId . " 
                  ORDER BY lp.created_at DESC";
                  
        $result = $db->readQuery($query);
        $history = [];
        while($row = mysqli_fetch_assoc($result)) {
            $history[] = $row;
        }
        return $history;
    }
}
