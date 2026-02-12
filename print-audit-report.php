<?php
include 'class/include.php';
include 'auth.php';

$from_date = $_GET['from'] ?? date('Y-m-d');
$to_date = $_GET['to'] ?? date('Y-m-d');
$user_id = $_GET['user_id'] ?? '';

$db = Database::getInstance();

$whereRent = "er.rental_date BETWEEN '$from_date' AND '$to_date'";
$whereReturn = "err.return_date BETWEEN '$from_date' AND '$to_date'";

if (!empty($user_id)) {
    $whereRent .= " AND er.created_by = '" . (int)$user_id . "'";
    $whereReturn .= " AND err.created_by = '" . (int)$user_id . "'";
}

// Re-use UNION logic from ajax/php/audit-report.php
$query = "
    SELECT 
        'Rent' as type,
        er.bill_number as bill_no,
        er.rental_date as date,
        cm.name as customer_name,
        u.name as creator_name,
        er.created_at as created_at_time
    FROM `equipment_rent` er
    LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
    LEFT JOIN `user` u ON er.created_by = u.id
    WHERE $whereRent

    UNION ALL

    SELECT 
        'Return' as type,
        er.bill_number as bill_no,
        err.return_date as date,
        cm.name as customer_name,
        u.name as creator_name,
        err.created_at as created_at_time
    FROM `equipment_rent_returns` err
    INNER JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
    INNER JOIN `equipment_rent` er ON eri.rent_id = er.id
    LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
    LEFT JOIN `user` u ON err.created_by = u.id
    WHERE $whereReturn
    
    ORDER BY created_at_time DESC
";

$result = $db->readQuery($query);
$report_rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $report_rows[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Report - <?php echo $from_date ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .company-name { font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .company-meta { font-size: 14px; margin-top: 5px; }
        .report-title { font-size: 18px; font-weight: bold; margin: 20px 0; text-align: center; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #eee; }
        .text-right { text-align: right; }
        .badge { padding: 3px 6px; border-radius: 4px; color: #fff; font-size: 11px; font-weight: bold; }
        .bg-primary { background-color: #3b5de7; }
        .bg-warning { background-color: #f1b44c; }
        @media print {
            button { display: none; }
            body { margin: 0; padding: 15px; }
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
        Rent/Return Bill Audit Report - බිල්පත් විගණන වාර්තාව <br>
        <span style="font-size: 14px; font-weight: normal;">
            සිට: <?php echo $from_date ?> &nbsp; දක්වා: <?php echo $to_date ?>
        </span>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>වර්ගය (Type)</th>
                <th>බිල් අංකය (Bill No)</th>
                <th>දිනය (Date)</th>
                <th>පාරිභෝගිකයා (Customer)</th>
                <th>සකස් කළේ (Created By)</th>
                <th>වේලාව (Created At)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($report_rows) > 0): ?>
                <?php foreach ($report_rows as $index => $row): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td>
                        <span class="badge <?php echo $row['type'] === 'Rent' ? 'bg-primary' : 'bg-warning'; ?>">
                            <?php echo $row['type']; ?>
                        </span>
                    </td>
                    <td><?php echo $row['bill_no']; ?></td>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo $row['customer_name'] ?: 'N/A'; ?></td>
                    <td><strong><?php echo $row['creator_name'] ?: 'System'; ?></strong></td>
                    <td><?php echo date('Y-m-d h:i A', strtotime($row['created_at_time'])); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;">නොමැත (No records found).</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: right;">
        <small>මුද්‍රණය කළේ: <?php echo date('Y-m-d H:i:s'); ?></small>
    </div>

    <button onclick="window.print()" style="margin-top:20px; padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Report</button>

</body>
</html>
