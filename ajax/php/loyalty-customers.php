<?php

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/loyalty-debug.log');

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Filter Loyalty Customers
if (isset($_POST['filter'])) {
    $db = Database::getInstance();

    $start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
    $length = isset($_REQUEST['length']) ? (int) $_REQUEST['length'] : 100;
    $search = $_REQUEST['search']['value'] ?? '';
    
    $startDate = $_POST['start_date'] ?? date('Y-m-01');
    $endDate = $_POST['end_date'] ?? date('Y-m-t');
    $minSales = isset($_POST['min_sales']) && $_POST['min_sales'] !== '' ? floatval($_POST['min_sales']) : 0;

    // Base query conditions
    $where = "WHERE er.rental_date BETWEEN '$startDate' AND '$endDate'";
    
    if (!empty($search)) {
        $where .= " AND (cm.name LIKE '%$search%' OR cm.code LIKE '%$search%' OR cm.mobile_number LIKE '%$search%')";
    }

    // Main grouping query
    $baseFrom = "FROM equipment_rent er 
                JOIN customer_master cm ON er.customer_id = cm.id 
                $where 
                GROUP BY er.customer_id";

    // Column selection for totals (needed for HAVING clause)
    $totalsSelect = "SUM(COALESCE(er.transport_cost, 0)) as total_transport,
                    (
                        SELECT SUM(eri.amount) 
                        FROM equipment_rent_items eri 
                        JOIN equipment_rent er2 ON eri.rent_id = er2.id 
                        WHERE er2.customer_id = cm.id 
                        AND er2.rental_date BETWEEN '$startDate' AND '$endDate'
                    ) as total_rent_amount,
                    (
                        SELECT SUM(
                            CASE 
                                WHEN type = 'earn' THEN points 
                                WHEN type = 'redeem' THEN -points
                                ELSE 0 
                            END
                        ) 
                        FROM loyalty_points lp 
                        WHERE lp.customer_id = cm.id
                    ) as points_balance";

    // Having clause
    $having = "";
    if ($minSales > 0) {
        $having = "HAVING (total_transport + total_rent_amount) >= $minSales";
    }

    // Total filtered records
    // If filtering by sales, we must calculate totals in the subquery
    if ($minSales > 0) {
        $countSql = "SELECT COUNT(*) as filtered FROM (
                        SELECT er.customer_id, $totalsSelect 
                        $baseFrom 
                        $having
                     ) as temp";
    } else {
        $countSql = "SELECT COUNT(*) as filtered FROM (SELECT er.customer_id $baseFrom) as temp";
    }
    
    $countQuery = $db->readQuery($countSql);
    $filteredData = mysqli_fetch_assoc($countQuery)['filtered'];
    
    // Total unfiltered (ignoring min sales and search, but keeping date range ideally?)
    $totalData = $filteredData;

    // Fetch Aggregated Data
    $sql = "SELECT 
                cm.id as customer_id, cm.code, cm.name, cm.mobile_number,
                COUNT(DISTINCT er.id) as bill_count,
                $totalsSelect
            $baseFrom 
            $having
            ORDER BY bill_count DESC, total_rent_amount DESC 
            LIMIT $start, $length";
            
    $dataQuery = $db->readQuery($sql);
    
    $data = [];
    $key = 1;
    
    while ($row = mysqli_fetch_assoc($dataQuery)) {
        $totalValue = floatval($row['total_transport']) + floatval($row['total_rent_amount']);
        $points = floatval($row['points_balance'] ?? 0);
        
        $actionBtn = '<button class="btn btn-sm btn-soft-primary add-points-btn" data-id="'.$row['customer_id'].'" data-name="'.htmlspecialchars($row['name']).'" title="Add Points"><i class="uil uil-star"></i></button>';

        $nestedData = [
            "key" => $key,
            "id" => $row['customer_id'],
            "code" => $row['code'],
            "name" => $row['name'],
            "mobile" => $row['mobile_number'],
            "bill_count" => $row['bill_count'],
            "total_value" => number_format($totalValue, 2),
            "points" => number_format($points, 2),
            "action" => $actionBtn
        ];
        
        $data[] = $nestedData;
        $key++;
    }

    echo json_encode([
        "draw" => intval($_REQUEST['draw'] ?? 1),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($filteredData),
        "data" => $data
    ]);
    exit;
}

// Get Customer Invoices for Expansion
if (isset($_POST['action']) && $_POST['action'] === 'get_customer_invoices') {
    $customerId = $_POST['customer_id'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    
    $db = Database::getInstance();
    
    // Get all bills for this customer in range
    $query = "SELECT er.*, 
              (SELECT SUM(amount) FROM equipment_rent_items WHERE rent_id = er.id) as rent_total
              FROM equipment_rent er 
              WHERE er.customer_id = $customerId 
              AND er.rental_date BETWEEN '$startDate' AND '$endDate' 
              ORDER BY er.rental_date DESC";
              
    $result = $db->readQuery($query);
    
    $invoices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transport = floatval($row['transport_cost']);
        $rent = floatval($row['rent_total']);
        $total = $transport + $rent;
        
        $statusBadge = '';
        if ($row['status'] == 'rented') {
            $statusBadge = '<span class="badge bg-warning">Rented</span>';
        } else {
            $statusBadge = '<span class="badge bg-success">Returned</span>';
        }
        
        $invoices[] = [
            'id' => $row['id'],
            'bill_number' => $row['bill_number'],
            'date' => $row['rental_date'],
            'total_amount' => number_format($total, 2),
            'status_label' => $statusBadge
        ];
    }
    
    echo json_encode(['status' => 'success', 'data' => $invoices]);
    exit;
}

// Save Points
if (isset($_POST['action']) && $_POST['action'] === 'save_points') {
    try {
        $POINTS = new LoyaltyPoints(NULL);
        $POINTS->customer_id = $_POST['customer_id'];
        $POINTS->points = $_POST['points'];
        $POINTS->type = $_POST['type'];
        $POINTS->description = $_POST['description'];
        
        if ($POINTS->create()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database Insert Failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
    }
    exit;
}
