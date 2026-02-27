<?php

include '../../class/include.php';

header('Content-Type: application/json; charset=UTF8');

// Get repair job report
if (isset($_POST['action']) && $_POST['action'] == 'get_repair_job_report') {
    $from_date = $_POST['from_date'] ?? null;
    $to_date = $_POST['to_date'] ?? null;
    $status = $_POST['status'] ?? 'all';

    $db = Database::getInstance();

    // Base query
    $query = "SELECT r.*, e.name as employee_name 
              FROM `repair_jobs` r 
              LEFT JOIN `employee_master` e ON r.employee_id = e.id 
              WHERE 1=1";

    // Date filter (using created_at or item_breakdown_date - usually created_at for reports)
    // Let's use created_at casting to DATE for filter
    if ($from_date && $to_date) {
        $query .= " AND DATE(r.created_at) BETWEEN '$from_date' AND '$to_date'";
    }

    // Status filter
    if ($status != 'all') {
        $query .= " AND r.job_status = '$status'";
    }

    $query .= " ORDER BY r.created_at DESC";

    $result = $db->readQuery($query);

    $data = [];
    $total_jobs = 0;
    $total_revenue = 0;
    $total_commission = 0;
    $total_repair_charges = 0;

    while ($row = mysqli_fetch_array($result)) {
        
        $repair_charge = floatval($row['repair_charge']);
        $commission_amount = floatval($row['commission_amount']);
        $total_cost = floatval($row['total_cost']);
        $item_cost = $total_cost - $repair_charge; // Approximate item cost derived

        // Format status label
        $status_labels = [
            'pending' => '<span class="badge badge-pending">Pending - පොරොත්තු</span>',
            'in_progress' => '<span class="badge badge-in_progress">In Progress - ප්‍රගතියේ</span>',
            'completed' => '<span class="badge badge-completed">Completed - සම්පූර්ණයි</span>',
            'delivered' => '<span class="badge badge-delivered">Delivered - භාර දෙන ලදී</span>',
            'cannot_repair' => '<span class="badge badge-cannot_repair">Cannot Repair - අලුත්වැඩියා කළ නොහැක</span>'
        ];
        $status_badge = $status_labels[$row['job_status']] ?? $row['job_status'];

        $data[] = [
            'job_code' => $row['job_code'],
            'created_at' => $row['created_at'],
            'customer_name' => $row['customer_name'] . ($row['customer_phone'] ? ' (' . $row['customer_phone'] . ')' : ''),
            'machine_name' => $row['machine_name'],
            'status' => $status_badge,
            'employee_name' => $row['employee_name'] ?? 'Not Assigned',
            'repair_charge' => number_format($repair_charge, 2),
            'commission_amount' => number_format($commission_amount, 2),
            'item_cost' => number_format($item_cost, 2),
            'total_cost' => number_format($total_cost, 2),
            // Unformatted values for potential client-side summation if needed
            'val_repair_charge' => $repair_charge,
            'val_commission' => $commission_amount,
            'val_item_cost' => $item_cost,
            'val_total_cost' => $total_cost,
            'id' => $row['id']
        ];

        $total_jobs++;
        $total_revenue += $total_cost;
        $total_commission += $commission_amount;
        $total_repair_charges += $repair_charge;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'summary' => [
            'total_jobs' => $total_jobs,
            'total_revenue' => number_format($total_revenue, 2),
            'total_commission' => number_format($total_commission, 2),
            'total_repair_charges' => number_format($total_repair_charges, 2)
        ]
    ]);
    exit();
}
?>
