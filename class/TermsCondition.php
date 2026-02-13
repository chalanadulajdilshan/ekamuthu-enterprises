<?php

class TermsCondition
{
    public $id;
    public $sort_order;
    public $description;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `terms_conditions` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->sort_order = $result['sort_order'];
                $this->description = $result['description'];
                $this->is_active = $result['is_active'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `terms_conditions` (
            `sort_order`, `description`, `is_active`
        ) VALUES (
            '$this->sort_order', '$this->description', '$this->is_active'
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
        $query = "UPDATE `terms_conditions` SET 
            `sort_order` = '$this->sort_order', 
            `description` = '$this->description',
            `is_active` = '$this->is_active'
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
        $query = "DELETE FROM `terms_conditions` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `terms_conditions` ORDER BY sort_order ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getActive()
    {
        $query = "SELECT * FROM `terms_conditions` WHERE `is_active` = 1 ORDER BY sort_order ASC";
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
        $query = "SELECT * FROM `terms_conditions` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'] ?? 0;
    }

    public function fetchForDataTable($request)
    {
        $db = Database::getInstance();

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 100;
        $search = $request['search']['value'] ?? '';

        // Total records
        $totalSql = "SELECT COUNT(*) as total FROM terms_conditions";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (description LIKE '%$search%')";
        }

        // Filtered records
        $filteredSql = "SELECT COUNT(*) as filtered FROM terms_conditions $where";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "SELECT * FROM terms_conditions $where ORDER BY sort_order ASC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $statusLabel = $row['is_active'] == 1
                ? '<span class="badge bg-soft-success font-size-12">Active</span>'
                : '<span class="badge bg-soft-danger font-size-12">Inactive</span>';

            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "sort_order" => $row['sort_order'],
                "description" => $row['description'],
                "is_active" => $row['is_active'],
                "status_label" => $statusLabel
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
