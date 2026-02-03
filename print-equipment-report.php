<?php
include 'class/include.php';
include 'auth.php';

$from_date = $_GET['from'] ?? date('Y-m-d');
$to_date = $_GET['to'] ?? date('Y-m-d');

$db = Database::getInstance();

// Query Resources
$query = "SELECT r.*, c.name as customer_name, c.mobile_number 
          FROM `equipment_rent` r 
          LEFT JOIN `customer_master` c ON r.customer_id = c.id 
          WHERE r.rental_date BETWEEN '$from_date' AND '$to_date' 
          ORDER BY r.rental_date DESC, r.id DESC";

$result = $db->readQuery($query);

// Calc Totals
$total_deposit = 0;
$total_transport = 0;
$total_additional = 0;
$total_refund = 0;
$total_revenue = 0;
$report_conversations = [];

while ($row = mysqli_fetch_assoc($result)) {
    $rent_id = $row['id'];
    $deposit = floatval($row['deposit_total']);
    $transport = floatval($row['transport_cost']);

    // Get returns info
    $qReturns = "SELECT 
                    COALESCE(SUM(err.additional_payment), 0) as tot_additional,
                    COALESCE(SUM(err.refund_amount), 0) as tot_refund 
                 FROM equipment_rent_items eri
                 JOIN equipment_rent_returns err ON eri.id = err.rent_item_id
                 WHERE eri.rent_id = $rent_id";
    $resReturns = mysqli_fetch_assoc($db->readQuery($qReturns));
    $additional = floatval($resReturns['tot_additional']);
    $refund = floatval($resReturns['tot_refund']);

    $net_revenue = $deposit + $transport + $additional - $refund;

    // Get Items
    $qItems = "SELECT GROUP_CONCAT(DISTINCT e.item_name SEPARATOR ', ') as items
               FROM equipment_rent_items eri
               JOIN equipment e ON eri.equipment_id = e.id
               WHERE eri.rent_id = $rent_id";
    $resItems = mysqli_fetch_assoc($db->readQuery($qItems));
    
    $row['items_txt'] = $resItems['items'] ?? '';
    // Store raw values for table display
    $row['val_additional'] = $additional;
    $row['val_refund'] = $refund;
    $row['revenue'] = $net_revenue;
    $report_conversations[] = $row;

    $total_deposit += $deposit;
    $total_transport += $transport;
    $total_additional += $additional;
    $total_refund += $refund;
    $total_revenue += $net_revenue;
}

// Calculate Global Item Count
$qTotalItems = "SELECT SUM(eri.quantity) as total_qty 
                FROM equipment_rent_items eri 
                JOIN equipment_rent er ON eri.rent_id = er.id 
                WHERE er.rental_date BETWEEN '$from_date' AND '$to_date'";
$resTotalItems = mysqli_fetch_assoc($db->readQuery($qTotalItems));
$total_items_count = $resTotalItems['total_qty'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Rental Report - <?php echo $from_date ?></title>
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
        දෛනික කුලී වාර්තාව (Daily Rental Report) <br>
        <span style="font-size: 14px; font-weight: normal;">
            සිට: <?php echo $from_date ?> &nbsp; දක්වා: <?php echo $to_date ?>
        </span>
    </div>

    <div class="summary-box">
        <div class="stat-item">
            <span class="stat-label">නිකුත් කළ මුළු අයිතම (Total Items)</span>
            <span class="stat-value"><?php echo $total_items_count; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">එකතු කළ මුළු තැන්පතු (Total Deposit)</span>
            <span class="stat-value">Rs. <?php echo number_format($total_deposit, 2); ?></span>
        </div>

    </div>

    <table>
        <thead>
            <tr>
                <th>බිල් අංකය</th>
                <th>දිනය</th>
                <th>පාරිභෝගිකයා</th>
                <th>අයිතම</th>
                <th class="text-right">තැන්පතු</th>
                <th class="text-right">ප්‍රවාහන</th>
                <th class="text-right">අමතර</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_deposit = 0;
            $grand_transport = 0;
            $grand_additional = 0;
            $grand_refund = 0;
            $grand_revenue = 0;
            
            if (count($report_conversations) > 0): 
            ?>
                <?php foreach ($report_conversations as $row): 
                    $grand_deposit += $row['deposit_total'];
                    // Ensure these keys exist in $row, they should be from the loop above
                    // We need to make sure we capture transport in the loop above or via row.
                    // transport_cost is in $row from the query.
                    // additional, refund are calculated variables in the loop, we need to add them to $row array in the loop above first.
                ?>
                <tr>
                    <td><?php echo $row['bill_number']; ?></td>
                    <td><?php echo $row['rental_date']; ?></td>
                    <td><?php echo $row['customer_name']; ?></td>
                    <td><?php echo $row['items_txt']; ?></td>
                    <td class="text-right"><?php echo number_format($row['deposit_total'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['transport_cost'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($row['val_additional'] ?? 0, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="4" class="text-right">එකතුව (TOTAL):</td>
                    <td class="text-right"><?php echo number_format($total_deposit, 2); ?></td>
                    <td class="text-right"><?php echo number_format($total_transport ?? 0, 2); ?></td>
                    <td class="text-right"><?php echo number_format($total_additional ?? 0, 2); ?></td>
                </tr>
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
