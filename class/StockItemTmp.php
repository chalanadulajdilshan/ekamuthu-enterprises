<?php

class StockItemTmp
{
    public $id;
    public $arn_id;
    public $item_id;
    public $qty;
    public $cost;
    public $department_id;
    public $list_price;
    public $invoice_price;
    public $created_at;
    public $status;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `stock_item_tmp` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                foreach ($result as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `stock_item_tmp` (
            `arn_id`, `item_id`, `qty`, `cost`, `list_price`,`invoice_price`, `department_id`, `status`, `created_at`
        ) VALUES (
            '{$this->arn_id}', '{$this->item_id}', '{$this->qty}', '{$this->cost}',
            '{$this->list_price}','{$this->invoice_price}', '{$this->department_id}', '{$this->status}', NOW()
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
        $query = "UPDATE `stock_item_tmp` SET
            `arn_id` = '{$this->arn_id}',
            `item_id` = '{$this->item_id}',
            `qty` = '{$this->qty}',
            `cost` = '{$this->cost}',
            `department_id` = '{$this->department_id}',
            `list_price` = '{$this->list_price}',
            `invoice_price` = '{$this->invoice_price}'
        WHERE `id` = '{$this->id}'";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return $this->__construct($this->id);
        } else {
            return false;
        }
    }

