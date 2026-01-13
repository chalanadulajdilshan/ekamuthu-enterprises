<?php

class ItemMaster
{

    public $id;
    public $code;
    public $name;
    public $brand;
    public $size;
    public $pattern;
    public $group;
    public $category;
    public $re_order_level;
    public $re_order_qty;
    public $list_price;
    public $invoice_price;
    public $stock_type;
    public $note;
    public $discount;
    public $is_active;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `item_master` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->code = $result['code'];
                $this->name = $result['name'];
                $this->brand = $result['brand'];
                $this->size = $result['size'];
                $this->pattern = $result['pattern'];
                $this->group = $result['group'];
                $this->category = $result['category'];
                $this->list_price = $result['list_price'];
                $this->invoice_price = $result['invoice_price'];
                $this->re_order_level = $result['re_order_level'];
                $this->re_order_qty = $result['re_order_qty'];
                $this->stock_type = $result['stock_type'];
                $this->note = $result['note'];
                $this->discount = $result['discount'];
                $this->is_active = $result['is_active'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `item_master` (
    `code`, `name`, `brand`, `size`, `pattern`, `group`, `category`, 
     `re_order_level`, `re_order_qty`, `stock_type`, `note`,`list_price`,`invoice_price`,`discount`, `is_active`
) VALUES (
    '$this->code', '$this->name', '$this->brand', '$this->size', '$this->pattern', '$this->group',
    '$this->category',  '$this->re_order_level', '$this->re_order_qty',
     '$this->stock_type', '$this->note', '$this->list_price', '$this->invoice_price', '$this->discount', '$this->is_active'
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
        $query = "UPDATE `item_master` SET 
            `code` = '$this->code', 
            `name` = '$this->name', 
            `brand` = '$this->brand', 
            `size` = '$this->size',  
            `pattern` = '$this->pattern', 
            `group` = '$this->group', 
            `category` = '$this->category', 
            `list_price` = '$this->list_price', 
            `invoice_price` = '$this->invoice_price', 
            `re_order_level` = '$this->re_order_level', 
            `re_order_qty` = '$this->re_order_qty', 
            `stock_type` = '$this->stock_type', 
            `note` = '$this->note',
             `discount` = '$this->discount', 
            `is_active` = '$this->is_active'
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
        $query = "DELETE FROM `item_master` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `item_master` ORDER BY name ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // You can change this method name/logic based on your real use case
    public function getItemsByCategory($category_id)
    {
        $query = "SELECT * FROM `item_master` WHERE `category` = '$category_id' ORDER BY name ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getItemsFiltered($category_id = 0, $brand_id = 0, $group_id = 0, $department_id = 0, $item_code = '')
    {
        $conditions = [];

        if ((int) $category_id > 0) {
            $conditions[] = "`im`.`category` = '" . (int) $category_id . "'";
        }

        if ((int) $brand_id > 0) {
            $conditions[] = "`im`.`brand` = '" . (int) $brand_id . "'";
        }

        if ((int) $group_id > 0) {
            $conditions[] = "`im`.`group` = '" . (int) $group_id . "'";
        }

        if (!empty($item_code)) {
            $conditions[] = "(`im`.`code` LIKE '%" . $item_code . "%' OR `im`.`name` LIKE '%" . $item_code . "%')";
        }
        // Get the logged-in user's department ID from session
        $userDepartmentId = 0;
        if (isset($_SESSION['id'])) {
            $USER = new User($_SESSION['id']);
            $userDepartmentId = $USER->department_id;
        }

        // Join condition to filter by ARN department (stock_item_tmp) instead of stock_master
        $join = "";
        $effectiveDepartmentId = ((int) $department_id > 0) ? (int) $department_id : $userDepartmentId;

        if ($effectiveDepartmentId > 0) {
            // Filter items that have ARNs in stock_item_tmp for this department with available quantity
            $join = "INNER JOIN stock_item_tmp sit ON sit.item_id = im.id 
                     AND sit.department_id = '" . $effectiveDepartmentId . "' 
                     AND sit.qty > 0";
            $join .= " INNER JOIN arn_master am ON sit.arn_id = am.id 
                      AND (am.is_cancelled IS NULL OR am.is_cancelled = 0)";
        }


        $where = "";
        if (count($conditions) > 0) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        $query = "SELECT DISTINCT im.* FROM item_master im $join $where ORDER BY im.name ASC";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $items = [];

        $STOCK_TMP = new StockItemTmp(NULL);

        while ($row = mysqli_fetch_assoc($result)) {
            $CATEGORY = new CategoryMaster($row['category']);
            $BRAND = new Brand($row['brand']);
            $GROUP_MASTER = new GroupMaster($row['group']);
            $STOCK_MASTER = new StockMaster(NULL);

            $row['group'] = $GROUP_MASTER->name;
            $row['category'] = $CATEGORY->name;
            $row['brand'] = $BRAND->name;

            // Get ARN records filtered by department directly using the method
            if ($userDepartmentId > 0) {
                // Get only ARNs that belong to the user's department
                $row['stock_tmp'] = $STOCK_TMP->getByItemIdAndDepartment($row['id'], $userDepartmentId);
            } else {
                // If no user department is set, include all (fallback for admin users)
                $row['stock_tmp'] = $STOCK_TMP->getByItemId($row['id']);
            }

            // Calculate total available quantity from ARN records (stock_item_tmp)
            $totalQty = 0;
            foreach ($row['stock_tmp'] as $tmpRow) {
                $totalQty += (float)$tmpRow['qty'];
            }
            $row['total_available_qty'] = $totalQty;

            foreach ($row['stock_tmp'] as $key => $stockRow) {
                // ARN
                $arnData = new ArnMaster($stockRow['arn_id']);
                $row['stock_tmp'][$key]['arn_no'] = $arnData ? $arnData->arn_no : null;

                if (!$arnData || $arnData->is_cancelled == 1) {
                    unset($row['stock_tmp'][$key]);
                    continue;
                }
                usort($row['stock_tmp'], function ($a, $b) {
                    return strtotime($a['created_at']) - strtotime($b['created_at']);
                });

                $row['stock_tmp'][$key]['final_cost'] = $stockRow['cost']; // Assuming 'cost' = final cost
                $row['stock_tmp'][$key]['list_price'] = $stockRow['list_price'];
                $row['stock_tmp'][$key]['invoice_price'] = $stockRow['invoice_price'];

                // Department
                $DEPARTMENT_MASTER = new DepartmentMaster($stockRow['department_id']);
                $departmentName = $DEPARTMENT_MASTER ? $DEPARTMENT_MASTER->name : null;
                $row['stock_tmp'][$key]['department'] = $departmentName;
            }

            $items[] = $row;
        }


        return $items;
    }



    public function getLastID()
    {
        $query = "SELECT * FROM `item_master` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'];
    }

    public function fetchForDataTable($request)
    {

        $db = Database::getInstance();

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 100;
        $search = $request['search']['value'] ?? '';
        $searchTerm = $request['search_term'] ?? '';
        $brandId = $request['brand_id'] ?? null;
        $categoryId = $request['category_id'] ?? null;

        $status = $request['status'] ?? null;
        $stockOnly = isset($request['stock_only']) ? filter_var($request['stock_only'], FILTER_VALIDATE_BOOLEAN) : false;
        $departmentId = isset($request['department_id']) ? (int)$request['department_id'] : 0;
        $expandDepartments = isset($request['expand_departments']) ? filter_var($request['expand_departments'], FILTER_VALIDATE_BOOLEAN) : false;

        $where = "WHERE 1=1";
        $join = "";
        $having = "";

        // Search filter
        if (!empty($search)) {
            $where .= " AND (im.name LIKE '%$search%' OR im.code LIKE '%$search%')";
        }

        // Additional search term from custom search box
        if (!empty($searchTerm)) {
            $where .= " AND (im.name LIKE '%$searchTerm%' OR im.code LIKE '%$searchTerm%')";
        }


        //brand filter
        if (!empty($brandId)) {
            $brandId = (int) $brandId;
            $where .= " AND im.brand = {$brandId}";
        }
        //category filter
        if (!empty($categoryId)) {
            $categoryId = (int) $categoryId;
            $where .= " AND im.category = {$categoryId}";
        }


        // Status filter
        if (!empty($status)) {
            if ($status === 'active' || $status === '1' || $status === 1) {
                $where .= " AND im.is_active = 1";
            } elseif ($status === 'inactive' || $status === '0' || $status === 0) {
                $where .= " AND im.is_active = 0";
            }
        }

        // Stock only filter - filter out items with 0 quantity in the specified department
        if ($stockOnly && $departmentId > 0) {
            $join = " LEFT JOIN stock_master sm2 ON im.id = sm2.item_id AND sm2.department_id = $departmentId";
            $having = " HAVING available_qty > 0";
        } elseif ($stockOnly) {
            // If no department is specified but stock_only is true, filter out items with 0 total quantity
            $having = " HAVING total_qty > 0";
        }

        // Department filter
        if ($departmentId > 0 && !$stockOnly) {
            $join = " LEFT JOIN stock_master sm2 ON im.id = sm2.item_id AND sm2.department_id = $departmentId";
        }

        // Check if we're on the stock transfer page and need to show all departments
        $showAllDepartments = isset($request['show_all_departments']) ? (bool)$request['show_all_departments'] : false;
        $fromDepartmentId = isset($request['from_department_id']) ? (int)$request['from_department_id'] : 0;

        // If showing all departments but we have a from_department_id (stock transfer case)
        if ($showAllDepartments && $fromDepartmentId > 0) {
            $join = " LEFT JOIN stock_master sm2 ON im.id = sm2.item_id AND sm2.department_id = $fromDepartmentId";
        }

        // If expanding departments (All departments as separate rows)
        if ($expandDepartments && $departmentId === 0) {
            // Build items base query with filters, but no LIMIT
            $itemsSql = "
                SELECT 
                    im.*, 
                    IFNULL((SELECT SUM(quantity) FROM stock_master WHERE item_id = im.id), 0) as total_qty 
                FROM item_master im
                $join
                $where
                GROUP BY im.id
            ";

            // Apply stock-only filter on total quantity if no specific department is chosen
            if ($stockOnly) {
                $itemsSql .= " HAVING total_qty > 0";
            }

            // Execute items query
            $itemsQuery = $db->readQuery($itemsSql);
            $items = [];
            $itemIds = [];
            while ($row = mysqli_fetch_assoc($itemsQuery)) {
                $items[] = $row;
                $itemIds[] = (int)$row['id'];
            }

            // Prefetch aggregated department stocks for selected items
            $deptStockMap = [];
            if (!empty($itemIds)) {
                $idsStr = implode(',', array_map('intval', $itemIds));
                $stockAggSql = "
                    SELECT item_id, department_id, SUM(quantity) AS quantity
                    FROM stock_master
                    WHERE item_id IN ($idsStr)
                    GROUP BY item_id, department_id
                ";
                $stockAggResult = $db->readQuery($stockAggSql);
                while ($sRow = mysqli_fetch_assoc($stockAggResult)) {
                    $iid = (int)$sRow['item_id'];
                    if (!isset($deptStockMap[$iid])) $deptStockMap[$iid] = [];
                    $deptStockMap[$iid][] = [
                        'department_id' => (int)$sRow['department_id'],
                        'quantity' => (float)$sRow['quantity']
                    ];
                }
            }

            // Expand items into per-department rows
            $expanded = [];
            $key = 1;
            foreach ($items as $row) {
                $CATEGORY = new CategoryMaster($row['category']);
                $BRAND = new Brand($row['brand']);

                $deptStocks = $deptStockMap[$row['id']] ?? [];
                // If stockOnly, filter out zero/negative quantities
                if ($stockOnly) {
                    $deptStocks = array_values(array_filter($deptStocks, function ($ds) {
                        return (float)$ds['quantity'] > 0;
                    }));
                }

                // If there are no department stocks and stockOnly is false, still show a row with 0 qty per department? Here we skip if none.
                foreach ($deptStocks as $ds) {
                    $nestedData = [
                        "key" => $key,
                        "id" => $row['id'],
                        "code" => $row['code'],
                        "name" => $row['name'],
                        "pattern" => $row['pattern'],
                        "size" => $row['size'],
                        "group" => $row['group'],
                        "re_order_level" => $row['re_order_level'],
                        "re_order_qty" => $row['re_order_qty'],
                        "brand_id" => $row['brand'],
                        "brand" => $BRAND->name,
                        "category_id" => $row['category'],
                        "category" => $CATEGORY->name,
                        "list_price" => $row['list_price'],
                        "invoice_price" => $row['invoice_price'],
                        "discount" => $row['discount'],
                        "stock_type" => $row['stock_type'],
                        "note" => $row['note'],
                        "status" => $row['is_active'],
                        // For expanded rows, available_qty is the department quantity
                        "qty" => $row['total_qty'],
                        "available_qty" => (float)$ds['quantity'],
                        // department_stock contains only this department
                        "department_stock" => [
                            [
                                'department_id' => (int)$ds['department_id'],
                                'quantity' => (float)$ds['quantity']
                            ]
                        ],
                        // Helper fields used by frontend to render department row
                        "row_department_id" => (int)$ds['department_id'],
                        "row_department_qty" => (float)$ds['quantity'],
                        "status_label" => $row['is_active'] == 1
                            ? '<span class="badge bg-soft-success font-size-12">Active</span>'
                            : '<span class="badge bg-soft-danger font-size-12">Inactive</span>'
                    ];

                    $expanded[] = $nestedData;
                    $key++;
                }
            }

            // Pagination for expanded rows
            $recordsFiltered = count($expanded);
            $recordsTotal = $recordsFiltered; // For simplicity
            $pagedData = array_slice($expanded, $start, $length);

            return [
                "draw" => intval($request['draw']),
                "recordsTotal" => intval($recordsTotal),
                "recordsFiltered" => intval($recordsFiltered),
                "data" => $pagedData
            ];
        }

        // Total records (no filter) - non-expanded flow
        $totalSql = "SELECT COUNT(*) as total FROM item_master";
        $totalQuery = $db->readQuery($totalSql);
        $totalRow = mysqli_fetch_assoc($totalQuery);
        $totalData = $totalRow['total'];

        // Filtered records with JOIN and aggregation
        $filteredSql = "
        SELECT 
            im.*, 
            " . ($departmentId > 0 ?
            "IFNULL((SELECT SUM(sm.quantity) FROM stock_master sm WHERE sm.item_id = im.id AND sm.department_id = $departmentId), 0) as available_qty, " : "") . "
            IFNULL((SELECT SUM(quantity) FROM stock_master WHERE item_id = im.id), 0) as total_qty 
        FROM item_master im
        $join
        $where
        GROUP BY im.id 
        $having";



        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_num_rows($filteredQuery);


        // Paginated query
        $sql = "$filteredSql LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;
        $STOCK_TMP_HELPER = new StockItemTmp(NULL);
        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $CATEGORY = new CategoryMaster($row['category']);
            $BRAND = new Brand($row['brand']);

            // Get department stock information (aggregate by department)
            $departmentStocks = [];
            $stockQuery = "SELECT department_id, SUM(quantity) AS quantity FROM stock_master WHERE item_id = {$row['id']} GROUP BY department_id";
            $stockResult = $db->readQuery($stockQuery);
            while ($stockRow = mysqli_fetch_assoc($stockResult)) {
                $departmentStocks[] = [
                    'department_id' => (int)$stockRow['department_id'],
                    'quantity' => (float)$stockRow['quantity']
                ];
            }

            // Build ARN-wise stock lots from stock_item_tmp
            $stockTmpLots = $STOCK_TMP_HELPER->getByItemId($row['id']);
            foreach ($stockTmpLots as $idx => $lot) {
                // Attach ARN number
                $arnObj = new ArnMaster($lot['arn_id']);
                $stockTmpLots[$idx]['arn_no'] = $arnObj ? $arnObj->arn_no : null;
                // Attach department name
                $depObj = new DepartmentMaster($lot['department_id']);
                $stockTmpLots[$idx]['department'] = $depObj ? $depObj->name : null;
            }

            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "code" => $row['code'],
                "name" => $row['name'],
                "pattern" => $row['pattern'],
                "size" => $row['size'],
                "group" => $row['group'],
                "re_order_level" => $row['re_order_level'],
                "re_order_qty" => $row['re_order_qty'],
                "brand_id" => $row['brand'],
                "brand" => $BRAND->name,
                "category_id" => $row['category'],
                "category" => $CATEGORY->name,
                "list_price" => $row['list_price'],
                "invoice_price" => $row['invoice_price'],
                "discount" => $row['discount'],
                "stock_type" => $row['stock_type'],
                "note" => $row['note'],
                "status" => $row['is_active'],
                "qty" => $row['total_qty'],
                "available_qty" => $departmentId > 0 ? $row['available_qty'] : $row['total_qty'],
                "department_stock" => $departmentStocks,
                // Provide ARN-wise lots for frontend expandable details
                "stock_tmp" => $stockTmpLots,
                "status_label" => $row['is_active'] == 1
                    ? '<span class="badge bg-soft-success font-size-12">Active</span>'
                    : '<span class="badge bg-soft-danger font-size-12">Inactive</span>'
            ];

            $data[] = $nestedData;
            $key++;
        }

        return [
            "draw" => intval($request['draw']),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($filteredData),
            "data" => $data
        ];
    }

