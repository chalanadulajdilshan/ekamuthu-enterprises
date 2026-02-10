<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

if (isset($_POST['action']) && $_POST['action'] === 'get_item_income_report') {
    $from_date = $_POST['from_date'] ?? date('Y-m-d');
    $to_date   = $_POST['to_date'] ?? date('Y-m-d');

    $db = Database::getInstance();

    // Rental amount calculation per return row (per returned qty)
    $rentalCalc = "CASE WHEN COALESCE(e.is_fixed_rate, 0) = 1
                       THEN ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) * err.return_qty)
                       ELSE (GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                           * ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END))
                           * err.return_qty)
                   END";

    $query = "SELECT 
                    e.id AS equipment_id,
                    e.code AS equipment_code,
                    e.item_name AS equipment_name,
                    eri.sub_equipment_id,
                    se.code AS sub_equipment_code,
                    SUM(err.return_qty) AS total_return_qty,
                    SUM($rentalCalc) AS rental_amount,
                    SUM(COALESCE(err.extra_day_amount,0)) AS extra_day_amount,
                    SUM(COALESCE(err.penalty_amount,0)) AS penalty_amount,
                    SUM(COALESCE(err.additional_payment,0)) AS additional_payment,
                    SUM(COALESCE(err.damage_amount,0)) AS damage_amount,
                    SUM(COALESCE(err.refund_amount,0)) AS refund_amount,
                    -- deposit portion (per-unit deposit * returned qty) so deposit refunds don't reduce income
                    SUM((COALESCE(eri.deposit_amount,0) / NULLIF(eri.quantity,0)) * err.return_qty) AS deposit_return_amount
                FROM equipment_rent_returns err
                INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                INNER JOIN equipment_rent er ON eri.rent_id = er.id
                LEFT JOIN equipment e ON eri.equipment_id = e.id
                LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                WHERE err.return_date BETWEEN '$from_date' AND '$to_date'
                GROUP BY e.id, eri.sub_equipment_id
                ORDER BY e.code ASC, se.code ASC";

    $result = $db->readQuery($query);

    $rows = [];
    $totals = [
        'rental'     => 0,
        'extra_day'  => 0,
        'penalty'    => 0,
        'additional' => 0,
        'damage'     => 0,
        'refund'     => 0,
        'net'        => 0
    ];

    while ($row = mysqli_fetch_assoc($result)) {
        $rental     = floatval($row['rental_amount'] ?? 0);
        $extraDay   = floatval($row['extra_day_amount'] ?? 0);
        $penalty    = floatval($row['penalty_amount'] ?? 0);
        $additional = floatval($row['additional_payment'] ?? 0);
        $damage     = floatval($row['damage_amount'] ?? 0);
        $refund     = floatval($row['refund_amount'] ?? 0);
        $depositRet = floatval($row['deposit_return_amount'] ?? 0);

        // Net income per grouped item: refund can include returned deposits, add deposit portion back
        $net = $rental + $extraDay + $penalty + $additional + $damage - $refund + $depositRet;

        $displayName = $row['equipment_code'] . ' - ' . ($row['equipment_name'] ?? '');
        $subLabel = $row['sub_equipment_code'] ?? null;
        $itemLabel = $subLabel ? ($displayName . ' / ' . $subLabel) : $displayName;

        $rows[] = [
            'equipment_code' => $row['equipment_code'] ?? '',
            'equipment_name' => $row['equipment_name'] ?? '',
            'sub_equipment_code' => $subLabel,
            'item_label' => $itemLabel,
            'total_return_qty' => intval($row['total_return_qty'] ?? 0),
            'rental_amount' => $rental,
            'extra_day_amount' => $extraDay,
            'penalty_amount' => $penalty,
            'additional_payment' => $additional,
            'damage_amount' => $damage,
            'refund_amount' => $refund,
            'deposit_return_amount' => $depositRet,
            'net_income' => $net
        ];

        $totals['rental']     += $rental;
        $totals['extra_day']  += $extraDay;
        $totals['penalty']    += $penalty;
        $totals['additional'] += $additional;
        $totals['damage']     += $damage;
        $totals['refund']     += $refund;
        // deposit portion is only for net, not shown separately in summary cards
        $totals['net']        += $net;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $rows,
        'summary' => [
            'rental' => number_format($totals['rental'], 2),
            'extra_day' => number_format($totals['extra_day'], 2),
            'penalty' => number_format($totals['penalty'], 2),
            'additional' => number_format($totals['additional'], 2),
            'damage' => number_format($totals['damage'], 2),
            'refund' => number_format($totals['refund'], 2),
            'net' => number_format($totals['net'], 2)
        ]
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
