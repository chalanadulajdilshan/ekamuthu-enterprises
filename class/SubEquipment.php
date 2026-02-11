<?php

class SubEquipment
{
    public $id;
    public $equipment_id;
    public $department_id;
    public $code;
    public $rental_status;
    public $qty;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `sub_equipment` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->equipment_id = $result['equipment_id'];
                $this->department_id = $result['department_id'];
                $this->code = $result['code'];
                $this->rental_status = $result['rental_status'];
                $this->qty = $result['qty'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `sub_equipment` (
            `equipment_id`, `department_id`, `code`, `rental_status`, `qty`
        ) VALUES (
            '$this->equipment_id', '$this->department_id', '$this->code', '$this->rental_status', '$this->qty'
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
            `department_id` = '$this->department_id',
            `code` = '$this->code',
            `rental_status` = '$this->rental_status',
            `qty` = '$this->qty'
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

    public function getByCode($code)
    {
        $query = "SELECT * FROM `sub_equipment` WHERE `code` = '$code' LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result) {
            $this->id = $result['id'];
            $this->equipment_id = $result['equipment_id'];
            $this->department_id = $result['department_id'];
            $this->code = $result['code'];
            $this->rental_status = $result['rental_status'];
            $this->qty = $result['qty'];
            return true;
        }
        return false;
    }

    public function fetchForDataTable($request, $equipment_id = null)
    {
        $db = Database::getInstance();

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 10;
        
        // Base where clause
        $where = "WHERE 1=1";

        // Handle "All" records (length = -1 in DataTable)
        $limitClause = "";
        if ($length != -1) {
            $limitClause = "LIMIT $start, $length";
        }
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
        $sql = "SELECT se.*, e.code as equipment_code, e.item_name as equipment_name, dm.name as department_name
                FROM sub_equipment se 
                LEFT JOIN equipment e ON se.equipment_id = e.id 
                LEFT JOIN department_master dm ON se.department_id = dm.id
                $where ORDER BY se.id DESC $limitClause";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "equipment_id" => $row['equipment_id'],
                "equipment_name" => ($row['equipment_code'] ?? '') . ' - ' . ($row['equipment_name'] ?? ''),
                "department_id" => $row['department_id'],
                "department_name" => $row['department_name'] ?? '-',
                "code" => $row['code'],
                "rental_status" => $row['rental_status'],
                "qty" => $row['qty'],
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
    public function checkDuplicate($equipment_id, $department_id)
    {
        $query = "SELECT * FROM `sub_equipment` WHERE `equipment_id` = '$equipment_id' AND `department_id` = '$department_id'";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
