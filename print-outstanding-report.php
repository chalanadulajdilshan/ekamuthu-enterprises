<?php
include 'class/include.php';
include 'auth.php';

$customerId = isset($_GET['customer_id']) && !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

$db = Database::getInstance();

$where = "WHERE 1=1";
if ($customerId > 0) {
    $where .= " AND er.customer_id = $customerId";
}

// Get Outstanding Invoices
$query = "SELECT 
            er.id as rent_id,
            er.bill_number,
            er.rental_date,
            cm.name as customer_name,
            pt.name as payment_type_name,
            SUM(err.outstanding_amount) as total_outstanding,
            SUM(err.customer_paid) as total_paid_for_items
          FROM `equipment_rent` er
          LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
          LEFT JOIN `payment_type` pt ON er.payment_type_id = pt.id
          INNER JOIN `equipment_rent_items` eri ON er.id = eri.rent_id
          INNER JOIN `equipment_rent_returns` err ON eri.id = err.rent_item_id
          $where
          GROUP BY er.id
          HAVING total_outstanding > 0
          ORDER BY er.rental_date DESC";

$result = $db->readQuery($query);

$data = [];
$grandTotalRent = 0;
$grandTotalPaid = 0;
$grandTotalBalance = 0;
$customerFilterName = '';

while ($row = mysqli_fetch_assoc($result)) {
    
    $totalOutstanding = floatval($row['total_outstanding'] ?? 0);
    $totalPaidForItems = floatval($row['total_paid_for_items'] ?? 0);

    // Total Rent (Billed) = Outstanding + Paid
    $totalRent = $totalOutstanding + $totalPaidForItems;
    
    // Use the paid amount from returns as the "Total Paid" for this report context
    $totalPaid = $totalPaidForItems;

    $balance = $totalOutstanding;

    $data[] = [
        'bill_number' => $row['bill_number'],
        'rental_date' => $row['rental_date'],
        'payment_type_name' => $row['payment_type_name'] ?? 'N/A',
        'customer_name' => $row['customer_name'],
        'total_rent' => $totalRent,
        'total_paid' => $totalPaid,
        'balance' => $balance
    ];
    
    $grandTotalRent += $totalRent;
    $grandTotalPaid += $totalPaid;
    $grandTotalBalance += $balance;

    if ($customerId > 0) {
        $customerFilterName = $row['customer_name'];
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
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        
        body { 
            font-family: 'Roboto', sans-serif; 
            font-size: 13px; 
            color: #333; 
            margin: 0; 
            padding: 20px; 
            background: #fff; 
        }
        
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            padding: 20px;
        }

        /* Bold Header Design */
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding: 20px; 
            background: #1a1a1a; 
            color: white;
            border-radius: 8px 8px 0 0;
        }
        .company-name { 
            font-size: 26px; 
            font-weight: 700; 
            margin-bottom: 5px; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
        }
        .company-meta { 
            font-size: 13px; 
            color: #ccc; 
            font-weight: 400;
        }
        
        .report-section {
            border: 1px solid #ddd;
            border-top: none;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }

        .report-title { 
            font-size: 20px; 
            font-weight: 700; 
            margin: 0 0 20px; 
            text-align: center; 
            color: #222; 
            text-transform: uppercase;
            border-bottom: 2px solid #0d6efd;
            display: inline-block;
            padding-bottom: 5px;
        }
        
        .report-subtitle {
            display: block;
            margin-top: 5px;
            font-size: 14px;
            color: #555;
            text-transform: none;
        }

        /* Summary Boxes */
        .summary-box { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 30px; 
            gap: 20px;
        }
        .stat-item { 
            flex: 1;
            text-align: center; 
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
        }
        .stat-label { 
            font-size: 11px; 
            font-weight: 600; 
            text-transform: uppercase; 
            color: #6c757d; 
            margin-bottom: 5px; 
            display: block; 
        }
        .stat-value { 
            font-size: 20px; 
            font-weight: 700; 
            color: #212529; 
        }
        .text-danger { color: #dc3545 !important; }
        .text-success { color: #198754 !important; }
        
        /* Table Styling */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 12px;
        }
        th { 
            background-color: #f1f3f5; 
            color: #495057; 
            font-weight: 600; 
            text-transform: uppercase; 
            padding: 12px 10px; 
            text-align: left; 
            border-bottom: 2px solid #dee2e6; 
        }
        td { 
            padding: 10px; 
            border-bottom: 1px solid #e9ecef; 
            color: #212529; 
        }
        tr:hover { background-color: #f8f9fa; }
        
        .text-right { text-align: right; }
        
        .footer { 
            margin-top: 40px; 
            font-size: 11px; 
            text-align: center; 
            color: #adb5bd; 
            border-top: 1px solid #e9ecef; 
            padding-top: 15px; 
        }
        
        .btn-print {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-print:hover { background-color: #0b5ed7; }

        @media print {
            body { padding: 0; background: #fff; }
            .container { max-width: 100%; width: 100%; margin: 0; padding: 0; }
            .report-section { border: none; padding: 0; }
            .btn-print { display: none; }
            
            /* Print-friendly adjustments */
            .header { background: none; color: #000; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; border-radius: 0; }
            .company-name { color: #000; }
            .company-meta { color: #333; }
            
            .stat-item { border: 1px solid #000; background: #fff; }
            .stat-value { color: #000; }
            
            th { background-color: #eee !important; color: #000; border-bottom: 1px solid #000; }
            td { border-bottom: 1px solid #ccc; color: #000; }
            tr:hover { background-color: transparent; }
            
            
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container">
        <div class="header">
            <div class="company-name"><?php echo $COMPANY_PROFILE_DETAILS->name ?? 'Company Name'; ?></div>
            <div class="company-meta">
                <?php echo $COMPANY_PROFILE_DETAILS->address ?? ''; ?><br>
                Tel: <?php echo $COMPANY_PROFILE_DETAILS->phone_number ?? ''; ?> | Email: <?php echo $COMPANY_PROFILE_DETAILS->email ?? ''; ?>
            </div>
        </div>

        <div class="report-section">
            <div style="text-align: center;">
                <div class="report-title">
                    Outstanding Report
                    <?php if (!empty($customerFilterName)): ?>
                        <span class="report-subtitle">
                            Customer: <strong><?php echo $customerFilterName; ?></strong>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="summary-box">
                <div class="stat-item">
                    <span class="stat-label">Total Rent</span>
                    <span class="stat-value">Rs. <?php echo number_format($grandTotalRent, 2); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Paid</span>
                    <span class="stat-value text-success">Rs. <?php echo number_format($grandTotalPaid, 2); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Outstanding</span>
                    <span class="stat-value text-danger">Rs. <?php echo number_format($grandTotalBalance, 2); ?></span>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>Payment Type</th>
                        <th>Customer Name</th>
                        <th class="text-right">Rent Amount</th>
                        <th class="text-right">Paid Amount</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data) > 0): ?>
                        <?php $i = 1; foreach ($data as $row): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo $row['bill_number']; ?></strong></td>
                            <td><?php echo $row['rental_date']; ?></td>
                            <td><span style="background: #f1f3f5; padding: 2px 6px; border-radius: 4px; font-size: 11px;"><?php echo $row['payment_type_name']; ?></span></td>
                            <td><?php echo $row['customer_name']; ?></td>
                            <td class="text-right"><?php echo number_format($row['total_rent'], 2); ?></td>
                            <td class="text-right text-success"><?php echo number_format($row['total_paid'], 2); ?></td>
                            <td class="text-right text-danger"><strong><?php echo number_format($row['balance'], 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background-color: #e9ecef;">
                            <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                            <td class="text-right"><strong><?php echo number_format($grandTotalRent, 2); ?></strong></td>
                            <td class="text-right text-success"><strong><?php echo number_format($grandTotalPaid, 2); ?></strong></td>
                            <td class="text-right text-danger" style="font-size: 14px;"><strong><?php echo number_format($grandTotalBalance, 2); ?></strong></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding: 40px; color: #777;">No outstanding records found for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer">
                Printed on: <?php echo date('Y-m-d H:i:s'); ?> | <?php echo $COMPANY_PROFILE_DETAILS->name ?? 'Company Name'; ?>
            </div>
        </div>

        <div style="text-align: center;">
            <button onclick="window.print()" class="btn-print">Print Report</button>
        </div>
    </div>

</body>
</html>
