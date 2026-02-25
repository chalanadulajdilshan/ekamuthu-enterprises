<?php

class IssueNote
{
    public $id;
    public $issue_note_code;
    public $rent_invoice_id;
    public $customer_id;
    public $issue_date;
    public $issue_status;
    public $remarks;
    public $department_id;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `issue_notes` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->issue_note_code = $result['issue_note_code'];
                $this->rent_invoice_id = $result['rent_invoice_id'];
                $this->customer_id = $result['customer_id'];
                $this->issue_date = $result['issue_date'];
                $this->issue_status = $result['issue_status'];
                $this->remarks = $result['remarks'];
                $this->department_id = $result['department_id'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `issue_notes` (
            `issue_note_code`, `rent_invoice_id`, `customer_id`, `issue_date`, `issue_status`, `remarks`, `department_id`
        ) VALUES (
            '" . $db->escapeString($this->issue_note_code) . "',
            '" . (int) $this->rent_invoice_id . "',
            '" . (int) $this->customer_id . "',
            '" . $db->escapeString($this->issue_date) . "',
            '" . $db->escapeString($this->issue_status) . "',
            '" . $db->escapeString($this->remarks) . "',
            " . ($this->department_id ? (int)$this->department_id : "NULL") . "
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
        $query = "UPDATE `issue_notes` SET
            `rent_invoice_id` = '" . (int) $this->rent_invoice_id . "',
            `customer_id` = '" . (int) $this->customer_id . "',
            `issue_date` = '" . $db->escapeString($this->issue_date) . "',
            `issue_status` = '" . $db->escapeString($this->issue_status) . "',
            `remarks` = '" . $db->escapeString($this->remarks) . "',
            `department_id` = " . ($this->department_id ? (int)$this->department_id : "NULL") . "
            WHERE `id` = " . (int) $this->id;

        return $db->readQuery($query) ? true : false;
    }

    public function delete()
    {
        $ITEM = new IssueNoteItem(null);
        $ITEM->deleteByIssueNoteId($this->id);

        $query = "DELETE FROM `issue_notes` WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function getItems()
    {
        $ITEM = new IssueNoteItem(null);
        return $ITEM->getByIssueNoteId($this->id);
    }

    public function getLastID()
    {
        $query = "SELECT * FROM `issue_notes` ORDER BY `id` DESC LIMIT 1";
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
        $totalSql = "SELECT COUNT(*) as total FROM issue_notes";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (i.issue_note_code LIKE '%$search%' OR er.bill_number LIKE '%$search%' OR cm.name LIKE '%$search%')";
        }

        $excludeReturned = isset($request['exclude_returned']) && $request['exclude_returned'] == 'true';

        // Base query with totals
        $baseQuery = "SELECT i.*, er.bill_number, cm.name as customer_name, cm.code as customer_code,
                      (SELECT SUM(issued_quantity) FROM issue_note_items WHERE issue_note_id = i.id) as total_issued,
                       (SELECT SUM(iri.return_quantity) FROM issue_return_items iri 
                       INNER JOIN issue_returns ir ON iri.return_id = ir.id 
                       WHERE ir.issue_note_id = i.id) as total_returned,
                       dm.name as department_name
                       FROM issue_notes i
                       LEFT JOIN equipment_rent er ON i.rent_invoice_id = er.id
                       LEFT JOIN customer_master cm ON i.customer_id = cm.id
                       LEFT JOIN department_master dm ON i.department_id = dm.id
                      $where";

        if ($excludeReturned) {
            $baseQuery .= " HAVING (total_issued > IFNULL(total_returned, 0))";
        }

        // Filtered records count
        $filteredSql = "SELECT COUNT(*) as filtered FROM ($baseQuery) as sub";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "$baseQuery ORDER BY i.id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        $statusLabels = [
            'pending' => '<span class="badge bg-soft-secondary font-size-12">Pending</span>',
            'issued' => '<span class="badge bg-soft-success font-size-12">Issued</span>',
            'cancelled' => '<span class="badge bg-soft-danger font-size-12">Cancelled</span>'
        ];

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $statusLabel = $statusLabels[$row['issue_status']] ?? $row['issue_status'];

            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "issue_note_code" => $row['issue_note_code'],
                "rent_invoice_ref" => $row['bill_number'],
                "customer_name" => $row['customer_code'] . ' - ' . $row['customer_name'],
                "department" => $row['department_name'] ?? '-',
                "issue_date" => $row['issue_date'],
                "status" => $statusLabel,
                "created_at" => $row['created_at']
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
