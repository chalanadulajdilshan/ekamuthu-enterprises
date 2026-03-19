<?php
include '../../class/include.php';
header('Content-Type: application/json');

function ensureRemarksTable($db)
{
    // Create table if it doesn't exist to avoid query failures that break JSON responses
    $createSql = "CREATE TABLE IF NOT EXISTS `equipment_rent_remarks` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `rent_id` INT(11) NOT NULL,
        `remark` TEXT,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_rent_id` (`rent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $db->readQuery($createSql) !== false;
}

$action = $_POST['action'] ?? '';

// Ensure outstanding report reference table exists
function ensureOutstandingRefTable($db)
{
    $createSql = "CREATE TABLE IF NOT EXISTS `outstanding_report_refs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `rent_id` INT(11) NOT NULL,
        `customer_id` INT(11) NOT NULL,
        `ref_number` VARCHAR(50) NOT NULL,
        `year` INT(4) NOT NULL,
        `month` INT(2) NOT NULL,
        `from_date` DATE NOT NULL,
        `to_date` DATE NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_ref` (`rent_id`, `year`, `month`),
        KEY `idx_customer` (`customer_id`),
        KEY `idx_ref_number` (`ref_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $db->readQuery($createSql) !== false;
}

// Generate or retrieve outstanding report reference number
if ($action === 'get_outstanding_ref') {
    $rentId = isset($_POST['rent_id']) ? (int)$_POST['rent_id'] : 0;
    $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $fromDate = $_POST['from_date'] ?? '';
    $toDate = $_POST['to_date'] ?? '';

    if ($rentId <= 0 || $customerId <= 0 || empty($fromDate) || empty($toDate)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        exit;
    }

    $db = Database::getInstance();
    if (!ensureOutstandingRefTable($db)) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot create reference table']);
        exit;
    }

    // Extract year and month from toDate
    $toDt = DateTime::createFromFormat('Y-m-d', $toDate);
    if (!$toDt) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
        exit;
    }
    $year = (int)$toDt->format('Y');
    $month = (int)$toDt->format('m');

    // Check if reference already exists
    $checkSql = "SELECT ref_number FROM `outstanding_report_refs` WHERE `rent_id` = $rentId AND `year` = $year AND `month` = $month";
    $checkResult = $db->readQuery($checkSql);
    
    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $row = mysqli_fetch_assoc($checkResult);
        echo json_encode(['status' => 'success', 'ref_number' => $row['ref_number']]);
        exit;
    }

    // Generate new reference number: OSR-YYYYMM-XXXXX
    $prefix = 'OSR-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-';
    
    // Get last sequence for this month
    $seqSql = "SELECT MAX(CAST(SUBSTRING(ref_number, -5) AS UNSIGNED)) as last_seq FROM `outstanding_report_refs` WHERE `year` = $year AND `month` = $month";
    $seqResult = $db->readQuery($seqSql);
    $lastSeq = 0;
    if ($seqResult && $seqRow = mysqli_fetch_assoc($seqResult)) {
        $lastSeq = (int)($seqRow['last_seq'] ?? 0);
    }
    
    $newSeq = $lastSeq + 1;
    $refNumber = $prefix . str_pad($newSeq, 5, '0', STR_PAD_LEFT);

    // Insert new reference
    $fromDateEsc = mysqli_real_escape_string($db->DB_CON, $fromDate);
    $toDateEsc = mysqli_real_escape_string($db->DB_CON, $toDate);
    $refNumberEsc = mysqli_real_escape_string($db->DB_CON, $refNumber);
    
    $insertSql = "INSERT INTO `outstanding_report_refs` (`rent_id`, `customer_id`, `ref_number`, `year`, `month`, `from_date`, `to_date`, `created_at`) 
                  VALUES ($rentId, $customerId, '$refNumberEsc', $year, $month, '$fromDateEsc', '$toDateEsc', NOW())";
    
    $result = $db->readQuery($insertSql);
    if ($result) {
        echo json_encode(['status' => 'success', 'ref_number' => $refNumber]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to generate reference number']);
    }
    exit;
}