    public function delete()
    {
        $query = "DELETE FROM `stock_item_tmp` WHERE `id` = '{$this->id}'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `stock_item_tmp` ORDER BY `id` DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getByArnId($arn_id)
    {
        $query = "SELECT * FROM `stock_item_tmp` WHERE `arn_id` = '" . (int) $arn_id . "'";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }
    public function getByItemId($id)
    {
        $query = "SELECT sit.* 
                 FROM `stock_item_tmp` sit
                 INNER JOIN `arn_master` am ON sit.arn_id = am.id
                 WHERE sit.`item_id` = '" . (int) $id . "' 
                 AND (am.is_cancelled IS NULL OR am.is_cancelled = 0)";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getByItemIdAndDepartment($id, $department_id)
    {
        $query = "SELECT sit.* 
                 FROM `stock_item_tmp` sit
                 INNER JOIN `arn_master` am ON sit.arn_id = am.id
                 WHERE sit.`item_id` = '" . (int) $id . "' 
                 AND sit.`department_id` = '" . (int) $department_id . "' 
                 AND (am.is_cancelled IS NULL OR am.is_cancelled = 0)";
        
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            $array_res[] = $row;
        }

        return $array_res;
    }



    /**
     * Move quantity between departments using FIFO across non-cancelled ARN lots.
     * Keeps stock_item_tmp aligned with inter-department transfers.
     */
    public function transferBetweenDepartments($item_id, $from_department_id, $to_department_id, $transfer_qty)
    {
        $db = Database::getInstance();

        $item_id = (int)$item_id;
        $from_department_id = (int)$from_department_id;
        $to_department_id = (int)$to_department_id;
        $remaining = (float)$transfer_qty;

        if ($remaining <= 0) {
            return true;
        }

        // Fetch FIFO lots from source department, excluding cancelled ARNs
        $query = "
            SELECT sit.*
            FROM stock_item_tmp sit
            INNER JOIN arn_master am ON am.id = sit.arn_id
            WHERE sit.item_id = {$item_id}
              AND sit.department_id = {$from_department_id}
              AND (am.is_cancelled IS NULL OR am.is_cancelled = 0)
              AND sit.qty > 0
            ORDER BY sit.created_at ASC, sit.id ASC
        ";
        $result = $db->readQuery($query);

        while ($remaining > 0 && ($lot = mysqli_fetch_assoc($result))) {
            $movable = min($remaining, (float)$lot['qty']);

            // 1) Deduct from source lot
            $newQty = (float)$lot['qty'] - $movable;
            $updateSrc = "UPDATE stock_item_tmp SET qty = '" . $newQty . "' WHERE id = " . (int)$lot['id'];
            $db->readQuery($updateSrc);

            // 2) Add to destination department lot with same ARN and pricing (upsert by arn_id+item+department)
            $destFind = "SELECT id, qty FROM stock_item_tmp WHERE arn_id = " . (int)$lot['arn_id'] . " AND item_id = {$item_id} AND department_id = {$to_department_id} LIMIT 1";
            $destRes = $db->readQuery($destFind);
            if ($destRow = mysqli_fetch_assoc($destRes)) {
                $destNewQty = (float)$destRow['qty'] + $movable;
                $updateDest = "UPDATE stock_item_tmp SET qty = '" . $destNewQty . "' WHERE id = " . (int)$destRow['id'];
                $db->readQuery($updateDest);
            } else {
                $insertDest = "INSERT INTO stock_item_tmp (arn_id, item_id, qty, cost, list_price, invoice_price, department_id, created_at) VALUES ('" . (int)$lot['arn_id'] . "', '{$item_id}', '" . $movable . "', '" . (float)$lot['cost'] . "', '" . (float)$lot['list_price'] . "', '" . (float)$lot['invoice_price'] . "', '{$to_department_id}', NOW())";
                $db->readQuery($insertDest);
            }

            $remaining -= $movable;
        }

        // If we couldn't move full quantity due to lack of FIFO lots, return false
        return $remaining <= 0;
    }

    /**
     * Add quantity back to stock_item_tmp using FIFO (oldest lots first).
     * Reverses the deduction done during sales.
     */
    public function addBackQuantity($item_id, $department_id, $qty_to_add)
    {
        $db = Database::getInstance();

        $item_id = (int)$item_id;
        $department_id = (int)$department_id;
        $remaining = (float)$qty_to_add;

        if ($remaining <= 0) {
            return true;
        }

        // Fetch oldest lots for the item in the department, excluding cancelled ARNs
        $query = "
            SELECT sit.*
            FROM stock_item_tmp sit
            INNER JOIN arn_master am ON am.id = sit.arn_id
            WHERE sit.item_id = {$item_id}
              AND sit.department_id = {$department_id}
              AND (am.is_cancelled IS NULL OR am.is_cancelled = 0)
            ORDER BY sit.created_at ASC, sit.id ASC
        ";
        $result = $db->readQuery($query);

        while ($remaining > 0 && ($lot = mysqli_fetch_assoc($result))) {
            $current_qty = (float)$lot['qty'];

            // Add the remaining quantity to the oldest lot
            $new_qty = $current_qty + $remaining;
            $update = "UPDATE stock_item_tmp SET qty = '" . $new_qty . "' WHERE id = " . (int)$lot['id'];
            $db->readQuery($update);

            $remaining = 0; // Since we add all to the first (oldest) lot
        }

        // If no lots found, or remaining > 0, return false
        return $remaining <= 0;
    }

    public function updateStockItemTmpPrice($id, $field, $value)
    {
        $allowedFields = ['cost', 'invoice_price', 'list_price'];

        if (!in_array($field, $allowedFields)) {
            return ['error' => 'Invalid field'];
        }

        if (!is_numeric($value)) {
            return ['error' => 'Value must be numeric'];
        }

        $value = floatval($value);

        if (in_array($field, ['cash_dis', 'credit_dis']) && ($value < 0 || $value > 100)) {
            return ['error' => 'Discount must be between 0 and 100'];
        }

        $db = Database::getInstance();
        $value = mysqli_real_escape_string($db->DB_CON, $value);
        $id = (int) $id;

        $query = "UPDATE `stock_item_tmp` SET `$field` = '$value' WHERE `id` = $id";

        $result = $db->readQuery($query);

        if ($result) {
            return ['success' => true];
        } else {
            return ['error' => 'Database update failed'];
        }
    }

    public function updateQtyByArnId($arn_id, $item_id, $department_id, $qty_change)
    {
        $db = Database::getInstance();

        // 1. Get the current quantity
        $selectQuery = "SELECT `qty` FROM `stock_item_tmp` 
                    WHERE `arn_id` = '{$arn_id}' 
                      AND `item_id` = '{$item_id}' 
                      AND `department_id` = '{$department_id}' 
                    LIMIT 1";


        $result = $db->readQuery($selectQuery);

        if ($row = mysqli_fetch_assoc($result)) {
            $currentQty = (float)$row['qty'];


            $newQty = $currentQty + $qty_change;


            if ($newQty < 0) {
                return false;
            }

            // 3. Update with new quantity
            $updateQuery = "UPDATE `stock_item_tmp` SET 
                            `qty` = '{$newQty}' 
                        WHERE `arn_id` = '{$arn_id}' 
                          AND `item_id` = '{$item_id}' 
                          AND `department_id` = '{$department_id}'";

            $updateResult = $db->readQuery($updateQuery);

            return $updateResult ? true : false;
        }

        // Record not found
        return false;
    }

    /**
     * Deduct quantity from the most recent ARN lots (reverse FIFO) for a given item and department.
     * Ensures deductions are applied to latest lots first, cascading to older lots when needed.
     */
    public function deductFromLatestArnLots($item_id, $department_id, $qty_to_deduct)
    {
        $item_id = (int)$item_id;
        $department_id = (int)$department_id;
        $remaining = (float)$qty_to_deduct;

        if ($remaining <= 0) {
            return ['success' => true, 'deducted' => 0];
        }

        $db = Database::getInstance();

        // Check available quantity across active ARN lots
        $availableQuery = "
            SELECT IFNULL(SUM(sit.qty), 0) AS total_qty
            FROM stock_item_tmp sit
            INNER JOIN arn_master am ON am.id = sit.arn_id
            WHERE sit.item_id = {$item_id}
              AND sit.department_id = {$department_id}
              AND sit.qty > 0
              AND (am.is_cancelled IS NULL OR am.is_cancelled = 0)
        ";
        $availableRes = $db->readQuery($availableQuery);
        $availableRow = mysqli_fetch_assoc($availableRes);
        $availableQty = $availableRow ? (float)$availableRow['total_qty'] : 0.0;

        if ($availableQty < $remaining) {
            return [
                'success' => false,
                'available' => $availableQty,
                'message' => 'Insufficient quantity across existing ARN lots.'
            ];
        }

        $lotQuery = "
            SELECT sit.id, sit.arn_id, sit.qty, sit.cost, sit.list_price
            FROM stock_item_tmp sit
            INNER JOIN arn_master am ON am.id = sit.arn_id
            WHERE sit.item_id = {$item_id}
              AND sit.department_id = {$department_id}
              AND sit.qty > 0
              AND (am.is_cancelled IS NULL OR am.is_cancelled = 0)
            ORDER BY sit.created_at DESC, sit.id DESC
        ";
        $lotRes = $db->readQuery($lotQuery);

        while ($remaining > 0 && ($lot = mysqli_fetch_assoc($lotRes))) {
            $lotQty = (float)$lot['qty'];
            if ($lotQty <= 0) {
                continue;
            }

            $deduct = min($remaining, $lotQty);
            $newQty = $lotQty - $deduct;

            $updateTmp = "UPDATE stock_item_tmp SET qty = '" . $this->formatNumber($newQty) . "' WHERE id = " . (int)$lot['id'];
            $db->readQuery($updateTmp);

            $arnUpdateSuccess = $this->applyArnItemDeduction(
                $db,
                (int)$lot['arn_id'],
                $item_id,
                $deduct,
                (float)$lot['cost'],
                (float)$lot['list_price']
            );

            if (!$arnUpdateSuccess) {
                return [
                    'success' => false,
                    'available' => $availableQty - ($qty_to_deduct - $remaining),
                    'message' => 'Failed to apply deduction to ARN item records.'
                ];
            }

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            return [
                'success' => false,
                'available' => $qty_to_deduct - $remaining,
                'message' => 'Unable to distribute deduction across ARN lots.'
            ];
        }

        return ['success' => true, 'deducted' => $qty_to_deduct];
    }

    /**
     * Apply deducted quantities to `arn_items` rows and keep `arn_master` totals consistent.
     */
    private function applyArnItemDeduction($db, $arn_id, $item_id, $deductQty, $fallbackCost, $fallbackListPrice)
    {
        $arn_id = (int)$arn_id;
        $item_id = (int)$item_id;
        $remaining = (float)$deductQty;

        if ($remaining <= 0) {
            return true;
        }

        $arnItemsQuery = "
            SELECT id, received_qty, final_cost, list_price
            FROM arn_items
            WHERE arn_id = {$arn_id}
              AND item_code = {$item_id}
            ORDER BY id DESC
        ";

        $arnItemsRes = $db->readQuery($arnItemsQuery);

        while ($remaining > 0 && ($arnItem = mysqli_fetch_assoc($arnItemsRes))) {
            $rowQty = (float)$arnItem['received_qty'];
            if ($rowQty <= 0) {
                continue;
            }

            $consume = min($remaining, $rowQty);
            $newQty = $rowQty - $consume;

            $unitCost = (float)$arnItem['final_cost'];
            if ($unitCost <= 0) {
                $unitCost = (float)$fallbackCost;
            }

            $listPrice = isset($arnItem['list_price']) ? (float)$arnItem['list_price'] : 0.0;
            if ($listPrice <= 0) {
                $listPrice = (float)$fallbackListPrice;
            }

            $newUnitTotal = $unitCost * $newQty;

            $updateArnItem = "
                UPDATE arn_items
                SET received_qty = '" . $this->formatNumber($newQty) . "',
                    unit_total = '" . $this->formatNumber($newUnitTotal) . "'
                WHERE id = " . (int)$arnItem['id'];
            $db->readQuery($updateArnItem);

            $discountPerUnit = max(0, $listPrice - $unitCost);
            $totalValueReduce = $unitCost * $consume;
            $discountReduce = $discountPerUnit * $consume;

            $updateArnMaster = "
                UPDATE arn_master
                SET 
                    total_received_qty = GREATEST(total_received_qty - " . $this->formatNumber($consume) . ", 0),
                    total_arn_value = GREATEST(total_arn_value - " . $this->formatNumber($totalValueReduce) . ", 0),
                    total_discount = GREATEST(total_discount - " . $this->formatNumber($discountReduce) . ", 0)
                WHERE id = {$arn_id}
            ";
            $db->readQuery($updateArnMaster);

            $remaining -= $consume;
        }

        return $remaining <= 0;
    }

    private function formatNumber($value)
    {
        return number_format((float)$value, 4, '.', '');
    }
}
