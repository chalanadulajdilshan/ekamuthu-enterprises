<?php
include '../../class/include.php';
header('Content-Type: application/json');

// Follow-up status / rep assignment per rent bill. A bill without a row = follow-up required, unassigned.
function ensureFollowupTable($db)
{
    $createSql = "CREATE TABLE IF NOT EXISTS `rent_overdue_followup` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `rent_id` INT(11) NOT NULL,
        `followup_required` TINYINT(1) NOT NULL DEFAULT 1,
        `rep_id` INT(11) DEFAULT NULL,
        `assigned_at` DATETIME DEFAULT NULL,
        `assigned_by` INT(11) DEFAULT NULL,
        `note` VARCHAR(255) DEFAULT NULL,
        `updated_by` INT(11) DEFAULT NULL,
        `updated_at` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_rent` (`rent_id`),
        KEY `idx_rep` (`rep_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $db->readQuery($createSql) !== false;
}

// "1 year 2 months 5 days" between due date and as-of date
function lateText($dueDate, $asOfDate)
{
    $diff = (new DateTime($dueDate))->diff(new DateTime($asOfDate));
    $parts = [];
    if ($diff->y > 0) $parts[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
    if ($diff->d > 0) $parts[] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
    return $parts ? implode(' ', $parts) : '0 days';
}

function parseRentIds($raw)
{
    $ids = is_array($raw) ? $raw : explode(',', (string)$raw);
    return array_values(array_unique(array_filter(array_map('intval', $ids))));
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance();
$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

if (!ensureFollowupTable($db)) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot create follow-up table']);
    exit;
}

if ($action === 'get_overdue_bills') {
    $asOfDate = $_POST['as_of_date'] ?? date('Y-m-d');
    $asOfDateSafe = preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOfDate) ? $asOfDate : date('Y-m-d');

    $dueExpr = "DATE_ADD(eri.rental_date, INTERVAL (CASE WHEN eri.rent_type = 'month' THEN eri.duration * DAY(LAST_DAY(eri.rental_date)) ELSE eri.duration END) DAY)";

    $query = "SELECT
                er.id AS rent_id,
                er.bill_number,
                er.rental_date AS bill_date,
                er.workplace_address,
                er.deposit_total,
                cm.id AS customer_id,
                cm.code AS customer_code,
                cm.name AS customer_name,
                cm.mobile_number,
                cm.mobile_number_2,
                cm.address AS customer_address,
                e.item_name AS equipment_name,
                e.code AS equipment_code,
                se.code AS sub_equipment_code,
                eri.rent_type,
                eri.duration,
                $dueExpr AS due_date,
                (eri.quantity - COALESCE((SELECT SUM(return_qty) FROM equipment_rent_returns err WHERE err.rent_item_id = eri.id), 0)) AS pending_qty,
                DATEDIFF('$asOfDateSafe', $dueExpr) AS overdue_days,
                (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) AS per_unit_daily
              FROM equipment_rent_items eri
              INNER JOIN equipment_rent er ON eri.rent_id = er.id
              LEFT JOIN customer_master cm ON er.customer_id = cm.id
              LEFT JOIN equipment e ON eri.equipment_id = e.id
              LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
              WHERE COALESCE(er.is_cancelled, 0) = 0
              HAVING pending_qty > 0 AND overdue_days > 0";

    $result = $db->readQuery($query);
    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'Error executing query: ' . mysqli_error($db->DB_CON)]);
        exit;
    }

    // Group pending items per bill; bill lateness = most overdue item
    $bills = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rentId = (int)$row['rent_id'];
        $overdueDays = (int)$row['overdue_days'];
        $pendingQty = (float)$row['pending_qty'];
        $perUnitDaily = floatval($row['per_unit_daily']);

        // Same projection as Rented Item Outstanding Report
        if (($row['rent_type'] ?? 'day') === 'month') {
            $chargedDays = max(1, (int)ceil($overdueDays / 30)) * 30;
            $projected = round($perUnitDaily * $chargedDays * $pendingQty, 2);
        } else {
            $projected = round($perUnitDaily * $overdueDays * $pendingQty, 2);
        }

        if (!isset($bills[$rentId])) {
            $location = trim($row['workplace_address'] ?? '');
            $bills[$rentId] = [
                'rent_id' => $rentId,
                'bill_number' => $row['bill_number'],
                'bill_date' => $row['bill_date'],
                'customer_id' => (int)$row['customer_id'],
                'customer_code' => $row['customer_code'] ?? '',
                'customer_name' => $row['customer_name'] ?? '',
                'mobile_number' => trim(implode(' / ', array_filter([$row['mobile_number'] ?? '', $row['mobile_number_2'] ?? '']))),
                'location' => $location !== '' ? $location : trim($row['customer_address'] ?? ''),
                'deposit_total' => floatval($row['deposit_total'] ?? 0),
                'due_date' => $row['due_date'],
                'overdue_days' => $overdueDays,
                'pending_qty' => 0,
                'projected_outstanding' => 0,
                'items' => []
            ];
        }

        $bill = &$bills[$rentId];
        if ($overdueDays > $bill['overdue_days']) {
            $bill['overdue_days'] = $overdueDays;
            $bill['due_date'] = $row['due_date'];
        }
        $bill['pending_qty'] += $pendingQty;
        $bill['projected_outstanding'] += $projected;
        $bill['items'][] = [
            'item' => trim(($row['equipment_code'] ?? '') . ' ' . ($row['equipment_name'] ?? '')),
            'sub_equipment' => $row['sub_equipment_code'] ?? '',
            'pending_qty' => $pendingQty,
            'due_date' => $row['due_date'],
            'overdue_days' => $overdueDays
        ];
        unset($bill);
    }

    if (empty($bills)) {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }

    $rentIdList = implode(',', array_keys($bills));
    foreach ($bills as &$bill) {
        $bill['late_text'] = lateText($bill['due_date'], $asOfDateSafe);
        $bill['projected_outstanding'] = round($bill['projected_outstanding'], 2);
        $bill['recorded_outstanding'] = 0;
        $bill['payments'] = ['deposits' => [], 'returns' => [], 'settlements' => []];
        $bill['paid_total'] = 0;
        $bill['followup_required'] = 1;
        $bill['rep_id'] = null;
        $bill['rep_name'] = '';
        $bill['assigned_at'] = null;
        $bill['note'] = '';
    }
    unset($bill);

    // Deposits
    $hasDepositRows = [];
    $depResult = $db->readQuery("SELECT rent_id, amount, payment_date, remark FROM deposit_payments WHERE rent_id IN ($rentIdList) ORDER BY payment_date ASC, id ASC");
    if ($depResult) {
        while ($r = mysqli_fetch_assoc($depResult)) {
            $rentId = (int)$r['rent_id'];
            $hasDepositRows[$rentId] = true;
            $bills[$rentId]['payments']['deposits'][] = [
                'date' => $r['payment_date'],
                'amount' => floatval($r['amount']),
                'remark' => $r['remark'] ?? ''
            ];
        }
    }
    // Older bills may have deposit_total without deposit_payments rows
    foreach ($bills as $rentId => $b) {
        if (empty($hasDepositRows[$rentId]) && $b['deposit_total'] > 0) {
            $bills[$rentId]['payments']['deposits'][] = [
                'date' => $b['bill_date'],
                'amount' => $b['deposit_total'],
                'remark' => 'Deposit at rent'
            ];
        }
    }

    // Partial returns: amount paid at return + remaining recorded outstanding
    $retResult = $db->readQuery("SELECT eri.rent_id, err.return_date, err.return_qty, err.initial_customer_paid, err.outstanding_amount,
                                        e.item_name, e.code AS equipment_code
                                 FROM equipment_rent_returns err
                                 INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                                 LEFT JOIN equipment e ON eri.equipment_id = e.id
                                 WHERE eri.rent_id IN ($rentIdList)
                                 ORDER BY err.return_date ASC, err.id ASC");
    if ($retResult) {
        while ($r = mysqli_fetch_assoc($retResult)) {
            $rentId = (int)$r['rent_id'];
            $bills[$rentId]['recorded_outstanding'] += floatval($r['outstanding_amount']);
            $paid = floatval($r['initial_customer_paid'] ?? 0);
            if ($paid > 0) {
                $bills[$rentId]['payments']['returns'][] = [
                    'date' => $r['return_date'],
                    'amount' => $paid,
                    'remark' => trim(($r['equipment_code'] ?? '') . ' ' . ($r['item_name'] ?? '')) . ' (Qty ' . floatval($r['return_qty']) . ')'
                ];
            }
        }
    }

    // Outstanding settlements (receipts created by Outstanding Payment for rent bills)
    $setResult = $db->readQuery("SELECT prm.invoice_id AS rent_id, pr.receipt_no, pr.entry_date, prm.amount,
                                        COALESCE(pt.name, 'Unknown') AS payment_method, prm.cheq_no, prm.ref_no
                                 FROM payment_receipt_method prm
                                 INNER JOIN payment_receipt pr ON prm.receipt_id = pr.id
                                 LEFT JOIN payment_type pt ON prm.payment_type_id = pt.id
                                 WHERE prm.invoice_id IN ($rentIdList) AND pr.remark = 'Rent Payment Settlement'
                                 ORDER BY pr.entry_date ASC, prm.id ASC");
    if ($setResult) {
        while ($r = mysqli_fetch_assoc($setResult)) {
            $rentId = (int)$r['rent_id'];
            $ref = array_filter([
                $r['cheq_no'] ? 'Cheque: ' . $r['cheq_no'] : '',
                $r['ref_no'] ? 'Ref: ' . $r['ref_no'] : ''
            ]);
            $bills[$rentId]['payments']['settlements'][] = [
                'date' => $r['entry_date'],
                'amount' => floatval($r['amount']),
                'remark' => trim('#' . $r['receipt_no'] . ' ' . $r['payment_method'] . ($ref ? ' - ' . implode(' | ', $ref) : ''))
            ];
        }
    }

    // Follow-up status + assigned rep
    $fuResult = $db->readQuery("SELECT f.rent_id, f.followup_required, f.rep_id, f.assigned_at, f.note, me.full_name AS rep_name
                                FROM rent_overdue_followup f
                                LEFT JOIN marketing_executive me ON f.rep_id = me.id
                                WHERE f.rent_id IN ($rentIdList)");
    if ($fuResult) {
        while ($r = mysqli_fetch_assoc($fuResult)) {
            $rentId = (int)$r['rent_id'];
            $bills[$rentId]['followup_required'] = (int)$r['followup_required'];
            $bills[$rentId]['rep_id'] = $r['rep_id'] !== null ? (int)$r['rep_id'] : null;
            $bills[$rentId]['rep_name'] = $r['rep_name'] ?? '';
            $bills[$rentId]['assigned_at'] = $r['assigned_at'];
            $bills[$rentId]['note'] = $r['note'] ?? '';
        }
    }

    foreach ($bills as &$bill) {
        $bill['recorded_outstanding'] = round($bill['recorded_outstanding'], 2);
        $total = 0;
        foreach ($bill['payments'] as $group) {
            foreach ($group as $p) {
                $total += $p['amount'];
            }
        }
        $bill['paid_total'] = round($total, 2);
    }
    unset($bill);

    $data = array_values($bills);
    usort($data, function ($a, $b) {
        return $b['overdue_days'] <=> $a['overdue_days'];
    });

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

if ($action === 'assign_rep') {
    $rentIds = parseRentIds($_POST['rent_ids'] ?? []);
    $repId = (int)($_POST['rep_id'] ?? 0);

    if (empty($rentIds)) {
        echo json_encode(['status' => 'error', 'message' => 'No bills selected']);
        exit;
    }

    // rep_id 0 = remove assignment
    $repSql = 'NULL';
    $assignedAtSql = 'NULL';
    $assignedBySql = 'NULL';
    if ($repId > 0) {
        $REP = new MarketingExecutive($repId);
        if (!$REP->id) {
            echo json_encode(['status' => 'error', 'message' => 'Selected rep not found']);
            exit;
        }
        $repSql = $repId;
        $assignedAtSql = 'NOW()';
        $assignedBySql = $userId;
    }

    $values = [];
    foreach ($rentIds as $rentId) {
        $values[] = "($rentId, 1, $repSql, $assignedAtSql, $assignedBySql, $userId, NOW())";
    }

    // Assigning always puts the bill back into follow-up
    $sql = "INSERT INTO `rent_overdue_followup` (`rent_id`, `followup_required`, `rep_id`, `assigned_at`, `assigned_by`, `updated_by`, `updated_at`)
            VALUES " . implode(',', $values) . "
            ON DUPLICATE KEY UPDATE `followup_required` = 1, `rep_id` = VALUES(`rep_id`), `assigned_at` = VALUES(`assigned_at`),
                `assigned_by` = VALUES(`assigned_by`), `updated_by` = VALUES(`updated_by`), `updated_at` = VALUES(`updated_at`)";

    if ($db->readQuery($sql) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to assign: ' . mysqli_error($db->DB_CON)]);
        exit;
    }

    $msg = $repId > 0 ? count($rentIds) . ' bill(s) assigned to ' . $REP->full_name : 'Assignment removed from ' . count($rentIds) . ' bill(s)';
    echo json_encode(['status' => 'success', 'message' => $msg]);
    exit;
}

if ($action === 'set_followup') {
    $rentIds = parseRentIds($_POST['rent_ids'] ?? []);
    $required = (int)($_POST['followup_required'] ?? 1) === 1 ? 1 : 0;
    $note = $db->escapeString(mb_substr(trim($_POST['note'] ?? ''), 0, 255));

    if (empty($rentIds)) {
        echo json_encode(['status' => 'error', 'message' => 'No bills selected']);
        exit;
    }

    $values = [];
    foreach ($rentIds as $rentId) {
        $values[] = "($rentId, $required, '$note', $userId, NOW())";
    }

    // Marking "no follow-up" also clears any rep assignment
    $repReset = $required === 0 ? ", `rep_id` = NULL, `assigned_at` = NULL, `assigned_by` = NULL" : '';
    $sql = "INSERT INTO `rent_overdue_followup` (`rent_id`, `followup_required`, `note`, `updated_by`, `updated_at`)
            VALUES " . implode(',', $values) . "
            ON DUPLICATE KEY UPDATE `followup_required` = VALUES(`followup_required`), `note` = VALUES(`note`),
                `updated_by` = VALUES(`updated_by`), `updated_at` = VALUES(`updated_at`)$repReset";

    if ($db->readQuery($sql) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update: ' . mysqli_error($db->DB_CON)]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => count($rentIds) . ' bill(s) ' . ($required ? 'moved to follow-up' : 'marked as no follow-up')
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
