<?php

class StockMaster
{
    public $id;
    public $item_id;
    public $department_id;
    public $quantity;
    public $created_at;
    public $is_active;
    public $remark;

    // Constructor to initialize StockMaster object with ID
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT `id`, `item_id`, `department_id`, `quantity`, `created_at`, `is_active`, `remark` 
                      FROM `stock_master` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->item_id = $result['item_id'];
                $this->department_id = $result['department_id'];
                $this->quantity = $result['quantity'];
                $this->created_at = $result['created_at'];
                $this->is_active = $result['is_active'];
                $this->remark = $result['remark'];
            }
        }
    }

    // Create a new stock_master record
    public function create()
    {
        $query = "INSERT INTO `stock_master` (`item_id`, `department_id`, `quantity`, `created_at`, `is_active`, `remark`) VALUES (
            '" . $this->item_id . "',
            '" . $this->department_id . "',
            '" . $this->quantity . "',
            NOW(),
            '" . $this->is_active . "',
            '" . $this->remark . "')";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    // Update existing stock_master record
    public function update()
    {
        $query = "UPDATE `stock_master` SET 
                    `item_id` = '" . $this->item_id . "',
                    `department_id` = '" . $this->department_id . "',
                    `quantity` = '" . $this->quantity . "',
                    `is_active` = '" . $this->is_active . "',
                    `remark` = '" . $this->remark . "'
                  WHERE `id` = '" . $this->id . "'";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return $this->__construct($this->id);
        } else {
            return false;
        }
    }

    public static function updateQtyByItemAndDepartment($department_id, $item_id, $new_quantity)
    {
        $db = Database::getInstance();

        $query = "UPDATE `stock_master` 
              SET `quantity` = '" . (float) $new_quantity . "', `is_active` = 1
              WHERE `item_id` = '" . (int) $item_id . "' 
              AND `department_id` = '" . (int) $department_id . "'";

        return $db->readQuery($query);
    }

    // Delete a stock_master record
    public function delete()
    {
        $query = "DELETE FROM `stock_master` WHERE `id` = '" . $this->id . "'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Get all records
    public function all()
    {
        $query = "SELECT `id`, `item_id`, `department_id`, `quantity`, `created_at`, `is_active`, `remark` 
                  FROM `stock_master` ORDER BY `created_at` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get only active records
    public function getActive()
    {
        $query = "SELECT `id`, `item_id`, `department_id`, `quantity`, `created_at`, `is_active`, `remark` 
                  FROM `stock_master` WHERE `is_active` = 1 ORDER BY `created_at` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }



    // Get available quantity for item + department
    public static function getAvailableQuantity($department_id, $item_id)
    {
        $query = "SELECT IFNULL(SUM(`quantity`),0) AS quantity FROM `stock_master` 
                  WHERE `department_id` = " . (int) $department_id . " 
                  AND `item_id` = " . (int) $item_id;


        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result ? (int) $result['quantity'] : 0;
    }

    public static function getAvailableQuantityBy($item_id)
    {
        $query = "SELECT 
                sm.department_id, 
                d.name AS department_name, 
                SUM(sm.quantity) AS quantity
              FROM stock_master sm
              LEFT JOIN department_master d ON sm.department_id = d.id
              WHERE sm.item_id = " . (int) $item_id . " 
              AND sm.is_active = 1
              GROUP BY sm.department_id
              ORDER BY d.name ASC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $departments = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $departments[] = [
                'department_name' => $row['department_name'],
                'quantity' => (int) $row['quantity']
            ];
        }

        return $departments;
    }



    public function transferQuantity($item_id, $from_department_id, $to_department_id, $transfer_qty, $remark = '')
    {

        $FROM_DEPARTMENT = new DepartmentMaster($from_department_id);
        $TO_DEPARTMENT = new DepartmentMaster($to_department_id);

        $db = Database::getInstance();

        // 1. Check available quantity in from_department across all rows
        $sumQuery = "SELECT IFNULL(SUM(quantity),0) AS total_qty FROM `stock_master`
                  WHERE `item_id` = '" . (int)$item_id . "'
                    AND `department_id` = '" . (int)$from_department_id . "'";
        $sumRes = mysqli_fetch_assoc($db->readQuery($sumQuery));
        $totalAvailable = (float)$sumRes['total_qty'];

        if ($totalAvailable <= 0) {
            return ['status' => 'error', 'message' => 'No stock found in source department.'];
        }

        if ($totalAvailable < $transfer_qty) {
            return ['status' => 'error', 'message' => 'Insufficient quantity in source department.'];
        }

        // 2. Deduct quantity from source department across rows (FIFO by created_at, id)
        $remainingToDeduct = (float)$transfer_qty;
        $rowsQuery = "SELECT id, quantity FROM `stock_master`
                      WHERE `item_id` = '" . (int)$item_id . "' AND `department_id` = '" . (int)$from_department_id . "'
                      ORDER BY `created_at` ASC, `id` ASC";
        $rowsRes = $db->readQuery($rowsQuery);
        while ($remainingToDeduct > 0 && ($row = mysqli_fetch_assoc($rowsRes))) {
            $rowQty = (float)$row['quantity'];
            if ($rowQty <= 0) {
                continue;
            }
            $deduct = min($remainingToDeduct, $rowQty);
            $newRowQty = $rowQty - $deduct;
            $db->readQuery("UPDATE `stock_master` SET `quantity` = '" . (int)$newRowQty . "', `is_active` = 1, `remark` = '" . $remark . "' WHERE `id` = '" . (int)$row['id'] . "'");
            $remainingToDeduct -= $deduct;
        }

        // 2b. Record out transaction for total transfer qty
        $STOCK_TRANSACTION_OUT = new StockTransaction(NULL);
        $STOCK_TRANSACTION_OUT->item_id = $item_id;
        $STOCK_TRANSACTION_OUT->type = 9; // deduction
        $STOCK_TRANSACTION_OUT->date = date('Y-m-d');
        $STOCK_TRANSACTION_OUT->qty_out = $transfer_qty;
        $STOCK_TRANSACTION_OUT->qty_in = 0;
        $STOCK_TRANSACTION_OUT->remark = 'Quantity deducted to ' . $FROM_DEPARTMENT->name . ' to ' . $TO_DEPARTMENT->name;
        $STOCK_TRANSACTION_OUT->create();

        // 3. Check if item exists in target department
        $queryTo = "SELECT * FROM `stock_master` 
                WHERE `item_id` = '" . (int) $item_id . "' 
                  AND `department_id` = '" . (int) $to_department_id . "' 
                LIMIT 1";
        $resultTo = mysqli_fetch_assoc($db->readQuery($queryTo));

        if ($resultTo) {
            // Exists: Update quantity
            $newQtyTo = $resultTo['quantity'] + $transfer_qty;
            $updateTo = "UPDATE `stock_master` SET `quantity` = '" . (int) $newQtyTo . "', `is_active` = 1, `remark` = '" . $remark . "' WHERE `id` = '" . (int) $resultTo['id'] . "'";
            $db->readQuery($updateTo);

            // Create transaction for addition
            $STOCK_TRANSACTION_IN = new StockTransaction(NULL);
            $STOCK_TRANSACTION_IN->item_id = $item_id;
            $STOCK_TRANSACTION_IN->type = 8; // addition
            $STOCK_TRANSACTION_IN->date = date('Y-m-d');
            $STOCK_TRANSACTION_IN->qty_in = $transfer_qty;
            $STOCK_TRANSACTION_IN->qty_out = 0;
            $STOCK_TRANSACTION_IN->remark = 'Quantity added to ' . $TO_DEPARTMENT->name . ' From ' . $FROM_DEPARTMENT->name;
            $STOCK_TRANSACTION_IN->create();
        } else {
            // Not exists: Insert new record
            $insert = "INSERT INTO `stock_master` (`item_id`, `department_id`, `quantity`, `is_active`, `remark`, `created_at`)
                   VALUES ('" . (int) $item_id . "', '" . (int) $to_department_id . "', '" . (int) $transfer_qty . "', 1, '" . $remark . "', NOW())";
            $db->readQuery($insert);

            $STOCK_TRANSACTION_IN = new StockTransaction(NULL);
            $STOCK_TRANSACTION_IN->item_id = $item_id;
            $STOCK_TRANSACTION_IN->type = 8; // addition
            $STOCK_TRANSACTION_IN->date = date('Y-m-d');
            $STOCK_TRANSACTION_IN->qty_in = $transfer_qty;
            $STOCK_TRANSACTION_IN->qty_out = 0;
            $STOCK_TRANSACTION_IN->remark = 'New Quantity added to ' . $TO_DEPARTMENT->name . ' From ' . $FROM_DEPARTMENT->name;
            $STOCK_TRANSACTION_IN->create();
        }

        // Keep stock_item_tmp aligned using FIFO lots across ARNs
        try {
            $STOCK_ITEM_TMP = new StockItemTmp(NULL);
            $STOCK_ITEM_TMP->transferBetweenDepartments($item_id, $from_department_id, $to_department_id, $transfer_qty);
        } catch (Exception $e) {
            // fail silently to avoid blocking transfer if tmp sync fails
        }

        return ['status' => 'success', 'message' => 'Stock transferred successfully.'];
    }

    public function adjustQuantity($item_id, $department_id, $adjust_qty, $adjust_type, $remark = '')
    {

        $db = Database::getInstance();
        $DEPARTMENT = new DepartmentMaster($department_id);

        // Get existing stock record
        $query = "SELECT * FROM `stock_master`
              WHERE `item_id` = '" . (int) $item_id . "'
              AND `department_id` = '" . (int) $department_id . "' 
              LIMIT 1";


        $result = mysqli_fetch_assoc($db->readQuery($query));

        if ($result) {

            if ($adjust_type === 'additions') {
                $newQty = $result['quantity'] + $adjust_qty;
                $transactionType = 6; // custom code for adjustment increase Get by stock adjusestment table
            } elseif ($adjust_type === 'deductions') {
                if ($result['quantity'] < $adjust_qty) {
                    return ['status' => 'error', 'message' => 'Insufficient stock to adjust.'];
                }
                $newQty = $result['quantity'] - $adjust_qty;
                $transactionType = 7; // custom code for adjustment decrease Get by stock adjusestment table
            } else {
                return ['status' => 'error', 'message' => 'Invalid adjustment type.'];
            }

            $update = "UPDATE `stock_master` SET `quantity` = '" . (int) $newQty . "', `remark` = '" . $remark . "' 
                   WHERE `id` = '" . (int) $result['id'] . "'";
            $db->readQuery($update);
        } else {
            // No existing record, only allow increase
            if ($adjust_type !== 'additions') {
                return ['status' => 'error', 'message' => 'No existing stock to decrease.'];
            }

            $insert = "INSERT INTO `stock_master` (`item_id`, `department_id`, `quantity`, `is_active`, `remark`, `created_at`)
                   VALUES ('" . (int) $item_id . "', '" . (int) $department_id . "', '" . (int) $adjust_qty . "', 1, '" . $remark . "', NOW())";
            $db->readQuery($insert);
            $transactionType = 6; // adjustment increase
        }

        // Record in stock transaction
        $STOCK_TRANSACTION = new StockTransaction(NULL);
        $STOCK_TRANSACTION->item_id = $item_id;
        $STOCK_TRANSACTION->type = $transactionType;
        $STOCK_TRANSACTION->date = date('Y-m-d');
        $STOCK_TRANSACTION->qty_in = ($adjust_type === 'additions') ? $adjust_qty : 0;
        $STOCK_TRANSACTION->qty_out = ($adjust_type === 'deductions') ? $adjust_qty : 0;
        $STOCK_TRANSACTION->remark = 'Stock adjustment in ' . $DEPARTMENT->name . ' - ' . $remark;
        $STOCK_TRANSACTION->create();

        return ['status' => 'success', 'message' => 'Stock adjusted successfully.'];
    }
    public static function getTotalAvailableQuantity($item_id)
    {
        $query = "SELECT SUM(quantity) AS total_quantity
              FROM stock_master
              WHERE item_id = " . (int) $item_id . "  ";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $row = mysqli_fetch_assoc($result);

        return (int) $row['total_quantity'];
    }

    public static function getTotalAvailableQuantityByDepartment($item_id, $department_id)
    {
        $query = "SELECT SUM(quantity) AS total_quantity
              FROM stock_master
              WHERE item_id = " . (int) $item_id . " 
              AND department_id = " . (int) $department_id . " ";

        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $row = mysqli_fetch_assoc($result);

        return (int) $row['total_quantity'];
    }

    public static function getDepartmentWiseStock($item_id)
    {
        $query = "SELECT 
                sm.department_id,
                d.name AS department_name,
                IFNULL(sm.quantity, 0) AS quantity
              FROM department_master d
              LEFT JOIN stock_master sm 
                ON sm.department_id = d.id 
                AND sm.item_id = " . (int) $item_id . " 
                AND sm.is_active = 1
              ORDER BY d.name ASC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $departments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $departments[] = [
                'department_id'   => (int)$row['department_id'],
                'department_name' => $row['department_name'],
                'quantity'        => (int)$row['quantity']
            ];
        }

        return $departments;
    }
}
