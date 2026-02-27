<?php

class RepairJob
{
    public $id;
    public $job_code;
    public $item_type;
    public $machine_code;
    public $machine_name;
    public $customer_name;
    public $customer_address;
    public $customer_phone;
    public $item_breakdown_date;
    public $job_status;
    public $employee_id;
    public $technical_issue;
    public $repair_charge;
    public $commission_percentage;
    public $commission_amount;
    public $total_cost;
    public $remark;
    public $is_outsource;
    public $outsource_name;
    public $outsource_address;
    public $outsource_phone;
    public $outsource_cost;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `repair_jobs` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->job_code = $result['job_code'];
                $this->item_type = $result['item_type'];
                $this->machine_code = $result['machine_code'] ?? '';
                $this->machine_name = $result['machine_name'] ?? '';
                $this->customer_name = $result['customer_name'];
                $this->customer_address = $result['customer_address'];
                $this->customer_phone = $result['customer_phone'];
                $this->item_breakdown_date = $result['item_breakdown_date'];
                $this->job_status = $result['job_status'];
                $this->employee_id = $result['employee_id'];
                $this->technical_issue = $result['technical_issue'];
                $this->repair_charge = $result['repair_charge'];
                $this->commission_percentage = $result['commission_percentage'];
                $this->commission_amount = $result['commission_amount'];
                $this->total_cost = $result['total_cost'];
                $this->remark = $result['remark'];
                $this->is_outsource = $result['is_outsource'] ?? 0;
                $this->outsource_name = $result['outsource_name'] ?? '';
                $this->outsource_address = $result['outsource_address'] ?? '';
                $this->outsource_cost = $result['outsource_cost'] ?? 0;
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `repair_jobs` (
            `job_code`, `item_type`, `machine_code`, `machine_name`, `customer_name`, `customer_address`, `customer_phone`,
            `item_breakdown_date`, `job_status`, `employee_id`, `technical_issue`, `repair_charge`, `commission_percentage`, `commission_amount`, `total_cost`, `remark`,
            `is_outsource`, `outsource_name`, `outsource_address`, `outsource_phone`, `outsource_cost`
        ) VALUES (
            '" . $db->escapeString($this->job_code) . "',
            '" . $db->escapeString($this->item_type) . "',
            '" . $db->escapeString($this->machine_code) . "',
            '" . $db->escapeString($this->machine_name) . "',
            '" . $db->escapeString($this->customer_name) . "',
            '" . $db->escapeString($this->customer_address) . "',
            '" . $db->escapeString($this->customer_phone) . "',
            " . ($this->item_breakdown_date ? "'" . $db->escapeString($this->item_breakdown_date) . "'" : "NULL") . ",
            '" . $db->escapeString($this->job_status) . "',
            " . ($this->employee_id ? "'" . (int) $this->employee_id . "'" : "NULL") . ",
            '" . $db->escapeString($this->technical_issue) . "',
            '" . floatval($this->repair_charge) . "',
            '" . floatval($this->commission_percentage) . "',
            '" . floatval($this->commission_amount) . "',
            '" . floatval($this->total_cost) . "',
            '" . $db->escapeString($this->remark) . "',
            '" . (int) $this->is_outsource . "',
            '" . $db->escapeString($this->outsource_name) . "',
            '" . $db->escapeString($this->outsource_address) . "',
            '" . $db->escapeString($this->outsource_phone) . "',
            '" . floatval($this->outsource_cost) . "'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);
            return $this->id;
        }
        return false;
    }

    public function update()
    {
        $db = Database::getInstance();
        $query = "UPDATE `repair_jobs` SET
            `job_code` = '" . $db->escapeString($this->job_code) . "',
            `item_type` = '" . $db->escapeString($this->item_type) . "',
            `machine_code` = '" . $db->escapeString($this->machine_code) . "',
            `machine_name` = '" . $db->escapeString($this->machine_name) . "',
            `customer_name` = '" . $db->escapeString($this->customer_name) . "',
            `customer_address` = '" . $db->escapeString($this->customer_address) . "',
            `customer_phone` = '" . $db->escapeString($this->customer_phone) . "',
            `item_breakdown_date` = " . ($this->item_breakdown_date ? "'" . $db->escapeString($this->item_breakdown_date) . "'" : "NULL") . ",
            `job_status` = '" . $db->escapeString($this->job_status) . "',
            `employee_id` = " . ($this->employee_id ? "'" . (int) $this->employee_id . "'" : "NULL") . ",
            `technical_issue` = '" . $db->escapeString($this->technical_issue) . "',
            `repair_charge` = '" . floatval($this->repair_charge) . "',
            `commission_percentage` = '" . floatval($this->commission_percentage) . "',
            `commission_amount` = '" . floatval($this->commission_amount) . "',
            `total_cost` = '" . floatval($this->total_cost) . "',
            `remark` = '" . $db->escapeString($this->remark) . "',
            `is_outsource` = '" . (int) $this->is_outsource . "',
            `outsource_name` = '" . $db->escapeString($this->outsource_name) . "',
            `outsource_address` = '" . $db->escapeString($this->outsource_address) . "',
            `outsource_phone` = '" . $db->escapeString($this->outsource_phone) . "',
            `outsource_cost` = '" . floatval($this->outsource_cost) . "'
            WHERE `id` = " . (int) $this->id;

        return $db->readQuery($query) ? true : false;
    }

    public function delete()
    {
        // Delete items first
        $ITEM = new RepairJobItem(null);
        $ITEM->deleteByJobId($this->id);

        $query = "DELETE FROM `repair_jobs` WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getItems()
    {
        $ITEM = new RepairJobItem(null);
        return $ITEM->getByJobId($this->id);
    }

    public function updateTotalCost()
    {
        $query = "UPDATE `repair_jobs` SET `total_cost` = (
            (SELECT COALESCE(SUM(`total_price`), 0) FROM `repair_job_items` WHERE `job_id` = " . (int) $this->id . ") + `repair_charge`
        ) WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function updateDeliveryStatus($statusStr)
    {
        $db = Database::getInstance();
        $query = "UPDATE `repair_jobs` SET 
            `job_status` = '" . $db->escapeString($statusStr) . "' 
            WHERE `id` = " . (int) $this->id;

        return $db->readQuery($query);
    }

    public function getByJobCode($job_code)
    {
        $db = Database::getInstance();
        $query = "SELECT * FROM `repair_jobs` WHERE `job_code` = '" . $db->escapeString($job_code) . "' LIMIT 1";
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result) {
            $this->id = $result['id'];
            $this->job_code = $result['job_code'];
            $this->item_type = $result['item_type'];
            $this->machine_code = $result['machine_code'] ?? '';
            $this->machine_name = $result['machine_name'] ?? '';
            $this->customer_name = $result['customer_name'];
            $this->customer_address = $result['customer_address'];
            $this->customer_phone = $result['customer_phone'];
            $this->item_breakdown_date = $result['item_breakdown_date'];
            $this->job_status = $result['job_status'];
            $this->employee_id = $result['employee_id'];
            $this->technical_issue = $result['technical_issue'];
            $this->repair_charge = $result['repair_charge'];
            $this->commission_percentage = $result['commission_percentage'];
            $this->commission_amount = $result['commission_amount'];
            $this->total_cost = $result['total_cost'];
            $this->remark = $result['remark'];
            $this->is_outsource = $result['is_outsource'] ?? 0;
            $this->outsource_name = $result['outsource_name'] ?? '';
            $this->outsource_address = $result['outsource_address'] ?? '';
            $this->outsource_phone = $result['outsource_phone'] ?? '';
            $this->outsource_cost = $result['outsource_cost'] ?? 0;
            return true;
        }
        return false;
    }

    public function getLastID()
    {
        $query = "SELECT * FROM `repair_jobs` ORDER BY `id` DESC LIMIT 1";
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
        $totalSql = "SELECT COUNT(*) as total FROM repair_jobs";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (job_code LIKE '%$search%' OR customer_name LIKE '%$search%' OR customer_phone LIKE '%$search%')";
        }

        // Filtered records
        $filteredSql = "SELECT COUNT(*) as filtered FROM repair_jobs $where";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "SELECT * FROM repair_jobs $where ORDER BY id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        $statusLabels = [
            'pending' => '<span class="badge bg-soft-danger text-danger" style="font-size:13px;">Pending</span>',
            'checking' => '<span class="badge bg-soft-info" style="font-size:13px;">Checking</span>',
            'in_progress' => '<span class="badge bg-soft-warning" style="font-size:13px;">In Progress</span>',
            'completed' => '<span class="badge bg-soft-success" style="font-size:13px;">Completed</span>',
            'cannot_repair' => '<span class="badge bg-soft-danger" style="font-size:13px;">Cannot Repair</span>',
            'delivered' => '<span class="badge bg-soft-primary" style="font-size:13px;">Delivered</span>'
        ];

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $statusLabel = $statusLabels[$row['job_status']] ?? $row['job_status'];

            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "job_code" => $row['job_code'],
                "item_type" => ucfirst($row['item_type']),
                "customer_name" => $row['customer_name'],
                "customer_phone" => $row['customer_phone'],
                "item_breakdown_date" => $row['item_breakdown_date'],
                "job_status" => $row['job_status'],
                "status_label" => $statusLabel,
                "total_cost" => number_format($row['total_cost'], 2)
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
