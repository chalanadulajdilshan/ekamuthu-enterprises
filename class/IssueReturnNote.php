<?php

class IssueReturnNote
{
    public $id;
    public $return_code;
    public $issue_note_id;
    public $return_date;
    public $remarks;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `issue_returns` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->return_code = $result['return_code'];
                $this->issue_note_id = $result['issue_note_id'];
                $this->return_date = $result['return_date'];
                $this->remarks = $result['remarks'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $query = "INSERT INTO `issue_returns` (
            `return_code`, `issue_note_id`, `return_date`, `remarks`
        ) VALUES (
            '" . $db->escapeString($this->return_code) . "',
            '" . (int) $this->issue_note_id . "',
            '" . $db->escapeString($this->return_date) . "',
            '" . $db->escapeString($this->remarks) . "'
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
        $query = "UPDATE `issue_returns` SET
            `return_date` = '" . $db->escapeString($this->return_date) . "',
            `remarks` = '" . $db->escapeString($this->remarks) . "'
            WHERE `id` = " . (int) $this->id;

        return $db->readQuery($query) ? true : false;
    }

    public function fetchForDataTable($request)
    {
        $db = Database::getInstance();

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 100;
        $search = $request['search']['value'] ?? '';

        // Total records
        $totalSql = "SELECT COUNT(*) as total FROM issue_returns";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (r.return_code LIKE '%$search%' OR i.issue_note_code LIKE '%$search%' OR cm.name LIKE '%$search%')";
        }

        // Filtered records
        $filteredSql = "SELECT COUNT(*) as filtered FROM issue_returns r
                        LEFT JOIN issue_notes i ON r.issue_note_id = i.id
                        LEFT JOIN customer_master cm ON i.customer_id = cm.id
                        $where";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "SELECT r.*, i.issue_note_code, cm.name as customer_name, cm.code as customer_code
                FROM issue_returns r
                LEFT JOIN issue_notes i ON r.issue_note_id = i.id
                LEFT JOIN customer_master cm ON i.customer_id = cm.id
                $where ORDER BY r.id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "return_code" => $row['return_code'],
                "issue_note_ref" => $row['issue_note_code'],
                "customer_name" => $row['customer_code'] . ' - ' . $row['customer_name'],
                "return_date" => $row['return_date'],
                "remarks" => $row['remarks'],
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

    public function getItems()
    {
        $ITEM = new IssueReturnNoteItem(null);
        return $ITEM->getByReturnId($this->id);
    }

    public function delete()
    {
        $ITEM = new IssueReturnNoteItem(null);
        $ITEM->deleteByReturnId($this->id);

        $query = "DELETE FROM `issue_returns` WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }
}
