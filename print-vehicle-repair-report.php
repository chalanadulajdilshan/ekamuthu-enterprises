<?php
include 'class/include.php';
include 'auth.php';

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$vehicle_id = $_GET['vehicle_id'] ?? 'all';

$db = Database::getInstance();

$where = " WHERE 1=1";
if (!empty($vehicle_id) && $vehicle_id != 'all') {
    $where .= " AND v.id = " . (int)$vehicle_id;
}

// Subqueries for totals
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

$total_all_repairs = 0;
$total_all_breakdowns = 0;
$report_data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $report_data[] = $row;
    $total_all_repairs += floatval($row['total_repair_expense']);
    $total_all_breakdowns += intval($row['breakdown_count']);
}

$vehicle_title = "All Vehicles";
if ($vehicle_id != 'all') {
    $V = new Vehicle($vehicle_id);
    $vehicle_title = $V->vehicle_no . ' (' . $V->ref_no . ')';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Repair Expenses Report <?php echo (!empty($from_date)) ? '- '.htmlspecialchars($from_date) : ''; ?></title>
    <style>
        body { font-family: 'Inter', Arial, sans-serif; font-size: 14px; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .company-name { font-size: 26px; font-weight: 800; text-transform: uppercase; color: #1a1a1a; }
        .company-meta { font-size: 13px; margin-top: 5px; color: #555; }
        .report-title { font-size: 18px; font-weight: bold; margin: 20px 0; text-align: center; text-decoration: underline; color: #2c3e50; }
        .summary-box { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; display: flex; justify-content: space-around; background: #fbfbfb; border-radius: 8px; }
        .stat-item { text-align: center; flex: 1; }
        .stat-label { font-size: 12px; font-weight: bold; color: #666; display: block; margin-bottom: 5px; text-transform: uppercase; }
        .stat-value { font-size: 18px; font-weight: bold; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        th, td { border: 1px solid #eee; padding: 10px; text-align: left; }
        th { background-color: #f7f7f7; font-weight: bold; border-bottom: 2px solid #ddd; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 40px; font-size: 11px; text-align: center; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
        
        @media print {
            .btn-print { display: none; }
            body { margin: 0; padding: 10mm; }
            @page {
                size: portrait;
                margin: 5mm;
            }
        }
        
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-print:hover { background: #219150; }
    </style>
</head>
<body onload="window.print()">

    <button class="btn-print" onclick="window.print()">Print Report</button>

    <div class="header">
        <div class="company-name"><?php echo $COMPANY_PROFILE_DETAILS->name ?? 'Ekamuthu Enterprises'; ?></div>
        <div class="company-meta">
            <?php echo $COMPANY_PROFILE_DETAILS->address ?? ''; ?><br>
            Tel: <?php echo $COMPANY_PROFILE_DETAILS->phone_number ?? ''; ?> | Email: <?php echo $COMPANY_PROFILE_DETAILS->email ?? ''; ?>
        </div>
    </div>

    <div class="report-title">
        Vehicle Repair Expenses & Breakdown Summary Report <br>
        <span style="font-size: 14px; font-weight: normal; display: block; margin-top: 5px;">
            <?php if (!empty($from_date) && !empty($to_date)): ?>
                Period: <?php echo htmlspecialchars($from_date); ?> To <?php echo htmlspecialchars($to_date); ?>
            <?php else: ?>
                All Time
            <?php endif; ?>
        </span>
        <span style="font-size: 13px; font-weight: normal; color: #666;">
            Vehicle: <?php echo $vehicle_title; ?>
        </span>
    </div>

    <div class="summary-box">
        <div class="stat-item">
            <span class="stat-label">Total Vehicles</span>
            <span class="stat-value"><?php echo count($report_data); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Total Repair Expenses</span>
            <span class="stat-value">Rs. <?php echo number_format($total_all_repairs, 2); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Total Breakdowns</span>
            <span class="stat-value"><?php echo $total_all_breakdowns; ?></span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Vehicle No</th>
                <th>Ref No</th>
                <th>Brand</th>
                <th>Model</th>
                <th class="text-center">Breakdown Count</th>
                <th class="text-right">Total Repair Expense (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($report_data) > 0): ?>
                <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['vehicle_no']); ?></td>
                    <td><?php echo htmlspecialchars($row['ref_no']); ?></td>
                    <td><?php echo htmlspecialchars($row['brand']); ?></td>
                    <td><?php echo htmlspecialchars($row['model']); ?></td>
                    <td class="text-center"><?php echo $row['breakdown_count']; ?></td>
                    <td class="text-right"><?php echo number_format($row['total_repair_expense'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background-color: #f7f7f7; font-size: 13px;">
                    <td colspan="4" class="text-right">GRAND TOTAL:</td>
                    <td class="text-center"><?php echo $total_all_breakdowns; ?></td>
                    <td class="text-right"><?php echo number_format($total_all_repairs, 2); ?></td>
                </tr>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No records found for the selected criteria.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <small>Generated on: <?php echo date('Y-m-d H:i:s'); ?> | Ekamuthu Enterprises System</small>
    </div>

</body>
</html>
