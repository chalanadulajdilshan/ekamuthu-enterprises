<?php
include 'class/include.php';
include 'auth.php';

$from_date = $_GET['from_date'] ?? date('Y-m-d');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? 'all';
$employee_id = $_GET['employee_id'] ?? 'all';
$search_query = $_GET['search_query'] ?? '';

$db = Database::getInstance();

// Base query
$query = "SELECT r.*, e.name as employee_name 
          FROM `repair_jobs` r 
          LEFT JOIN `employee_master` e ON r.employee_id = e.id 
          WHERE 1=1";

if (!empty($search_query)) {
    $search_query_esc = $db->escapeString($search_query);
    $query .= " AND (r.machine_name LIKE '%$search_query_esc%' OR r.machine_code LIKE '%$search_query_esc%' OR r.job_code LIKE '%$search_query_esc%')";
}

if (!empty($from_date) && !empty($to_date)) {
    $query .= " AND r.item_breakdown_date BETWEEN '$from_date' AND '$to_date'";
}

if ($status != 'all') {
    $query .= " AND r.job_status = '$status'";
}

if ($employee_id != 'all') {
    $query .= " AND r.employee_id = '$employee_id'";
}

$query .= " ORDER BY r.item_breakdown_date ASC, r.id ASC";

$result = $db->readQuery($query);

$total_jobs = 0;
$total_revenue = 0;
$total_commission = 0;
$total_repair_charges = 0;
$total_item_cost = 0;
$report_data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $repair_charge = floatval($row['repair_charge'] ?? 0);
    $commission_amount = floatval($row['commission_amount'] ?? 0);
    $total_cost = floatval($row['total_cost'] ?? 0);
    $item_cost = $total_cost - $repair_charge; 

    // Format status label
    $status_labels = [
        'pending' => 'Pending - පොරොත්තු',
        'in_progress' => 'In Progress - ප්‍රගතියේ',
        'completed' => 'Completed - සම්පූර්ණයි',
        'delivered' => 'Delivered - භාර දෙන ලදී',
        'cannot_repair' => 'Cannot Repair - අලුත්වැඩියා කළ නොහැක'
    ];
    $status_label = $status_labels[$row['job_status']] ?? ucfirst($row['job_status']);

    $row['status_label'] = $status_label;
    $row['customer_display'] = $row['customer_name'] . ($row['customer_phone'] ? ' (' . $row['customer_phone'] . ')' : '');
    $row['employee_name'] = $row['employee_name'] ?? 'Not Assigned';
    $row['repair_charge'] = $repair_charge;
    $row['commission_amount'] = $commission_amount;
    $row['item_cost'] = $item_cost;
    $row['total_cost'] = $total_cost;
    
    $report_data[] = $row;
    
    $total_jobs++;
    $total_revenue += $total_cost;
    $total_commission += $commission_amount;
    $total_repair_charges += $repair_charge;
    $total_item_cost += $item_cost;
}

// Convert status to readable text for subtitle
$status_title_map = [
    'all' => 'All Statuses',
    'pending' => 'Pending - පොරොත්තු',
    'in_progress' => 'In Progress - ප්‍රගතියේ',
    'completed' => 'Completed - සම්පූර්ණයි',
    'delivered' => 'Delivered - භාර දෙන ලදී',
    'cannot_repair' => 'Cannot Repair - අලුත්වැඩියා කළ නොහැක'
];
$status_title = $status_title_map[$status] ?? 'All Statuses';

