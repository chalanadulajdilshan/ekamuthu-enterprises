<?php

class EquipmentRentReturn
{
    public $id;
    public $rent_item_id;
    public $return_date;
    public $return_time;
    public $return_qty;
    public $damage_amount;
    public $after_9am_extra_day;
    public $extra_day_amount;
    public $penalty_percentage;
    public $penalty_amount;
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
                $this->return_time = $result['return_time'] ?? null;
                $this->return_qty = $result['return_qty'];
                $this->damage_amount = $result['damage_amount'];
                $this->after_9am_extra_day = $result['after_9am_extra_day'] ?? 0;
                $this->extra_day_amount = $result['extra_day_amount'] ?? 0;
                $this->penalty_percentage = $result['penalty_percentage'] ?? 0;
                $this->penalty_amount = $result['penalty_amount'] ?? 0;
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
            `rent_item_id`, `return_date`, `return_time`, `return_qty`, `damage_amount`, 
            `after_9am_extra_day`, `extra_day_amount`, `penalty_percentage`, `penalty_amount`,
            `settle_amount`, `refund_amount`, `additional_payment`, `remark`, `created_by`
        ) VALUES (
            '$this->rent_item_id', '$this->return_date', " .
            ($this->return_time ? "'{$this->return_time}'" : "NULL") . ", '$this->return_qty', 
            '$this->damage_amount', '$this->after_9am_extra_day', '$this->extra_day_amount',
            '$this->penalty_percentage', '$this->penalty_amount',
            '$this->settle_amount', '$this->refund_amount', 
            '$this->additional_payment', '$this->remark', " . 
            (isset($_SESSION['id']) ? "'{$_SESSION['id']}'" : "NULL") . "
        )";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);
            
            // Restore bulk stock if applicable
            $this->handleBulkStockAdjustment($this->return_qty, true);
            
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
            `return_time` = " . ($this->return_time ? "'{$this->return_time}'" : "NULL") . ",
            `return_qty` = '$this->return_qty',
            `damage_amount` = '$this->damage_amount',
            `after_9am_extra_day` = '$this->after_9am_extra_day',
            `extra_day_amount` = '$this->extra_day_amount',
            `penalty_percentage` = '$this->penalty_percentage',
            `penalty_amount` = '$this->penalty_amount',
            `settle_amount` = '$this->settle_amount',
            `refund_amount` = '$this->refund_amount',
            `additional_payment` = '$this->additional_payment',
            `remark` = '$this->remark'
            WHERE `id` = '$this->id'";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        
        if ($result) {
            // Handle qty change in update if needed (EquipmentRentReturn updates are rare but supported)
            $oldReturn = new EquipmentRentReturn($this->id);
            if ($oldReturn->return_qty != $this->return_qty) {
                $diff = $this->return_qty - $oldReturn->return_qty;
                $this->handleBulkStockAdjustment(abs($diff), $diff > 0);
            }

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
            // Adjust bulk stock back (subtract from stock since we are deleting a return)
            $this->handleBulkStockAdjustment($this->return_qty, false);

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

    private function handleBulkStockAdjustment($quantity, $isAdding)
    {
        $db = Database::getInstance();
        $itemQuery = "SELECT eri.equipment_id, eri.department_id, eri.sub_equipment_id 
                      FROM equipment_rent_items eri 
                      WHERE eri.id = " . (int) $this->rent_item_id;
        $item = mysqli_fetch_assoc($db->readQuery($itemQuery));

        if ($item && !$item['sub_equipment_id'] && $item['equipment_id'] && $item['department_id']) {
            // When returning, we DECREASE the rented_qty
            $op = $isAdding ? "-" : "+";
            $updateStock = "UPDATE `sub_equipment` SET `rented_qty` = `rented_qty` $op " . (int)$quantity . " 
                            WHERE `equipment_id` = " . (int)$item['equipment_id'] . " 
                            AND `department_id` = " . (int)$item['department_id'];
            $db->readQuery($updateStock);
        }
    }

    public static function calculateSettlement($rent_item_id, $return_qty, $damage_amount, $return_date = null, $return_time = null, $after_9am_extra_day = 0, $extra_day_amount = 0, $penalty_percentage = 0)
    {
        $db = Database::getInstance();
        
        // Get rent item details with rent type and duration, including customer deposit
        $query = "SELECT eri.*, 
                  e.deposit_one_day as deposit_per_item,
                  e.is_fixed_rate,
                  er.deposit_total as customer_deposit,
                  er.id as rent_id,
                  (SELECT COALESCE(SUM(return_qty), 0) FROM equipment_rent_returns WHERE rent_item_id = eri.id) as already_returned,
                  eri.quantity - (SELECT COALESCE(SUM(return_qty), 0) FROM equipment_rent_returns WHERE rent_item_id = eri.id) as pending_qty
                  FROM equipment_rent_items eri
                  LEFT JOIN equipment e ON eri.equipment_id = e.id
                  LEFT JOIN equipment_rent er ON eri.rent_id = er.id
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
        // Calculate daily rate: for monthly rentals, divide by 30 to get per-day rate
        $per_unit_daily = ($rent_type === 'month') ? ($per_unit_rent_total / 30) : $per_unit_rent_total;

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
        
        // Check if return is late (used_days > duration_days)
        $is_late = $used_days > $duration_days;
        $overdue_days = $is_late ? ($used_days - $duration_days) : 0;

        $isAfterCutoff = false;
        if (intval($after_9am_extra_day) === 1) {
            $t = $return_time ?: '00:00';
            if (preg_match('/^\d{2}:\d{2}/', $t)) {
                $isAfterCutoff = (strtotime($t) >= strtotime('09:00'));
            }
        }

        // Check if this is a fixed-rate item
        $is_fixed_rate = intval($item['is_fixed_rate'] ?? 0) === 1;

        // For fixed-rate items, normalize to a single charged day and no extra/penalty
        if ($is_fixed_rate) {
            $used_days = 1;
            $overdue_days = 0;
            $is_late = false;
            $suggestedExtraDayAmount = 0;
            $finalExtraDayAmount = 0;
            $charged_days = 1;
            // Daily shown as flat rate
            $per_unit_daily = $per_unit_rent_total;
        } else {
            $suggestedExtraDayAmount = $isAfterCutoff ? ($per_unit_daily * $return_qty) : 0;
            $finalExtraDayAmount = $isAfterCutoff ? (floatval($extra_day_amount) > 0 ? floatval($extra_day_amount) : $suggestedExtraDayAmount) : 0;
            $charged_days = $used_days + ($finalExtraDayAmount > 0 ? 1 : 0);
        }

        // Calculate deposit for this return quantity
        $deposit_for_return = $per_unit_deposit * $return_qty;
        
        // Calculate REMAINING customer deposit after previous returns
        // Sum all rental amounts (rent charged) from previous returns across ALL items in this rent order
        $customer_deposit = floatval($item['customer_deposit'] ?? 0);
        $rent_id = $item['rent_id'];
        
        // Get total rent already charged from previous returns for this entire rent order
        // For fixed-rate items, the rental charge is just (amount/quantity) * return_qty (no day multiplication)
        $prevRentQuery = "SELECT COALESCE(SUM(
                            CASE WHEN COALESCE(e2.is_fixed_rate, 0) = 1
                                THEN ((COALESCE(eri2.amount,0) / NULLIF(eri2.quantity,0)) * err2.return_qty)
                                ELSE (GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri2.rental_date, err2.return_date) / 86400))
                                    * ((COALESCE(eri2.amount,0) / NULLIF(eri2.quantity,0)) / (CASE WHEN eri2.rent_type = 'month' THEN 30 ELSE 1 END))
                                    * err2.return_qty)
                            END
                            + COALESCE(err2.extra_day_amount, 0)
                            + COALESCE(err2.damage_amount, 0)
                            + COALESCE(err2.penalty_amount, 0)
                          ), 0) as total_previous_charges
                          FROM equipment_rent_returns err2
                          INNER JOIN equipment_rent_items eri2 ON err2.rent_item_id = eri2.id
                          LEFT JOIN equipment e2 ON eri2.equipment_id = e2.id
                          WHERE eri2.rent_id = " . (int) $rent_id;
        $prevResult = mysqli_fetch_assoc($db->readQuery($prevRentQuery));
        $total_previous_charges = floatval($prevResult['total_previous_charges'] ?? 0);
        
        // Remaining deposit = original deposit - total charges already deducted from previous returns
        $remaining_deposit = max(0, $customer_deposit - $total_previous_charges);
        
        // Customer deposit share for this return is the remaining deposit (capped by what's available)
        $customer_deposit_share = $remaining_deposit;

        // Rental charge for returned quantity
        // For fixed-rate items: flat rate regardless of days used
        if ($is_fixed_rate) {
            $rental_amount = $per_unit_rent_total * $return_qty;
        } else {
            // Standard: based on days used (extra day amount is separate)
            $rental_amount = $per_unit_daily * $used_days * $return_qty;
        }
        
        // Calculate penalty if late and penalty_percentage is provided (10% to 20%)
        $penalty_percentage = floatval($penalty_percentage);
        if ($penalty_percentage < 0) $penalty_percentage = 0;
        if ($penalty_percentage > 20) $penalty_percentage = 20;
        $penalty_amount = 0;
        // No penalty for fixed-rate items
        if (!$is_fixed_rate && $is_late && $penalty_percentage > 0) {
            $penalty_amount = ($rental_amount * $penalty_percentage) / 100;
        }
        
        // Calculate settlement
        // settle_amount: rental + extra_day + damage + penalty - customer deposit share
        $settle_amount = ($rental_amount + $finalExtraDayAmount + floatval($damage_amount) + $penalty_amount) - $customer_deposit_share;
        
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
            'customer_deposit_share' => round($customer_deposit_share, 2),
            'customer_deposit_original' => round($customer_deposit, 2),
            'total_previous_charges' => round($total_previous_charges, 2),
            'remaining_deposit' => round($remaining_deposit, 2),
            'rental_amount' => round($rental_amount, 2),
            'per_unit_daily' => round($per_unit_daily, 2),
            'used_days' => $used_days,
            'charged_days' => $charged_days,
            'duration_days' => $duration_days,
            'is_late' => $is_late,
            'overdue_days' => $overdue_days,
            'extra_day_amount' => round($finalExtraDayAmount, 2),
            'penalty_percentage' => round($penalty_percentage, 2),
            'penalty_amount' => round($penalty_amount, 2),
            'damage_amount' => round(floatval($damage_amount), 2),
            'settle_amount' => round($settle_amount, 2),
            'refund_amount' => round($refund_amount, 2),
            'additional_payment' => round($additional_payment, 2),
            'pending_qty' => $item['pending_qty'],
            'already_returned' => $item['already_returned'],
            'total_quantity' => $item['quantity'],
            'is_fixed_rate' => $is_fixed_rate
        ];
    }

    public static function getByRentItemId($rent_item_id)
    {
        $query = "SELECT err.*, u.name as created_by_name,
                         COALESCE(e.is_fixed_rate, 0) AS is_fixed_rate,
                         -- duration days (month=>30)
                         CASE WHEN eri.rent_type = 'month' THEN eri.duration * 30 ELSE eri.duration END AS duration_days,
                         -- per-unit daily rate
                         (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END) AS per_unit_daily,
                         -- used days from rental_date to this return_date (>=1), matching PHP ceil day diff
                         GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400)) AS used_days,
                         -- rental for this return qty based on days used (or flat for fixed-rate)
                         CASE WHEN COALESCE(e.is_fixed_rate, 0) = 1
                           THEN (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) * err.return_qty
                           ELSE GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                             * ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END))
                             * err.return_qty
                         END AS rental_amount
                  FROM `equipment_rent_returns` err
                  LEFT JOIN `user` u ON err.created_by = u.id
                  LEFT JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
                  LEFT JOIN `equipment` e ON eri.equipment_id = e.id
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
            $extraDayAmount = floatval($row['extra_day_amount'] ?? 0);
            $penaltyAmount = floatval($row['penalty_amount'] ?? 0);
            $penaltyPercentage = floatval($row['penalty_percentage'] ?? 0);
            $settleCalc = ($rentalAmount + $extraDayAmount + $damageAmount + $penaltyAmount) - $depositForReturn;
            $additionalPayment = $settleCalc > 0 ? $settleCalc : 0;
            $refundAmount = $settleCalc < 0 ? abs($settleCalc) : 0;

            $row['deposit_for_return'] = round($depositForReturn, 2);
            $row['rental_amount'] = round($rentalAmount, 2);
            $row['settle_amount_calc'] = round($settleCalc, 2);
            $row['additional_payment'] = round($additionalPayment, 2);
            $row['refund_amount'] = round($refundAmount, 2);
            $row['extra_day_amount'] = round($extraDayAmount, 2);
            $row['penalty_amount'] = round($penaltyAmount, 2);
            $row['penalty_percentage'] = round($penaltyPercentage, 2);

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

    /**
     * Get the latest return date/time (as string) across all items for a rent.
     */
    public static function getLatestReturnDateTimeByRentId($rent_id)
    {
        $db = Database::getInstance();
        $rent_id = (int) $rent_id;

        $query = "SELECT err.return_date, err.return_time
                  FROM equipment_rent_returns err
                  INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                  WHERE eri.rent_id = {$rent_id}
                  ORDER BY err.return_date DESC, err.return_time DESC, err.id DESC
                  LIMIT 1";

        $row = mysqli_fetch_assoc($db->readQuery($query));

        if (!$row || empty($row['return_date'])) {
            return null;
        }

        $date = $row['return_date'];
        $time = $row['return_time'] ?? null;
        return $time ? ($date . ' ' . $time) : $date;
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
