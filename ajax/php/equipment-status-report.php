<?php
include '../../class/include.php';

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=UTF-8');

$db = Database::getInstance();

// Handle Stats Request
if (isset($_POST['action']) && $_POST['action'] == 'get_stats') {
    $counts = [
        'available' => 0,
        'rented' => 0,
        'damage' => 0,
        'repair' => 0,
        'total' => 0
    ];

    // 1. Sub Equipment Aggregation
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

    // 2. Bulk Equipment Aggregation
    // Calculate total bulk items and total rented bulk items
    $bulkSql = "SELECT SUM(e.quantity) as total_bulk_qty,
                SUM(
                    (SELECT COALESCE(SUM(eri.quantity - (SELECT COALESCE(SUM(return_qty),0) FROM equipment_rent_returns WHERE rent_item_id = eri.id)), 0)
                     FROM equipment_rent_items eri 
                     WHERE eri.equipment_id = e.id 
                     AND eri.status = 'rented'
                     AND (eri.sub_equipment_id IS NULL OR eri.sub_equipment_id = 0)
                    )
                ) as total_bulk_rented
                FROM equipment e
                WHERE e.no_sub_items = 1";
    
    $bulkResult = $db->readQuery($bulkSql);
    $bulkRow = mysqli_fetch_assoc($bulkResult);
    
    $bulkTotal = (float)($bulkRow['total_bulk_qty'] ?? 0);
    $bulkRented = (float)($bulkRow['total_bulk_rented'] ?? 0);
    $bulkAvailable = max(0, $bulkTotal - $bulkRented);

    $counts['available'] += $bulkAvailable;
    $counts['rented'] += $bulkRented;
    $counts['total'] += $bulkTotal;

    echo json_encode(['status' => 'success', 'data' => $counts]);
    exit;
}

$start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
$length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 25;
$search = mysqli_real_escape_string($db->DB_CON, $_REQUEST['search']['value'] ?? '');
$statusFilter = mysqli_real_escape_string($db->DB_CON, $_REQUEST['status'] ?? '');
$fromDate = mysqli_real_escape_string($db->DB_CON, $_REQUEST['from_date'] ?? date('Y-m-01'));
$toDate = mysqli_real_escape_string($db->DB_CON, $_REQUEST['to_date'] ?? date('Y-m-d'));

// We need to fetch data from two sources:
// 1. Bulk Equipment (equipment table where no_sub_items = 1) -> dynamic calculation
// 2. Sub Equipment (sub_equipment table) -> static status

$data = [];

// --- 1. Sub Equipment ---
$subQuery = "SELECT se.code, se.rental_status, e.item_name, c.name as category_name, dm.name as department_name, se.equipment_id,
             (SELECT eri.rent_id FROM equipment_rent_items eri WHERE eri.sub_equipment_id = se.id AND eri.status = 'rented' ORDER BY eri.id DESC LIMIT 1) AS active_rent_id,
             (SELECT rj.id FROM repair_jobs rj WHERE TRIM(rj.machine_code) = TRIM(se.code) AND rj.job_status NOT IN ('delivered') ORDER BY rj.id DESC LIMIT 1) AS active_repair_job_id,
             (SELECT COALESCE(SUM(eri.quantity), 0) FROM equipment_rent_items eri WHERE eri.sub_equipment_id = se.id AND DATE(eri.rental_date) BETWEEN '$fromDate' AND '$toDate') as rented_in_range,
             (SELECT COALESCE(SUM(err.return_qty), 0) FROM equipment_rent_returns err JOIN equipment_rent_items eri ON err.rent_item_id = eri.id WHERE eri.sub_equipment_id = se.id AND DATE(err.return_date) BETWEEN '$fromDate' AND '$toDate') as returned_in_range
             FROM sub_equipment se 
             JOIN equipment e ON se.equipment_id = e.id
             LEFT JOIN equipment_category c ON e.category = c.id
             LEFT JOIN department_master dm ON se.department_id = dm.id
             WHERE 1=1";

if (!empty($statusFilter)) {
    if ($statusFilter == 'available') {
        $subQuery .= " AND (se.rental_status = 'available' OR se.rental_status IS NULL OR se.rental_status = '')";
    } elseif ($statusFilter == 'rented') {
        $subQuery .= " AND (se.rental_status = 'rented' OR se.rental_status = 'rent')";
    } else {
        $subQuery .= " AND se.rental_status LIKE '$statusFilter%'";
    }
}

if (!empty($search)) {
    $subQuery .= " AND (se.code LIKE '%$search%' OR e.item_name LIKE '%$search%')";
}

