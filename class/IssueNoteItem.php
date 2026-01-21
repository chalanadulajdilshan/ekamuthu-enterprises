<?php

class IssueNoteItem
{
    public $id;
    public $issue_note_id;
    public $equipment_id;
    public $sub_equipment_id;
    public $ordered_quantity;
    public $issued_quantity;
    public $rent_type;
    public $duration;
    public $remarks;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `issue_note_items` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->issue_note_id = $result['issue_note_id'];
                $this->equipment_id = $result['equipment_id'];
                $this->sub_equipment_id = $result['sub_equipment_id'];
                $this->ordered_quantity = $result['ordered_quantity'];
                $this->issued_quantity = $result['issued_quantity'];
                $this->rent_type = $result['rent_type'];
                $this->duration = $result['duration'];
                $this->remarks = $result['remarks'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `issue_note_items` (
            `issue_note_id`, `equipment_id`, `sub_equipment_id`, `ordered_quantity`, `issued_quantity`, `rent_type`, `duration`, `remarks`
        ) VALUES (
            '" . (int) $this->issue_note_id . "',
            '" . (int) $this->equipment_id . "',
            " . ($this->sub_equipment_id ? (int) $this->sub_equipment_id : "NULL") . ",
            '" . (int) $this->ordered_quantity . "',
            '" . (int) $this->issued_quantity . "',
            '" . $db->escapeString($this->rent_type) . "',
            '" . (float) $this->duration . "',
            '" . $db->escapeString($this->remarks) . "'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);
            return $this->id;
        }
        return false;
    }

    public function deleteByIssueNoteId($issue_note_id)
    {
        $query = "DELETE FROM `issue_note_items` WHERE `issue_note_id` = " . (int) $issue_note_id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getByIssueNoteId($issue_note_id)
    {
        $query = "SELECT ini.*, 
                  e.code as equipment_code, e.item_name as equipment_name,
                  se.code as sub_equipment_code
                  FROM `issue_note_items` ini
                  LEFT JOIN `equipment` e ON ini.equipment_id = e.id
                  LEFT JOIN `sub_equipment` se ON ini.sub_equipment_id = se.id
                  WHERE ini.issue_note_id = " . (int) $issue_note_id . "
                  ORDER BY ini.id ASC";
        
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $array_res[] = $row;
        }

        return $array_res;
    }
}
