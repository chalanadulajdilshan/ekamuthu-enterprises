<?php
include 'class/include.php';
include 'auth.php';

$customerId = isset($_GET['customer_id']) && !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

$db = Database::getInstance();

$where = '';
if ($customerId > 0) {
    $where = "WHERE `id` = $customerId";
}

// Get customers
$query = "SELECT `id`, `name` FROM `customer_master` $where ORDER BY `name` ASC";
$result = $db->readQuery($query);

$data = [];
$grandTotalRent = 0;
$grandTotalPaid = 0;
$grandTotalBalance = 0;
$customerFilterName = '';

while ($row = mysqli_fetch_assoc($result)) {
    $cusId = $row['id'];

    $summaryQuery = "SELECT 
                        SUM(err.outstanding_amount) as total_outstanding,
                        SUM(err.customer_paid) as total_paid_for_items
                     FROM `equipment_rent_returns` err
                     INNER JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
                     INNER JOIN `equipment_rent` er ON eri.rent_id = er.id
                     WHERE er.customer_id = '$cusId'";

    $summaryResult = $db->readQuery($summaryQuery);
    $summaryRow = mysqli_fetch_assoc($summaryResult);

    $totalOutstanding = floatval($summaryRow['total_outstanding'] ?? 0);
    $totalPaidForItems = floatval($summaryRow['total_paid_for_items'] ?? 0);

    $totalRent = $totalOutstanding + $totalPaidForItems;
    $totalPaid = $totalPaidForItems;
    $balance = $totalOutstanding;

    if ($balance > 0) {
        $data[] = [
            'customer_name' => $row['name'],
            'total_rent' => $totalRent,
            'total_paid' => $totalPaid,
            'balance' => $balance
        ];

        $grandTotalRent += $totalRent;
        $grandTotalPaid += $totalPaid;
        $grandTotalBalance += $balance;
    }

    if ($customerId > 0) {
        $customerFilterName = $row['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstanding Report - <?php echo $COMPANY_PROFILE_DETAILS->name ?? 'Company'; ?></title>
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
        .text-danger { color: red; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #eee; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #777; border-top: 1px solid #ccc; padding-top: 10px; }
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
        Outstanding Report <br>
        <?php if (!empty($customerFilterName)): ?>
            <span style="font-size: 14px; font-weight: normal;">
                පාරිභෝගිකයා: <?php echo $customerFilterName; ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="summary-box">
        <div class="stat-item">
            <span class="stat-label">මුළු කුලී මුදල (Total Rent)</span>
            <span class="stat-value">Rs. <?php echo number_format($grandTotalRent, 2); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">මුළු ගෙවූ මුදල (Total Paid)</span>
            <span class="stat-value">Rs. <?php echo number_format($grandTotalPaid, 2); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">මුළු බාකිය (Total Outstanding)</span>
            <span class="stat-value text-danger">Rs. <?php echo number_format($grandTotalBalance, 2); ?></span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>පාරිභෝගිකයා (Customer Name)</th>
                <th class="text-right">මුළු කුලී මුදල (Total Rent Amount)</th>
                <th class="text-right">මුළු ගෙවූ මුදල (Total Paid Amount)</th>
                <th class="text-right">බාකිය (Balance Outstanding)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($data) > 0): ?>
                <?php $i = 1; foreach ($data as $row): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['customer_name']; ?></td>
                    <td class="text-right"><?php echo number_format($row['total_rent'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['total_paid'], 2); ?></td>
                    <td class="text-right text-danger"><?php echo number_format($row['balance'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="2" class="text-right">එකතුව (TOTAL):</td>
                    <td class="text-right"><?php echo number_format($grandTotalRent, 2); ?></td>
                    <td class="text-right"><?php echo number_format($grandTotalPaid, 2); ?></td>
                    <td class="text-right text-danger"><?php echo number_format($grandTotalBalance, 2); ?></td>
                </tr>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">බාකි නොමැත (No outstanding records found).</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: right;">
        <small>මුද්‍රණය කළේ: <?php echo date('Y-m-d H:i:s'); ?></small>
    </div>

    <button onclick="window.print()" style="margin-top:20px; padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Report</button>

</body>
</html>