// --- 2. Bulk Equipment ---
// Logic: 
// IF status is 'available': show bulk item with (total - rented) qty
// IF status is 'rented': show bulk item with (rented) qty
// IF status is 'damage'/'repair': show bulk item with 0 qty or don't show (unless we track bulk damage separately which we don't seem to)

$bulkQuery = "SELECT e.id, e.code, e.item_name, e.quantity as total_qty, c.name as category_name, dm.name as department_name,
              (SELECT COALESCE(SUM(eri.quantity - (SELECT COALESCE(SUM(return_qty),0) FROM equipment_rent_returns WHERE rent_item_id = eri.id)), 0)
               FROM equipment_rent_items eri 
               WHERE eri.equipment_id = e.id 
               AND eri.status = 'rented'
               AND (eri.sub_equipment_id IS NULL OR eri.sub_equipment_id = 0)
              ) as rented_qty,
              (SELECT COALESCE(SUM(eri.quantity), 0)
               FROM equipment_rent_items eri 
               WHERE eri.equipment_id = e.id 
               AND (eri.sub_equipment_id IS NULL OR eri.sub_equipment_id = 0)
               AND DATE(eri.rental_date) BETWEEN '$fromDate' AND '$toDate'
              ) as rented_in_range,
              (SELECT COALESCE(SUM(err.return_qty), 0)
               FROM equipment_rent_returns err
               JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
               WHERE eri.equipment_id = e.id
               AND (eri.sub_equipment_id IS NULL OR eri.sub_equipment_id = 0)
               AND DATE(err.return_date) BETWEEN '$fromDate' AND '$toDate'
              ) as returned_in_range
              FROM equipment e
              LEFT JOIN equipment_category c ON e.category = c.id
              LEFT JOIN department_master dm ON e.department_id = dm.id
              WHERE e.no_sub_items = 1";

if (!empty($search)) {
    $bulkQuery .= " AND (e.code LIKE '%$search%' OR e.item_name LIKE '%$search%')";
}

// Execute Queries
$allResults = [];

// Fetch Sub Equipment
$subResult = $db->readQuery($subQuery);
while ($row = mysqli_fetch_assoc($subResult)) {
    $status = $row['rental_status'] ? $row['rental_status'] : 'available';
    $allResults[] = [
        'code' => $row['code'],
        'item_name' => $row['item_name'],
        'category' => $row['category_name'],
        'department' => $row['department_name'],
        'status' => $status,
        'quantity' => 1,
        'equipment_id' => $row['equipment_id'],
        'active_rent_id' => $row['active_rent_id'],
        'active_repair_job_id' => $row['active_repair_job_id'],
        'is_sub' => true,
        'rented_in_range' => (float)$row['rented_in_range'],
        'returned_in_range' => (float)$row['returned_in_range']
    ];
}

// Fetch Bulk Equipment
$bulkResult = $db->readQuery($bulkQuery);
while ($row = mysqli_fetch_assoc($bulkResult)) {
    $total = (float)$row['total_qty'];
    $rented = (float)$row['rented_qty'];
    $available = max(0, $total - $rented);
    
    // Add 'Available' row
    if (empty($statusFilter) || $statusFilter == 'available') {
        if ($available > 0) {
            $allResults[] = [
                'code' => $row['code'],
                'item_name' => $row['item_name'],
                'category' => $row['category_name'],
                'department' => $row['department_name'],
                'status' => 'available',
                'quantity' => $available,
                'equipment_id' => $row['id'],
                'is_sub' => false,
                'rented_in_range' => (float)$row['rented_in_range'],
                'returned_in_range' => (float)$row['returned_in_range']
            ];
        }
    }

    // Add 'Rented' row
    if (empty($statusFilter) || $statusFilter == 'rented') {
        if ($rented > 0) {
            $allResults[] = [
                'code' => $row['code'],
                'item_name' => $row['item_name'],
                'category' => $row['category_name'],
                'department' => $row['department_name'],
                'status' => 'rented',
                'quantity' => $rented,
                'equipment_id' => $row['id'],
                'is_sub' => false,
                'rented_in_range' => (float)$row['rented_in_range'],
                'returned_in_range' => (float)$row['returned_in_range']
            ];
        }
    }
}

// Pagination & Filtering (Manual implementation since we merged sources)
$totalRecords = count($allResults);
$filteredRecords = $totalRecords; // Search is already applied in SQL

// Slice for pagination
$pagedData = array_slice($allResults, $start, $length);

// Format for DataTable
$formattedData = [];
$key = $start + 1;
foreach ($pagedData as $row) {
    // Basic status normalization for display
    $row['key'] = $key++;
    $formattedData[] = $row;
}

echo json_encode([
    "draw" => intval($_REQUEST['draw']),
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $filteredRecords,
    "data" => $formattedData
]);
