<?php
include 'class/include.php';
include 'auth.php';

$db = Database::getInstance();

// Query for blacklisted customers
$query = "SELECT * FROM `customer_master` WHERE `is_blacklisted` = 1 ORDER BY `name` ASC";
$result = $db->readQuery($query);

$total_customers = 0;
$report_data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $report_data[] = $row;
    $total_customers++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blacklisted Customers Report</title>
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
        .text-danger { color: #d9534f; }
        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #777; border-top: 1px solid #ccc; padding-top: 10px; }
        @media print {
            button { display: none; }
            body { margin: 0; padding: 15px; }
            @page {
                size: portrait;
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
        අසාදු ලේඛනගත පාරිභෝගික වාර්තාව (Blacklisted Customers Report)
    </div>

    <div class="summary-box">
        <div class="stat-item">
            <span class="stat-label">Total Blacklisted Customers (මුළු පාරිභෝගිකයින්)</span>
            <span class="stat-value"><?php echo $total_customers; ?></span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code - කේතය</th>
                <th>Customer Name - පාරිභෝගික නම</th>
                <th>Mobile - දුරකථන අංකය</th>
                <th>NIC - හැඳුනුම්පත් අංකය</th>
                <th>Blacklist Reason - හේතුව</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($report_data) > 0): ?>
                <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo $row['code']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['mobile_number']; ?></td>
                    <td><?php echo $row['nic']; ?></td>
                    <td class="text-danger"><?php echo $row['blacklist_reason']; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">නොමැත (No records found).</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <small>මුද්‍රණය කළේ (Printed on): <?php echo date('Y-m-d H:i:s'); ?></small>
    </div>

    <button onclick="window.print()" style="margin-top:20px; padding: 10px 20px; font-size: 16px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px;">Print Report</button>

</body>
</html>
