<?php
include 'class/include.php';
include 'auth.php';

$from_date = $_GET['from'] ?? date('Y-m-01');
$to_date   = $_GET['to'] ?? date('Y-m-d');

$db = Database::getInstance();

$rentalCalc = "CASE WHEN err.rental_override IS NOT NULL
                       THEN err.rental_override
                       ELSE CASE WHEN COALESCE(e.is_fixed_rate, 0) = 1
                           THEN ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) * err.return_qty)
                           ELSE (GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                               * ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END))
                               * err.return_qty)
                       END
                   END";

$query = "SELECT 
                e.id AS equipment_id,
                e.code AS equipment_code,
                e.item_name AS equipment_name,
                e.value AS equipment_value,
                eri.sub_equipment_id,
                se.code AS sub_equipment_code,
                COALESCE(se.value, 0) AS sub_equipment_value,
                SUM(err.return_qty) AS rented_qty,
                SUM($rentalCalc) AS rent_value
            FROM equipment_rent_returns err
            INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
            LEFT JOIN equipment e ON eri.equipment_id = e.id
            LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
            WHERE err.return_date BETWEEN '$from_date' AND '$to_date'
            GROUP BY e.id, eri.sub_equipment_id
            ORDER BY e.code ASC, se.code ASC";

$result = $db->readQuery($query);
$equipmentMap = [];
while ($row = mysqli_fetch_assoc($result)) {
    $eqId = $row['equipment_id'];
    if (!isset($equipmentMap[$eqId])) {
        $equipmentMap[$eqId] = [
            'equipment_id' => $eqId,
            'equipment_code' => $row['equipment_code'],
            'equipment_name' => $row['equipment_name'],
            'sub_equipment' => []
        ];
    }

    $value = floatval($row['sub_equipment_value']);
    if ($value == 0) {
        $value = floatval($row['equipment_value'] ?? 0);
    }

    $equipmentMap[$eqId]['sub_equipment'][] = [
        'sub_equipment_id' => $row['sub_equipment_id'],
        'sub_equipment_code' => $row['sub_equipment_code'] ?? null,
        'value' => $value,
        'rented_qty' => intval($row['rented_qty']),
        'rent_value' => floatval($row['rent_value']),
        'repair_qty' => 0,
        'repair_cost' => 0,
        'profit' => 0,
        'roi' => 0
    ];
}

$repairQuery = "SELECT 
                    rj.machine_code,
                    COUNT(rj.id) AS repair_qty,
                    SUM(COALESCE(rj.total_cost, 0)) AS repair_cost
                FROM repair_jobs rj
                WHERE rj.item_type = 'company'
                AND rj.created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
                AND rj.machine_code != ''
                GROUP BY rj.machine_code";
$repairResult = $db->readQuery($repairQuery);
$repairMap = [];
while ($rRow = mysqli_fetch_assoc($repairResult)) {
    $repairMap[$rRow['machine_code']] = [
        'repair_qty' => intval($rRow['repair_qty']),
        'repair_cost' => floatval($rRow['repair_cost'])
    ];
}

$totalValue = 0;
$totalRentValue = 0;
$totalRepairCost = 0;
$totalProfit = 0;
$totalRentedQty = 0;

foreach ($equipmentMap as &$eq) {
    $eqTotals = ['value' => 0, 'rented_qty' => 0, 'rent_value' => 0, 'repair_qty' => 0, 'repair_cost' => 0];

    foreach ($eq['sub_equipment'] as &$se) {
        $seCode = $se['sub_equipment_code'];
        if ($seCode && isset($repairMap[$seCode])) {
            $se['repair_qty'] = $repairMap[$seCode]['repair_qty'];
            $se['repair_cost'] = $repairMap[$seCode]['repair_cost'];
        }

        $se['rent_value'] = round($se['rent_value'], 2);
        $se['repair_cost'] = round($se['repair_cost'], 2);
        $se['profit'] = round($se['rent_value'] - $se['repair_cost'], 2);
        $se['roi'] = ($se['value'] > 0) ? round(($se['profit'] / $se['value']) * 100, 2) : 0;

        $eqTotals['value'] += $se['value'];
        $eqTotals['rented_qty'] += $se['rented_qty'];
        $eqTotals['rent_value'] += $se['rent_value'];
        $eqTotals['repair_qty'] += $se['repair_qty'];
        $eqTotals['repair_cost'] += $se['repair_cost'];
    }
    unset($se);

    $eqProfit = $eqTotals['rent_value'] - $eqTotals['repair_cost'];
    $eq['totals'] = [
        'value' => round($eqTotals['value'], 2),
        'rented_qty' => $eqTotals['rented_qty'],
        'rent_value' => round($eqTotals['rent_value'], 2),
        'repair_qty' => $eqTotals['repair_qty'],
        'repair_cost' => round($eqTotals['repair_cost'], 2),
        'profit' => round($eqProfit, 2),
        'roi' => ($eqTotals['value'] > 0) ? round(($eqProfit / $eqTotals['value']) * 100, 2) : 0
    ];

    $totalValue += $eqTotals['value'];
    $totalRentedQty += $eqTotals['rented_qty'];
    $totalRentValue += $eqTotals['rent_value'];
    $totalRepairCost += $eqTotals['repair_cost'];
    $totalProfit += $eqProfit;
}
unset($eq);

