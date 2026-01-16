<?php

class SubEquipment
{
    public $id;
    public $equipment_id;
    public $code;
    // Name removed

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `sub_equipment` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->equipment_id = $result['equipment_id'];
                $this->code = $result['code'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `sub_equipment` (
            `equipment_id`, `code`
        ) VALUES (
            '$this->equipment_id', '$this->code'
        )";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    public function update()
    {
        $query = "UPDATE `sub_equipment` SET 
            `equipment_id` = '$this->equipment_id', 
            `code` = '$this->code'
            WHERE `id` = '$this->id'";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function delete()
    {
        $query = "DELETE FROM `sub_equipment` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT se.*, e.code as equipment_code, e.item_name as equipment_name
                  FROM `sub_equipment` se
                  LEFT JOIN `equipment` e ON se.equipment_id = e.id
                  ORDER BY se.id DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getByEquipmentId($equipment_id)
    {
        $query = "SELECT * FROM `sub_equipment` WHERE `equipment_id` = " . (int) $equipment_id . " ORDER BY id DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getLastID()
    {
        $query = "SELECT * FROM `sub_equipment` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'] ?? 0;
    }

    public function getByCode($code)
    {
        $query = "SELECT * FROM `sub_equipment` WHERE `code` = '$code' LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result) {
            $this->id = $result['id'];
            $this->equipment_id = $result['equipment_id'];
            $this->code = $result['code'];
            return true;
        }
        return false;
    }

    public function fetchForDataTable($request, $equipment_id = null)
    {
        $db = Database::getInstance();

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 100;
        $search = $request['search']['value'] ?? '';

        // Base where clause
        $where = "WHERE 1=1";
        if ($equipment_id) {
            $where .= " AND se.equipment_id = " . (int) $equipment_id;
        }

        // Total records
        $totalSql = "SELECT COUNT(*) as total FROM sub_equipment se $where";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        if (!empty($search)) {
            $where .= " AND (se.code LIKE '%$search%')";
        }

        // Filtered records
        $filteredSql = "SELECT COUNT(*) as filtered FROM sub_equipment se 
                        LEFT JOIN equipment e ON se.equipment_id = e.id $where";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "SELECT se.*, e.code as equipment_code, e.item_name as equipment_name
                FROM sub_equipment se 
                LEFT JOIN equipment e ON se.equipment_id = e.id 
                $where ORDER BY se.id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "equipment_id" => $row['equipment_id'],
                "equipment_name" => ($row['equipment_code'] ?? '') . ' - ' . ($row['equipment_name'] ?? ''),
                "code" => $row['code']
            ];

            $data[] = $nestedData;
            $key++;
        }

        return [
            "draw" => intval($request['draw'] ?? 1),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($filteredData),
            "data" => $data
        ];
    }
}
