<?php

class RentType
{
    public $id;
    public $equipment_id;
    public $name;
    public $price;
    public $deposit_amount;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `rent_type` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->equipment_id = $result['equipment_id'];
                $this->name = $result['name'];
                $this->price = $result['price'];
                $this->deposit_amount = $result['deposit_amount'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `rent_type` (
            `equipment_id`, `name`, `price`, `deposit_amount`
        ) VALUES (
            '$this->equipment_id', '$this->name', '$this->price', '$this->deposit_amount'
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
        $query = "UPDATE `rent_type` SET 
            `equipment_id` = '$this->equipment_id', 
            `name` = '$this->name',
            `price` = '$this->price', 
            `deposit_amount` = '$this->deposit_amount'
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
        $query = "DELETE FROM `rent_type` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `rent_type` ORDER BY name ASC";
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
        $query = "SELECT * FROM `rent_type` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'] ?? 0;
    }

    public function getByEquipmentId($equipment_id)
    {
        $query = "SELECT * FROM `rent_type` WHERE `equipment_id` = '" . (int) $equipment_id . "'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function fetchForDataTable($request)
    {
        $db = Database::getInstance();

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 100;
        $search = $request['search']['value'] ?? '';

        // Total records
        $totalSql = "SELECT COUNT(*) as total FROM rent_type";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (name LIKE '%$search%')";
        }

        // Filtered records
        $filteredSql = "SELECT COUNT(*) as filtered FROM rent_type $where";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "SELECT rt.*, e.item_name as equipment_name 
                FROM rent_type rt 
                LEFT JOIN equipment e ON rt.equipment_id = e.id 
                $where ORDER BY rt.id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "equipment_id" => $row['equipment_id'],
                "equipment_name" => $row['equipment_name'] ?? '',
                "name" => $row['name'],
                "price" => $row['price'],
                "deposit_amount" => $row['deposit_amount']
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
