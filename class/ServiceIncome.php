<?php

class ServiceIncome
{
    public $id;
    public $name;
    public $amount;
    public $remark;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `service_income` WHERE `id` = " . (int)$id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->name = $result['name'];
                $this->amount = $result['amount'];
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `service_income` (`name`, `amount`, `remark`, `created_at`) 
                  VALUES (
                    '{$this->name}', 
                    '{$this->amount}', 
                    '{$this->remark}', 
                    NOW()
                  )";
        $db = Database::getInstance();
        return $db->readQuery($query) ? mysqli_insert_id($db->DB_CON) : false;
    }

    public function update()
    {
        $query = "UPDATE `service_income` 
                  SET 
                    `name` = '{$this->name}', 
                    `amount` = '{$this->amount}', 
                    `remark` = '{$this->remark}'
                  WHERE `id` = '{$this->id}'";

        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function delete()
    {
        $query = "DELETE FROM `service_income` WHERE `id` = '{$this->id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `service_income` ORDER BY `created_at` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }
    
    public function getLastID()
    {
        $query = "SELECT * FROM `service_income` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'];
    }
}
?>
