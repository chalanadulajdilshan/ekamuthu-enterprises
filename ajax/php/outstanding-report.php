<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'get_outstanding_report') {
    $customerId = isset($_POST['customer_id']) && !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

    $where = "WHERE 1=1";
    if ($customerId > 0) {
        $where .= " AND er.customer_id = $customerId";
    }

    $db = Database::getInstance();
    $today = date('Y-m-d');

    $rentSummary = [];

    // Helper closure to initialize rent meta only once
    $ensureSummary = function (&$rentSummary, $row) {
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
                'payments' => [],
                'deposits' => [],
                'items' => []
            ];
        }
        return $rentId;
    };

    // Recorded outstanding comes from processed returns
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

    // Projected outstanding for items that are overdue but not fully returned yet
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

        // Bill items (base rental items)
        $itemsSql = "SELECT 
                        er.id AS rent_id,
                        eri.quantity,
                        eri.amount,
                        eri.duration,
                        eri.rent_type,
                        COALESCE((SELECT SUM(return_qty) FROM equipment_rent_returns err2 WHERE err2.rent_item_id = eri.id), 0) AS returned_qty,
                        e.item_name,
                        e.code AS equipment_code,
                        se.code AS sub_equipment_code
                    FROM equipment_rent_items eri
                    INNER JOIN equipment_rent er ON eri.rent_id = er.id
                    LEFT JOIN equipment e ON eri.equipment_id = e.id
                    LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                    WHERE er.id IN ($rentIdList)";

        $itemsResult = $db->readQuery($itemsSql);
        if ($itemsResult) {
            while ($iRow = mysqli_fetch_assoc($itemsResult)) {
                $rentId = (int)$iRow['rent_id'];
                if (!isset($rentSummary[$rentId])) continue;
                $qty = floatval($iRow['quantity'] ?? 0);
                $returned = floatval($iRow['returned_qty'] ?? 0);
                $pending = max(0, $qty - $returned);
                $returnStatus = $pending <= 0 ? 'Returned' : 'Not Returned';
                $rentSummary[$rentId]['items'][] = [
                    'item' => trim(($iRow['equipment_code'] ?? '') . ' ' . ($iRow['item_name'] ?? '')),
                    'sub_equipment' => $iRow['sub_equipment_code'] ?? '',
                    'quantity' => $qty,
                    'returned_qty' => $returned,
                    'pending_qty' => $pending,
                    'return_status' => $returnStatus,
                    'amount' => $iRow['amount'] ?? 0,
                    'duration' => $iRow['duration'] ?? 0,
                    'rent_type' => $iRow['rent_type'] ?? ''
                ];
            }
        }

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

        // Payment history per rent/invoice
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

    // Build response payload
    $data = [];
    $grandTotalRent = 0;
    $grandTotalPaid = 0;
    $grandTotalBalance = 0;

    // Sort by rental date desc before pushing to array for consistent view
    uasort($rentSummary, function ($a, $b) {
        return strtotime($b['rental_date']) <=> strtotime($a['rental_date']);
    });

    foreach ($rentSummary as $rentId => $summary) {
        $recordedOutstanding = $summary['recorded_outstanding'] ?? 0;
        $projectedOutstanding = $summary['projected_outstanding'] ?? 0;
        $recordedPaid = $summary['recorded_paid'] ?? 0;

        // Sum deposits (for separate display; do not add to rent totals)
        $depositTotal = 0;
        if (!empty($summary['deposits'])) {
            foreach ($summary['deposits'] as $dep) {
                $depositTotal += floatval($dep['amount'] ?? 0);
            }
        }

        // Balance: charges minus recorded payments minus deposits (deposit reduces what is owed)
        $balance = max(0, ($recordedOutstanding + $projectedOutstanding) - $recordedPaid - $depositTotal);

        // Total paid reflects only actual rental payments (exclude deposits from paid)
        $totalPaid = $recordedPaid;

        // Only show rows that still have anything pending
        if ($balance <= 0) {
            continue;
        }

        // Total rent is remaining balance + recorded payments (deposits already reduce balance)
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
            'total_rent' => number_format($totalRent, 2),
            'total_paid' => number_format($totalPaid, 2),
            'balance' => number_format($balance, 2),
            'recorded_outstanding' => number_format($recordedOutstanding, 2),
            'projected_outstanding' => number_format($projectedOutstanding, 2),
            'recorded_outstanding_raw' => $recordedOutstanding,
            'projected_outstanding_raw' => $projectedOutstanding,
            'payments' => $summary['payments'] ?? [],
            'recorded_details' => $summary['recorded_details'] ?? [],
            'deposits' => $summary['deposits'] ?? [],
            'items' => $summary['items'] ?? []
        ];

        $grandTotalRent += $totalRent;
        $grandTotalPaid += $totalPaid;
        $grandTotalBalance += $balance;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'grand_total_rent' => number_format($grandTotalRent, 2),
        'grand_total_paid' => number_format($grandTotalPaid, 2),
        'grand_total_balance' => number_format($grandTotalBalance, 2)
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);