$employee_title = "All Employees";
if ($employee_id != 'all') {
    $EMP = new EmployeeMaster($employee_id);
    $employee_title = $EMP->name . ' (' . $EMP->code . ')';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair Jobs Report <?php echo (!empty($from_date)) ? '- '.htmlspecialchars($from_date) : ''; ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .company-name { font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .company-meta { font-size: 14px; margin-top: 5px; }
        .report-title { font-size: 18px; font-weight: bold; margin: 20px 0; text-align: center; text-decoration: underline; }
        .summary-box { border: 1px solid #333; padding: 15px; margin-bottom: 20px; display: flex; justify-content: space-around; background: #f9f9f9; }
        .stat-item { text-align: center; }
        .stat-label { font-size: 14px; font-weight: bold; color: #555; display: block; margin-bottom: 5px; }
        .stat-value { font-size: 20px; font-weight: bold; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #eee; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #777; border-top: 1px solid #ccc; padding-top: 10px; }
        @media print {
            button { display: none; }
            body { margin: 0; padding: 15px; }
            @page {
                size: landscape;
                margin: 10mm;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <div class="company-name"><?php echo $COMPANY_PROFILE_DETAILS->name ?? 'Company Name'; ?></div>
        <div class="company-meta">
            <?php echo $COMPANY_PROFILE_DETAILS->address ?? ''; ?><br>
            Tel: <?php echo $COMPANY_PROFILE_DETAILS->phone_number ?? ''; ?> | Email: <?php echo $COMPANY_PROFILE_DETAILS->email ?? ''; ?>
        </div>
    </div>

    <div class="report-title">
        අලුත්වැඩියා රැකියා වාර්තාව (Repair Jobs Report) <br>
        <span style="font-size: 14px; font-weight: normal; display: block; margin-top: 5px;">
            <?php if (!empty($from_date) && !empty($to_date)): ?>
                From Date (සිට): <?php echo htmlspecialchars($from_date); ?> &nbsp; | &nbsp; To Date (දක්වා): <?php echo htmlspecialchars($to_date); ?>
            <?php else: ?>
                All Dates (සියලුම දින)
            <?php endif; ?>
        </span>
        <span style="font-size: 14px; font-weight: normal; color: #555;">
            තත්ත්වය (Status): <?php echo $status_title; ?> | සේවකයා (Employee): <?php echo $employee_title; ?>
            <?php if (!empty($search_query)): ?>
                | සෙවීම (Search): <?php echo htmlspecialchars($search_query); ?>
            <?php endif; ?>
        </span>
    </div>

    <div class="summary-box">
        <div class="stat-item">
            <span class="stat-label">Total Jobs (මුළු රැකියා)</span>
            <span class="stat-value"><?php echo $total_jobs; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Total Revenue (මුළු ආදායම)</span>
            <span class="stat-value">Rs. <?php echo number_format($total_revenue, 2); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Total Commission (කොමිස්)</span>
            <span class="stat-value">Rs. <?php echo number_format($total_commission, 2); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Repair Charges (ගාස්තු)</span>
            <span class="stat-value">Rs. <?php echo number_format($total_repair_charges, 2); ?></span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Job Code - කේතය</th>
                <th>Breakdown Date - දිනය</th>
                <th>Complete Date - දිනය</th>
                <th>Customer - පාරිභෝගික</th>
                <th>Machine/Item - යන්ත්‍රය</th>
                <th>Code - කේතය</th>
                <th>Status - තත්ත්වය</th>
                <th>Employee - සේවකයා</th>
                <th class="text-right">Repair Charge<br>ගාස්තුව</th>
                <th class="text-right">Commission<br>කොමිස්</th>
                <th class="text-right">Item Cost<br>අයිතම</th>
                <th class="text-right">Total Cost<br>මුළු</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($report_data) > 0): ?>
                <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo $row['job_code']; ?></td>
                    <td><?php echo $row['item_breakdown_date']; ?></td>
                    <td><?php echo $row['item_completed_date']; ?></td>
                    <td><?php echo $row['customer_display']; ?></td>
                    <td><?php echo $row['machine_name']; ?></td>
                    <td><?php echo $row['machine_code']; ?></td>
                    <td><?php echo $row['status_label']; ?></td>
                    <td><?php echo $row['employee_name']; ?></td>
                    <td class="text-right"><?php echo number_format($row['repair_charge'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['commission_amount'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['item_cost'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['total_cost'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="8" class="text-right">එකතුව (TOTAL):</td>
                    <td class="text-right"><?php echo number_format($total_repair_charges, 2); ?></td>
                    <td class="text-right"><?php echo number_format($total_commission, 2); ?></td>
                    <td class="text-right"><?php echo number_format($total_item_cost, 2); ?></td>
                    <td class="text-right"><?php echo number_format($total_revenue, 2); ?></td>
                </tr>
            <?php else: ?>
                <tr><td colspan="10" style="text-align:center;">නොමැත (No records found).</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <small>මුද්‍රණය කළේ (Printed on): <?php echo date('Y-m-d H:i:s'); ?></small>
    </div>

    <button onclick="window.print()" style="margin-top:20px; padding: 10px 20px; font-size: 16px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px;">Print Report</button>

</body>
</html>
