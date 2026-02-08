
<?php

class EquipmentRent
{
    public $id;
    public $bill_number;
    public $customer_id;
    public $equipment_id;
    public $rental_date;
    public $received_date;
    public $status;
    public $quantity;
    public $remark;
    public $transport_cost;
    public $deposit_total;
    public $payment_type_id;
    public $total_items;
    public $cheque_number;
    public $cheque_date;
    public $cheque_branch_id;
    public $transfer_branch_id;
    public $bank_account_number;
    public $bank_reference;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `equipment_rent` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->bill_number = $result['bill_number'];
                $this->customer_id = $result['customer_id'];
                $this->equipment_id = $result['equipment_id'] ?? null;
                $this->rental_date = $result['rental_date'];
                $this->received_date = $result['received_date'];
                $this->status = $result['status'];
                $this->quantity = $result['quantity'] ?? 0;
                $this->transport_cost = $result['transport_cost'] ?? 0;
                $this->deposit_total = $result['deposit_total'] ?? 0;
                $this->payment_type_id = $result['payment_type_id'] ?? null;
                $this->remark = $result['remark'];
                $this->total_items = $result['total_items'] ?? 0;
                $this->cheque_number = $result['cheque_number'] ?? null;
                $this->cheque_date = $result['cheque_date'] ?? null;
                $this->cheque_branch_id = $result['cheque_branch_id'] ?? null;
                $this->transfer_branch_id = $result['transfer_branch_id'] ?? null;
                $this->bank_account_number = $result['bank_account_number'] ?? null;
                $this->bank_reference = $result['bank_reference'] ?? null;
                $this->created_by = $result['created_by'] ?? null;
                $this->created_at = $result['created_at'] ?? null;
                $this->updated_at = $result['updated_at'] ?? null;
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `equipment_rent` (
            `bill_number`, `customer_id`, `rental_date`, `received_date`, `status`, `remark`, `total_items`, `transport_cost`, `deposit_total`, `payment_type_id`, `cheque_number`, `cheque_date`, `cheque_branch_id`, `transfer_branch_id`, `bank_account_number`, `bank_reference`, `created_by`
        ) VALUES (
            '$this->bill_number', '$this->customer_id', '$this->rental_date', " .
            ($this->received_date ? "'$this->received_date'" : "NULL") . ", '$this->status', '$this->remark', '$this->total_items', '$this->transport_cost', '$this->deposit_total', " .
            ($this->payment_type_id ? "'$this->payment_type_id'" : "NULL") . ", " .
            ($this->cheque_number ? "'" . addslashes($this->cheque_number) . "'" : "NULL") . ", " .
            ($this->cheque_date ? "'$this->cheque_date'" : "NULL") . ", " .
            ($this->cheque_branch_id ? "'$this->cheque_branch_id'" : "NULL") . ", " .
            ($this->transfer_branch_id ? "'$this->transfer_branch_id'" : "NULL") . ", " .
            ($this->bank_account_number ? "'" . addslashes($this->bank_account_number) . "'" : "NULL") . ", " .
            ($this->bank_reference ? "'" . addslashes($this->bank_reference) . "'" : "NULL") . ", " .
            ($this->created_by ? "'$this->created_by'" : "NULL") .
            "
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
            `bill_number` = '$this->bill_number', 
            `customer_id` = '$this->customer_id',
            `rental_date` = '$this->rental_date', 
            `received_date` = " . ($this->received_date ? "'$this->received_date'" : "NULL") . ", 
            `status` = '$this->status', 
            `remark` = '$this->remark',
            `total_items` = '$this->total_items',
            `transport_cost` = '$this->transport_cost',
            `deposit_total` = '$this->deposit_total',
            `payment_type_id` = " . ($this->payment_type_id ? "'$this->payment_type_id'" : "NULL") . ",
            `cheque_number` = " . ($this->cheque_number ? "'" . addslashes($this->cheque_number) . "'" : "NULL") . ",
            `cheque_date` = " . ($this->cheque_date ? "'$this->cheque_date'" : "NULL") . ",
            `cheque_branch_id` = " . ($this->cheque_branch_id ? "'$this->cheque_branch_id'" : "NULL") . ",
            `transfer_branch_id` = " . ($this->transfer_branch_id ? "'$this->transfer_branch_id'" : "NULL") . ",
            `bank_account_number` = " . ($this->bank_account_number ? "'" . addslashes($this->bank_account_number) . "'" : "NULL") . ",
            `bank_reference` = " . ($this->bank_reference ? "'" . addslashes($this->bank_reference) . "'" : "NULL") . "
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
        // First delete all rent items (this will also release sub_equipment)
        $RENT_ITEM = new EquipmentRentItem(null);
        $RENT_ITEM->deleteByRentId($this->id);
        
        $query = "DELETE FROM `equipment_rent` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function updateTotalItems()
    {
        $query = "UPDATE `equipment_rent` SET `total_items` = (
            SELECT COUNT(*) FROM `equipment_rent_items` WHERE `rent_id` = '$this->id'
        ) WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getItems()
    {
        $RENT_ITEM = new EquipmentRentItem(null);
        return $RENT_ITEM->getByRentId($this->id);
    }

    public function hasActiveRentals()
    {
        $query = "SELECT COUNT(*) as count FROM `equipment_rent_items` 
                  WHERE `rent_id` = '$this->id' AND `status` = 'rented'";
        $db = Database::getInstance();
        $result = mysqli_fetch_assoc($db->readQuery($query));
        return ($result['count'] ?? 0) > 0;
    }

    public function markAllReturned()
    {
        $items = $this->getItems();
        foreach ($items as $item) {
            if ($item['status'] === 'rented') {
                $RENT_ITEM = new EquipmentRentItem($item['id']);
                $RENT_ITEM->markAsReturned();
            }
        }
        $this->status = 'returned';
        $this->received_date = date('Y-m-d');
        return $this->update();
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

    public function getByBillNumber($bill_number)
    {
        $query = "SELECT * FROM `equipment_rent` WHERE `bill_number` = '$bill_number' LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result) {
            $this->id = $result['id'];
            $this->bill_number = $result['bill_number'];
            $this->customer_id = $result['customer_id'];
            $this->equipment_id = $result['equipment_id'];
            $this->rental_date = $result['rental_date'];
            $this->received_date = $result['received_date'];
            $this->status = $result['status'];
            $this->quantity = $result['quantity'];
            $this->transport_cost = $result['transport_cost'];
            $this->deposit_total = $result['deposit_total'];
            $this->payment_type_id = $result['payment_type_id'] ?? null;
            $this->cheque_number = $result['cheque_number'] ?? null;
            $this->cheque_date = $result['cheque_date'] ?? null;
            $this->cheque_branch_id = $result['cheque_branch_id'] ?? null;
            $this->transfer_branch_id = $result['transfer_branch_id'] ?? null;
            $this->bank_account_number = $result['bank_account_number'] ?? null;
            $this->bank_reference = $result['bank_reference'] ?? null;
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
            $where .= " AND (er.bill_number LIKE '%$search%' OR cm.name LIKE '%$search%' OR cm.code LIKE '%$search%' OR cm.nic LIKE '%$search%')";
        }

        if (!empty($request['exclude_issued'])) {
            $where .= " AND er.issue_status != 2";
        }

        if (!empty($request['exclude_returned'])) {
            $where .= " AND er.status != 'returned'";
        }

        // Base FROM/JOIN for reuse
        $fromJoin = "FROM equipment_rent er LEFT JOIN customer_master cm ON er.customer_id = cm.id";

        // pending_only filter handling
        $pendingOnlyHaving = "";
        if (!empty($request['pending_only'])) {
            $pendingOnlyHaving = "HAVING outstanding_items > 0";
        } elseif (!empty($request['returned_only'])) {
            $pendingOnlyHaving = "HAVING outstanding_items = 0";
        }

        // Filtered records count (respect pending_only)
        $filteredSql = "SELECT COUNT(*) as filtered FROM (
                            SELECT er.id,
                                   (SELECT COUNT(*) FROM equipment_rent_items eri
                                       WHERE eri.rent_id = er.id AND (eri.status = 'rented' OR (eri.quantity - COALESCE((SELECT SUM(return_qty) FROM equipment_rent_returns err WHERE err.rent_item_id = eri.id),0)) > 0)
                                   ) AS outstanding_items
                            $fromJoin
                            $where
                            $pendingOnlyHaving
                        ) sub";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Data query with outstanding counts
        $sql = "SELECT er.*, cm.name as customer_name, cm.code as customer_code,
                       (SELECT COUNT(*) FROM equipment_rent_items eri WHERE eri.rent_id = er.id) AS total_items,
                       (SELECT COUNT(*) FROM equipment_rent_items eri
                          WHERE eri.rent_id = er.id AND (eri.status = 'rented' OR (eri.quantity - COALESCE((SELECT SUM(return_qty) FROM equipment_rent_returns err WHERE err.rent_item_id = eri.id),0)) > 0)
                       ) AS outstanding_items
                $fromJoin
                $where
                $pendingOnlyHaving
                ORDER BY er.id DESC LIMIT $start, $length";
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
                "bill_number" => $row['bill_number'],
                "customer_id" => $row['customer_id'],
                "customer_name" => ($row['customer_code'] ?? '') . ' - ' . ($row['customer_name'] ?? ''),
                "customer_code" => $row['customer_code'],
                "rental_date" => $row['rental_date'],
                "received_date" => $row['received_date'],
                "status" => $row['status'],
                "status_label" => $statusLabel,
                "total_items" => $row['total_items'],
                "outstanding_items" => $row['outstanding_items'],
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
