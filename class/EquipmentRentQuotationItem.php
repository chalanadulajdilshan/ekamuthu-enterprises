<?php

class EquipmentRentQuotationItem
{
    public $id;
    public $quotation_id;
    public $equipment_id;
    public $sub_equipment_id;
    public $rental_date;
    public $return_date;
    public $quantity;
    public $rent_type;
    public $duration;
    public $unit_price;
    public $amount;
    public $transport;
    public $deposit;
    public $status;
    public $remark;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `equipment_rent_quotation_items` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->quotation_id = $result['quotation_id'];
                $this->equipment_id = $result['equipment_id'];
                $this->sub_equipment_id = $result['sub_equipment_id'];
                $this->rental_date = $result['rental_date'];
                $this->return_date = $result['return_date'];
                $this->quantity = $result['quantity'];
                $this->rent_type = $result['rent_type'];
                $this->duration = $result['duration'];
                $this->unit_price = $result['unit_price'] ?? 0.00;
                $this->amount = $result['amount'];
                $this->transport = $result['transport'] ?? 0.00;
                $this->deposit = $result['deposit'] ?? 0.00;
                $this->status = $result['status'];
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `equipment_rent_quotation_items` (
            `quotation_id`, `equipment_id`, `sub_equipment_id`, `rental_date`, `return_date`, `quantity`, `rent_type`, `duration`, `unit_price`, `amount`, `status`, `remark`
        ) VALUES (
            '$this->quotation_id', '$this->equipment_id', '$this->sub_equipment_id', '$this->rental_date', " .
            ($this->return_date ? "'$this->return_date'" : "NULL") . ", '$this->quantity', '$this->rent_type', '$this->duration', '$this->unit_price', '$this->amount', '$this->status', '$this->remark'
        )";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);
            // NOTE: We do NOT update sub_equipment status for quotations as they don't reserve stock.
            return $this->id;
        } else {
            return false;
        }
    }

    public function update()
    {
        $query = "UPDATE `equipment_rent_quotation_items` SET 
            `equipment_id` = '$this->equipment_id', 
            `sub_equipment_id` = '$this->sub_equipment_id',
            `rental_date` = '$this->rental_date', 
            `return_date` = " . ($this->return_date ? "'$this->return_date'" : "NULL") . ", 
            `quantity` = '$this->quantity',
            `rent_type` = '$this->rent_type',
            `duration` = '$this->duration',
            `unit_price` = '$this->unit_price',
            `amount` = '$this->amount',
            `status` = '$this->status',
            `remark` = '$this->remark'
            WHERE `id` = '$this->id'";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        return $result ? true : false;
    }

    public function delete()
    {
        $query = "DELETE FROM `equipment_rent_quotation_items` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getByQuotationId($quotation_id)
    {
        $query = "SELECT eri.*, 
                  e.code as equipment_code, e.item_name as equipment_name,
                  e.rent_one_day, e.deposit_one_day, e.rent_one_month,
                  se.code as sub_equipment_code
                  FROM `equipment_rent_quotation_items` eri
                  LEFT JOIN `equipment` e ON eri.equipment_id = e.id
                  LEFT JOIN `sub_equipment` se ON eri.sub_equipment_id = se.id
                  WHERE eri.quotation_id = " . (int) $quotation_id . "
                  ORDER BY eri.id ASC";
        
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $array_res[] = $row;
        }

        return $array_res;
    }

    public function deleteByQuotationId($quotation_id)
    {
        $query = "DELETE FROM `equipment_rent_quotation_items` WHERE `quotation_id` = " . (int) $quotation_id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }
}
