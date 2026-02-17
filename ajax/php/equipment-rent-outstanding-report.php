<?php
header('Content-Type: application/json');
require_once('../../class/Database.php');

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'get_outstanding_report') {
        $customerId = $_POST['customer_id'] ?? '';
        $asOfDate = $_POST['as_of_date'] ?? date('Y-m-d');
        // ensure date format
        $asOfDateSafe = preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOfDate) ? $asOfDate : date('Y-m-d');

        $db = Database::getInstance();

        $query = "SELECT 
                    er.bill_number,
                    er.id as rent_id,
                    cm.name AS customer_name,
                    cm.code AS customer_code,
                    e.item_name AS equipment_name,
                    e.code AS equipment_code,
                    se.code AS sub_equipment_code,
                    eri.rental_date,
                    CASE WHEN eri.rent_type = 'month' THEN eri.duration * 30 ELSE eri.duration END AS duration_days,
                    DATE_ADD(eri.rental_date, INTERVAL (CASE WHEN eri.rent_type = 'month' THEN eri.duration * 30 ELSE eri.duration END) DAY) AS due_date,
                    (eri.quantity - COALESCE((SELECT SUM(return_qty) FROM equipment_rent_returns err WHERE err.rent_item_id = eri.id), 0)) AS pending_qty,
                    GREATEST(0, DATEDIFF('$asOfDateSafe', DATE_ADD(eri.rental_date, INTERVAL (CASE WHEN eri.rent_type = 'month' THEN eri.duration * 30 ELSE eri.duration END) DAY))) AS overdue_days,
                    (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END) AS per_unit_daily
                  FROM equipment_rent_items eri
                  LEFT JOIN equipment_rent er ON eri.rent_id = er.id
                  LEFT JOIN customer_master cm ON er.customer_id = cm.id
                  LEFT JOIN equipment e ON eri.equipment_id = e.id
                  LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                  WHERE 1=1";

        if (!empty($customerId)) {
            $query .= " AND er.customer_id = " . (int)$customerId;
        }

        $result = $db->readQuery($query);
        if (!$result) {
            throw new Exception('Error executing query: ' . mysqli_error($db->DB_CON));
        }

        $rows = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $durationDays = max(1, (int)$row['duration_days']);
            $overdueDays = max(0, (int)$row['overdue_days']);
            $pendingQty = max(0, (float)$row['pending_qty']);
            $perUnitDaily = floatval($row['per_unit_daily']);
            $outstandingAmount = ($overdueDays > 0 && $pendingQty > 0)
                ? round($perUnitDaily * $overdueDays * $pendingQty, 2)
                : 0;

            if ($pendingQty <= 0 || $overdueDays <= 0) {
                continue; // skip items not overdue or fully returned
            }

            $rows[] = [
                'bill_number' => $row['bill_number'],
                'rent_id' => (int)$row['rent_id'],
                'customer_name' => trim(($row['customer_code'] ?? '') . ' - ' . ($row['customer_name'] ?? '')),
                'equipment' => trim(($row['equipment_code'] ?? '') . ' ' . ($row['equipment_name'] ?? '')),
                'sub_equipment' => $row['sub_equipment_code'] ?? '',
                'rental_date' => $row['rental_date'],
                'due_date' => $row['due_date'],
                'pending_qty' => $pendingQty,
                'overdue_days' => $overdueDays,
                'per_unit_daily' => round($perUnitDaily, 2),
                'outstanding_amount' => $outstandingAmount
            ];
        }

        if (empty($rows)) {
            $response = [
                'status' => 'success',
                'message' => 'No overdue rentals found',
                'data' => [],
                'summary' => [
                    'projected_outstanding' => '0.00',
                    'recorded_outstanding' => '0.00'
                ]
            ];
            echo json_encode($response);
            exit;
        }

        // Collect rent IDs to fetch recorded outstanding + payment history
        $rentIds = array_unique(array_map(function ($item) {
            return (int)$item['rent_id'];
        }, $rows));

        $recordedOutstandingMap = [];
        $outstandingDetailsMap = [];
        $paymentHistoryMap = [];

        if (!empty($rentIds)) {
            $rentIdList = implode(',', array_map('intval', $rentIds));

            // Outstanding per return (only these rent IDs)
            $outstandingSql = "SELECT 
                                    eri.rent_id,
                                    err.id AS return_id,
                                    err.return_date,
                                    err.return_qty,
                                    err.additional_payment,
                                    err.customer_paid,
                                    err.outstanding_amount,
                                    err.remark,
                                    e.item_name,
                                    e.code AS equipment_code,
                                    se.code AS sub_equipment_code
                                FROM equipment_rent_returns err
                                INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                                LEFT JOIN equipment e ON eri.equipment_id = e.id
                                LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                                WHERE eri.rent_id IN ($rentIdList)
                                ORDER BY err.return_date ASC, err.id ASC";

            $outstandingResult = $db->readQuery($outstandingSql);
            if ($outstandingResult) {
                while ($oRow = mysqli_fetch_assoc($outstandingResult)) {
                    $rentId = (int)$oRow['rent_id'];
                    $amount = floatval($oRow['outstanding_amount']);
                    if (!isset($recordedOutstandingMap[$rentId])) {
                        $recordedOutstandingMap[$rentId] = 0;
                    }
                    $recordedOutstandingMap[$rentId] += $amount;
                    $outstandingDetailsMap[$rentId][] = [
                        'return_id' => (int)$oRow['return_id'],
                        'return_date' => $oRow['return_date'],
                        'item' => trim(($oRow['equipment_code'] ?? '') . ' ' . ($oRow['item_name'] ?? '')),
                        'sub_equipment' => $oRow['sub_equipment_code'] ?? '',
                        'return_qty' => floatval($oRow['return_qty']),
                        'additional_payment' => floatval($oRow['additional_payment']),
                        'customer_paid' => floatval($oRow['customer_paid']),
                        'outstanding_amount' => $amount,
                        'remark' => $oRow['remark'] ?? ''
                    ];
                }
            }

            // Payment history (only these rent IDs)
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
                    $paymentHistoryMap[$rentId][] = [
                        'receipt_no' => $pRow['receipt_no'],
                        'entry_date' => $pRow['entry_date'],
                        'amount' => floatval($pRow['amount']),
                        'payment_method' => $pRow['payment_method'],
                        'cheq_no' => $pRow['cheq_no'] ?? '',
                        'ref_no' => $pRow['ref_no'] ?? ''
                    ];
                }
            }
        }

        // Prepare final payload with summaries
        $data = [];
        $projectedTotal = 0;
        $recordedTotal = 0;

        // Sort by overdue days desc and projected outstanding desc for readability
        usort($rows, function ($a, $b) {
            if ($a['overdue_days'] === $b['overdue_days']) {
                return $b['outstanding_amount'] <=> $a['outstanding_amount'];
            }
            return $b['overdue_days'] <=> $a['overdue_days'];
        });

        foreach ($rows as $row) {
            $rentId = $row['rent_id'];
            $recordedOutstanding = round($recordedOutstandingMap[$rentId] ?? 0, 2);
            $projectedTotal += $row['outstanding_amount'];
            $recordedTotal += $recordedOutstanding;

            $row['recorded_outstanding'] = $recordedOutstanding;
            $row['outstanding_details'] = $outstandingDetailsMap[$rentId] ?? [];
            $row['payments'] = $paymentHistoryMap[$rentId] ?? [];

            $data[] = $row;
        }

        $response = [
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $data,
            'summary' => [
                'projected_outstanding' => number_format($projectedTotal, 2),
                'recorded_outstanding' => number_format($recordedTotal, 2)
            ]
        ];
    } else {
        throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => []
    ];
}

echo json_encode($response);
