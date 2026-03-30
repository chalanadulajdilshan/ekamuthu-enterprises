<?php

class SupplierMaster
{
    public $id;
    public $code;
    public $name;
    public $address;
    public $mobile_number;
    public $mobile_number_2;
    public $email;
    public $contact_person;
    public $contact_person_number;
    public $credit_limit;
    public $outstanding;
    public $is_active;
    public $remark;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `supplier_master` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_assoc($db->readQuery($query));

            if ($result) {
                foreach ($result as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $code = $db->escapeString($this->code ?? '');
        $name = $db->escapeString($this->name ?? '');
        $address = $db->escapeString($this->address ?? '');
        $mobile_number = $db->escapeString($this->mobile_number ?? '');
        $mobile_number_2 = $db->escapeString($this->mobile_number_2 ?? '');
        $email = $db->escapeString($this->email ?? '');
        $contact_person = $db->escapeString($this->contact_person ?? '');
        $contact_person_number = $db->escapeString($this->contact_person_number ?? '');
        $credit_limit = is_numeric($this->credit_limit) ? $this->credit_limit : 0;
        $outstanding = is_numeric($this->outstanding) ? $this->outstanding : 0;
        $is_active = $this->is_active ?? 1;
        $remark = $db->escapeString($this->remark ?? '');

        $query = "INSERT INTO `supplier_master` (
                    `code`, `name`, `address`, `mobile_number`, `mobile_number_2`, `email`, `contact_person`, `contact_person_number`,
                    `credit_limit`, `outstanding`, `is_active`, `remark`
                ) VALUES (
                    '{$code}', '{$name}', '{$address}', '{$mobile_number}', '{$mobile_number_2}', '{$email}', '{$contact_person}', '{$contact_person_number}',
                    '{$credit_limit}', '{$outstanding}', '{$is_active}', '{$remark}'
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
        $code = $db->escapeString($this->code ?? '');
        $name = $db->escapeString($this->name ?? '');
        $address = $db->escapeString($this->address ?? '');
        $mobile_number = $db->escapeString($this->mobile_number ?? '');
        $mobile_number_2 = $db->escapeString($this->mobile_number_2 ?? '');
        $email = $db->escapeString($this->email ?? '');
        $contact_person = $db->escapeString($this->contact_person ?? '');
        $contact_person_number = $db->escapeString($this->contact_person_number ?? '');
        $credit_limit = is_numeric($this->credit_limit) ? $this->credit_limit : 0;
        $outstanding = is_numeric($this->outstanding) ? $this->outstanding : 0;
        $is_active = $this->is_active ?? 1;
        $remark = $db->escapeString($this->remark ?? '');

        $query = "UPDATE `supplier_master` SET 
                    `code` = '{$code}', 
                    `name` = '{$name}', 
                    `address` = '{$address}', 
                    `mobile_number` = '{$mobile_number}', 
                    `mobile_number_2` = '{$mobile_number_2}', 
                    `email` = '{$email}', 
                    `contact_person` = '{$contact_person}', 
                    `contact_person_number` = '{$contact_person_number}', 
                    `credit_limit` = '{$credit_limit}', 
                    `outstanding` = '{$outstanding}', 
                    `is_active` = '{$is_active}', 
                    `remark` = '{$remark}'
                WHERE `id` = '{$this->id}'";

        return $db->readQuery($query);
    }

    public function delete()
    {
        $query = "DELETE FROM `supplier_master` WHERE `id` = '{$this->id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `supplier_master` ORDER BY name ASC";
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
        $query = "SELECT * FROM `supplier_master` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $res = $db->readQuery($query);
        if (mysqli_num_rows($res) > 0) {
            $result = mysqli_fetch_array($res);
            return $result['id'];
        }
        return 0;
    }

    public function fetchForDataTable($request)
    {
        $db = Database::getInstance();

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 100;
        $search = $db->escapeString($request['search']['value'] ?? '');

        // Total records
        $totalSql = "SELECT COUNT(*) as total FROM supplier_master";
        $totalQuery = $db->readQuery($totalSql);
        $totalRow = mysqli_fetch_assoc($totalQuery);
        $totalData = $totalRow['total'];

        // Search filter
        $sql = "SELECT * FROM supplier_master WHERE 1=1 ";

        if (!empty($search)) {
            $sql .= " AND (
                name LIKE '%$search%'
                OR code LIKE '%$search%'
                OR mobile_number LIKE '%$search%'
                OR email LIKE '%$search%'
            ) ";
        }

        $filteredQuery = $db->readQuery($sql);
        $filteredData = mysqli_num_rows($filteredQuery);

        // Add pagination
        $sql .= " ORDER BY id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = $start + 1;
        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "code" => $row['code'],
                "name" => $row['name'],
                "address" => $row['address'],
                "mobile_number" => $row['mobile_number'],
                "mobile_number_2" => $row['mobile_number_2'],
                "email" => $row['email'],
                "contact_person" => $row['contact_person'],
                "contact_person_number" => $row['contact_person_number'],
                "credit_limit" => number_format($row['credit_limit'] ?? 0, 2),
                "outstanding" => number_format($row['outstanding'] ?? 0, 2),
                "is_active" => $row['is_active'],
                "status" => $row['is_active'],
                "status_label" => $row['is_active'] == 1 
                    ? '<span class="badge bg-success">Active</span>' 
                    : '<span class="badge bg-danger">Inactive</span>',
                "remark" => $row['remark']
            ];

            $data[] = $nestedData;
            $key++;
        }

        return [
            "draw" => intval($request['draw'] ?? 0),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($filteredData),
            "data" => $data
        ];
    }

    public static function searchSuppliers($search)
    {
        $db = Database::getInstance();
        $search = $db->escapeString($search);
        $query = "SELECT * FROM supplier_master 
                WHERE (code LIKE '%$search%' OR name LIKE '%$search%') ";

        $result = $db->readQuery($query);

        $suppliers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $suppliers[] = $row;
        }

        return $suppliers;
    }
}
