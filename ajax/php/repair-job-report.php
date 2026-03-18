<?php

include '../../class/include.php';

header('Content-Type: application/json; charset=UTF8');

// Get repair job report
if (isset($_POST['action']) && $_POST['action'] == 'get_repair_job_report') {
    $db = Database::getInstance();
    $from_date = isset($_POST['from_date']) ? $db->escapeString($_POST['from_date']) : null;
    $to_date = isset($_POST['to_date']) ? $db->escapeString($_POST['to_date']) : null;
    $status = isset($_POST['status']) ? $db->escapeString($_POST['status']) : 'all';
    $employee_id = isset($_POST['employee_id']) ? $db->escapeString($_POST['employee_id']) : 'all';
    $search_query = isset($_POST['search_query']) ? $db->escapeString($_POST['search_query']) : '';

    // Base query
    $query = "SELECT r.*, e.name as employee_name 
              FROM `repair_jobs` r 
              LEFT JOIN `employee_master` e ON r.employee_id = e.id 
              WHERE 1=1";

    // Search filter
    if (!empty($search_query)) {
        $query .= " AND (r.machine_name LIKE '%$search_query%' OR r.machine_code LIKE '%$search_query%' OR r.job_code LIKE '%$search_query%')";
    }

    // Date filter
    if ($from_date && $to_date) {
        $query .= " AND r.item_breakdown_date BETWEEN '$from_date' AND '$to_date'";
    }

    // Status filter
    if ($status != 'all') {
        $query .= " AND r.job_status = '$status'";
    }

    // Employee filter
    if ($employee_id != 'all') {
        $query .= " AND r.employee_id = '$employee_id'";
    }

    $query .= " ORDER BY CAST(r.job_code AS UNSIGNED) ASC";

    $result = $db->readQuery($query);

    $data = [];
    $employee_summary = [];
    $total_jobs = 0;
    $total_revenue = 0;
    $total_commission = 0;
    $total_repair_charges = 0;

    while ($row = mysqli_fetch_array($result)) {
        
        $repair_charge = floatval($row['repair_charge']);
        $commission_amount = floatval($row['commission_amount']);
        $total_cost = floatval($row['total_cost']);
        $item_cost = $total_cost - $repair_charge; 

        $emp_id = $row['employee_id'] ?? 0;
        $emp_name = $row['employee_name'] ?? 'Not Assigned';
        $job_status = $row['job_status'];

        if (!isset($employee_summary[$emp_id])) {
            $employee_summary[$emp_id] = [
                'name' => $emp_name,
                'pending' => 0,
                'checking' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'delivered' => 0,
                'cannot_repair' => 0,
                'cancelled' => 0,
                'total' => 0
            ];
        }
        $employee_summary[$emp_id][$job_status] = ($employee_summary[$emp_id][$job_status] ?? 0) + 1;
        $employee_summary[$emp_id]['total']++;

        // Format status label
        $status_labels = [
            'pending' => '<span class="badge badge-pending">Pending - පොරොත්තු</span>',
            'checking' => '<span class="badge badge-info">Checking - පරීක්ෂා කරමින්</span>',
            'in_progress' => '<span class="badge badge-in_progress">In Progress - ප්‍රගතියේ</span>',
            'completed' => '<span class="badge badge-completed">Completed - සම්පූර්ණයි</span>',
            'delivered' => '<span class="badge badge-delivered">Delivered - භාර දෙන ලදී</span>',
            'cannot_repair' => '<span class="badge badge-cannot_repair">Cannot Repair - අලුත්වැඩියා කළ නොහැක</span>',
            'cancelled' => '<span class="badge badge-cancelled">Cancelled - අවලංගුයි</span>'
        ];
        $status_badge = $status_labels[$row['job_status']] ?? $row['job_status'];

        $data[] = [
            'job_code' => $row['job_code'],
            'item_breakdown_date' => $row['item_breakdown_date'],
            'item_completed_date' => $row['item_completed_date'] ?? '',
            'customer_name' => $row['customer_name'] . ($row['customer_phone'] ? ' (' . $row['customer_phone'] . ')' : ''),
            'machine_name' => $row['machine_name'],
            'machine_code' => $row['machine_code'],
            'status' => $status_badge,
            'employee_name' => $row['employee_name'] ?? 'Not Assigned',
            'repair_charge' => number_format($repair_charge, 2),
            'commission_amount' => number_format($commission_amount, 2),
            'item_cost' => number_format($item_cost, 2),
            'total_cost' => number_format($total_cost, 2),
            'val_repair_charge' => $repair_charge,
            'val_commission' => $commission_amount,
            'val_item_cost' => $item_cost,
            'val_total_cost' => $total_cost,
            'id' => $row['id']
        ];

        if ($job_status !== 'cancelled') {
            $total_jobs++;
            $total_revenue += $total_cost;
            $total_commission += $commission_amount;
            $total_repair_charges += $repair_charge;
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'employee_summary' => array_values($employee_summary),
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
