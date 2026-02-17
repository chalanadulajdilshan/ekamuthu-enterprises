<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

if (isset($_POST['action']) && $_POST['action'] == 'get_report') {
    
    $from = $_POST['from'] ?? date('Y-m-01');
    $to = $_POST['to'] ?? date('Y-m-d');
    
    $basic_filter = "WHERE `created_at` BETWEEN '$from 00:00:00' AND '$to 23:59:59'";
    
    $db = Database::getInstance();
    
    // 1. Total Outsource Cost
    $q1 = "SELECT SUM(`outsource_cost`) as total FROM `repair_jobs` $basic_filter AND `is_outsource` = 1";
    $outsource_cost = mysqli_fetch_assoc($db->readQuery($q1))['total'] ?? 0;
    
    // 2. Total Commission
    $q2 = "SELECT SUM(`commission_amount`) as total FROM `repair_jobs` $basic_filter";
    $commission = mysqli_fetch_assoc($db->readQuery($q2))['total'] ?? 0;
    
    // 3. Total Repair Income (Total Cost charged to customer)
    $q3 = "SELECT SUM(`total_cost`) as total FROM `repair_jobs` $basic_filter";
    $total_income = mysqli_fetch_assoc($db->readQuery($q3))['total'] ?? 0;
    
    // 4. Total Profit (Income - Outsource Cost)
    // Note: This is an approximation. Ideally we subtract parts cost too if we had buy price.
    $total_profit = $total_income - $outsource_cost;
    
    // 5. Total Machines (All)
    $q5 = "SELECT COUNT(*) as count FROM `repair_jobs` $basic_filter";
    $total_machines = mysqli_fetch_assoc($db->readQuery($q5))['count'] ?? 0;
    
    // 6. Total Outside Machines
    $q6 = "SELECT COUNT(*) as count FROM `repair_jobs` $basic_filter AND `is_outsource` = 1";
    $total_outsource_machines = mysqli_fetch_assoc($db->readQuery($q6))['count'] ?? 0;

    // Total In-house Machines (Total - Outsource)
    $total_in_house_machines = $total_machines - $total_outsource_machines;
    
    // 7. Cannot Repair (Taken back)
    // We assume if status is 'cannot_repair', it is taken back or returned.
    $q7 = "SELECT COUNT(*) as count FROM `repair_jobs` $basic_filter AND `job_status` = 'cannot_repair'";
    $cannot_repair = mysqli_fetch_assoc($db->readQuery($q7))['count'] ?? 0;

    // 7.1 Pending
    $q_pending = "SELECT COUNT(*) as count FROM `repair_jobs` $basic_filter AND `job_status` = 'pending'";
    $count_pending = mysqli_fetch_assoc($db->readQuery($q_pending))['count'] ?? 0;

    // 7.2 Checking
    $q_checking = "SELECT COUNT(*) as count FROM `repair_jobs` $basic_filter AND `job_status` = 'checking'";
    $count_checking = mysqli_fetch_assoc($db->readQuery($q_checking))['count'] ?? 0;

    // 7.3 In Progress
    $q_progress = "SELECT COUNT(*) as count FROM `repair_jobs` $basic_filter AND `job_status` = 'in_progress'";
    $count_in_progress = mysqli_fetch_assoc($db->readQuery($q_progress))['count'] ?? 0;
    
    // 8. Repaired but NOT Taken (Completed but not delivered)
    // Status 'completed' means repaired but not yet marked as delivered.
    $q8 = "SELECT COUNT(*) as count FROM `repair_jobs` $basic_filter AND `job_status` = 'completed'";
    $repaired_not_taken = mysqli_fetch_assoc($db->readQuery($q8))['count'] ?? 0;
    
    // 9. Repaired and TAKEN (Delivered) 
    $q9 = "SELECT COUNT(*) as count FROM `repair_jobs` $basic_filter AND `job_status` = 'delivered'";
    $repaired_taken = mysqli_fetch_assoc($db->readQuery($q9))['count'] ?? 0;
    
    
    echo json_encode([
        "status" => "success",
        "data" => [
            "outsource_cost" => number_format($outsource_cost, 2),
            "commission" => number_format($commission, 2),
            "total_income" => number_format($total_income, 2),
            "total_profit" => number_format($total_profit, 2),
            "total_machines" => $total_machines,
            "total_outsource_machines" => $total_outsource_machines,
            "total_in_house_machines" => $total_in_house_machines,
            "cannot_repair" => $cannot_repair,
            "pending" => $count_pending,
            "checking" => $count_checking,
            "in_progress" => $count_in_progress,
            "repaired_not_taken" => $repaired_not_taken,
            "repaired_taken" => $repaired_taken
        ]
    ]);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'get_job_details') {

    $from = $_POST['from'] ?? date('Y-m-01');
    $to = $_POST['to'] ?? date('Y-m-d');
    $type = $_POST['type'] ?? '';

    $basic_filter = "WHERE `created_at` BETWEEN '$from 00:00:00' AND '$to 23:59:59'";
    $extra_filter = "";

    switch ($type) {
        case 'total_machines':
            $extra_filter = "";
            break;
        case 'total_outsource_machines':
            $extra_filter = "AND `is_outsource` = 1";
            break;
        case 'total_in_house_machines':
            $extra_filter = "AND (`is_outsource` = 0 OR `is_outsource` IS NULL)";
            break;
        case 'cannot_repair':
            $extra_filter = "AND `job_status` = 'cannot_repair'";
            break;
        case 'pending':
            $extra_filter = "AND `job_status` = 'pending'";
            break;
        case 'checking':
            $extra_filter = "AND `job_status` = 'checking'";
            break;
        case 'in_progress':
            $extra_filter = "AND `job_status` = 'in_progress'";
            break;
        case 'repaired_not_taken':
            $extra_filter = "AND (`job_status` = 'completed')";
            break;
        case 'repaired_taken':
            $extra_filter = "AND `job_status` = 'delivered'";
            break;
        default:
            $extra_filter = "AND 1=0"; // Invalid type, return nothing
            break;
    }

    $db = Database::getInstance();
    $sql = "SELECT id, job_code, created_at, customer_name, customer_phone, item_type, machine_name, machine_code, technical_issue, job_status, total_cost 
            FROM `repair_jobs` $basic_filter $extra_filter ORDER BY id DESC";
    
    $result = $db->readQuery($sql);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $row['total_cost'] = number_format($row['total_cost'], 2);
        $data[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);
    exit();
}
?>
