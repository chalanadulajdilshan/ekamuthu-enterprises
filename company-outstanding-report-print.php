<!doctype html>
<?php
include 'class/include.php';

if (!isset($_SESSION)) {
    session_start();
}

$US = new User($_SESSION['id']);
$COMPANY_PROFILE = new CompanyProfile($US->company_id);

$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$billNo = $_GET['bill_no'] ?? '';
$companyOnly = ($_GET['company_only'] ?? '1') === '1';

$db = Database::getInstance();

$whereParts = ["er.status = 'returned'"];

if (!empty($fromDate) && !empty($toDate)) {
    $fromSafe = $db->escapeString($fromDate);
    $toSafe = $db->escapeString($toDate);
    $whereParts[] = "er.rental_date BETWEEN '{$fromSafe} 00:00:00' AND '{$toSafe} 23:59:59'";
}

if (!empty($billNo)) {
    $billSafe = mysqli_real_escape_string($db->DB_CON, $billNo);
    $whereParts[] = "er.bill_number LIKE '%{$billSafe}%'";
}

$whereSql = 'WHERE ' . implode(' AND ', $whereParts);

$query = "SELECT 
            er.id,
            er.bill_number,
            er.rental_date,
            er.received_date,
            er.status,
            er.is_cancelled,
            er.total_items,
            cm.name AS customer_name,
            cm.code AS customer_code,
            (
                SELECT COALESCE(SUM(err.company_outstanding), 0)
                FROM equipment_rent_returns err
                INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                WHERE eri.rent_id = er.id
            ) AS company_outstanding_total
        FROM equipment_rent er
        LEFT JOIN customer_master cm ON er.customer_id = cm.id
        {$whereSql}
        ORDER BY er.rental_date DESC";

$result = $db->readQuery($query);
$data = [];
$totalCompanyOutstanding = 0;
$totalBills = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $companyOutstanding = (float) ($row['company_outstanding_total'] ?? 0);
    if ($companyOnly && $companyOutstanding <= 0) {
        continue;
    }
    
    $totalCompanyOutstanding += $companyOutstanding;
    $totalBills++;
    $data[] = $row;
}

$dateRange = '';
if (!empty($fromDate) && !empty($toDate)) {
    $dateRange = date('Y-m-d', strtotime($fromDate)) . ' to ' . date('Y-m-d', strtotime($toDate));
}
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Company Outstanding Report - <?php echo htmlspecialchars($COMPANY_PROFILE->name); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'main-css.php' ?>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body, html {
                width: 100%;
                margin: 0;
                padding: 0;
            }

            .card {
                box-shadow: none;
                border: none;
            }

            @page {
                size: auto;
                margin: 10mm;
            }
        }

        body {
            font-family: Arial, sans-serif;
            background: #fff;
        }

        .report-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
        }

        .report-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .report-header p {
            margin: 2px 0;
            font-size: 13px;
        }

        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0;
            text-transform: uppercase;
        }

        .summary-boxes {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            gap: 10px;
        }

        .summary-box {
            flex: 1;
            border: 2px solid #333;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
        }

        .summary-box .label {
            font-size: 12px;
            color: #555;
            margin-bottom: 5px;
        }

        .summary-box .value {
            font-size: 22px;
            font-weight: bold;
        }

        .summary-box.outstanding .value {
            color: #dc2626;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th,
        table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        table th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }

        table td.text-end {
            text-align: right;
        }

        table td.text-center {
            text-align: center;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>

<body>

<div class="container mt-3">
    <div class="no-print mb-3">
        <button onclick="window.print()" class="btn btn-success">Print</button>
        <button onclick="window.close()" class="btn btn-secondary ms-2">Close</button>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Header -->
            <div class="report-header">
                <h2><?php echo strtoupper(htmlspecialchars($COMPANY_PROFILE->name)); ?></h2>
                <p><?php echo htmlspecialchars($COMPANY_PROFILE->address ?? 'No 50 Hill St, Dehiwala-Mount Lavinia'); ?></p>
                <p>Tel | Email: <?php echo htmlspecialchars($COMPANY_PROFILE->email ?? 'ekamuthu@gmail.com'); ?></p>
            </div>

            <!-- Title -->
            <div class="report-title">COMPANY OUTSTANDING REPORT</div>

            <!-- Date Range -->
            <?php if ($dateRange): ?>
                <div style="text-align:center; margin-bottom:10px; font-size:13px;">
                    <strong>Period:</strong> <?php echo htmlspecialchars($dateRange); ?>
                </div>
            <?php endif; ?>

            <!-- Summary Boxes -->
            <div class="summary-boxes">
                <div class="summary-box">
                    <div class="label">සම්පුර්ණ බිල්පත් / Total Bills</div>
                    <div class="value"><?php echo number_format($totalBills); ?></div>
                </div>
                <div class="summary-box outstanding">
                    <div class="label">සමාගම් හිඟ මුදල / Company Outstanding</div>
                    <div class="value">Rs. <?php echo number_format($totalCompanyOutstanding, 2); ?></div>
                </div>
            </div>

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>බිල්පත් / Bill No</th>
                        <th>පාරිභෝගිකයා / Customer</th>
                        <th>නිකුත් දිනය / Issue Date</th>
                        <th>ලද දිනය / Received Date</th>
                        <th class="text-center">අයිතම / Items</th>
                        <th>තත්ත්වය / Status</th>
                        <th class="text-end">සමාගම් හිඟ / Company Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No records found</td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($data as $row): ?>
                            <?php
                            $companyOutstanding = (float) ($row['company_outstanding_total'] ?? 0);
                            $isCancelled = (int)($row['is_cancelled'] ?? 0) === 1 || $row['status'] === 'cancelled';
                            $statusLabel = 'Returned';
                            if ($isCancelled) {
                                $statusLabel = 'Cancelled';
                            }
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['bill_number']); ?></td>
                                <td><?php echo htmlspecialchars(trim(($row['customer_code'] ?? '') . ' - ' . ($row['customer_name'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars($row['rental_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['received_date']); ?></td>
                                <td class="text-center"><?php echo (int)($row['total_items'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($statusLabel); ?></td>
                                <td class="text-end"><?php echo number_format($companyOutstanding, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background-color:#fef3c7;font-weight:bold;">
                        <td colspan="7" class="text-end">සමාගම් හිඟ මුදල / Total Company Outstanding:</td>
                        <td class="text-end" style="color:#dc2626;">Rs. <?php echo number_format($totalCompanyOutstanding, 2); ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Footer Note -->
            <div class="footer-note">
                <p><strong>සමාගම් හිඟ:</strong> This refers to amounts the company owes to customers as refunds from returned equipment rentals.</p>
                <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
