<?php

class IssueReturnNoteItem
{
    public $id;
    public $return_id;
    public $equipment_id;
    public $sub_equipment_id;
    public $issued_quantity;
    public $return_quantity;
    public $remarks;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `issue_return_items` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->return_id = $result['return_id'];
                $this->equipment_id = $result['equipment_id'];
                $this->sub_equipment_id = $result['sub_equipment_id'];
                $this->issued_quantity = $result['issued_quantity'];
                $this->return_quantity = $result['return_quantity'];
                $this->remarks = $result['remarks'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `issue_return_items` (
            `return_id`, `equipment_id`, `sub_equipment_id`, `issued_quantity`, `return_quantity`, `remarks`
        ) VALUES (
            '" . (int) $this->return_id . "',
            '" . (int) $this->equipment_id . "',
            " . ($this->sub_equipment_id ? (int) $this->sub_equipment_id : "NULL") . ",
            '" . (int) $this->issued_quantity . "',
            '" . (int) $this->return_quantity . "',
            '" . $db->escapeString($this->remarks) . "'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);
            return $this->id;
        }
        return false;
    }

    public function getByReturnId($return_id)
    {
        $query = "SELECT iri.*, 
                  e.code as equipment_code, e.item_name as equipment_name,
                  se.code as sub_equipment_code
                  FROM `issue_return_items` iri
                  LEFT JOIN `equipment` e ON iri.equipment_id = e.id
                  LEFT JOIN `sub_equipment` se ON iri.sub_equipment_id = se.id
                  WHERE iri.return_id = " . (int) $return_id . "
                  ORDER BY iri.id ASC";
        
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $array_res[] = $row;
        }

        return $array_res;
    }

    public function deleteByReturnId($return_id)
    {
        $query = "DELETE FROM `issue_return_items` WHERE `return_id` = " . (int) $return_id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }
}
