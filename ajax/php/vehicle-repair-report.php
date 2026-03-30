<?php
include_once(dirname(__FILE__) . '/../../class/include.php');
include_once(dirname(__FILE__) . '/../../auth.php');

if ($_POST['action'] == 'GET_REPORT') {
    $from_date = $_POST['fromDate'];
    $to_date = $_POST['toDate'];
    $vehicle_id = $_POST['vehicleId'];

    $db = Database::getInstance();

    $where = " WHERE 1=1";
    if (!empty($vehicle_id) && $vehicle_id != 'all') {
        $where .= " AND v.id = " . (int)$vehicle_id;
    }

    // Subqueries for totals to ensure we get 0 if no records exist
    $repair_subquery = "SELECT SUM(amount) FROM vehicle_repairs WHERE vehicle_id = v.id";
    if (!empty($from_date) && !empty($to_date)) {
        $repair_subquery .= " AND repair_date BETWEEN '$from_date' AND '$to_date'";
    }

    $breakdown_subquery = "SELECT COUNT(*) FROM vehicle_breakdowns WHERE vehicle_id = v.id";
    if (!empty($from_date) && !empty($to_date)) {
        $breakdown_subquery .= " AND DATE(breakdown_date) BETWEEN '$from_date' AND '$to_date'";
    }

    $query = "SELECT v.id, v.vehicle_no, v.ref_no, v.brand, v.model,
              COALESCE(($repair_subquery), 0) as total_repair_expense,
              COALESCE(($breakdown_subquery), 0) as breakdown_count
              FROM vehicles v
              $where
              ORDER BY v.vehicle_no ASC";

    $result = $db->readQuery($query);
    $data = array();
    $total_all_repairs = 0;
    $total_all_breakdowns = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        // Only include vehicles that have at least one repair or breakdown if a date range is selected?
        // Actually, users usually want to see all vehicles, but maybe only those with activity.
        // Let's include everything but highlight activity.
        
        $row['total_repair_expense_formatted'] = number_format($row['total_repair_expense'], 2);
        $data[] = $row;
        
        $total_all_repairs += $row['total_repair_expense'];
        $total_all_breakdowns += $row['breakdown_count'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'summary' => [
            'total_repairs' => number_format($total_all_repairs, 2),
            'total_breakdowns' => $total_all_breakdowns,
            'vehicle_count' => count($data)
        ]
    ]);
    exit();
}
