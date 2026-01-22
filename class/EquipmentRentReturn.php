<?php

class EquipmentRentReturn
{
    public $id;
    public $rent_item_id;
    public $return_date;
    public $return_qty;
    public $damage_amount;
    public $settle_amount;
    public $refund_amount;
    public $additional_payment;
    public $remark;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `equipment_rent_returns` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->rent_item_id = $result['rent_item_id'];
                $this->return_date = $result['return_date'];
                $this->return_qty = $result['return_qty'];
                $this->damage_amount = $result['damage_amount'];
                $this->settle_amount = $result['settle_amount'];
                $this->refund_amount = $result['refund_amount'];
                $this->additional_payment = $result['additional_payment'];
                $this->remark = $result['remark'];
                $this->created_by = $result['created_by'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `equipment_rent_returns` (
            `rent_item_id`, `return_date`, `return_qty`, `damage_amount`, 
            `settle_amount`, `refund_amount`, `additional_payment`, `remark`, `created_by`
        ) VALUES (
            '$this->rent_item_id', '$this->return_date', '$this->return_qty', 
            '$this->damage_amount', '$this->settle_amount', '$this->refund_amount', 
            '$this->additional_payment', '$this->remark', " . 
            (isset($_SESSION['id']) ? "'{$_SESSION['id']}'" : "NULL") . "
        )";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);
            
            // Update sub_equipment status if fully returned
            $this->checkAndUpdateSubEquipmentStatus();
            
            return $this->id;
        } else {
            return false;
        }
    }

    public function update()
    {
        $query = "UPDATE `equipment_rent_returns` SET 
            `return_date` = '$this->return_date',
            `return_qty` = '$this->return_qty',
            `damage_amount` = '$this->damage_amount',
            `settle_amount` = '$this->settle_amount',
            `refund_amount` = '$this->refund_amount',
            `additional_payment` = '$this->additional_payment',
            `remark` = '$this->remark'
            WHERE `id` = '$this->id'";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        
        if ($result) {
            // Update sub_equipment status if fully returned
            $this->checkAndUpdateSubEquipmentStatus();
            return true;
        }
        
        return false;
    }

    public function delete()
    {
        $query = "DELETE FROM `equipment_rent_returns` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        
        if ($result) {
            // Check if we need to update sub_equipment status back to rented
            $this->checkAndUpdateSubEquipmentStatus();
            return true;
        }
        
        return false;
    }

    private function checkAndUpdateSubEquipmentStatus()
    {
        $db = Database::getInstance();
        
        // Get rent item details
        $itemQuery = "SELECT eri.*, 
                      (SELECT COALESCE(SUM(return_qty), 0) FROM equipment_rent_returns WHERE rent_item_id = eri.id) as total_returned
                      FROM equipment_rent_items eri 
                      WHERE eri.id = " . (int) $this->rent_item_id;
        $itemResult = mysqli_fetch_assoc($db->readQuery($itemQuery));
        
        if ($itemResult && $itemResult['sub_equipment_id']) {
            $newStatus = 'rented';
            
            // If fully returned, mark as available
            if ($itemResult['total_returned'] >= $itemResult['quantity']) {
                $newStatus = 'available';
            }
            
            $statusQuery = "UPDATE `sub_equipment` SET `rental_status` = '$newStatus' 
                           WHERE `id` = " . (int) $itemResult['sub_equipment_id'];
            $db->readQuery($statusQuery);
        }
    }

    public static function calculateSettlement($rent_item_id, $return_qty, $damage_amount, $return_date = null)
    {
        $db = Database::getInstance();
        
        // Get rent item details with rent type and duration
        $query = "SELECT eri.*, 
                  e.deposit_one_day as deposit_per_item,
                  (SELECT COALESCE(SUM(return_qty), 0) FROM equipment_rent_returns WHERE rent_item_id = eri.id) as already_returned,
                  eri.quantity - (SELECT COALESCE(SUM(return_qty), 0) FROM equipment_rent_returns WHERE rent_item_id = eri.id) as pending_qty
                  FROM equipment_rent_items eri
                  LEFT JOIN equipment e ON eri.equipment_id = e.id
                  WHERE eri.id = " . (int) $rent_item_id;
        
        $item = mysqli_fetch_assoc($db->readQuery($query));
        
        if (!$item) {
            return [
                'error' => true,
                'message' => 'Rent item not found'
            ];
        }
        
        // Per-unit values
        $quantity = max(1, floatval($item['quantity']));
        $per_unit_rent_total = floatval($item['amount']) / $quantity;
        $per_unit_deposit = floatval($item['deposit_amount']) / $quantity;
        $duration = max(1, floatval($item['duration']));
        $rent_type = $item['rent_type'];
        $duration_days = ($rent_type === 'month') ? $duration * 30 : $duration;
        $per_unit_daily = $per_unit_rent_total / $duration_days;

        // Validate return quantity
        if ($return_qty > $item['pending_qty']) {
            return [
                'error' => true,
                'message' => "Cannot return more than pending quantity ({$item['pending_qty']})"
            ];
        }
        
        // Determine used days based on rental_date to return_date
        $rental_date = $item['rental_date'];
        $return_date = $return_date ?: date('Y-m-d');
        $rental_dt = strtotime($rental_date);
        $return_dt = strtotime($return_date);
        $used_days = max(1, (int)ceil(($return_dt - $rental_dt) / 86400));

        // Calculate deposit for this return quantity
        $deposit_for_return = $per_unit_deposit * $return_qty;

        // Rental charge for returned quantity based on days used
        $rental_amount = $per_unit_daily * $used_days * $return_qty;
        
        // Calculate settlement
        // settle_amount: rental + damage - deposit share
        $settle_amount = ($rental_amount + floatval($damage_amount)) - $deposit_for_return;
        
        $refund_amount = 0;
        $additional_payment = 0;
        
        if ($settle_amount < 0) {
            // Customer gets refund
            $refund_amount = abs($settle_amount);
            $additional_payment = 0;
        } else {
            // Customer needs to pay additional
            $refund_amount = 0;
            $additional_payment = $settle_amount;
        }
        
        return [
            'error' => false,
            'deposit_for_return' => round($deposit_for_return, 2),
            'rental_amount' => round($rental_amount, 2),
            'per_unit_daily' => round($per_unit_daily, 2),
            'used_days' => $used_days,
            'damage_amount' => round(floatval($damage_amount), 2),
            'settle_amount' => round($settle_amount, 2),
            'refund_amount' => round($refund_amount, 2),
            'additional_payment' => round($additional_payment, 2),
            'pending_qty' => $item['pending_qty'],
            'already_returned' => $item['already_returned'],
            'total_quantity' => $item['quantity']
        ];
    }

    public static function getByRentItemId($rent_item_id)
    {
        $query = "SELECT err.*, u.name as created_by_name,
                         -- duration days (month=>30)
                         CASE WHEN eri.rent_type = 'month' THEN eri.duration * 30 ELSE eri.duration END AS duration_days,
                         -- per-unit daily rate
                         (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / NULLIF(CASE WHEN eri.rent_type = 'month' THEN eri.duration * 30 ELSE eri.duration END,0) AS per_unit_daily,
                         -- used days from rental_date to this return_date (>=1), matching PHP ceil day diff
                         GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400)) AS used_days,
                         -- rental for this return qty based on days used
                         GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                           * ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / NULLIF(CASE WHEN eri.rent_type = 'month' THEN eri.duration * 30 ELSE eri.duration END,0))
                           * err.return_qty AS rental_amount
                  FROM `equipment_rent_returns` err
                  LEFT JOIN `user` u ON err.created_by = u.id
                  LEFT JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
                  WHERE err.rent_item_id = " . (int) $rent_item_id . "
                  ORDER BY err.return_date DESC, err.id DESC";
        
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $returns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Recompute deposit share and settlement to match PHP calculation used elsewhere
            $perUnitDeposit = 0;
            if (isset($row['deposit_amount']) && isset($row['quantity']) && floatval($row['quantity']) != 0) {
                $perUnitDeposit = floatval($row['deposit_amount']) / floatval($row['quantity']);
            }
            $depositForReturn = $perUnitDeposit * floatval($row['return_qty']);

            $rentalAmount = floatval($row['rental_amount'] ?? 0);
            $damageAmount = floatval($row['damage_amount'] ?? 0);
            $settleCalc = ($rentalAmount + $damageAmount) - $depositForReturn;
            $additionalPayment = $settleCalc > 0 ? $settleCalc : 0;
            $refundAmount = $settleCalc < 0 ? abs($settleCalc) : 0;

            $row['deposit_for_return'] = round($depositForReturn, 2);
            $row['rental_amount'] = round($rentalAmount, 2);
            $row['settle_amount_calc'] = round($settleCalc, 2);
            $row['additional_payment'] = round($additionalPayment, 2);
            $row['refund_amount'] = round($refundAmount, 2);

            $returns[] = $row;
        }

        return $returns;
    }

    public static function getTotalReturnedQty($rent_item_id)
    {
        $db = Database::getInstance();
        $query = "SELECT COALESCE(SUM(return_qty), 0) as total_returned 
                  FROM equipment_rent_returns 
                  WHERE rent_item_id = " . (int) $rent_item_id;
        
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return $result ? intval($result['total_returned']) : 0;
    }

    public static function getTotalDamageAmount($rent_item_id)
    {
        $db = Database::getInstance();
        $query = "SELECT COALESCE(SUM(damage_amount), 0) as total_damage 
                  FROM equipment_rent_returns 
                  WHERE rent_item_id = " . (int) $rent_item_id;
        
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return $result ? floatval($result['total_damage']) : 0;
    }

    public static function getTotalSettlementAmount($rent_item_id)
    {
        $db = Database::getInstance();
        $query = "SELECT 
                  COALESCE(SUM(refund_amount), 0) as total_refund,
                  COALESCE(SUM(additional_payment), 0) as total_additional
                  FROM equipment_rent_returns 
                  WHERE rent_item_id = " . (int) $rent_item_id;
        
        $result = mysqli_fetch_assoc($db->readQuery($query));
        
        if ($result) {
            return [
                'total_refund' => floatval($result['total_refund']),
                'total_additional' => floatval($result['total_additional']),
                'net_settlement' => floatval($result['total_additional']) - floatval($result['total_refund'])
            ];
        }
        
        return [
            'total_refund' => 0,
            'total_additional' => 0,
            'net_settlement' => 0
        ];
    }

    public static function getReturnSummaryByRentId($rent_id)
    {
        $db = Database::getInstance();
        $query = "SELECT 
                  eri.id as rent_item_id,
                  e.code as equipment_code,
                  e.item_name as equipment_name,
                  se.code as sub_equipment_code,
                  eri.quantity as total_qty,
                  eri.total_returned_qty,
                  eri.pending_qty,
                  eri.deposit_amount,
                  eri.status,
                  COUNT(err.id) as return_count,
                  COALESCE(SUM(err.damage_amount), 0) as total_damage,
                  COALESCE(SUM(err.refund_amount), 0) as total_refund,
                  COALESCE(SUM(err.additional_payment), 0) as total_additional
                  FROM equipment_rent_items eri
                  LEFT JOIN equipment e ON eri.equipment_id = e.id
                  LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                  LEFT JOIN equipment_rent_returns err ON eri.id = err.rent_item_id
                  WHERE eri.rent_id = " . (int) $rent_id . "
                  GROUP BY eri.id
                  ORDER BY eri.id ASC";
        
        $result = $db->readQuery($query);
        $summary = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $summary[] = $row;
        }
        
        return $summary;
    }
}
