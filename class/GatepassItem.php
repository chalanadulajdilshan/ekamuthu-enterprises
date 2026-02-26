<?php

class GatepassItem
{
    public $id;
    public $gatepass_id;
    public $equipment_id;
    public $sub_equipment_id;
    public $quantity;
    public $remarks;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `gatepass_items` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                foreach ($result as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `gatepass_items` (
            `gatepass_id`, `equipment_id`, `sub_equipment_id`, `quantity`, `remarks`
        ) VALUES (
            '{$this->gatepass_id}', '{$this->equipment_id}', " . 
            ($this->sub_equipment_id ? "'{$this->sub_equipment_id}'" : "NULL") . ", 
            '{$this->quantity}', '{$db->escapeString($this->remarks)}'
        )";

        $result = $db->readQuery($query);
        return $result ? mysqli_insert_id($db->DB_CON) : false;
    }

    public function getByGatepassId($gatepass_id)
    {
        $query = "SELECT gi.*, e.item_name as equipment_name, se.code as sub_equipment_code 
                  FROM `gatepass_items` gi
                  JOIN `equipment` e ON gi.equipment_id = e.id
                  LEFT JOIN `sub_equipment` se ON gi.sub_equipment_id = se.id
                  WHERE gi.gatepass_id = " . (int)$gatepass_id;
        
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function deleteByGatepassId($gatepass_id)
    {
        $query = "DELETE FROM `gatepass_items` WHERE `gatepass_id` = " . (int)$gatepass_id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }
}
