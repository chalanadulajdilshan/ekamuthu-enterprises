<?php
include '../../class/include.php';

// Keep error reporting on for debugging as requested by user
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

$db = Database::getInstance();

// Extract filters early as they're used in multiple places
$statusFilter = mysqli_real_escape_string($db->DB_CON, $_REQUEST['status'] ?? '');
$fromDate = $_REQUEST['from_date'] ?? '';
$toDate = $_REQUEST['to_date'] ?? '';

// Fallback to default if empty string or not set
if (empty($fromDate)) $fromDate = date('Y-m-01');
if (empty($toDate)) $toDate = date('Y-m-d');

$fromDate = mysqli_real_escape_string($db->DB_CON, $fromDate);
$toDate = mysqli_real_escape_string($db->DB_CON, $toDate);

// Handle Stats Request
if (isset($_POST['action']) && $_POST['action'] == 'get_stats') {
    $counts = [
        'available' => 0,
        'rented' => 0,
        'damage' => 0,
        'repair' => 0,
        'total' => 0
    ];

    // Sub Equipment
    $subSql = "SELECT rental_status, COUNT(*) as count FROM sub_equipment GROUP BY rental_status";
    $subResult = $db->readQuery($subSql);
    while ($row = mysqli_fetch_assoc($subResult)) {
        $status = strtolower($row['rental_status'] ?? 'available');
        $count = (int)$row['count'];
        if ($status == 'available' || $status == '') $counts['available'] += $count;
        elseif ($status == 'rented' || $status == 'rent') $counts['rented'] += $count;
        elseif ($status == 'damage' || $status == 'damaged') $counts['damage'] += $count;
        elseif ($status == 'repair') $counts['repair'] += $count;
        $counts['total'] += $count;
    }

    // Bulk Equipment
    // A bulk item is rented if it is in equipment_rent_items with status 'rented' AND sub_equipment_id is 0 or NULL
    $bulkSql = "SELECT 
                (SELECT SUM(quantity) FROM equipment WHERE no_sub_items = 1) as total_bulk_qty,
                (SELECT COALESCE(SUM(eri.quantity - (SELECT COALESCE(SUM(return_qty),0) FROM equipment_rent_returns WHERE rent_item_id = eri.id)), 0)
                 FROM equipment_rent_items eri 
                 JOIN equipment e ON eri.equipment_id = e.id
                 WHERE e.no_sub_items = 1 
                 AND eri.status = 'rented'
                 AND (eri.sub_equipment_id IS NULL OR eri.sub_equipment_id = 0)
                ) as total_bulk_rented";
    
    $bulkResult = $db->readQuery($bulkSql);
    $bulkRow = mysqli_fetch_assoc($bulkResult);
    $bulkTotal = (float)($bulkRow['total_bulk_qty'] ?? 0);
    $bulkRented = (float)($bulkRow['total_bulk_rented'] ?? 0);
    
    $counts['available'] += max(0, $bulkTotal - $bulkRented);
    $counts['rented'] += $bulkRented;
    $counts['total'] += $bulkTotal;

    echo json_encode(['status' => 'success', 'data' => $counts]);
    exit;
}

$start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
$length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 25;
$search = mysqli_real_escape_string($db->DB_CON, $_REQUEST['search']['value'] ?? '');

// 1. Get filtered counts for pagination
$subCountSql = "SELECT COUNT(*) as count FROM sub_equipment se JOIN equipment e ON se.equipment_id = e.id WHERE 1=1";
$subStatusWhere = "";
if (!empty($statusFilter)) {
    if ($statusFilter == 'available') $subStatusWhere = " AND (se.rental_status = 'available' OR se.rental_status IS NULL OR se.rental_status = '')";
    elseif ($statusFilter == 'rented') $subStatusWhere = " AND (se.rental_status = 'rented' OR se.rental_status = 'rent')";
    else $subStatusWhere = " AND se.rental_status LIKE '$statusFilter%'";
}
$subCountSql .= $subStatusWhere;
if (!empty($search)) $subCountSql .= " AND (se.code LIKE '%$search%' OR e.item_name LIKE '%$search%')";

$subCountRes = mysqli_fetch_assoc($db->readQuery($subCountSql));
$subTotalFiltered = (int)$subCountRes['count'];

$bulkCountSql = "SELECT COUNT(*) as count FROM equipment e WHERE e.no_sub_items = 1";
if (!empty($search)) $bulkCountSql .= " AND (e.code LIKE '%$search%' OR e.item_name LIKE '%$search%')";

$bulkCountRes = mysqli_fetch_assoc($db->readQuery($bulkCountSql));
$bulkTotalFiltered = (int)$bulkCountRes['count'];

// For Bulk Items, each record can produce up to 2 rows (one for available, one for rented) 
// if status filter is empty. For simplicity, we'll over-estimate or handle them sequentially.
// Since bulk items are usually few, we'll keep the current logic but optimize the sub-items first.

$totalRecordsFiltered = $subTotalFiltered; // Placeholder

// 2. Fetch Data with LIMIT if possible
// If $start is within sub-items, we can limit the subQuery.
$data = [];
$key = $start + 1;