// Save damage amount against a rent item
if ($action === 'save_damage_amount') {
    $rentItemId = isset($_POST['rent_item_id']) ? (int)$_POST['rent_item_id'] : 0;
    $damageAmount = isset($_POST['damage_amount']) ? floatval($_POST['damage_amount']) : 0;

    if ($rentItemId <= 0 || $damageAmount < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid item or amount']);
        exit;
    }

    $db = Database::getInstance();
    $damageAmount = round($damageAmount, 2);
    $updateSql = "UPDATE `equipment_rent_items` SET `damage_amount` = $damageAmount, `updated_at` = NOW() WHERE `id` = $rentItemId";
    $result = $db->readQuery($updateSql);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Damage amount saved', 'rent_item_id' => $rentItemId, 'damage_amount' => number_format($damageAmount, 2, '.', '')]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save damage amount']);
    }
    exit;
}

// Save a free-form remark against a rent invoice
if ($action === 'save_rent_remark') {
    $rentId = isset($_POST['rent_id']) ? (int)$_POST['rent_id'] : 0;
    $remarkText = trim($_POST['remark'] ?? '');

    if ($rentId <= 0 || $remarkText === '') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid rent or empty remark']);
        exit;
    }

    $db = Database::getInstance();
    if (!ensureRemarksTable($db)) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot create remarks table']);
        exit;
    }
    $remarkEscaped = mysqli_real_escape_string($db->DB_CON, $remarkText);
    $insertSql = "INSERT INTO `equipment_rent_remarks` (`rent_id`, `remark`, `created_at`) VALUES ($rentId, '$remarkEscaped', NOW())";
    $result = $db->readQuery($insertSql);

    if ($result) {
        $newId = mysqli_insert_id($db->DB_CON);
        echo json_encode([
            'status' => 'success',
            'message' => 'Remark saved',
            'remark' => [
                'id' => $newId,
                'rent_id' => $rentId,
                'remark' => $remarkText,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save remark']);
    }
    exit;
}

if ($action === 'get_outstanding_report') {
    $customerId = isset($_POST['customer_id']) && !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $fromDateRaw = $_POST['from_date'] ?? '';
    $toDateRaw = $_POST['to_date'] ?? '';
    $monthFilterRaw = $_POST['month_filter'] ?? '';

    $fromDate = null;
    $toDate = null;

    $monthFilter = null;
    if (!empty($fromDateRaw)) {
        $fromDt = DateTime::createFromFormat('Y-m-d', $fromDateRaw);
        if ($fromDt) {
            $fromDate = $fromDt->format('Y-m-d');
        }
    }

    if (!empty($toDateRaw)) {
        $toDt = DateTime::createFromFormat('Y-m-d', $toDateRaw);
        if ($toDt) {
            $toDate = $toDt->format('Y-m-d');
        }
    }

    if ($monthFilterRaw !== '' && $monthFilterRaw !== null) {
        $monthInt = (int)$monthFilterRaw;
        if ($monthInt >= 1 && $monthInt <= 12) {
            $monthFilter = $monthInt;
            // Month filter takes precedence over date range
            $fromDate = null;
            $toDate = null;
        }
    }

    // Exclude cancelled bills from all outstanding calculations
    $where = "WHERE er.is_cancelled = 0 AND er.status <> 'cancelled'";
    if ($customerId > 0) {
        $where .= " AND er.customer_id = $customerId";
    }
    if ($monthFilter) {
        $where .= " AND MONTH(er.rental_date) = $monthFilter";
    } elseif ($fromDate && $toDate) {
        $where .= " AND DATE(er.rental_date) BETWEEN '$fromDate' AND '$toDate'";
    } elseif ($fromDate) {
        $where .= " AND DATE(er.rental_date) >= '$fromDate'";
    } elseif ($toDate) {
        $where .= " AND DATE(er.rental_date) <= '$toDate'";
    }

    $db = Database::getInstance();
    $today = $toDate ?: date('Y-m-d');

    $rentSummary = [];

    // Helper closure to initialize rent meta only once
    $ensureSummary = function (&$rentSummary, $row) {
        $rentId = (int)$row['rent_id'];
        if (!isset($rentSummary[$rentId])) {
            $rentSummary[$rentId] = [
                'bill_number' => $row['bill_number'],
                'rental_date' => $row['rental_date'],
                'customer_id' => isset($row['customer_id']) ? (int)$row['customer_id'] : 0,
                'customer_name' => $row['customer_name'],
                'customer_mobile' => $row['customer_mobile'] ?? '',
                'customer_mobile_2' => $row['customer_mobile_2'] ?? '',
                'company_name' => $row['company_name'] ?? '',
                'is_company' => $row['is_company'] ?? 0,
                'payment_type_name' => $row['payment_type_name'] ?? 'N/A',
                'rent_status' => $row['rent_status'] ?? '',
                'recorded_outstanding' => 0,
                'recorded_paid' => 0,
                'projected_outstanding' => 0,
                'recorded_details' => [],
                'payments' => [],
                'deposits' => [],
                'remarks' => [],
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
                        cm.id as customer_id,
                        cm.name as customer_name,
                        cm.company_name,
                        cm.is_company,
                        cm.mobile_number as customer_mobile,
                        cm.mobile_number_2 as customer_mobile_2,
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
                        cm.id as customer_id,
                        cm.name as customer_name,
                        cm.company_name,
                        cm.is_company,
                        cm.mobile_number as customer_mobile,
                        cm.mobile_number_2 as customer_mobile_2,
                        pt.name as payment_type_name,
                        er.status as rent_status,
                    (eri.quantity - COALESCE((SELECT SUM(return_qty) FROM equipment_rent_returns err2 WHERE err2.rent_item_id = eri.id), 0)) AS pending_qty,
                    (DATEDIFF('$today', eri.rental_date) + 1) AS used_days,
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
                        eri.id AS rent_item_id,
                        eri.quantity,
                        eri.amount,
                        COALESCE(eri.damage_amount, 0) AS damage_amount,
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
                    'rent_item_id' => (int)($iRow['rent_item_id'] ?? 0),
                    'item' => trim(($iRow['equipment_code'] ?? '') . ' ' . ($iRow['item_name'] ?? '')),
                    'sub_equipment' => $iRow['sub_equipment_code'] ?? '',
                    'quantity' => $qty,
                    'returned_qty' => $returned,
                    'pending_qty' => $pending,
                    'return_status' => $returnStatus,
                    'amount' => $iRow['amount'] ?? 0,
                    'damage_amount' => floatval($iRow['damage_amount'] ?? 0),
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

        // Free-form remarks per rent/invoice
        if (ensureRemarksTable($db)) {
            $remarksSql = "SELECT id, rent_id, remark, created_at FROM equipment_rent_remarks WHERE rent_id IN ($rentIdList) ORDER BY created_at DESC, id DESC";
            $remarksResult = $db->readQuery($remarksSql);
            if ($remarksResult) {
                while ($remRow = mysqli_fetch_assoc($remarksResult)) {
                    $rentId = (int)$remRow['rent_id'];
                    if (!isset($rentSummary[$rentId])) continue;

                    $rentSummary[$rentId]['remarks'][] = [
                        'id' => (int)$remRow['id'],
                        'remark' => $remRow['remark'] ?? '',
                        'created_at' => $remRow['created_at'] ?? ''
                    ];
                }
            }
        }

        // Return history per rent/invoice
        $returnsSql = "SELECT 
                            er.id AS rent_id,
                            err.return_date,
                            err.return_time,
                            err.return_qty,
                            err.extra_day_amount,
                            err.penalty_amount,
                            err.damage_amount,
                            err.additional_payment,
                            err.customer_paid,
                            err.outstanding_amount,
                            err.refund_amount,
                            err.remark,
                            err.rental_override,
                            err.extra_charge_amount,
                            err.repair_cost,
                            eri.amount AS item_amount,
                            eri.quantity AS item_qty,
                            eri.rental_date AS item_rental_date,
                            eri.rent_type,
                            eri.duration,
                            COALESCE(e.is_fixed_rate, 0) AS is_fixed_rate,
                            (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) AS per_unit_daily,
                            e.item_name,
                            e.code AS equipment_code,
                            se.code AS sub_equipment_code
                        FROM equipment_rent_returns err
                        INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                        INNER JOIN equipment_rent er ON eri.rent_id = er.id
                        LEFT JOIN equipment e ON eri.equipment_id = e.id
                        LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                        WHERE er.id IN ($rentIdList)
                        ORDER BY err.return_date DESC, err.id DESC";

        $returnsResult = $db->readQuery($returnsSql);
        if ($returnsResult) {
            while ($rRow = mysqli_fetch_assoc($returnsResult)) {
                $rentId = (int)$rRow['rent_id'];
                if (!isset($rentSummary[$rentId])) {
                    continue;
                }

                $perUnitDaily = floatval($rRow['per_unit_daily'] ?? 0);
                $returnQty = floatval($rRow['return_qty'] ?? 0);
                $returnDate = $rRow['return_date'] ?? date('Y-m-d');
                $rentalDate = $rRow['item_rental_date'] ?? $returnDate;
                $rentType = $rRow['rent_type'] ?? '';
                $duration = max(1, floatval($rRow['duration'] ?? 1));
                $durationDays = ($rentType === 'month') ? ($duration * 30) : $duration;

                $usedDays = max(1, (int)ceil((strtotime($returnDate) - strtotime($rentalDate)) / 86400));
                $isFixedRate = intval($rRow['is_fixed_rate'] ?? 0) === 1;

                // Rental amount calculation mirrors EquipmentRentReturn::getByRentItemId
                if ($isFixedRate) {
                    $rentalAmount = $perUnitDaily * $returnQty;
                } else {
                    $rentalAmount = $usedDays * $perUnitDaily * $returnQty;
                }

                if (!empty($rRow['rental_override'])) {
                    $rentalAmount = floatval($rRow['rental_override']);
                }

                $additionalPayment = floatval($rRow['additional_payment'] ?? 0);
                $refundAmount = floatval($rRow['refund_amount'] ?? 0);
                $settlementAmount = $additionalPayment > 0 ? $additionalPayment : $refundAmount;
                $settlementType = $additionalPayment > 0 ? 'pay' : ($refundAmount > 0 ? 'refund' : 'none');

                $rentSummary[$rentId]['return_history'][] = [
                    'return_date' => $rRow['return_date'],
                    'return_time' => $rRow['return_time'],
                    'item' => trim(($rRow['equipment_code'] ?? '') . ' ' . ($rRow['item_name'] ?? '')),
                    'sub_equipment' => $rRow['sub_equipment_code'] ?? '',
                    'quantity' => $returnQty,
                    'rental_amount' => round($rentalAmount, 2),
                    'extra_day_amount' => floatval($rRow['extra_day_amount'] ?? 0),
                    'penalty_amount' => floatval($rRow['penalty_amount'] ?? 0),
                    'damage_amount' => floatval($rRow['damage_amount'] ?? 0),
                    'extra_charge_amount' => floatval($rRow['extra_charge_amount'] ?? 0),
                    'repair_cost' => floatval($rRow['repair_cost'] ?? 0),
                    'settlement_amount' => round($settlementAmount, 2),
                    'settlement_type' => $settlementType,
                    'paid' => floatval($rRow['customer_paid'] ?? 0),
                    'outstanding' => floatval($rRow['outstanding_amount'] ?? 0),
                    'remark' => $rRow['remark'] ?? ''
                ];
            }
        }
    }

    // Build response payload
    $data = [];
    $grandTotalDayRent = 0;
    $grandTotalRent = 0;
    $grandTotalRentPlusInitial = 0;
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

        // Sum non-deposit payment receipts
        $paymentReceiptsTotal = 0;
        if (!empty($summary['payments'])) {
            foreach ($summary['payments'] as $pay) {
                $paymentReceiptsTotal += floatval($pay['amount'] ?? 0);
            }
        }

        // Sum deposits (for separate display; do not add to rent totals)
        $depositTotal = 0;
        $nonInitialDepositTotal = 0;
        if (!empty($summary['deposits'])) {
            foreach ($summary['deposits'] as $dep) {
                $depAmount = floatval($dep['amount'] ?? 0);
                $depositTotal += $depAmount;
                // Count non-initial deposits toward Total Paid
                if (strtolower(trim($dep['remark'] ?? '')) !== 'initial deposit') {
                    $nonInitialDepositTotal += $depAmount;
                }
            }
        }
        $initialDepositTotal = max(0, $depositTotal - $nonInitialDepositTotal);

        // Sum damage amounts from items
        $totalDamageAmount = 0;
        $dayRentTotal = 0; // total rental rate as shown in detail view
        if (!empty($summary['items'])) {
            foreach ($summary['items'] as $item) {
                $totalDamageAmount += floatval($item['damage_amount'] ?? 0);

                // Align with detail view: use the raw item amount regardless of rent type
                $dayRentTotal += floatval($item['amount'] ?? 0);
            }
        }

        // Rental charges (do NOT include deposits) + damage amounts
        $totalCharges = $recordedOutstanding + $projectedOutstanding + $totalDamageAmount;

        // Paid = recorded payments + receipt payments + non-initial deposits
        $totalPaid = $recordedPaid + $paymentReceiptsTotal + $nonInitialDepositTotal;

        // Balance = charges - actual payments (deposits excluded)
        $balance = max(0, $totalCharges - $totalPaid);

        // Only show rows that still have anything pending
        if ($balance <= 0) {
            continue;
        }

        // Total rent shown = total charges only (deposits not added)
        $totalRent = $totalCharges;
        $rentPlusInitial = $totalRent + $initialDepositTotal;

        $statusLabel = (isset($summary['rent_status']) && strtolower($summary['rent_status']) === 'returned')
            ? 'Returned'
            : 'Not Returned';

        $data[] = [
            'id' => $rentId,
            'customer_id' => $summary['customer_id'] ?? 0,
            'bill_number' => $summary['bill_number'],
            'rental_date' => $summary['rental_date'],
            'payment_type_name' => $summary['payment_type_name'] ?? 'N/A',
            'customer_name' => $summary['customer_name'],
            'company_name' => $summary['company_name'] ?? '',
            'is_company' => $summary['is_company'] ?? 0,
            'customer_mobile' => $summary['customer_mobile'] ?? '',
            'customer_mobile_2' => $summary['customer_mobile_2'] ?? '',
            'status_label' => $statusLabel,
            'day_rent' => number_format($dayRentTotal, 2),
            'day_rent_raw' => $dayRentTotal,
            'total_rent' => number_format($totalRent, 2),
            'rent_plus_initial' => number_format($rentPlusInitial, 2),
            'total_paid' => number_format($totalPaid, 2),
            'balance' => number_format($balance, 2),
            'recorded_outstanding' => number_format($recordedOutstanding, 2),
            'projected_outstanding' => number_format($projectedOutstanding, 2),
            'recorded_outstanding_raw' => $recordedOutstanding,
            'projected_outstanding_raw' => $projectedOutstanding,
            'initial_deposit_total' => number_format($initialDepositTotal, 2),
            'initial_deposit_total_raw' => $initialDepositTotal,
            'deposit_total' => number_format($depositTotal, 2),
            'deposit_total_raw' => $depositTotal,
            'payments' => $summary['payments'] ?? [],
            'recorded_details' => $summary['recorded_details'] ?? [],
            'deposits' => $summary['deposits'] ?? [],
            'remarks' => $summary['remarks'] ?? [],
            'items' => $summary['items'] ?? [],
            'return_history' => $summary['return_history'] ?? []
        ];

        $grandTotalRent += $totalRent;
        $grandTotalDayRent += $dayRentTotal; // NEW
        $grandTotalRentPlusInitial += $rentPlusInitial;
        $grandTotalPaid += $totalPaid;
        $grandTotalBalance += $balance;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'grand_total_day_rent' => number_format($grandTotalDayRent ?? 0, 2),
        'grand_total_rent' => number_format($grandTotalRent, 2),
        'grand_total_rent_plus_initial' => number_format($grandTotalRentPlusInitial, 2),
        'grand_total_paid' => number_format($grandTotalPaid, 2),
        'grand_total_balance' => number_format($grandTotalBalance, 2)
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);

