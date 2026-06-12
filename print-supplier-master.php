<?php
include 'class/include.php';
include 'auth.php';

$SUPPLIER_MASTER = new SupplierMaster(NULL);
$suppliers = $SUPPLIER_MASTER->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier List - <?php echo $COMPANY_PROFILE_DETAILS->name ?? 'Company'; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #fff;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }
        th {
            background-color: #f1f3f5;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            padding: 10px 8px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
            color: #212529;
            vertical-align: top;
        }
        tr:hover { background-color: #f8f9fa; }

        .badge-active {
            background: #198754; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px;
        }
        .badge-inactive {
            background: #dc3545; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px;
        }

        .footer {
            margin-top: 30px;
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
            .header { background: none; color: #000; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; border-radius: 0; }
            .company-name { color: #000; }
            .company-meta { color: #333; }
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
                <div class="report-title">Supplier List</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 35px;">#</th>
                        <th>Supplier Code</th>
                        <th>Supplier Name</th>
                        <th>Address</th>
                        <th>Mobile 01</th>
                        <th>Mobile 02</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($suppliers)): ?>
                        <?php $i = 1; foreach ($suppliers as $s): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><strong><?php echo htmlspecialchars($s['code'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($s['address'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($s['mobile_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($s['mobile_number_2'] ?? ''); ?></td>
                                <td>
                                    <?php if (($s['is_active'] ?? 0) == 1): ?>
                                        <span class="badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding: 40px; color: #777;">No suppliers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer">
                Total Suppliers: <strong><?php echo count($suppliers); ?></strong> |
                Printed on: <?php echo date('Y-m-d H:i:s'); ?> | <?php echo $COMPANY_PROFILE_DETAILS->name ?? 'Company Name'; ?>
            </div>
        </div>

        <div style="text-align: center;">
            <button onclick="window.print()" class="btn-print">Print Report</button>
        </div>
    </div>

</body>
</html>
