<?php

// Overdue rent follow-up: rep assignment, rep visits and rep collections (shared by admin pages and rep-app)
class RepFollowup
{
    const VISIT_OUTCOMES = [
        'returned' => 'Items Returned',
        'partial_return' => 'Partially Returned',
        'promised' => 'Promised to Return / Pay',
        'not_available' => 'Customer Not Available',
        'refused' => 'Refused',
        'other' => 'Other'
    ];

    const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'cheque' => 'Cheque'
    ];

    // no_proof = rep entered it but no proof yet, so it is not counted as paid
    const COLLECTION_STATUSES = [
        'no_proof' => 'No Proof',
        'proof_added' => 'Proof Added',
        'verified' => 'Verified',
        'rejected' => 'Rejected'
    ];

    private static $schemaReady = false;

    public static function ensureSchema()
    {
        if (self::$schemaReady) {
            return true;
        }
        $db = Database::getInstance();

        $tables = [
            "CREATE TABLE IF NOT EXISTS `rent_overdue_followup` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `rep_visits` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `rep_id` INT(11) NOT NULL,
                `rent_id` INT(11) NOT NULL,
                `customer_id` INT(11) DEFAULT NULL,
                `visit_at` DATETIME NOT NULL,
                `outcome` VARCHAR(30) NOT NULL,
                `promised_date` DATE DEFAULT NULL,
                `remark` TEXT,
                `latitude` DECIMAL(10,7) DEFAULT NULL,
                `longitude` DECIMAL(10,7) DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_rep_date` (`rep_id`, `visit_at`),
                KEY `idx_rent` (`rent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `rep_collections` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `rep_id` INT(11) NOT NULL,
                `rent_id` INT(11) NOT NULL,
                `customer_id` INT(11) DEFAULT NULL,
                `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `payment_method` VARCHAR(20) NOT NULL DEFAULT 'cash',
                `reference` VARCHAR(100) DEFAULT NULL,
                `remark` VARCHAR(255) DEFAULT NULL,
                `proof_image` VARCHAR(255) DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'no_proof',
                `collected_at` DATETIME NOT NULL,
                `proof_added_at` DATETIME DEFAULT NULL,
                `verified_by` INT(11) DEFAULT NULL,
                `verified_at` DATETIME DEFAULT NULL,
                `admin_remark` VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_rep_date` (`rep_id`, `collected_at`),
                KEY `idx_rent` (`rent_id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            // Mobile app login tokens (only the SHA-256 of the token is stored)
            "CREATE TABLE IF NOT EXISTS `rep_app_tokens` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `rep_id` INT(11) NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `device` VARCHAR(100) DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_used_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_token` (`token_hash`),
                KEY `idx_rep` (`rep_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        foreach ($tables as $sql) {
            if ($db->readQuery($sql) === false) {
                return false;
            }
        }

        // Rep app login on the existing sales executive record
        $hasLogin = mysqli_num_rows($db->readQuery("SHOW COLUMNS FROM `marketing_executive` LIKE 'app_username'")) > 0;
        if (!$hasLogin) {
            $db->readQuery("ALTER TABLE `marketing_executive`
                ADD COLUMN `app_username` VARCHAR(50) DEFAULT NULL,
                ADD COLUMN `app_password` VARCHAR(255) DEFAULT NULL,
                ADD COLUMN `app_enabled` TINYINT(1) NOT NULL DEFAULT 0,
                ADD COLUMN `app_last_login` DATETIME DEFAULT NULL,
                ADD UNIQUE KEY `uniq_app_username` (`app_username`)");
        }

        self::$schemaReady = true;
        return true;
    }

    // "1 year 2 months 5 days" between due date and as-of date
    public static function lateText($dueDate, $asOfDate)
    {
        $diff = (new DateTime($dueDate))->diff(new DateTime($asOfDate));
        $parts = [];
        if ($diff->y > 0) $parts[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
        if ($diff->m > 0) $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        if ($diff->d > 0) $parts[] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
        return $parts ? implode(' ', $parts) : '0 days';
    }

    /**
     * Bills with items still not returned after the due date, one entry per bill.
     * $onlyRentIds limits the result (e.g. bills assigned to one rep).
     */
    public static function getOverdueBills($asOfDate, $onlyRentIds = null)
    {
        self::ensureSchema();
        $db = Database::getInstance();
        $asOfDateSafe = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$asOfDate) ? $asOfDate : date('Y-m-d');

        $rentFilter = '';
        if ($onlyRentIds !== null) {
            $ids = array_filter(array_map('intval', $onlyRentIds));
            if (empty($ids)) {
                return [];
            }
            $rentFilter = ' AND er.id IN (' . implode(',', $ids) . ')';
        }

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
                    $dueExpr AS due_date,
                    (eri.quantity - COALESCE((SELECT SUM(return_qty) FROM equipment_rent_returns err WHERE err.rent_item_id = eri.id), 0)) AS pending_qty,
                    DATEDIFF('$asOfDateSafe', $dueExpr) AS overdue_days,
                    (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) AS per_unit_daily
                  FROM equipment_rent_items eri
                  INNER JOIN equipment_rent er ON eri.rent_id = er.id
                  LEFT JOIN customer_master cm ON er.customer_id = cm.id
                  LEFT JOIN equipment e ON eri.equipment_id = e.id
                  LEFT JOIN sub_equipment se ON eri.sub_equipment_id = se.id
                  WHERE COALESCE(er.is_cancelled, 0) = 0 $rentFilter
                  HAVING pending_qty > 0 AND overdue_days > 0";

        $result = $db->readQuery($query);
        if (!$result) {
            throw new Exception('Error executing query: ' . mysqli_error($db->DB_CON));
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
                    'recorded_outstanding' => 0,
                    'items' => [],
                    'payments' => ['deposits' => [], 'returns' => [], 'settlements' => []],
                    'paid_total' => 0,
                    'rep_collections' => [],
                    'last_visit' => null,
                    'followup_required' => 1,
                    'rep_id' => null,
                    'rep_name' => '',
                    'assigned_at' => null,
                    'note' => ''
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
            return [];
        }

        $rentIdList = implode(',', array_keys($bills));

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

        // Collections entered by reps (kept apart from the accounts)
        $colResult = $db->readQuery("SELECT rc.*, me.full_name AS rep_name FROM rep_collections rc
                                     LEFT JOIN marketing_executive me ON rc.rep_id = me.id
                                     WHERE rc.rent_id IN ($rentIdList) ORDER BY rc.collected_at ASC");
        if ($colResult) {
            while ($r = mysqli_fetch_assoc($colResult)) {
                $bills[(int)$r['rent_id']]['rep_collections'][] = [
                    'date' => substr($r['collected_at'], 0, 10),
                    'amount' => floatval($r['amount']),
                    'status' => $r['status'],
                    'status_label' => self::COLLECTION_STATUSES[$r['status']] ?? $r['status'],
                    'remark' => trim(($r['rep_name'] ?? '') . ' - ' . (self::PAYMENT_METHODS[$r['payment_method']] ?? $r['payment_method']) . ($r['reference'] ? ' (' . $r['reference'] . ')' : ''))
                ];
            }
        }

        // Latest rep visit per bill
        $visitResult = $db->readQuery("SELECT v.rent_id, v.visit_at, v.outcome, v.promised_date, v.remark, me.full_name AS rep_name
                                       FROM rep_visits v
                                       INNER JOIN (SELECT rent_id, MAX(id) AS max_id FROM rep_visits WHERE rent_id IN ($rentIdList) GROUP BY rent_id) lv ON lv.max_id = v.id
                                       LEFT JOIN marketing_executive me ON v.rep_id = me.id");
        if ($visitResult) {
            while ($r = mysqli_fetch_assoc($visitResult)) {
                $bills[(int)$r['rent_id']]['last_visit'] = [
                    'visit_at' => $r['visit_at'],
                    'outcome' => self::VISIT_OUTCOMES[$r['outcome']] ?? $r['outcome'],
                    'promised_date' => $r['promised_date'],
                    'remark' => $r['remark'] ?? '',
                    'rep_name' => $r['rep_name'] ?? ''
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
            $bill['late_text'] = self::lateText($bill['due_date'], $asOfDateSafe);
            $bill['projected_outstanding'] = round($bill['projected_outstanding'], 2);
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

        return $data;
    }

    // Rent IDs currently assigned to a rep for follow-up
    public static function getAssignedRentIds($repId)
    {
        $db = Database::getInstance();
        $result = $db->readQuery("SELECT rent_id FROM rent_overdue_followup WHERE rep_id = " . (int)$repId . " AND followup_required = 1");
        $ids = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = (int)$row['rent_id'];
        }
        return $ids;
    }

    /**
     * Visits and collections between two dates (inclusive), optionally for one rep.
     * Totals: collected (all except rejected), confirmed (proof added / verified), no-proof count + amount.
     */
    public static function getActivity($fromDate, $toDate, $repId = null, $rentId = null)
    {
        self::ensureSchema();
        $db = Database::getInstance();
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fromDate) ? $fromDate : date('Y-m-d');
        $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$toDate) ? $toDate : $from;

        $visitWhere = "v.visit_at BETWEEN '$from 00:00:00' AND '$to 23:59:59'";
        $colWhere = "rc.collected_at BETWEEN '$from 00:00:00' AND '$to 23:59:59'";
        if ($repId) {
            $visitWhere .= " AND v.rep_id = " . (int)$repId;
            $colWhere .= " AND rc.rep_id = " . (int)$repId;
        }
        if ($rentId) {
            $visitWhere .= " AND v.rent_id = " . (int)$rentId;
            $colWhere .= " AND rc.rent_id = " . (int)$rentId;
        }

        $visits = [];
        $result = $db->readQuery("SELECT v.*, er.bill_number, cm.code AS customer_code, cm.name AS customer_name, me.full_name AS rep_name
                                  FROM rep_visits v
                                  LEFT JOIN equipment_rent er ON v.rent_id = er.id
                                  LEFT JOIN customer_master cm ON er.customer_id = cm.id
                                  LEFT JOIN marketing_executive me ON v.rep_id = me.id
                                  WHERE $visitWhere ORDER BY v.visit_at DESC, v.id DESC");
        while ($row = mysqli_fetch_assoc($result)) {
            $row['outcome_label'] = self::VISIT_OUTCOMES[$row['outcome']] ?? $row['outcome'];
            $visits[] = $row;
        }

        $collections = [];
        $totals = ['collected' => 0, 'confirmed' => 0, 'no_proof_count' => 0, 'no_proof_amount' => 0, 'verified' => 0, 'rejected' => 0];
        $result = $db->readQuery("SELECT rc.*, er.bill_number, cm.code AS customer_code, cm.name AS customer_name, me.full_name AS rep_name
                                  FROM rep_collections rc
                                  LEFT JOIN equipment_rent er ON rc.rent_id = er.id
                                  LEFT JOIN customer_master cm ON er.customer_id = cm.id
                                  LEFT JOIN marketing_executive me ON rc.rep_id = me.id
                                  WHERE $colWhere ORDER BY rc.collected_at DESC, rc.id DESC");
        while ($row = mysqli_fetch_assoc($result)) {
            $amount = floatval($row['amount']);
            $row['status_label'] = self::COLLECTION_STATUSES[$row['status']] ?? $row['status'];
            $row['method_label'] = self::PAYMENT_METHODS[$row['payment_method']] ?? $row['payment_method'];
            if ($row['status'] === 'rejected') {
                $totals['rejected'] += $amount;
            } else {
                $totals['collected'] += $amount;
            }
            if ($row['status'] === 'no_proof') {
                $totals['no_proof_count']++;
                $totals['no_proof_amount'] += $amount;
            } elseif ($row['status'] === 'proof_added' || $row['status'] === 'verified') {
                $totals['confirmed'] += $amount;
            }
            if ($row['status'] === 'verified') {
                $totals['verified'] += $amount;
            }
            $collections[] = $row;
        }

        return ['visits' => $visits, 'collections' => $collections, 'totals' => $totals];
    }

    public static function isAssigned($repId, $rentId)
    {
        $db = Database::getInstance();
        $result = $db->readQuery("SELECT id FROM rent_overdue_followup WHERE rep_id = " . (int)$repId . " AND rent_id = " . (int)$rentId . " AND followup_required = 1");
        return $result && mysqli_num_rows($result) > 0;
    }

    /**
     * Saves an uploaded proof image under uploads/rep-collections/YYYY/MM.
     * Returns the path relative to the project root, or throws on invalid files.
     */
    public static function saveProofImage($file)
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Proof upload failed (error ' . $file['error'] . ')');
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('Proof image must be under 10MB');
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new Exception('Proof must be a photo (JPG, PNG or WEBP)');
        }

        $relDir = 'uploads/rep-collections/' . date('Y/m');
        $absDir = dirname(__DIR__) . '/' . $relDir;
        if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) {
            throw new Exception('Cannot create upload folder');
        }

        $name = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $absDir . '/' . $name)) {
            throw new Exception('Cannot save proof image');
        }
        return $relDir . '/' . $name;
    }
}
