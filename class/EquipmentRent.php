<?php

class EquipmentRent
{
    public $id;
    public $code;
    public $customer_id;
    public $equipment_id;
    public $rental_date;
    public $received_date;
    public $status;
    public $quantity;
    public $remark;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `equipment_rent` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->code = $result['code'];
                $this->customer_id = $result['customer_id'];
                $this->equipment_id = $result['equipment_id'];
                $this->rental_date = $result['rental_date'];
                $this->received_date = $result['received_date'];
                $this->status = $result['status'];
                $this->quantity = $result['quantity'];
                $this->remark = $result['remark'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `equipment_rent` (
            `code`, `customer_id`, `equipment_id`, `rental_date`, `received_date`, `status`, `quantity`, `remark`
        ) VALUES (
            '$this->code', '$this->customer_id', '$this->equipment_id', '$this->rental_date', " .
            ($this->received_date ? "'$this->received_date'" : "NULL") . ", '$this->status', '$this->quantity', '$this->remark'
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
        $query = "UPDATE `equipment_rent` SET 
            `code` = '$this->code', 
            `customer_id` = '$this->customer_id',
            `equipment_id` = '$this->equipment_id', 
            `rental_date` = '$this->rental_date', 
            `received_date` = " . ($this->received_date ? "'$this->received_date'" : "NULL") . ", 
            `status` = '$this->status', 
            `quantity` = '$this->quantity',
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
        $query = "DELETE FROM `equipment_rent` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT er.*, cm.name as customer_name, cm.code as customer_code, 
                  e.item_name as equipment_name, e.code as equipment_code, e.quantity as available_quantity
                  FROM `equipment_rent` er
                  LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
                  LEFT JOIN `equipment` e ON er.equipment_id = e.id
                  ORDER BY er.id DESC";
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
        $query = "SELECT * FROM `equipment_rent` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'] ?? 0;
    }

    public function getByCode($code)
    {
        $query = "SELECT * FROM `equipment_rent` WHERE `code` = '$code' LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result) {
            $this->id = $result['id'];
            $this->code = $result['code'];
            $this->customer_id = $result['customer_id'];
            $this->equipment_id = $result['equipment_id'];
            $this->rental_date = $result['rental_date'];
            $this->received_date = $result['received_date'];
            $this->status = $result['status'];
            $this->quantity = $result['quantity'];
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
        $totalSql = "SELECT COUNT(*) as total FROM equipment_rent";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (er.code LIKE '%$search%' OR cm.name LIKE '%$search%' OR cm.code LIKE '%$search%' OR e.item_name LIKE '%$search%' OR e.code LIKE '%$search%')";
        }

        // Filtered records
        $filteredSql = "SELECT COUNT(*) as filtered FROM equipment_rent er 
                        LEFT JOIN customer_master cm ON er.customer_id = cm.id 
                        LEFT JOIN equipment e ON er.equipment_id = e.id $where";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "SELECT er.*, cm.name as customer_name, cm.code as customer_code, 
                e.item_name as equipment_name, e.code as equipment_code, e.quantity as available_quantity
                FROM equipment_rent er 
                LEFT JOIN customer_master cm ON er.customer_id = cm.id 
                LEFT JOIN equipment e ON er.equipment_id = e.id 
                $where ORDER BY er.id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            // Status label
            $statusLabels = [
                'available' => '<span class="badge bg-soft-success font-size-12">Available</span>',
                'rented' => '<span class="badge bg-soft-warning font-size-12">Rented</span>',
                'returned' => '<span class="badge bg-soft-info font-size-12">Returned</span>'
            ];
            $statusLabel = isset($statusLabels[$row['status']]) ? $statusLabels[$row['status']] : $row['status'];

            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "code" => $row['code'],
                "customer_id" => $row['customer_id'],
                "customer_name" => ($row['customer_code'] ?? '') . ' - ' . ($row['customer_name'] ?? ''),
                "customer_code" => $row['customer_code'],
                "equipment_id" => $row['equipment_id'],
                "equipment_name" => ($row['equipment_code'] ?? '') . ' - ' . ($row['equipment_name'] ?? ''),
                "equipment_code" => $row['equipment_code'],
                "rental_date" => $row['rental_date'],
                "received_date" => $row['received_date'],
                "status" => $row['status'],
                "status_label" => $statusLabel,
                "quantity" => $row['quantity'],
                "available_quantity" => $row['available_quantity'],
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
