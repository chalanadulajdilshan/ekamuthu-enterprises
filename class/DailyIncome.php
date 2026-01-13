<?php

class DailyIncome
{
    public $id;
    public $amount;
    public $date;
    public $remark;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `daily_income` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->amount = $result['amount'];
                $this->date = $result['date'];
                $this->remark = $result['remark'];
            }
        }
    }

    public function create()
    {
        // Escape values to prevent SQL injection
        $amount = (float) $this->amount;
        $date = mysqli_real_escape_string((Database::getInstance())->DB_CON, $this->date);
        $remark = mysqli_real_escape_string((Database::getInstance())->DB_CON, $this->remark);

        $query = "INSERT INTO `daily_income` (
            `amount`, `date`, `remark`
        ) VALUES (
            '$amount', '$date', '$remark'
        )";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            $insertId = mysqli_insert_id($db->DB_CON);
            return $insertId;
        } else {
            return false;
        }
    }

    public function update()
    {
        $amount = (float) $this->amount;
        $date = mysqli_real_escape_string((Database::getInstance())->DB_CON, $this->date);
        $remark = mysqli_real_escape_string((Database::getInstance())->DB_CON, $this->remark);
        $id = (int) $this->id;

        $query = "UPDATE `daily_income` SET 
            `amount` = '$amount', 
            `date` = '$date',
            `remark` = '$remark'
            WHERE `id` = '$id'";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function delete()
    {
        $id = (int) $this->id;
        $query = "DELETE FROM `daily_income` WHERE `id` = '$id'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function all()
    {
        $query = "SELECT * FROM `daily_income` ORDER BY `id` DESC";
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
        $query = "SELECT * FROM `daily_income` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result && isset($result['id'])) {
            return $result['id'];
        } else {
            return 0;
        }
    }

    public function getTotalIncome($dateFrom = null, $dateTo = null)
    {
        $where = "WHERE 1=1";

        if ($dateFrom) {
            $dateFrom = mysqli_real_escape_string((Database::getInstance())->DB_CON, $dateFrom);
            $where .= " AND date >= '$dateFrom'";
        }
        if ($dateTo) {
            $dateTo = mysqli_real_escape_string((Database::getInstance())->DB_CON, $dateTo);
            $where .= " AND date <= '$dateTo'";
        }

        $query = "SELECT SUM(amount) as total_amount FROM `daily_income` $where";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        return $result['total_amount'] ? $result['total_amount'] : 0;
    }

    public function getIncomeByDateRange($dateFrom, $dateTo)
    {
        $dateFrom = mysqli_real_escape_string((Database::getInstance())->DB_CON, $dateFrom);
        $dateTo = mysqli_real_escape_string((Database::getInstance())->DB_CON, $dateTo);

        $query = "SELECT * FROM `daily_income` WHERE date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY date DESC, id DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }
}
