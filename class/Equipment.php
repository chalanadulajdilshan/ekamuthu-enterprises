<?php

class Equipment
{
    public $id;
    public $code;
    public $item_name;
    public $category;
    public $serial_number;
    public $damage;
    public $size;
    public $rent_one_day;
    public $deposit_one_day;
    public $rent_one_month;
    public $value;
    public $quantity;
    public $image_name;
    public $remark;

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
                $this->damage = $result['damage'];
                $this->size = $result['size'];
                $this->rent_one_day = $result['rent_one_day'];
                $this->deposit_one_day = $result['deposit_one_day'];
                $this->rent_one_month = $result['rent_one_month'];
                $this->value = $result['value'];
                $this->quantity = $result['quantity'];
                $this->image_name = $result['image_name'];
                $this->remark = $result['remark'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `equipment` (
            `code`, `item_name`, `category`, `serial_number`, `damage`, `size`, `rent_one_day`, `deposit_one_day`, `rent_one_month`, `value`, `quantity`, `image_name`, `remark`
        ) VALUES (
            '$this->code', '$this->item_name', '$this->category', '$this->serial_number', '$this->damage', '$this->size', '$this->rent_one_day', '$this->deposit_one_day', '$this->rent_one_month', '$this->value', '$this->quantity', '$this->image_name', '$this->remark'
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
            `damage` = '$this->damage', 
            `size` = '$this->size',
            `rent_one_day` = '$this->rent_one_day',
            `deposit_one_day` = '$this->deposit_one_day',
            `rent_one_month` = '$this->rent_one_month',
            `value` = '$this->value',
            `quantity` = '$this->quantity',
            `image_name` = '$this->image_name',
            `remark` = '$this->remark'
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
            $this->damage = $result['damage'];
            $this->size = $result['size'];
            $this->rent_one_day = $result['rent_one_day'];
            $this->deposit_one_day = $result['deposit_one_day'];
            $this->rent_one_month = $result['rent_one_month'];
            $this->value = $result['value'];
            $this->quantity = $result['quantity'];
            $this->image_name = $result['image_name'];
            $this->remark = $result['remark'];
            return true;
        }
        return false;
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
                "damage" => $row['damage'],
                "size" => $row['size'],
                "rent_one_day" => $row['rent_one_day'],
                "deposit_one_day" => $row['deposit_one_day'],
                "rent_one_month" => $row['rent_one_month'],
                "value" => $row['value'],
                "quantity" => $row['quantity'],
                "image_name" => $row['image_name'],
                "remark" => $row['remark']
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
