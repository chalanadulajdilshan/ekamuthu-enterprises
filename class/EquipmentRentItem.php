<?php

class EquipmentRentItem
{
    public $id;
    public $rent_id;
    public $equipment_id;
    public $sub_equipment_id;
    public $rental_date;
    public $quantity;
    public $total_returned_qty;
    public $pending_qty;
    public $rent_type;
    public $duration;
    public $amount;
    public $deposit_amount;
    public $status;
    public $department_id;
    public $remark;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `equipment_rent_items` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->rent_id = $result['rent_id'];
                $this->equipment_id = $result['equipment_id'];
                $this->sub_equipment_id = $result['sub_equipment_id'];
                $this->rental_date = $result['rental_date'];
                $this->quantity = $result['quantity'];
                $this->total_returned_qty = $result['total_returned_qty'] ?? 0;
                $this->pending_qty = $result['pending_qty'] ?? $result['quantity'];
                $this->rent_type = $result['rent_type'];
                $this->duration = $result['duration'];
                $this->amount = $result['amount'];
                $this->deposit_amount = $result['deposit_amount'] ?? 0;
                $this->status = $result['status'];
                $this->department_id = $result['department_id'] ?? null;
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        // Handle NULL sub_equipment_id for "No Sub-Items" equipment
        $subEquipmentValue = (!empty($this->sub_equipment_id) && $this->sub_equipment_id != '0') 
            ? "'{$this->sub_equipment_id}'" 
            : "NULL";
        
        $deptValue = !empty($this->department_id) ? "'{$this->department_id}'" : "NULL";
        $now = date('Y-m-d H:i:s');

