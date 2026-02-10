<?php
include 'class/include.php';
include 'auth.php';

$from_date = $_GET['from'] ?? date('Y-m-d');
$to_date = $_GET['to'] ?? date('Y-m-d');

$db = Database::getInstance();

// Query Returns within Date Range
// er.deposit_total = Total deposit for the entire invoice (what customer paid upfront)
// Calculate rental_amount dynamically to match system logic
$query = "SELECT err.*, 
                 eri.rental_date,
                 eri.quantity as item_ordered_qty,
                 eri.rent_id,
                 e.item_name, e.code as item_code,
                 er.bill_number,
                 er.deposit_total as invoice_deposit,
                 c.name as customer_name, c.mobile_number,
                 -- Rental Amount Calculation (fixed-rate items use flat amount)
                 CASE WHEN COALESCE(e.is_fixed_rate, 0) = 1
                   THEN ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) * err.return_qty)
                   ELSE (GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                     * ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END))
                     * err.return_qty)
                 END AS calc_rental_amount
          FROM `equipment_rent_returns` err
          INNER JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
          LEFT JOIN `equipment` e ON eri.equipment_id = e.id
          INNER JOIN `equipment_rent` er ON eri.rent_id = er.id
          LEFT JOIN `customer_master` c ON er.customer_id = c.id
          WHERE err.return_date BETWEEN '$from_date' AND '$to_date'
          ORDER BY err.return_date DESC, err.id DESC";

$result = $db->readQuery($query);

$total_rental = 0;
$total_extra_day = 0;
$total_penalty = 0;
$total_additional = 0;
$total_damage = 0;
$report_data = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Basic fields from return
    $damage_amount = floatval($row['damage_amount'] ?? 0);
    $extra_day_amount = floatval($row['extra_day_amount'] ?? 0);
    $penalty_amount = floatval($row['penalty_amount'] ?? 0);
    
    // Calculated Rental from Query
    $rental_amount = floatval($row['calc_rental_amount'] ?? 0);
    
    $additional = floatval($row['additional_payment'] ?? 0);

    $row['rental_amount'] = $rental_amount;
    $row['extra_day_amount'] = $extra_day_amount;
    $row['penalty_amount'] = $penalty_amount;
    $row['additional_payment'] = $additional;
    $row['damage_amount'] = $damage_amount;
    
    $report_data[] = $row;
    
    $total_rental      += $rental_amount;
    $total_extra_day   += $extra_day_amount;
    $total_penalty     += $penalty_amount;
    $total_additional  += $additional;
    $total_damage      += $damage_amount;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Return Income Report - <?php echo $from_date ?></title>
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
        .text-success { color: green; }
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
        ආපසු භාරදීමේ ආදායම් වාර්තාව (Return Income Report) <br>
        <span style="font-size: 14px; font-weight: normal;">
            සිට: <?php echo $from_date ?> &nbsp; දක්වා: <?php echo $to_date ?>
        </span>
    </div>

    <div class="summary-box">
        <div class="stat-item">
            <span class="stat-label">කුලී (Rental)</span>
            <span class="stat-value">Rs. <?php echo number_format($total_rental, 2); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">අතිරික්ත දිනය (Extra Day)</span>
            <span class="stat-value">Rs. <?php echo number_format($total_extra_day, 2); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">දඩ (Penalty)</span>
            <span class="stat-value">Rs. <?php echo number_format($total_penalty, 2); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">අමතර ගෙවීම් (Additional)</span>
            <span class="stat-value text-success">Rs. <?php echo number_format($total_additional, 2); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">හානිය (Damage)</span>
            <span class="stat-value text-danger">Rs. <?php echo number_format($total_damage, 2); ?></span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>බිල් අංකය</th>
                <th>කුලී දිනය</th>
                <th>ආපසු දිනය</th>
                <th>පාරිභෝගිකයා</th>
                <th>අයිතමය</th>
                <th class="text-right">ප්‍රමාණය</th>
                <th class="text-right">කුලී</th>
                <th class="text-right">අතිරික්ත දිනය</th>
                <th class="text-right">දඩ</th>
                <th class="text-right">අමතර</th>
                <th class="text-right">හානිය</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($report_data) > 0): ?>
                <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo $row['bill_number']; ?></td>
                    <td><?php echo $row['rental_date']; ?></td>
                    <td><?php echo $row['return_date']; ?></td>
                    <td><?php echo $row['customer_name']; ?></td>
                    <td><?php echo $row['item_name']; ?></td>
                    <td class="text-right"><?php echo $row['return_qty']; ?></td>
                    <td class="text-right"><?php echo number_format($row['rental_amount'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['extra_day_amount'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['penalty_amount'], 2); ?></td>
                    <td class="text-right text-success"><?php echo number_format($row['additional_payment'], 2); ?></td>
                    <td class="text-right text-danger"><?php echo number_format($row['damage_amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="6" class="text-right">එකතුව (TOTAL):</td>
                    <td class="text-right"><?php echo number_format($total_rental, 2); ?></td>
                    <td class="text-right"><?php echo number_format($total_extra_day, 2); ?></td>
                    <td class="text-right"><?php echo number_format($total_penalty, 2); ?></td>
                    <td class="text-right text-success"><?php echo number_format($total_additional, 2); ?></td>
                    <td class="text-right text-danger"><?php echo number_format($total_damage, 2); ?></td>
                </tr>
            <?php else: ?>
                <tr><td colspan="11" style="text-align:center;">නොමැත (No records found).</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: right;">
        <small>මුද්‍රණය කළේ: <?php echo date('Y-m-d H:i:s'); ?></small>
    </div>

    <button onclick="window.print()" style="margin-top:20px; padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Report</button>

</body>
</html>