    public function getIdbyItemCode($code)
    {
        $query = "SELECT `id` FROM `item_master` WHERE `code` = '$code' LIMIT 1";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($row = mysqli_fetch_assoc($result)) {
            return $row['id'];
        }

        return null;
    }

    public static function checkReorderLevel()
    {
        $db = Database::getInstance();
        $query = "SELECT `id`, `code`, `name`, `re_order_level` FROM `item_master` WHERE `is_active` = 1";
        $result = $db->readQuery($query);

        $reorderItems = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $totalQuantity = StockMaster::getTotalAvailableQuantity($row['id']);
            $reorderLevel = (float) $row['re_order_level'];

            // Show reorder alert only if quantity <= reorder level and quantity > 0
            if ($totalQuantity <= $reorderLevel && $totalQuantity > 0) {
                $reorderItems[] = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                ];
            }
        }

        return $reorderItems;
    }

    public static function getItemsWithStock()
    {
        $db = Database::getInstance();
        $query = "SELECT im.*, 
                 IFNULL((SELECT SUM(quantity) FROM stock_master WHERE item_id = im.id), 0) as total_qty
                 FROM item_master im
                 WHERE im.is_active = 1
                 HAVING total_qty > 0
                 ORDER BY im.name ASC";

        $result = $db->readQuery($query);
        $items = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }

        return $items;
    }

    public static function getItemsByDepartmentAndStock($department_id, $min_quantity = 1, $search = '')
    {
        $db = Database::getInstance();

        // Prepare the base query
        $query = "SELECT 
                im.*, 
                IFNULL(sm.quantity, 0) as available_qty,
                b.name as brand_name,
                c.name as category_name,
                g.name as group_name
              FROM item_master im
              LEFT JOIN (
                  SELECT item_id, SUM(quantity) as quantity 
                  FROM stock_master 
                  WHERE department_id = '" . mysqli_real_escape_string($db->DB_CON, $department_id) . "'
                  AND is_active = 1
                  GROUP BY item_id
              ) sm ON im.id = sm.item_id
              LEFT JOIN brands b ON im.brand = b.id
              LEFT JOIN category_master c ON im.category = c.id
              LEFT JOIN group_master g ON im.group = g.id
              WHERE im.is_active = 1
              HAVING available_qty > 0";  // Always filter out items with zero quantity

        // Add search term if provided
        if (!empty($search)) {
            $search = mysqli_real_escape_string($db->DB_CON, $search);
            $query = "SELECT * FROM (" . $query . ") AS filtered 
                 WHERE code LIKE '%$search%' 
                 OR name LIKE '%$search%' 
                 OR brand_name LIKE '%$search%'
                 OR category_name LIKE '%$search%'";
        }

        $query .= " ORDER BY im.name ASC";

        $result = $db->readQuery($query);
        $items = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
            return $items;
        }
    }

    public static function isNameDuplicate($name, $excludeId = null)
    {
        $db = Database::getInstance();
        $query = "SELECT id FROM item_master WHERE name = '" . mysqli_real_escape_string($db->DB_CON, $name) . "'";
        if ($excludeId !== null) {
            $query .= " AND id != " . (int)$excludeId;
        }
        $query .= " LIMIT 1";
        $result = $db->readQuery($query);
        return mysqli_num_rows($result) > 0;
    }
}
