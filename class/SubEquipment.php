<?php

class SubEquipment
{
    public $id;
    public $equipment_id;
    public $department_id;
    public $code;
    public $rental_status;
    public $qty;
    public $rented_qty;
    public $is_repair;
    public $purchase_date;
    public $value;
    public $image;
    public $brand;
    public $company_customer_name;
    public $condition_type;

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
                $this->rented_qty = $result['rented_qty'] ?? 0;
                $this->is_repair = $result['is_repair'] ?? 0;
                $this->purchase_date = $result['purchase_date'] ?? null;
                $this->value = $result['value'] ?? 0.00;
                $this->image = $result['image'] ?? null;
                $this->brand = $result['brand'] ?? null;
                $this->company_customer_name = $result['company_customer_name'] ?? null;
                $this->condition_type = $result['condition_type'] ?? 'new';
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $this->is_repair = $this->is_repair ?? 0;
        $this->purchase_date = $this->purchase_date ? "'$this->purchase_date'" : "NULL";
        $this->value = $this->value ?? 0.00;
        $this->image = $this->image ? "'" . mysqli_real_escape_string($db->DB_CON, $this->image) . "'" : "NULL";
        $this->brand = $this->brand ? "'" . mysqli_real_escape_string($db->DB_CON, $this->brand) . "'" : "NULL";
        $this->company_customer_name = $this->company_customer_name ? "'" . mysqli_real_escape_string($db->DB_CON, $this->company_customer_name) . "'" : "NULL";
        $this->condition_type = $this->condition_type ?? 'new';
        
        $query = "INSERT INTO `sub_equipment` (
            `equipment_id`, `department_id`, `code`, `rental_status`, `qty`, `rented_qty`, `is_repair`,
            `purchase_date`, `value`, `image`, `brand`, `company_customer_name`, `condition_type`
        ) VALUES (
            '$this->equipment_id', '$this->department_id', '$this->code', '$this->rental_status', '$this->qty', '$this->rented_qty', '$this->is_repair',
            $this->purchase_date, '$this->value', $this->image, $this->brand, $this->company_customer_name, '$this->condition_type'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    public function update()
    {
        $db = Database::getInstance();
        $purchase_date = $this->purchase_date ? "'$this->purchase_date'" : "NULL";
        $value = $this->value ?? 0.00;
        $image = $this->image ? "'" . mysqli_real_escape_string($db->DB_CON, $this->image) . "'" : "NULL";
        $brand = $this->brand ? "'" . mysqli_real_escape_string($db->DB_CON, $this->brand) . "'" : "NULL";
        $company_customer_name = $this->company_customer_name ? "'" . mysqli_real_escape_string($db->DB_CON, $this->company_customer_name) . "'" : "NULL";
        $condition_type = $this->condition_type ?? 'new';
        
        $query = "UPDATE `sub_equipment` SET 
            `equipment_id` = '$this->equipment_id', 
            `department_id` = '$this->department_id',
            `code` = '$this->code',
            `rental_status` = '$this->rental_status',
            `qty` = '$this->qty',
            `rented_qty` = '$this->rented_qty',
            `is_repair` = '$this->is_repair',
            `purchase_date` = $purchase_date,
            `value` = '$value',
            `image` = $image,
            `brand` = $brand,
            `company_customer_name` = $company_customer_name,
            `condition_type` = '$condition_type'
            WHERE `id` = '$this->id'";

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
            $this->is_repair = $result['is_repair'] ?? 0;
            $this->purchase_date = $result['purchase_date'] ?? null;
            $this->value = $result['value'] ?? 0.00;
            $this->image = $result['image'] ?? null;
            $this->brand = $result['brand'] ?? null;
            $this->company_customer_name = $result['company_customer_name'] ?? null;
            $this->condition_type = $result['condition_type'] ?? 'new';
            return true;
        }
        return false;
    }

    // ... existing methods ...

    public static function updateRepairStatus($code, $is_repair)
    {
        $db = Database::getInstance();
        $code = mysqli_real_escape_string($db->DB_CON, $code);
        $is_repair = (int) $is_repair;
        $rental_status = ($is_repair === 1) ? 'repair' : 'available';
        
        // Update both is_repair and rental_status
        $query = "UPDATE `sub_equipment` SET `is_repair` = $is_repair, `rental_status` = '$rental_status' WHERE `code` = '$code'";
        return $db->readQuery($query);
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

        // Search filter (DataTables passes search[value])
        $search = '';
        if (!empty($request['search']['value'])) {
            $search = mysqli_real_escape_string($db->DB_CON, $request['search']['value']);
            $where .= " AND (se.code LIKE '%$search%' 
                        OR se.brand LIKE '%$search%'
                        OR se.company_customer_name LIKE '%$search%'
                        OR e.code LIKE '%$search%'
                        OR e.item_name LIKE '%$search%'
                        OR dm.name LIKE '%$search%')";
        }

        // Filtered records
        $filteredSql = "SELECT COUNT(*) as filtered FROM sub_equipment se 
                        LEFT JOIN equipment e ON se.equipment_id = e.id
                        LEFT JOIN department_master dm ON se.department_id = dm.id
                        $where";
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
                "rented_qty" => $row['rented_qty'] ?? 0,
                "purchase_date" => $row['purchase_date'] ?? null,
                "value" => $row['value'] ?? 0.00,
                "image" => $row['image'] ?? null,
                "brand" => $row['brand'] ?? null,
                "company_customer_name" => $row['company_customer_name'] ?? null,
                "condition_type" => $row['condition_type'] ?? 'new',
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

    /**
     * Update the parent equipment quantity to reflect the total sub-equipment quantity.
     * Uses SUM(qty) to respect per-row quantities rather than only counting rows.
     */
    public static function syncEquipmentQuantity($equipment_id)
    {
        if (!$equipment_id) {
            return false;
        }

        $db = Database::getInstance();
        $equipment_id = (int) $equipment_id;

        $countSql = "SELECT COALESCE(SUM(qty), 0) AS total_qty FROM sub_equipment WHERE equipment_id = $equipment_id";
        $result = $db->readQuery($countSql);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        $totalQty = $row ? (int) $row['total_qty'] : 0;

        $updateSql = "UPDATE equipment SET quantity = $totalQty WHERE id = $equipment_id";
        return $db->readQuery($updateSql);
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

    public static function getGlobalAvailableStock($equipment_id, $is_bulk = false)
    {
        $db = Database::getInstance();
        if ($is_bulk) {
            // For bulk items: Total Stock in all departments - Total Rented in all departments
            // Assuming sub_equipment tracks stock per department
            $query = "SELECT SUM(qty) - SUM(rented_qty) as available 
                      FROM sub_equipment 
                      WHERE equipment_id = " . (int)$equipment_id;
        } else {
            // For serialized items: Count of items with 'available' status
            $query = "SELECT COUNT(*) as available 
                      FROM sub_equipment 
                      WHERE equipment_id = " . (int)$equipment_id . " 
                      AND rental_status = 'available'";
        }
        
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return $result ? (float)$result['available'] : 0;
    }
}
