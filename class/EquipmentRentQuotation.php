<?php

class EquipmentRentQuotation
{
    public $id;
    public $quotation_number;
    public $customer_id;
    public $customer_name;
    public $equipment_id; // Keeping for compatibility if needed, though items handle this
    public $rental_date;
    public $received_date;
    public $status;
    public $quantity;
    public $remark;
    public $transport_cost;
    public $deposit_total;
    public $total_items;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `equipment_rent_quotation` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->quotation_number = $result['quotation_number'];
                $this->customer_id = $result['customer_id'];
                $this->customer_name = $result['customer_name'] ?? '';
                $this->rental_date = $result['rental_date'];
                $this->received_date = $result['received_date'];
                $this->status = $result['status'];
                $this->quantity = $result['quantity'] ?? 0;
                $this->remark = $result['remark'];
                $this->transport_cost = $result['transport_cost'] ?? 0;
                $this->deposit_total = $result['deposit_total'] ?? 0;
                $this->total_items = $result['total_items'] ?? 0;
                $this->created_at = $result['created_at'] ?? null;
                $this->updated_at = $result['updated_at'] ?? null;
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `equipment_rent_quotation` (
            `quotation_number`, `customer_id`, `customer_name`, `rental_date`, `received_date`, `status`, `remark`, `transport_cost`, `deposit_total`, `total_items`
        ) VALUES (
            '$this->quotation_number', '$this->customer_id', '$this->customer_name', '$this->rental_date', " .
            ($this->received_date ? "'$this->received_date'" : "NULL") . ", '$this->status', '$this->remark', '$this->transport_cost', '$this->deposit_total', '$this->total_items'
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
        $query = "UPDATE `equipment_rent_quotation` SET 
            `quotation_number` = '$this->quotation_number', 
            `customer_id` = '$this->customer_id',
            `customer_name` = '$this->customer_name',
            `rental_date` = '$this->rental_date', 
            `received_date` = " . ($this->received_date ? "'$this->received_date'" : "NULL") . ", 
            `status` = '$this->status', 
            `remark` = '$this->remark',
            `transport_cost` = '$this->transport_cost',
            `deposit_total` = '$this->deposit_total',
            `total_items` = '$this->total_items'
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
        // First delete all quotation items
        $QUOTATION_ITEM = new EquipmentRentQuotationItem(null);
        $QUOTATION_ITEM->deleteByQuotationId($this->id);
        
        $query = "DELETE FROM `equipment_rent_quotation` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function updateTotalItems()
    {
        $query = "UPDATE `equipment_rent_quotation` SET `total_items` = (
            SELECT COUNT(*) FROM `equipment_rent_quotation_items` WHERE `quotation_id` = '$this->id'
        ) WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getItems()
    {
        $QUOTATION_ITEM = new EquipmentRentQuotationItem(null);
        return $QUOTATION_ITEM->getByQuotationId($this->id);
    }

    public function all()
    {
        $query = "SELECT er.*, er.customer_name AS manual_customer_name, cm.name as customer_name, cm.code as customer_code
                  FROM `equipment_rent_quotation` er
                  LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
                  ORDER BY er.id DESC";
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
        $totalSql = "SELECT COUNT(*) as total FROM equipment_rent_quotation";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (er.quotation_number LIKE '%$search%' OR er.customer_name LIKE '%$search%' OR cm.name LIKE '%$search%' OR cm.code LIKE '%$search%')";
        }

        // Filtered records
        $filteredSql = "SELECT COUNT(*) as filtered FROM equipment_rent_quotation er 
                        LEFT JOIN customer_master cm ON er.customer_id = cm.id $where";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "SELECT er.*, er.customer_name AS manual_customer_name, cm.name as db_customer_name, cm.code as customer_code
                FROM equipment_rent_quotation er 
                LEFT JOIN customer_master cm ON er.customer_id = cm.id 
                $where ORDER BY er.id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            // Status label
            $statusLabels = [
                'pending' => '<span class="badge bg-soft-warning font-size-12">Pending</span>',
                'approved' => '<span class="badge bg-soft-success font-size-12">Approved</span>',
                'rejected' => '<span class="badge bg-soft-danger font-size-12">Rejected</span>'
            ];
            $statusLabel = isset($statusLabels[$row['status']]) ? $statusLabels[$row['status']] : $row['status'];

            $displayCustomer = '';
            if (!empty($row['customer_id'])) {
                $displayCustomer = trim(($row['customer_code'] ?? '') . ' - ' . ($row['db_customer_name'] ?? ''));
            }
            if (!$displayCustomer) {
                $displayCustomer = $row['manual_customer_name'] ?? $row['customer_code'] ?? '';
            }

            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "quotation_number" => $row['quotation_number'],
                "customer_id" => $row['customer_id'],
                "customer_name" => $displayCustomer,
                "customer_code" => $row['customer_code'],
                "rental_date" => $row['rental_date'],
                "received_date" => $row['received_date'],
                "status" => $row['status'],
                "status_label" => $statusLabel,
                "total_items" => $row['total_items'],
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
