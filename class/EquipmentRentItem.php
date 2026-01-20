<?php

class EquipmentRentItem
{
    public $id;
    public $rent_id;
    public $equipment_id;
    public $sub_equipment_id;
    public $rental_date;
    public $return_date;
    public $quantity;
    public $rent_type;
    public $duration;
    public $amount;
    public $deposit_amount;
    public $status;
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
                $this->return_date = $result['return_date'];
                $this->quantity = $result['quantity'];
                $this->rent_type = $result['rent_type'];
                $this->duration = $result['duration'];
                $this->amount = $result['amount'];
                $this->deposit_amount = $result['deposit_amount'] ?? 0;
                $this->status = $result['status'];
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `equipment_rent_items` (
            `rent_id`, `equipment_id`, `sub_equipment_id`, `rental_date`, `return_date`, `quantity`, `rent_type`, `duration`, `amount`, `status`, `remark`, `deposit_amount`
        ) VALUES (
            '$this->rent_id', '$this->equipment_id', '$this->sub_equipment_id', '$this->rental_date', " .
            ($this->return_date ? "'$this->return_date'" : "NULL") . ", '$this->quantity', '$this->rent_type', '$this->duration', '$this->amount', '$this->status', '$this->remark', '$this->deposit_amount'
        )";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);
            
            // Update sub_equipment rental status to 'rented'
            if ($this->status === 'rented' && $this->sub_equipment_id) {
                $this->updateSubEquipmentStatus($this->sub_equipment_id, 'rented');
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

        $query = "UPDATE `equipment_rent_items` SET 
            `equipment_id` = '$this->equipment_id', 
            `sub_equipment_id` = '$this->sub_equipment_id',
            `rental_date` = '$this->rental_date', 
            `return_date` = " . ($this->return_date ? "'$this->return_date'" : "NULL") . ", 
            `quantity` = '$this->quantity',
            `rent_type` = '$this->rent_type',
            `duration` = '$this->duration',
            `amount` = '$this->amount',
            `deposit_amount` = '$this->deposit_amount',
            `status` = '$this->status',
            `remark` = '$this->remark'
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
                    $this->updateSubEquipmentStatus($this->sub_equipment_id, 'rented');
                }
            } elseif ($oldStatus !== $this->status) {
                // Same sub equipment but status changed
                $newRentalStatus = ($this->status === 'returned') ? 'available' : 'rented';
                $this->updateSubEquipmentStatus($this->sub_equipment_id, $newRentalStatus);
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
        }

        $query = "DELETE FROM `equipment_rent_items` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getByRentId($rent_id)
    {
        $query = "SELECT eri.*, 
                  e.code as equipment_code, e.item_name as equipment_name,
                  se.code as sub_equipment_code
                  FROM `equipment_rent_items` eri
                  LEFT JOIN `equipment` e ON eri.equipment_id = e.id
                  LEFT JOIN `sub_equipment` se ON eri.sub_equipment_id = se.id
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

    public static function getAvailableSubEquipment($equipment_id)
    {
        $query = "SELECT se.*, e.code as equipment_code, e.item_name as equipment_name
                  FROM `sub_equipment` se
                  LEFT JOIN `equipment` e ON se.equipment_id = e.id
                  WHERE se.equipment_id = " . (int) $equipment_id . "
                  AND se.rental_status = 'available'
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
                  CASE WHEN se.rental_status = 'rented' THEN 
                    (SELECT CONCAT(er.code, ' - ', cm.name) 
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
        if ($exclude_item_id && $result['rental_status'] === 'rented') {
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
        $this->return_date = date('Y-m-d');
        
        if ($this->update()) {
            return true;
        }
        return false;
    }
}
