<?php
include 'class/include.php';
include 'auth.php';

$customerId = isset($_GET['customer_id']) && !empty($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$isSummary = isset($_GET['summary']) && $_GET['summary'] == '1';

$db = Database::getInstance();

$today = date('Y-m-d');
$where = "WHERE 1=1";
if ($customerId > 0) {
    $where .= " AND er.customer_id = $customerId";
}
if (!empty($searchTerm)) {
    $safeTerm = method_exists($db, 'getConnection') ? mysqli_real_escape_string($db->getConnection(), $searchTerm) : addslashes($searchTerm);
    $where .= " AND er.bill_number LIKE '%$safeTerm%'";
}

$customerFilterName = '';
$rentSummary = [];

$ensureSummary = function (&$rentSummary, $row) use (&$customerFilterName, $customerId) {
    $rentId = (int)$row['rent_id'];
    if (!isset($rentSummary[$rentId])) {
        $rentSummary[$rentId] = [
            'bill_number' => $row['bill_number'],
            'rental_date' => $row['rental_date'],
            'customer_name' => $row['customer_name'],
            'payment_type_name' => $row['payment_type_name'] ?? 'N/A',
            'rent_status' => $row['rent_status'] ?? '',
            'recorded_outstanding' => 0,
            'recorded_paid' => 0,
            'projected_outstanding' => 0,
            'recorded_details' => [],
            'payments' => []
        ];
    }

    if ($customerId > 0 && empty($customerFilterName) && !empty($row['customer_name'])) {
        $customerFilterName = $row['customer_name'];
    }

    return $rentId;
};

// Recorded outstanding (from processed returns)
$recordedSql = "SELECT 
                    er.id as rent_id,
                    er.bill_number,
                    er.rental_date,
                    cm.name as customer_name,
                    pt.name as payment_type_name,
                    er.status as rent_status,
                    SUM(err.outstanding_amount) as total_outstanding,
                    SUM(err.customer_paid) as total_paid_for_items
                FROM `equipment_rent` er
                LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
                LEFT JOIN `payment_type` pt ON er.payment_type_id = pt.id
                INNER JOIN `equipment_rent_items` eri ON er.id = eri.rent_id
                INNER JOIN `equipment_rent_returns` err ON eri.id = err.rent_item_id
                $where
                GROUP BY er.id
                HAVING total_outstanding > 0";

$recordedResult = $db->readQuery($recordedSql);
if ($recordedResult) {
    while ($row = mysqli_fetch_assoc($recordedResult)) {
        $rentId = $ensureSummary($rentSummary, $row);
        $rentSummary[$rentId]['recorded_outstanding'] = floatval($row['total_outstanding'] ?? 0);
        $rentSummary[$rentId]['recorded_paid'] = floatval($row['total_paid_for_items'] ?? 0);
    }
}

// Projected outstanding (items not yet returned) - aligned with UI calculation
$projectedSql = "SELECT 
                    er.id as rent_id,
                    er.bill_number,
                    er.rental_date,
                    cm.name as customer_name,
                    pt.name as payment_type_name,
                    er.status as rent_status,
                    (eri.quantity - COALESCE((SELECT SUM(return_qty) FROM equipment_rent_returns err2 WHERE err2.rent_item_id = eri.id), 0)) AS pending_qty,
                    GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, '$today') / 86400)) AS used_days,
                    (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) AS per_unit_daily
                FROM equipment_rent_items eri
                INNER JOIN equipment_rent er ON eri.rent_id = er.id
                LEFT JOIN customer_master cm ON er.customer_id = cm.id
                LEFT JOIN payment_type pt ON er.payment_type_id = pt.id
                $where";

$projectedResult = $db->readQuery($projectedSql);
if ($projectedResult) {
    while ($row = mysqli_fetch_assoc($projectedResult)) {
        $pendingQty = max(0, (float)$row['pending_qty']);
        if ($pendingQty <= 0) {
            continue;
        }

        $usedDays = max(1, (int)$row['used_days']);
        $perUnitDaily = floatval($row['per_unit_daily']);
        $projectedAmount = round($pendingQty * $usedDays * $perUnitDaily, 2);
        if ($projectedAmount <= 0) {
            continue;
        }

        $rentId = $ensureSummary($rentSummary, $row);
        $rentSummary[$rentId]['projected_outstanding'] += $projectedAmount;
    }
}

