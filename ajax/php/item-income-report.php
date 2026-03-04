<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

if (isset($_POST['action']) && $_POST['action'] === 'get_item_income_report') {
    $from_date = $_POST['from_date'] ?? date('Y-m-d');
    $to_date   = $_POST['to_date'] ?? date('Y-m-d');

    $db = Database::getInstance();

    // Rental amount calculation per return row (with rental_override support)
    $rentalCalc = "CASE WHEN err.rental_override IS NOT NULL
                       THEN err.rental_override
                       ELSE CASE WHEN COALESCE(e.is_fixed_rate, 0) = 1
                           THEN ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) * err.return_qty)
                           ELSE (GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                               * ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END))
                               * err.return_qty)
                       END
                   END";

    // Return data grouped by equipment and sub_equipment
    $query = "SELECT 
                    e.id AS equipment_id,
                    e.code AS equipment_code,
                    e.item_name AS equipment_name,
                    eri.sub_equipment_id,
                    se.code AS sub_equipment_code,
                    COALESCE(se.value, 0) AS sub_equipment_value,
                    COUNT(err.id) AS rented_qty,
                    SUM($rentalCalc) AS rent_value
                FROM equipment_rent_returns err
                INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                LEFT JOIN equipment e ON eri.equipment_id = e.id
                LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                WHERE err.return_date BETWEEN '$from_date' AND '$to_date'
                GROUP BY e.id, eri.sub_equipment_id
                ORDER BY e.code ASC, se.code ASC";

    $result = $db->readQuery($query);

    // Build equipment map
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

        $equipmentMap[$eqId]['sub_equipment'][] = [
            'sub_equipment_id' => $row['sub_equipment_id'],
            'sub_equipment_code' => $row['sub_equipment_code'] ?? null,
            'value' => floatval($row['sub_equipment_value']),
            'rented_qty' => intval($row['rented_qty']),
            'rent_value' => floatval($row['rent_value']),
            'repair_qty' => 0,
            'repair_cost' => 0,
            'profit' => 0,
            'roi' => 0
        ];
    }

    // Repair data for company items grouped by machine_code (= sub_equipment.code)
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

    // Merge repair data and calculate profit / ROI
    $totalValue = 0;
    $totalRentValue = 0;
    $totalRepairCost = 0;
    $totalProfit = 0;

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
        $totalRentValue += $eqTotals['rent_value'];
        $totalRepairCost += $eqTotals['repair_cost'];
        $totalProfit += $eqProfit;
    }
    unset($eq);

    echo json_encode([
        'status' => 'success',
        'data' => array_values($equipmentMap),
        'summary' => [
            'total_value' => number_format($totalValue, 2),
            'total_rent_value' => number_format($totalRentValue, 2),
            'total_repair_cost' => number_format($totalRepairCost, 2),
            'total_profit' => number_format($totalProfit, 2),
            'total_roi' => ($totalValue > 0) ? number_format(($totalProfit / $totalValue) * 100, 2) : '0.00'
        ]
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