if ($start < $subTotalFiltered) {
    $subLimit = $length;
    $subOffset = $start;
    
    $subQuery = "SELECT se.id AS se_id, se.code, se.rental_status, e.item_name, c.name as category_name, dm.name as department_name, se.equipment_id,
                 (SELECT eri.rent_id FROM equipment_rent_items eri WHERE eri.sub_equipment_id = se.id AND eri.status = 'rented' ORDER BY eri.id DESC LIMIT 1) AS active_rent_id,
                 (SELECT rj.id FROM repair_jobs rj WHERE TRIM(rj.machine_code) = TRIM(se.code) AND rj.job_status NOT IN ('delivered', 'cannot_repair') ORDER BY rj.id DESC LIMIT 1) AS active_repair_job_id
                 FROM sub_equipment se 
                 JOIN equipment e ON se.equipment_id = e.id
                 LEFT JOIN equipment_category c ON e.category = c.id
                 LEFT JOIN department_master dm ON se.department_id = dm.id
                 WHERE 1=1 $subStatusWhere";
    
    if (!empty($search)) $subQuery .= " AND (se.code LIKE '%$search%' OR e.item_name LIKE '%$search%')";
    
    $subQuery .= " ORDER BY se.code ASC LIMIT $subOffset, $subLimit";
    
    $subResult = $db->readQuery($subQuery);
    while ($row = mysqli_fetch_assoc($subResult)) {
        $status = $row['rental_status'] ? $row['rental_status'] : 'available';
        $data[] = [
            'key' => $key++,
            'code' => $row['code'],
            'item_name' => $row['item_name'],
            'category' => $row['category_name'],
            'department' => $row['department_name'],
            'status' => $status,
            'quantity' => 1,
            'equipment_id' => $row['equipment_id'],
            'active_rent_id' => $row['active_rent_id'],
            'active_repair_job_id' => $row['active_repair_job_id'],
            'is_sub' => true
        ];
    }
}

// Bulk items logic (only if we have room left in current page)
if (count($data) < $length) {
    // Handling bulk items is tricky with global pagination, but we'll fetch them if we're near the end of sub-items
    // For now, most items are sub-items, so this optimization covers 99% of use cases.
    // The original bulk query logic is retained here, but without pagination for simplicity in this combined scenario.
    // If the page starts after all sub-items, or if there's space left, we'll fetch bulk items.
    // This part would need more complex logic for true SQL-level pagination across both types.
    
    // If we are past the sub-items, or need more items to fill the page
    if ($start >= $subTotalFiltered || count($data) > 0) {
        $bulkQuery = "SELECT e.id, e.code, e.item_name, e.quantity as total_qty, c.name as category_name, dm.name as department_name,
                      (SELECT COALESCE(SUM(eri.quantity - (SELECT COALESCE(SUM(return_qty),0) FROM equipment_rent_returns WHERE rent_item_id = eri.id)), 0)
                       FROM equipment_rent_items eri 
                       WHERE eri.equipment_id = e.id 
                       AND eri.status = 'rented'
                       AND (eri.sub_equipment_id IS NULL OR eri.sub_equipment_id = 0)
                      ) as rented_qty
                      FROM equipment e
                      LEFT JOIN equipment_category c ON e.category = c.id
                      LEFT JOIN department_master dm ON e.department_id = dm.id
                      WHERE e.no_sub_items = 1";

        if (!empty($search)) {
            $bulkQuery .= " AND (e.code LIKE '%$search%' OR e.item_name LIKE '%$search%')";
        }
        // No LIMIT/OFFSET here for bulk items as it's complex to combine with sub-item pagination.
        // This means all matching bulk items are fetched, then potentially sliced.
        $bulkResult = $db->readQuery($bulkQuery);
        while ($row = mysqli_fetch_assoc($bulkResult)) {
            $total = (float)$row['total_qty'];
            $rented = (float)$row['rented_qty'];
            $available = max(0, $total - $rented);
            
            // Add 'Available' row
            if (empty($statusFilter) || $statusFilter == 'available') {
                if ($available > 0) {
                    $data[] = [
                        'key' => $key++,
                        'code' => $row['code'],
                        'item_name' => $row['item_name'],
                        'category' => $row['category_name'],
                        'department' => $row['department_name'],
                        'status' => 'available',
                        'quantity' => $available,
                        'equipment_id' => $row['id'],
                        'is_sub' => false,
                        'rented_in_range' => 0,
                        'returned_in_range' => 0
                    ];
                }
            }

            // Add 'Rented' row
            if (empty($statusFilter) || $statusFilter == 'rented') {
                if ($rented > 0) {
                    $data[] = [
                        'key' => $key++,
                        'code' => $row['code'],
                        'item_name' => $row['item_name'],
                        'category' => $row['category_name'],
                        'department' => $row['department_name'],
                        'status' => 'rented',
                        'quantity' => $rented,
                        'equipment_id' => $row['id'],
                        'is_sub' => false,
                        'rented_in_range' => 0,
                        'returned_in_range' => 0
                    ];
                }
            }
        }
    }
    // If bulk items were fetched, we need to slice them to fit the remaining page length
    if ($start >= $subTotalFiltered) {
        // If the page starts entirely within bulk items, adjust offset
        $bulkOffset = $start - $subTotalFiltered;
        $data = array_slice($data, $bulkOffset, $length);
    } else {
        // If bulk items are appended to sub-items, just slice the combined array
        $data = array_slice($data, 0, $length);
    }
}


// 3. Get absolute totals (before filtering) for DataTables recordsTotal
$absSubRes = mysqli_fetch_assoc($db->readQuery("SELECT COUNT(*) as count FROM sub_equipment"));
$absBulkRes = mysqli_fetch_assoc($db->readQuery("SELECT COUNT(*) as count FROM equipment WHERE no_sub_items = 1"));
$absoluteTotal = (int)$absSubRes['count'] + (int)$absBulkRes['count'];

echo json_encode([
    "draw" => intval($_REQUEST['draw'] ?? 0),
    "recordsTotal" => $absoluteTotal,
    "recordsFiltered" => $subTotalFiltered + $bulkTotalFiltered,
    "data" => $data
]);
exit;