$rentIds = array_keys($rentSummary);
if (!empty($rentIds)) {
    $rentIdList = implode(',', array_map('intval', $rentIds));

    // Recorded outstanding breakdown per return
    $detailsSql = "SELECT 
                        er.id AS rent_id,
                        err.return_date,
                        err.outstanding_amount,
                        err.customer_paid,
                        err.additional_payment,
                        err.remark,
                        e.item_name,
                        e.code AS equipment_code,
                        se.code AS sub_equipment_code
                    FROM equipment_rent_returns err
                    INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                    INNER JOIN equipment_rent er ON eri.rent_id = er.id
                    LEFT JOIN equipment e ON eri.equipment_id = e.id
                    LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                    WHERE er.id IN ($rentIdList)
                    ORDER BY err.return_date ASC, err.id ASC";

    $detailsResult = $db->readQuery($detailsSql);
    if ($detailsResult) {
        while ($dRow = mysqli_fetch_assoc($detailsResult)) {
            $rentId = (int)$dRow['rent_id'];
            if (!isset($rentSummary[$rentId])) {
                continue;
            }

            $rentSummary[$rentId]['recorded_details'][] = [
                'return_date' => $dRow['return_date'],
                'item' => trim(($dRow['equipment_code'] ?? '') . ' ' . ($dRow['item_name'] ?? '')),
                'sub_equipment' => $dRow['sub_equipment_code'] ?? '',
                'outstanding_amount' => floatval($dRow['outstanding_amount'] ?? 0),
                'customer_paid' => floatval($dRow['customer_paid'] ?? 0),
                'additional_payment' => floatval($dRow['additional_payment'] ?? 0),
                'remark' => $dRow['remark'] ?? ''
            ];
        }
    }

    // Payment history per rent invoice
    $paymentsSql = "SELECT 
                        prm.invoice_id AS rent_id,
                        pr.receipt_no,
                        pr.entry_date,
                        prm.amount,
                        COALESCE(pt.name, 'Unknown') AS payment_method,
                        prm.cheq_no,
                        prm.ref_no
                    FROM payment_receipt_method prm
                    INNER JOIN payment_receipt pr ON prm.receipt_id = pr.id
                    LEFT JOIN payment_type pt ON prm.payment_type_id = pt.id
                    WHERE prm.invoice_id IN ($rentIdList)
                    ORDER BY pr.entry_date ASC, prm.id ASC";

    $paymentsResult = $db->readQuery($paymentsSql);
    if ($paymentsResult) {
        while ($pRow = mysqli_fetch_assoc($paymentsResult)) {
            $rentId = (int)$pRow['rent_id'];
            if (!isset($rentSummary[$rentId])) {
                continue;
            }

            $rentSummary[$rentId]['payments'][] = [
                'receipt_no' => $pRow['receipt_no'],
                'entry_date' => $pRow['entry_date'],
                'amount' => floatval($pRow['amount'] ?? 0),
                'payment_method' => $pRow['payment_method'] ?? 'Unknown',
                'cheq_no' => $pRow['cheq_no'] ?? '',
                'ref_no' => $pRow['ref_no'] ?? ''
            ];
        }
    }

    // Deposit payments per rent
    $depositSql = "SELECT 
                        dp.rent_id,
                        dp.amount,
                        dp.payment_date,
                        dp.remark
                    FROM deposit_payments dp
                    WHERE dp.rent_id IN ($rentIdList)
                    ORDER BY dp.payment_date ASC, dp.id ASC";

    $depositResult = $db->readQuery($depositSql);
    if ($depositResult) {
        while ($depRow = mysqli_fetch_assoc($depositResult)) {
            $rentId = (int)$depRow['rent_id'];
            if (!isset($rentSummary[$rentId])) {
                continue;
            }

            $rentSummary[$rentId]['deposits'][] = [
                'payment_date' => $depRow['payment_date'],
                'amount' => floatval($depRow['amount'] ?? 0),
                'remark' => $depRow['remark'] ?? ''
            ];
        }
    }
}

// Build printable dataset
$data = [];
$grandTotalRent = 0;
$grandTotalPaid = 0;
$grandTotalBalance = 0;

uasort($rentSummary, function ($a, $b) {
    return strtotime($b['rental_date']) <=> strtotime($a['rental_date']);
});

foreach ($rentSummary as $rentId => $summary) {
    $recordedOutstanding = $summary['recorded_outstanding'] ?? 0;
    $projectedOutstanding = $summary['projected_outstanding'] ?? 0;
    $totalPaid = $summary['recorded_paid'] ?? 0;

    // Calculate total deposits
    $totalDeposits = 0;
    if (!empty($summary['deposits'])) {
        foreach ($summary['deposits'] as $dep) {
            $totalDeposits += floatval($dep['amount']);
        }
    }

    // Adjust balance and total paid with deposits
    $balance = max(0, ($recordedOutstanding + $projectedOutstanding) - $totalDeposits);
    $totalPaid += $totalDeposits;

    // Mirror UI: only include rows with a pending balance
    if ($balance <= 0) {
        continue;
    }

    $totalRent = $totalPaid + $balance;

    $statusLabel = (isset($summary['rent_status']) && strtolower($summary['rent_status']) === 'returned')
        ? 'Returned'
        : 'Not Returned';

    $data[] = [
        'bill_number' => $summary['bill_number'],
        'rental_date' => $summary['rental_date'],
        'payment_type_name' => $summary['payment_type_name'] ?? 'N/A',
        'customer_name' => $summary['customer_name'],
        'status_label' => $statusLabel,
        'total_rent' => $totalRent,
        'total_paid' => $totalPaid,
        'balance' => $balance,
        'recorded_outstanding' => $recordedOutstanding,
        'projected_outstanding' => $projectedOutstanding,
        'recorded_details' => $summary['recorded_details'] ?? [],
        'payments' => $summary['payments'] ?? [],
        'deposits' => $summary['deposits'] ?? []
    ];

    $grandTotalRent += $totalRent;
    $grandTotalPaid += $totalPaid;
    $grandTotalBalance += $balance;
}

