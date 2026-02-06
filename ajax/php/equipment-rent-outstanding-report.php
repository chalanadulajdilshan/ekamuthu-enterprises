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
                    GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, '$asOfDateSafe 23:59:59') / 86400)) AS used_days,
                    (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END) AS per_unit_daily
                  FROM equipment_rent_items eri
                  LEFT JOIN equipment_rent er ON eri.rent_id = er.id
                  LEFT JOIN customer_master cm ON er.customer_id = cm.id
                  LEFT JOIN equipment e ON eri.equipment_id = e.id
                  LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                  WHERE er.status != 'returned'";

        if (!empty($customerId)) {
            $query .= " AND er.customer_id = " . (int)$customerId;
        }

        $result = $db->readQuery($query);
        if (!$result) {
            throw new Exception('Error executing query: ' . mysqli_error($db->DB_CON));
        }

        $data = [];
        $totalOutstanding = 0;

        while ($row = mysqli_fetch_assoc($result)) {
            $durationDays = max(1, (int)$row['duration_days']);
            $usedDays = max(1, (int)$row['used_days']);
            $overdueDays = max(0, $usedDays - $durationDays);
            $pendingQty = max(0, (float)$row['pending_qty']);
            $perUnitDaily = floatval($row['per_unit_daily']);
            $outstandingAmount = $overdueDays > 0 && $pendingQty > 0 ? round($perUnitDaily * $overdueDays * $pendingQty, 2) : 0;

            if ($pendingQty <= 0 || $overdueDays <= 0) {
                continue; // skip items not overdue or fully returned
            }

            $totalOutstanding += $outstandingAmount;

            $data[] = [
                'bill_number' => $row['bill_number'],
                'rent_id' => $row['rent_id'],
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

        // Sort by overdue days desc and outstanding amount desc for readability
        usort($data, function ($a, $b) {
            if ($a['overdue_days'] === $b['overdue_days']) {
                return $b['outstanding_amount'] <=> $a['outstanding_amount'];
            }
            return $b['overdue_days'] <=> $a['overdue_days'];
        });

        $response = [
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $data,
            'summary' => [
                'total_outstanding' => number_format($totalOutstanding, 2)
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
