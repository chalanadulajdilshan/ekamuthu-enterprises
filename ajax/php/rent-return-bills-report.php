<?php

include '../../class/include.php';

header('Content-Type: application/json; charset=UTF8');

// Get rent and return bills report
if (isset($_POST['action']) && $_POST['action'] == 'get_rent_return_bills_report') {
    $from_date = $_POST['from_date'] ?? null;
    $to_date = $_POST['to_date'] ?? null;
    $bill_type = $_POST['bill_type'] ?? 'all';
    $rent_type = $_POST['rent_type'] ?? 'all';
    $payment_type = $_POST['payment_type'] ?? 'all';
    $bill_no = trim($_POST['bill_no'] ?? '');
    $search_items_only = isset($_POST['search_items_only']) && filter_var($_POST['search_items_only'], FILTER_VALIDATE_BOOLEAN);

    if ((!$from_date || !$to_date) && $bill_no === '') {
        echo json_encode(['status' => 'error', 'message' => 'Please select a date range or enter a bill number']);
        exit();
    }

    $db = Database::getInstance();
    $bill_no_filter = $bill_no !== '' ? $db->escapeString($bill_no) : '';
    $payment_type_filter = $payment_type !== 'all' ? $db->escapeString($payment_type) : '';
    $bills = [];

    // Fetch payment type list for dropdown population
    $paymentTypesResult = $db->readQuery("SELECT name FROM payment_type WHERE is_active = 1 ORDER BY name");
    $paymentTypes = [];
    while ($row = mysqli_fetch_assoc($paymentTypesResult)) {
        $paymentTypes[] = $row['name'];
    }

    // --- 1. Process Rent Bills ---
    if ($bill_type == 'all' || $bill_type == 'rent') {
        $rentConditions = [];
        if ($from_date && $to_date) {
            $rentConditions[] = "er.rental_date BETWEEN '$from_date' AND '$to_date'";
        }
        if ($bill_no_filter !== '') {
            if ($search_items_only) {
                $rentConditions[] = "(e.item_name LIKE '%$bill_no_filter%' OR se.code LIKE '%$bill_no_filter%')";
            } else {
                $rentConditions[] = "CAST(er.bill_number AS CHAR) LIKE '%$bill_no_filter%'";
            }
        }
        if ($rent_type !== 'all') {
            $rentConditions[] = "eri.rent_type = '$rent_type'";
        }
        if ($payment_type_filter !== '') {
            $rentConditions[] = "pt.name = '$payment_type_filter'";
        }
        if (empty($rentConditions)) {
            $rentConditions[] = '1=1';
        }
        $rentWhere = implode(' AND ', $rentConditions);

        $queryRent = "
            SELECT
                er.id as rent_id,
                CAST(er.bill_number AS CHAR) as bill_no,
                er.rental_date as date,
                er.rental_date as rent_date,
                er.received_date as return_date,
                er.deposit_total as deposit,
                er.transport_cost,
                CAST(er.remark AS CHAR) as remarks,
                CAST(CONCAT(cm.code, ' - ', cm.name) AS CHAR) as customer_name,
                CAST(cm.mobile_number AS CHAR) as customer_tel,
                CAST(cm.address AS CHAR) as customer_address,
                CAST(cm.nic AS CHAR) as customer_nic,
                pt.name as payment_type,
                e.item_name,
                se.code as sub_item_code,
                eri.amount as item_amount,
                eri.total_rent_amount as total_rent_amount,
                eri.duration as day_count,
                eri.quantity as quantity,
                eri.rent_type,
                er.status as rent_status
            FROM `equipment_rent` er
            LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
            LEFT JOIN `equipment_rent_items` eri ON er.id = eri.rent_id
            LEFT JOIN `equipment` e ON eri.equipment_id = e.id
            LEFT JOIN `sub_equipment` se ON eri.sub_equipment_id = se.id
            LEFT JOIN `payment_type` pt ON er.payment_type_id = pt.id
            WHERE $rentWhere
            ORDER BY er.id DESC
        ";

        $resultRent = $db->readQuery($queryRent);
        while ($row = mysqli_fetch_array($resultRent)) {
            $billNo = $row['bill_no'];
            
            // Initialize Bill Header if not exists
            if (!isset($bills['rent_' . $billNo])) {
                $bills['rent_' . $billNo] = [
                    'bill_type' => 'Rent',
                    'bill_no' => $billNo,
                    'date' => $row['date'],
                    'rent_status' => $row['rent_status'],
                    'customer_name' => $row['customer_name'],
                    'customer_tel' => $row['customer_tel'],
                    'customer_address' => $row['customer_address'],
                    'customer_nic' => $row['customer_nic'],
                    'rent_date' => $row['rent_date'],
                    'return_date' => $row['return_date'],
                    'deposit' => $row['deposit'],
                    'transport_cost' => $row['transport_cost'],
                    'remarks' => $row['remarks'],
                    'payment_type' => $row['payment_type'] ?? '- ',
                    'items' => [],
                    'total_qty' => 0,
                    'total_amount' => 0
                ];
            }

            // Daily Rent column should show eri.amount directly as per user request
            // This represents the line rate for the total quantity
            $dailyRent = floatval($row['item_amount']);
            $qty = floatval($row['quantity']);
            $days = floatval($row['day_count']);
            
            // For Issue (Rent) bills, use total_rent_amount from rent item table
            $itemProfit = floatval($row['total_rent_amount']);

            // Add Item
            $bills['rent_' . $billNo]['items'][] = [
                'name' => $row['item_name'] . ($row['sub_item_code'] ? ' (' . $row['sub_item_code'] . ')' : ''),
                'daily_rent' => $dailyRent,
                'day_count' => $days,
                'quantity' => $qty,
                'amount' => $itemProfit
            ];

            // Accumulate Bill Totals
            $bills['rent_' . $billNo]['total_qty'] += $qty;
            $bills['rent_' . $billNo]['total_amount'] += $itemProfit;
        }
    }

    // --- 2. Process Return Bills (Keep as individual rows or group?) ---
    // User asked for expand feature, likely for Rent. Returns are usually single line.
    // We will structure them similarly for consistency.
    if ($bill_type == 'all' || $bill_type == 'return') {
        $returnConditions = [];
        if ($from_date && $to_date) {
            $returnConditions[] = "err.return_date BETWEEN '$from_date' AND '$to_date'";
        }
        if ($bill_no_filter !== '') {
            if ($search_items_only) {
                $returnConditions[] = "(e.item_name LIKE '%$bill_no_filter%' OR se.code LIKE '%$bill_no_filter%')";
            } else {
                $returnConditions[] = "CAST(er.bill_number AS CHAR) LIKE '%$bill_no_filter%'";
            }
        }
        if ($rent_type !== 'all') {
            $returnConditions[] = "eri.rent_type = '$rent_type'";
        }
        if ($payment_type_filter !== '') {
            $returnConditions[] = "pt.name = '$payment_type_filter'";
        }
        if (empty($returnConditions)) {
            $returnConditions[] = '1=1';
        }
        $returnWhere = implode(' AND ', $returnConditions);

        $queryReturn = "
            SELECT
                er.bill_number as bill_no,
                err.return_date as date,
                er.rental_date as rent_date,
                err.return_date as return_date,
                err.return_qty as quantity,
                err.extra_day_amount,
                err.penalty_amount,
                err.damage_amount,
                err.after_9am_extra_day,
                er.deposit_total as deposit,
                CAST(err.remark AS CHAR) as remarks,
                CAST(CONCAT(cm.code, ' - ', cm.name) AS CHAR) as customer_name,
                CAST(cm.mobile_number AS CHAR) as customer_tel,
                CAST(cm.address AS CHAR) as customer_address,
                CAST(cm.nic AS CHAR) as customer_nic,
                pt.name as payment_type,
                e.item_name,
                se.code as sub_item_code,
                eri.amount as base_item_amount,
                eri.total_rent_amount as total_rent_amount,
                eri.quantity as base_item_qty,
                eri.rent_type,
                er_status.rent_status,
                COALESCE(e.is_fixed_rate, 0) as is_fixed_rate,
                -- Dynamic used days calculation matching rent-invoice.php logic
                GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400)) as calculated_days
            FROM `equipment_rent_returns` err
            INNER JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
            INNER JOIN `equipment_rent` er ON eri.rent_id = er.id
            LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
            LEFT JOIN `equipment` e ON eri.equipment_id = e.id
            LEFT JOIN `sub_equipment` se ON eri.sub_equipment_id = se.id
            LEFT JOIN `payment_type` pt ON er.payment_type_id = pt.id
            INNER JOIN (
                SELECT status as rent_status, id FROM `equipment_rent`
            ) er_status ON er.id = er_status.id
            WHERE $returnWhere
        ";

        $resultReturn = $db->readQuery($queryReturn);
        while ($row = mysqli_fetch_array($resultReturn)) {
            // Group returns by bill number and date
            $billNo = $row['bill_no'];
            $date = $row['date'];
            $key = 'return_' . $billNo . '_' . $date;
            
            // Daily Rent column should show eri.amount directly (Line Rate)
            $lineRate = floatval($row['base_item_amount']);
            $dailyRentDisplay = $lineRate;
            
            $returnQty = floatval($row['quantity']);
            $baseQty = floatval($row['base_item_qty']);
            $dayCount = floatval($row['calculated_days']);
            
            // Use total_rent_amount from rent item table
            $totalItemProfit = floatval($row['total_rent_amount']);

            // Initialize Grouped Return Bill if not exists
            if (!isset($bills[$key])) {
                $bills[$key] = [
                    'bill_type' => 'Return',
                    'bill_no' => $billNo,
                    'date' => $date,
                    'rent_status' => $row['rent_status'] ?? 'rented',
                    'customer_name' => $row['customer_name'],
                    'customer_tel' => $row['customer_tel'],
                    'customer_address' => $row['customer_address'],
                    'customer_nic' => $row['customer_nic'],
                    'rent_date' => $row['rent_date'],
                    'return_date' => $row['return_date'],
                    'deposit' => $row['deposit'],
                    'transport_cost' => 0,
                    'remarks' => $row['remarks'],
                    'after_9am' => intval($row['after_9am_extra_day']),
                    'payment_type' => $row['payment_type'] ?? '- ',
                    'items' => []
                ];
            }

            // Update after_9am if any item in the group is after 9am
            if (intval($row['after_9am_extra_day']) === 1) {
                $bills[$key]['after_9am'] = 1;
            }

            // Append Item
            $bills[$key]['items'][] = [
                'name' => $row['item_name'] . ($row['sub_item_code'] ? ' (' . $row['sub_item_code'] . ')' : ''),
                'daily_rent' => $dailyRentDisplay,
                'day_count' => $dayCount,
                'quantity' => $returnQty,
                'extra_day_amount' => floatval($row['extra_day_amount']),
                'penalty_amount' => floatval($row['penalty_amount']),
                'amount' => $totalItemProfit,
                'after_9am' => intval($row['after_9am_extra_day'])
            ];
        }
    }

    // Sort bills by date DESC
    usort($bills, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    // Final Data Formatting and Summary Calculation
    $finalData = [];
    $summary = [
        'total_quantity' => 0,
        'total_amount' => 0,
        'total_extra_amount' => 0,
        'total_profit' => 0,
        'total_deposit' => 0,
        'total_bills' => 0,
        'total_rent_bills' => 0,
        'total_return_bills' => 0,
        'date_range' => ($bill_no !== '' ? ($from_date && $to_date ? "$from_date to $to_date (" . ($search_items_only ? "Item" : "Bill No") . ": $bill_no)" : ($search_items_only ? "Item" : "Bill No") . ": $bill_no") : "$from_date to $to_date"),
        'search_bill_no' => $bill_no,
        'version' => '1.2',
        'payment_types' => $paymentTypes
    ];

    foreach ($bills as $bill) {
        $billTotalAmount = 0;
        $billTotalQty = 0;
        $billTotalExtraAmount = 0;
        
        foreach ($bill['items'] as $item) {
            $billTotalAmount += $item['amount'];
            $billTotalQty += $item['quantity'];
            if ($bill['bill_type'] == 'Return') {
                $billTotalExtraAmount += ($item['extra_day_amount'] ?? 0) + ($item['penalty_amount'] ?? 0);
            }
        }

        // Add Transport Cost to Amount only for Rent
        if ($bill['bill_type'] == 'Rent') {
            $billTotalAmount += floatval($bill['transport_cost']);
        }
        
        // Prepare Item Summaries (comma separated)
        $displayItems = [];
        $displayDailyRents = [];
        $displayDayCounts = [];
        $displayExtraAmounts = [];
        foreach ($bill['items'] as $item) {
            $displayItems[] = $item['name'];
            $displayDailyRents[] = $item['daily_rent'];
            $displayDayCounts[] = $item['day_count'];
            $displayExtraAmounts[] = ($item['extra_day_amount'] ?? 0) + ($item['penalty_amount'] ?? 0);
        }

        $finalData[] = [
            'bill_type' => $bill['bill_type'],
            'bill_no' => $bill['bill_no'],
            'date' => $bill['date'],
            'customer_name' => $bill['customer_name'],
            'customer_tel' => $bill['customer_tel'],
            'customer_address' => $bill['customer_address'],
            'customer_nic' => $bill['customer_nic'],
            'after_9am' => $bill['after_9am'] ?? 0,
            'equipment_item' => implode(', ', array_unique($displayItems)),
            'daily_rent' => implode(', ', array_unique($displayDailyRents)),
            'day_count' => implode(', ', array_unique($displayDayCounts)),
            'rent_date' => $bill['rent_date'],
            'return_date' => $bill['return_date'],
            'quantity' => $billTotalQty,
            'deposit' => $bill['deposit'],
            'profit_balance' => ($bill['rent_status'] === 'returned' && $bill['bill_type'] === 'Return') ? number_format($bill['deposit'] - ($billTotalAmount + $billTotalExtraAmount), 2) : '-',
            'payment_type' => $bill['payment_type'] ?? '- ',
            // Send raw numbers to avoid comma-induced truncation in JS
            'amount' => round($billTotalAmount, 2),
            'extra_amount_list' => implode(', ', array_map(function($v) { return number_format($v, 2); }, $displayExtraAmounts)),
            'extra_amount' => round($billTotalExtraAmount, 2),
            'remarks' => $bill['remarks'],
            'items' => $bill['items']
        ];

        // Update Summary
        $summary['total_bills']++;
        if ($bill['bill_type'] == 'Rent') $summary['total_rent_bills']++;
        if ($bill['bill_type'] == 'Return') $summary['total_return_bills']++;
        $summary['total_quantity'] += $billTotalQty;
        $summary['total_amount'] += $billTotalAmount;
        $summary['total_extra_amount'] += $billTotalExtraAmount;
        $summary['total_deposit'] += floatval($bill['deposit']);
        if ($bill['rent_status'] === 'returned' && $bill['bill_type'] === 'Return') {
            $summary['total_profit'] += ($bill['deposit'] - ($billTotalAmount + $billTotalExtraAmount));
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $finalData,
        'summary' => [
            'total_quantity' => $summary['total_quantity'],
            'total_amount' => number_format($summary['total_amount'], 2),
            'total_extra_amount' => number_format($summary['total_extra_amount'], 2),
            'total_profit' => number_format($summary['total_profit'], 2),
            'total_deposit' => number_format($summary['total_deposit'], 2),
            'total_bills' => $summary['total_bills'],
            'total_rent_bills' => $summary['total_rent_bills'],
            'total_return_bills' => $summary['total_return_bills'],
            'date_range' => $summary['date_range'],
            'version' => $summary['version'],
            'payment_types' => $summary['payment_types']
        ]
    ]);
    exit();
}

?>