if ($customerId > 0 && empty($customerFilterName)) {
    $customerQuery = $db->readQuery("SELECT name FROM customer_master WHERE id = $customerId LIMIT 1");
    if ($customerQuery && $row = mysqli_fetch_assoc($customerQuery)) {
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
                        <th>Status</th>
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
                            <td><span style="background: #f1f3f5; padding: 2px 6px; border-radius: 4px; font-size: 11px;">&nbsp;<?php echo $row['payment_type_name']; ?>&nbsp;</span></td>
                            <td><?php echo $row['status_label']; ?></td>
                            <td class="text-right">&nbsp;<?php echo number_format($row['total_rent'], 2); ?>&nbsp;</td>
                            <td class="text-right text-success">&nbsp;<?php echo number_format($row['total_paid'], 2); ?>&nbsp;</td>
                            <td class="text-right text-danger"><strong><?php echo number_format($row['balance'], 2); ?></strong></td>
                        </tr>

                        <?php if (!$isSummary): ?>
                        <tr>
                            <td colspan="8" style="padding:0 10px 15px 10px;">
                                <div style="display:flex; gap:20px;">
                                    <div style="flex:1; border:1px solid #dee2e6; border-radius:6px; padding:10px;">
                                        <h4 style="margin:0 0 8px; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Recorded Outstanding</h4>
                                        <p style="margin:0 0 10px; font-size:12px;">Total Recorded Outstanding: <strong><?php echo number_format($row['recorded_outstanding'], 2); ?></strong></p>
                                        <table style="width:100%; font-size:11px;">
                                            <thead>
                                                <tr>
                                                    <th>Return Date</th>
                                                    <th>Item</th>
                                                    <th class="text-right">Outstanding</th>
                                                    <th class="text-right">Paid</th>
                                                    <th>Remark</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($row['recorded_details'])): ?>
                                                    <?php foreach ($row['recorded_details'] as $detail): ?>
                                                        <tr>
                                                            <td><?php echo $detail['return_date'] ?? '-'; ?></td>
                                                            <td><?php echo $detail['item'] ?? '-'; ?></td>
                                                            <td class="text-right"><?php echo number_format($detail['outstanding_amount'] ?? 0, 2); ?></td>
                                                            <td class="text-right text-success"><?php echo number_format($detail['customer_paid'] ?? 0, 2); ?></td>
                                                            <td><?php echo !empty($detail['remark']) ? $detail['remark'] : '-'; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="5" style="text-align:center; padding:10px;">No recorded entries.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div style="flex:1; border:1px solid #dee2e6; border-radius:6px; padding:10px;">
                                        <h4 style="margin:0 0 8px; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Payment History</h4>
                                        <p style="margin:0 0 10px; font-size:12px;">Projected Outstanding: <strong><?php echo number_format($row['projected_outstanding'], 2); ?></strong></p>
                                        <table style="width:100%; font-size:11px;">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Receipt No</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Method / Reference</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($row['payments'])): ?>
                                                    <?php foreach ($row['payments'] as $payment): ?>
                                                        <tr>
                                                            <td><?php echo $payment['entry_date'] ?? '-'; ?></td>
                                                            <td><?php echo $payment['receipt_no'] ?? '-'; ?></td>
                                                            <td class="text-right text-success"><?php echo number_format($payment['amount'] ?? 0, 2); ?></td>
                                                            <td>
                                                                <?php
                                                                    $references = [];
                                                                    if (!empty($payment['payment_method'])) {
                                                                        $references[] = $payment['payment_method'];
                                                                    }
                                                                    if (!empty($payment['cheq_no'])) {
                                                                        $references[] = 'Cheque: ' . $payment['cheq_no'];
                                                                    }
                                                                    if (!empty($payment['ref_no'])) {
                                                                        $references[] = 'Ref: ' . $payment['ref_no'];
                                                                    }
                                                                    echo !empty($references) ? implode(' | ', $references) : '-';
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="4" style="text-align:center; padding:10px;">No payments recorded.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div style="margin-top: 15px; border:1px solid #dee2e6; border-radius:6px; padding:10px;">
                                    <h4 style="margin:0 0 8px; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;">Deposit Payments</h4>
                                    <table style="width:100%; font-size:11px;">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th class="text-right">Amount</th>
                                                <th>Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($row['deposits'])): ?>
                                                <?php foreach ($row['deposits'] as $dep): ?>
                                                    <tr>
                                                        <td><?php echo $dep['payment_date'] ?? '-'; ?></td>
                                                        <td class="text-right text-success"><?php echo number_format($dep['amount'] ?? 0, 2); ?></td>
                                                        <td><?php echo !empty($dep['remark']) ? $dep['remark'] : '-'; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="3" style="text-align:center; padding:10px;">No deposit payments.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
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