// Sort equipment by ROI descending
usort($equipmentMap, function ($a, $b) {
    return ($b['totals']['roi'] ?? 0) <=> ($a['totals']['roi'] ?? 0);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Wise Income Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; margin: 20px; }
        .header { text-align: center; margin-bottom: 10px; }
        .company-name { font-size: 20px; font-weight: bold; text-transform: uppercase; }
        .company-meta { font-size: 12px; margin-top: 4px; }
        .report-title { font-size: 16px; font-weight: bold; margin: 12px 0; text-align: center; }
        .summary { display: flex; gap: 12px; margin: 12px 0; }
        .card { flex: 1; border: 1px solid #ccc; padding: 10px; background: #f9f9f9; text-align: center; }
        .card-label { font-size: 12px; color: #555; }
        .card-value { font-size: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid #aaa; }
        th, td { border: 1px solid #aaa; padding: 6px 8px; }
        th { background: #e9e9e9; }
        .text-right { text-align: right; }
        .equipment-row { background: #f0f4f8; font-weight: bold; }
        .sub-row td:first-child { padding-left: 24px; }
        .profit-positive { color: #0a8f2f; }
        .profit-negative { color: #c62828; }
        @media print {
            body { margin: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div class="company-name"><?php echo $COMPANY_PROFILE_DETAILS->name ?? 'Company Name'; ?></div>
        <div class="company-meta">
            <?php echo $COMPANY_PROFILE_DETAILS->address ?? ''; ?> | Tel: <?php echo $COMPANY_PROFILE_DETAILS->phone_number ?? ''; ?> | Email: <?php echo $COMPANY_PROFILE_DETAILS->email ?? ''; ?>
        </div>
        <div class="report-title">Item Wise Income Report<br><span style="font-size:12px;font-weight:normal;">From: <?php echo $from_date; ?> To: <?php echo $to_date; ?></span></div>
    </div>

    <div class="summary">
        <div class="card">
            <div class="card-label">කුලියට දුන් එකතුව ප්‍රමාණය</div>
            <div class="card-value"><?php echo number_format($totalRentedQty, 0); ?></div>
        </div>
        <div class="card">
            <div class="card-label">එකතුව කුලී අගය</div>
            <div class="card-value">Rs. <?php echo number_format($totalRentValue, 2); ?></div>
        </div>
        <div class="card">
            <div class="card-label">එකතුව අලුත්වැඩියා වියදම</div>
            <div class="card-value">Rs. <?php echo number_format($totalRepairCost, 2); ?></div>
        </div>
        <div class="card">
            <div class="card-label">එකතුව ලාභය</div>
            <div class="card-value">Rs. <?php echo number_format($totalProfit, 2); ?></div>
        </div>
        <div class="card">
            <div class="card-label">අගය එකතුව</div>
            <div class="card-value">Rs. <?php echo number_format($totalValue, 2); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>අයිතම</th>
                <th class="text-right">අගය</th>
                <th class="text-right">කුලියට දුන් ප්‍රමාණය</th>
                <th class="text-right">කුලී අගය</th>
                <th class="text-right">අලුත්වැඩියා කල ප්‍රමාණය</th>
                <th class="text-right">අලුත්වැඩියා වියදම</th>
                <th class="text-right">ලාභය</th>
                <th class="text-right">ආයෝජනයන් මත ප්‍රතිලාභ ප්‍රතිශතය (ROI)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($equipmentMap) === 0): ?>
                <tr><td colspan="8" style="text-align:center;">No records found</td></tr>
            <?php else: ?>
                <?php foreach ($equipmentMap as $eq): ?>
                    <tr class="equipment-row">
                        <td><?php echo $eq['equipment_code'] . ' - ' . $eq['equipment_name']; ?></td>
                        <td class="text-right"><?php echo number_format($eq['totals']['value'], 2); ?></td>
                        <td class="text-right"><?php echo $eq['totals']['rented_qty']; ?></td>
                        <td class="text-right"><?php echo number_format($eq['totals']['rent_value'], 2); ?></td>
                        <td class="text-right"><?php echo $eq['totals']['repair_qty']; ?></td>
                        <td class="text-right"><?php echo number_format($eq['totals']['repair_cost'], 2); ?></td>
                        <td class="text-right <?php echo ($eq['totals']['profit'] >= 0) ? 'profit-positive' : 'profit-negative'; ?>"><?php echo number_format($eq['totals']['profit'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($eq['totals']['roi'], 2); ?>%</td>
                    </tr>
                    <?php if (count($eq['sub_equipment']) > 0): ?>
                        <?php foreach ($eq['sub_equipment'] as $se): ?>
                            <?php if (!$se['sub_equipment_code']) continue; ?>
                            <tr class="sub-row">
                                <td><?php echo $se['sub_equipment_code']; ?></td>
                                <td class="text-right"><?php echo number_format($se['value'], 2); ?></td>
                                <td class="text-right"><?php echo $se['rented_qty']; ?></td>
                                <td class="text-right"><?php echo number_format($se['rent_value'], 2); ?></td>
                                <td class="text-right"><?php echo $se['repair_qty']; ?></td>
                                <td class="text-right"><?php echo number_format($se['repair_cost'], 2); ?></td>
                                <td class="text-right <?php echo ($se['profit'] >= 0) ? 'profit-positive' : 'profit-negative'; ?>"><?php echo number_format($se['profit'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($se['roi'], 2); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 12px; text-align: right; font-size: 11px;">Printed on: <?php echo date('Y-m-d H:i:s'); ?></div>
    <div class="no-print" style="margin-top:10px; text-align:right;">
        <button onclick="window.print()" style="padding:8px 12px;">Print</button>
    </div>
</body>
</html>