        $query = "INSERT INTO `equipment_rent_items` (
            `rent_id`, `equipment_id`, `sub_equipment_id`, `rental_date`, `quantity`, `rent_type`, `duration`, `amount`, `status`, `remark`, `deposit_amount`, `total_returned_qty`, `pending_qty`, `department_id`, `created_at`
        ) VALUES (
            '$this->rent_id', '$this->equipment_id', $subEquipmentValue, '$this->rental_date', '$this->quantity', '$this->rent_type', '$this->duration', '$this->amount', '$this->status', '$this->remark', '$this->deposit_amount', '0', '$this->quantity', $deptValue, '$now'
        )";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);

            // Always update sub_equipment rental status when a unit is attached
            if ($this->sub_equipment_id) {
                // Default to rent unless explicitly returned
                $newStatus = ($this->status === 'returned') ? 'available' : 'rent';
                $this->updateSubEquipmentStatus($this->sub_equipment_id, $newStatus);
            } else {
                // Bulk item - deduct from department stock if status is rented
                if ($this->status === 'rented') {
                    $this->updateBulkStock($this->equipment_id, $this->department_id, $this->quantity, true);
                }
            }

            return $this->id;
        } else {
            return false;
        }
    }

    public function update()
    {
        // Get old status before update
        $oldItem = new EquipmentRentItem($this->id);
        $oldSubEquipmentId = $oldItem->sub_equipment_id;
        $oldStatus = $oldItem->status;
        $now = date('Y-m-d H:i:s');

        // Handle NULL sub_equipment_id for "No Sub-Items" equipment
        $subEquipmentValue = (!empty($this->sub_equipment_id) && $this->sub_equipment_id != '0') 
            ? "'{$this->sub_equipment_id}'" 
            : "NULL";

        $deptValue = !empty($this->department_id) ? "'{$this->department_id}'" : "NULL";

        $query = "UPDATE `equipment_rent_items` SET 
            `equipment_id` = '$this->equipment_id', 
            `sub_equipment_id` = $subEquipmentValue,
            `rental_date` = '$this->rental_date', 
            `quantity` = '$this->quantity',
            `rent_type` = '$this->rent_type',
            `duration` = '$this->duration',
            `amount` = '$this->amount',
            `deposit_amount` = '$this->deposit_amount',
            `status` = '$this->status',
            `department_id` = $deptValue,
            `remark` = '$this->remark',
            `updated_at` = '$now'
            WHERE `id` = '$this->id'";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            // Handle sub_equipment status changes
            if ($oldSubEquipmentId != $this->sub_equipment_id) {
                // Sub equipment changed - release old, mark new as rented
                if ($oldSubEquipmentId && $oldStatus === 'rented') {
                    $this->updateSubEquipmentStatus($oldSubEquipmentId, 'available');
                }
                if ($this->sub_equipment_id && $this->status === 'rented') {
                    $this->updateSubEquipmentStatus($this->sub_equipment_id, 'rent');
                }
            } elseif ($oldStatus !== $this->status) {
                // Same sub equipment but status changed
                $newRentalStatus = ($this->status === 'returned') ? 'available' : 'rent';
                $this->updateSubEquipmentStatus($this->sub_equipment_id, $newRentalStatus);
            }

            // Handle bulk item stock changes
            if (!$this->sub_equipment_id) {
                if ($oldItem->equipment_id != $this->equipment_id || $oldItem->department_id != $this->department_id) {
                    // Equipment or Department changed - restore old, deduct new
                    if ($oldStatus === 'rented') {
                        $this->updateBulkStock($oldItem->equipment_id, $oldItem->department_id, $oldItem->quantity, false);
                    }
                    if ($this->status === 'rented') {
                        $this->updateBulkStock($this->equipment_id, $this->department_id, $this->quantity, true);
                    }
                } elseif ($oldItem->quantity != $this->quantity || $oldStatus !== $this->status) {
                    // Same equipment/dept but qty or status changed
                    if ($oldStatus === 'rented' && $this->status === 'rented') {
                        // Just qty changed
                        $diff = $this->quantity - $oldItem->quantity;
                        if ($diff != 0) {
                            $this->updateBulkStock($this->equipment_id, $this->department_id, abs($diff), $diff > 0);
                        }
                    } elseif ($oldStatus === 'rented' && $this->status !== 'rented') {
                        // Changed from rented to something else - restore stock
                        $this->updateBulkStock($oldItem->equipment_id, $oldItem->department_id, $oldItem->quantity, false);
                    } elseif ($oldStatus !== 'rented' && $this->status === 'rented') {
                        // Changed to rented - deduct stock
                        $this->updateBulkStock($this->equipment_id, $this->department_id, $this->quantity, true);
                    }
                }
            }
            
            return true;
        } else {
            return false;
        }
    }

    public function delete()
    {
        // Release sub_equipment before deleting
        if ($this->sub_equipment_id && $this->status === 'rented') {
            $this->updateSubEquipmentStatus($this->sub_equipment_id, 'available');
        } elseif (!$this->sub_equipment_id && $this->status === 'rented') {
            // Restore bulk stock
            $this->updateBulkStock($this->equipment_id, $this->department_id, $this->quantity, false);
        }

        $query = "DELETE FROM `equipment_rent_items` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getByRentId($rent_id)
    {
        $query = "SELECT eri.*, 
                  e.code as equipment_code, e.item_name as equipment_name, e.no_sub_items,
                  se.code as sub_equipment_code,
                  dm.name as department_name,
                  eri.quantity - COALESCE(eri.total_returned_qty, 0) as pending_qty
                  FROM `equipment_rent_items` eri
                  LEFT JOIN `equipment` e ON eri.equipment_id = e.id
                  LEFT JOIN `sub_equipment` se ON eri.sub_equipment_id = se.id
                  LEFT JOIN `department_master` dm ON eri.department_id = dm.id
                  WHERE eri.rent_id = " . (int) $rent_id . "
                  ORDER BY eri.id ASC";
        
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $array_res[] = $row;
        }

        return $array_res;
    }

    public function deleteByRentId($rent_id)
    {
        // First release all sub_equipment
        $items = $this->getByRentId($rent_id);
        foreach ($items as $item) {
            if ($item['sub_equipment_id'] && $item['status'] === 'rented') {
                $this->updateSubEquipmentStatus($item['sub_equipment_id'], 'available');
            } elseif (!$item['sub_equipment_id'] && $item['status'] === 'rented') {
                $this->updateBulkStock($item['equipment_id'], $item['department_id'], $item['quantity'], false);
            }
        }

        $query = "DELETE FROM `equipment_rent_items` WHERE `rent_id` = " . (int) $rent_id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    private function updateSubEquipmentStatus($sub_equipment_id, $status)
    {
        $query = "UPDATE `sub_equipment` SET `rental_status` = '$status' WHERE `id` = " . (int) $sub_equipment_id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    private function updateBulkStock($equipment_id, $department_id, $quantity, $isRenting = true)
    {
        if (!$equipment_id || !$department_id) {
            return false;
        }

        $db = Database::getInstance();
        $operator = $isRenting ? "+" : "-";
        
        // Update the rented_qty field in sub_equipment (qty remains as Total Stock)
        $query = "UPDATE `sub_equipment` 
                  SET `rented_qty` = `rented_qty` $operator " . (int)$quantity . " 
                  WHERE `equipment_id` = " . (int)$equipment_id . " 
                  AND `department_id` = " . (int)$department_id;
        
        return $db->readQuery($query);
    }

    public static function getAvailableSubEquipment($equipment_id, $department_id = null)
    {
        $where = "se.equipment_id = " . (int) $equipment_id . " AND se.rental_status = 'available'";

        if ($department_id) {
            $where .= " AND se.department_id = " . (int) $department_id;
        }

        $query = "SELECT se.*, e.code as equipment_code, e.item_name as equipment_name, dm.name as department_name
                  FROM `sub_equipment` se
                  LEFT JOIN `equipment` e ON se.equipment_id = e.id
                  LEFT JOIN `department_master` dm ON se.department_id = dm.id
                  WHERE $where
                  ORDER BY se.code ASC";
        
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $array_res[] = $row;
        }

        return $array_res;
    }

    public static function getAllSubEquipmentWithStatus($equipment_id)
    {
        $query = "SELECT se.*, e.code as equipment_code, e.item_name as equipment_name,
                  CASE WHEN se.rental_status = 'rent' THEN 
                    (SELECT CONCAT(er.bill_number, ' - ', cm.name) 
                     FROM equipment_rent_items eri 
                     JOIN equipment_rent er ON eri.rent_id = er.id
                     LEFT JOIN customer_master cm ON er.customer_id = cm.id
                     WHERE eri.sub_equipment_id = se.id AND eri.status = 'rented' 
                     LIMIT 1)
                  ELSE NULL END as rented_to
                  FROM `sub_equipment` se
                  LEFT JOIN `equipment` e ON se.equipment_id = e.id
                  WHERE se.equipment_id = " . (int) $equipment_id . "
                  ORDER BY se.code ASC";
        
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $array_res[] = $row;
        }

        return $array_res;
    }

    public static function isSubEquipmentAvailable($sub_equipment_id, $exclude_item_id = null)
    {
        $query = "SELECT rental_status FROM `sub_equipment` WHERE `id` = " . (int) $sub_equipment_id;
        $db = Database::getInstance();
        $result = mysqli_fetch_assoc($db->readQuery($query));
        
        if (!$result) {
            return false;
        }
        
        // If excluding an item (for updates), check if this sub_equipment is only rented by that item
        if ($exclude_item_id && $result['rental_status'] === 'rent') {
            $checkQuery = "SELECT id FROM `equipment_rent_items` 
                          WHERE `sub_equipment_id` = " . (int) $sub_equipment_id . " 
                          AND `status` = 'rented' 
                          AND `id` != " . (int) $exclude_item_id;
            $checkResult = mysqli_fetch_assoc($db->readQuery($checkQuery));
            return !$checkResult; // Available if no other item is renting it
        }
        
        return $result['rental_status'] === 'available';
    }

    public function markAsReturned()
    {
        $this->status = 'returned';
        
        if ($this->update()) {
            return true;
        }
        return false;
    }

    public function getReturns()
    {
        $db = Database::getInstance();
        $query = "SELECT err.*, u.name as created_by_name
                  FROM equipment_rent_returns err
                  LEFT JOIN user u ON err.created_by = u.id
                  WHERE err.rent_item_id = " . (int) $this->id . "
                  ORDER BY err.return_date DESC, err.id DESC";
        
        $result = $db->readQuery($query);
        $returns = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $returns[] = $row;
        }
        
        return $returns;
    }

    public function getPendingQty()
    {
        return max(0, $this->quantity - ($this->total_returned_qty ?? 0));
    }

    public function isFullyReturned()
    {
        return $this->getPendingQty() <= 0;
    }
}
