<?php

class Equipment
{
    public $id;
    public $code;
    public $item_name;
    public $category;
    public $serial_number;
    public $is_condition;
    public $availability_status;
    public $queue;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `equipment` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->code = $result['code'];
                $this->item_name = $result['item_name'];
                $this->category = $result['category'];
                $this->serial_number = $result['serial_number'];
                $this->is_condition = $result['is_condition'];
                $this->availability_status = $result['availability_status'];
                $this->queue = $result['queue'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `equipment` (
            `code`, `item_name`, `category`, `serial_number`, `is_condition`, `availability_status`, `queue`
        ) VALUES (
            '$this->code', '$this->item_name', '$this->category', '$this->serial_number', '$this->is_condition', '$this->availability_status', '$this->queue'
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
        $query = "UPDATE `equipment` SET 
            `code` = '$this->code', 
            `item_name` = '$this->item_name',
            `category` = '$this->category', 
            `serial_number` = '$this->serial_number', 
            `is_condition` = '$this->is_condition', 
            `availability_status` = '$this->availability_status',
            `queue` = '$this->queue'
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
        $query = "DELETE FROM `equipment` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `equipment` ORDER BY item_name ASC";
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
        $query = "SELECT * FROM `equipment` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'] ?? 0;
    }

    public function getByCode($code)
    {
        $query = "SELECT * FROM `equipment` WHERE `code` = '$code' LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result) {
            $this->id = $result['id'];
            $this->code = $result['code'];
            $this->item_name = $result['item_name'];
            $this->category = $result['category'];
            $this->serial_number = $result['serial_number'];
            $this->is_condition = $result['is_condition'];
            $this->availability_status = $result['availability_status'];
            $this->queue = $result['queue'];
            return true;
        }
        return false;
    }

    public function getAvailable()
    {
        $query = "SELECT * FROM `equipment` WHERE `availability_status` = 1 ORDER BY item_name ASC";
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
        $totalSql = "SELECT COUNT(*) as total FROM equipment";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (item_name LIKE '%$search%' OR code LIKE '%$search%' OR serial_number LIKE '%$search%')";
        }

        // Filtered records
        $filteredSql = "SELECT COUNT(*) as filtered FROM equipment $where";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "SELECT * FROM equipment $where ORDER BY id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "code" => $row['code'],
                "item_name" => $row['item_name'],
                "category" => $row['category'],
                "serial_number" => $row['serial_number'],
                "is_condition" => $row['is_condition'],
                "availability_status" => $row['availability_status'],
                "queue" => $row['queue'],
                "status_label" => $row['availability_status'] == 1
                    ? '<span class="badge bg-soft-success font-size-12">Available</span>'
                    : '<span class="badge bg-soft-danger font-size-12">Unavailable</span>'
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
