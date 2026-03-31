<!doctype html>
<?php
include 'class/include.php';

if (!isset($_SESSION)) {
    session_start();
}

$bill_param = $_GET['bill_no'] ?? '';
$US = new User($_SESSION['id']);
$COMPANY_PROFILE = new CompanyProfile($US->company_id);

// Always try to find by bill number first, as bill numbers are now numeric
$EQUIPMENT_RENT = new EquipmentRent(null);
if ($EQUIPMENT_RENT->getByBillNumber($bill_param)) {
    $rent_id = $EQUIPMENT_RENT->id;
} elseif (is_numeric($bill_param)) {
    // If not found by bill number, try as an ID
    $EQUIPMENT_RENT = new EquipmentRent($bill_param);
    $rent_id = $bill_param;
} else {
    die('Rent record not found: ' . htmlspecialchars($bill_param));
}

// Verify rent exists
if (!$EQUIPMENT_RENT->id) {
    die('Rent record not found');
}

$CUSTOMER_MASTER = new CustomerMaster($EQUIPMENT_RENT->customer_id);

// Fetch all active invoice remarks (always show, no payment-type filtering)
$INVOICE_REMARK = new InvoiceRemark();
$paymentRemarks = $INVOICE_REMARK->getActiveGroups();

// Get rent items
$rent_items = $EQUIPMENT_RENT->getItems();

// DB instance for queries
$db = Database::getInstance();

// Issued quantities per equipment/sub-equipment for this rent
$issuedMap = [];
$issuedQuery = "SELECT ini.equipment_id, ini.sub_equipment_id, SUM(ini.issued_quantity) AS total_issued
                FROM issue_note_items ini
                INNER JOIN issue_notes n ON ini.issue_note_id = n.id
                WHERE n.rent_invoice_id = " . (int)$rent_id . "
                AND n.issue_status != 'cancelled'
                GROUP BY ini.equipment_id, ini.sub_equipment_id";
$issuedResult = $db->readQuery($issuedQuery);
while ($row = mysqli_fetch_assoc($issuedResult)) {
    $key = $row['equipment_id'] . '_' . ($row['sub_equipment_id'] ?? 'NULL');
    $issuedMap[$key] = (float)($row['total_issued'] ?? 0);
}

// Determine rent label based on rent types (daily vs monthly vs mixed)
$rent_types = array_unique(array_map(function ($ri) {
    return $ri['rent_type'] ?? '';
}, $rent_items));
$has_month = in_array('month', $rent_types, true);
// Treat anything non-month as daily for label purposes
$has_daily = count($rent_types) === 0 ? false : ($has_month ? count($rent_types) > 1 : true);

if ($has_month && !$has_daily) {
    $rent_label = 'Monthly Rental:';
} elseif (!$has_month && $has_daily) {
    $rent_label = 'Rental Amount:';
} else {
    $rent_label = 'Rental Amount:';
}

// Collect return rows across items for print
$return_rows = [];
foreach ($rent_items as $ritem) {
    if (empty($ritem['id'])) continue;
    $itemReturns = EquipmentRentReturn::getByRentItemId($ritem['id']);
    
    // Get stored damage amount for this item
    $storedDamage = floatval($ritem['damage_amount'] ?? 0);
    
    foreach ($itemReturns as $ret) {
        // Combine return damage with stored item damage
        $returnDamage = floatval($ret['damage_amount'] ?? 0);
        $totalDamage = $returnDamage + $storedDamage;
        
        $return_rows[] = array_merge($ret, [
            'equipment_name' => $ritem['equipment_name'] ?? '-',
            'equipment_code' => $ritem['equipment_code'] ?? '-',
            'sub_equipment_code' => $ritem['sub_equipment_code'] ?? '-',
            'stored_damage_amount' => $storedDamage,
            'total_damage_display' => $totalDamage,
        ]);
    }
}

// Calculate totals
$total_amount = 0;
foreach ($rent_items as $item) {
    $total_amount += floatval($item['amount']);
}

// Transport amount from record
$transport_amount = floatval($EQUIPMENT_RENT->transport_cost);
$total_deposit = floatval($EQUIPMENT_RENT->deposit_total);

// Calculate refund balance = deposit - total charges (rental + extra day + damage + penalty)
$db = Database::getInstance();
$chargesQuery = "SELECT COALESCE(SUM(
                        CASE WHEN err.rental_override IS NOT NULL
                            THEN err.rental_override
                            ELSE CASE WHEN COALESCE(e.is_fixed_rate, 0) = 1
                                THEN ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) * err.return_qty)
                                ELSE (GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                                    * (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0))
                                    * err.return_qty)
                            END
                        END
                        + COALESCE(err.extra_day_amount, 0)
                        + COALESCE(err.damage_amount, 0)
                        + COALESCE(err.penalty_amount, 0)
                        + COALESCE(err.extra_charge_amount, 0)
                        - COALESCE(err.repair_cost, 0)
                    ), 0) as total_charges
                    FROM equipment_rent_returns err
                    INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                    LEFT JOIN equipment e ON eri.equipment_id = e.id
                    WHERE eri.rent_id = " . (int) $rent_id;
$chargesResult = $db->readQuery($chargesQuery);
$chargesData = mysqli_fetch_assoc($chargesResult);
$total_charges = floatval($chargesData['total_charges'] ?? 0);
$refund_balance = $total_deposit - $total_charges;

// Only show refund balance when there are actual returns; no returns = no refund paid to customer
if (empty($return_rows)) {
    $refund_balance = 0;
}

// Calculate total customer paid across all returns
$total_customer_paid = 0;
$total_extra_charges = 0;
$total_repair_cost = 0;
foreach ($return_rows as $rr) {
    $total_customer_paid += floatval($rr['customer_paid'] ?? 0);
    $total_extra_charges += floatval($rr['extra_charge_amount'] ?? 0);
    $total_repair_cost += floatval($rr['repair_cost'] ?? 0);
}
// Include deposit in total customer paid (deposit is money the customer paid upfront)
$total_customer_paid += $total_deposit;
$isReturnInvoice = !empty($return_rows);

// Calculate net amount and outstanding
$hire_amount = $total_amount;
$net_amount = $total_amount + $total_deposit + $transport_amount + $total_extra_charges;
$total_outstanding = $net_amount; // For now, assuming full amount is outstanding

// Get customer WhatsApp number (mobile_number_2) for WhatsApp sharing
$customerMobile = !empty($CUSTOMER_MASTER->mobile_number_2) ? $CUSTOMER_MASTER->mobile_number_2 : '';
// Fallback to primary mobile if WP No is not available
if (empty($customerMobile)) {
    $customerMobile = !empty($CUSTOMER_MASTER->mobile_number) ? $CUSTOMER_MASTER->mobile_number : '';
}
if (!empty($customerMobile)) {
    $customerMobile = preg_replace('/\D/', '', $customerMobile);
    if (strlen($customerMobile) == 10) {
        $customerMobile = '94' . substr($customerMobile, 1);
    }
}
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Rent Invoice - <?php echo htmlspecialchars($EQUIPMENT_RENT->bill_number); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'main-css.php' ?>
    <link href="https://unicons.iconscout.com/release/v4.0.8/css/line.css" rel="stylesheet">

    <style>
        /* Rent invoice header banner */
        .rent-header-container {
            max-width: 980px;
            margin: 0 auto;
        }

        .rent-header-img {
            display: block;
            width: auto;
            max-width: 100%;
            height: 140px;
            max-height: 140px;
            object-fit: contain;
            margin: 0 auto 10px auto;
        }

        @media print {
            .rent-header-container {
                max-width: 980px !important;
            }

            .rent-header-img {
                margin-bottom: 6px !important;
                height: 140px !important;
                max-height: 140px !important;
                max-width: 100% !important;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body, html {
                width: 100%;
                margin: 0;
                padding: 0;
                font-size: 13px !important;
            }

            #invoice-content, .card {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none;
                border: none !important;
            }

            .card-body {
                padding: 5px 10px !important;
            }

            .container {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            @page {
                size: A4;
                margin: 5mm;
            }

            /* Compact header section */
            .invoice-title h3 {
                font-size: 18px !important;
                margin-bottom: 0 !important;
                padding-bottom: 1px !important;
            }

            .invoice-title .row.mb-3 {
                margin-bottom: 4px !important;
            }

            .invoice-title .row.mb-4 {
                margin-bottom: 6px !important;
            }

            .invoice-title p {
                margin-bottom: 1px !important;
                font-size: 13px !important;
                line-height: 1.4 !important;
                display: block !important;
            }

            .invoice-title div[style*="line-height"] {
                line-height: 1.4 !important;
            }

            .invoice-title div[style*="font-size"] {
                font-size: 13px !important;
            }

            .invoice-title .text-muted p {
                font-size: 12px !important;
            }

            .invoice-meta {
                margin-top: 4px !important;
                padding: 2px 8px !important;
            }

            .invoice-meta p {
                font-size: 12px !important;
            }

            /* Compact tables */
            #invoice-content table th,
            #invoice-content table td {
                padding: 2px 4px !important;
                font-size: 12px !important;
            }

            /* Borders only for equipment and returns tables */
            #invoice-content table.table-bordered,
            #invoice-content table.print-bordered {
                border: 1px solid #000 !important;
                border-color: #000 !important;
                border-collapse: collapse !important;
                border-spacing: 0 !important;
                border-style: solid !important;
                box-shadow: inset 0 0 0 1px #000 !important;
            }
            #invoice-content table.table-bordered th,
            #invoice-content table.table-bordered td,
            #invoice-content table.print-bordered th,
            #invoice-content table.print-bordered td {
                border: 1px solid #000 !important;
                border-color: #000 !important;
                border-collapse: collapse !important;
                border-spacing: 0 !important;
                border-style: solid !important;
                box-shadow: inset 0 0 0 1px #000 !important;
            }

            /* Ensure borders stay in print */
            @media print {
                #invoice-content { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                #invoice-content .table-responsive { overflow: visible !important; }
                #invoice-content table.table-bordered,
                #invoice-content table.table-bordered th,
                #invoice-content table.table-bordered td,
                #invoice-content table.print-bordered,
                #invoice-content table.print-bordered th,
                #invoice-content table.print-bordered td {
                    border: 1px solid #000 !important;
                    border-color: #000 !important;
                    border-collapse: collapse !important;
                    border-spacing: 0 !important;
                    border-style: solid !important;
                    box-shadow: inset 0 0 0 1px #000 !important;
                }
            }

            /* Remove borders from summary/signature tables */
            #invoice-content .summary-table,
            #invoice-content .summary-table td,
            #invoice-content .signature-table,
            #invoice-content .signature-table td {
                border: none !important;
                border-collapse: separate !important;
            }

            #invoice-content thead th {
                font-size: 12px !important;
            }

            #invoice-content tbody {
                font-size: 12px !important;
            }

            /* Compact Returns heading */
            h5.mt-3 {
                font-size: 12px !important;
                margin-top: 4px !important;
                margin-bottom: 2px !important;
            }

            .table-responsive.mt-3 {
                margin-top: 4px !important;
            }

            /* Compact summary section */
            .row.mt-3 {
                margin-top: 4px !important;
            }

            .summary-table td {
                padding: 1px 6px !important;
                font-size: 12px !important;
            }

            /* Compact terms section */
            .mt-4 {
                margin-top: 6px !important;
            }

            .mt-4 ul {
                margin-bottom: 0 !important;
                padding-left: 14px !important;
            }

            .mt-4 ul li {
                font-size: 11px !important;
                line-height: 1.3 !important;
                margin-bottom: 0 !important;
            }

            .mt-4 div[style*="min-height"] {
                min-height: auto !important;
                padding: 4px 0 !important;
            }

            /* Compact signature section */
            .row.mt-5 {
                margin-top: 10px !important;
            }

            .row.mt-5 td {
                padding-top: 15px !important;
                font-size: 12px !important;
            }

            /* Compact badges */
            .badge {
                font-size: 10px !important;
                padding: 1px 4px !important;
            }

            /* Force Bootstrap grid to work in print mode */
            .row {
                display: flex !important;
                flex-wrap: wrap !important;
            }

            .col-md-6 {
                flex: 0 0 50% !important;
                max-width: 50% !important;
                width: 50% !important;
            }

            .col-md-6 > div {
                font-size: 11px !important;
                line-height: 1.4 !important;
            }

            /* Force company info to top-right */
            .col-md-6.text-md-end,
            .col-md-6.text-sm-start.text-md-end {
                text-align: right !important;
            }

            .col-md-6.text-md-end .text-muted,
            .col-md-6.text-md-end .invoice-meta {
                text-align: right !important;
            }

            .col-md-6.text-md-end p {
                text-align: right !important;
            }

            /* Force all elements inside invoice header to be block */
            .invoice-title .col-md-6 p,
            .invoice-title .col-md-6 .mb-1 {
                display: block !important;
                width: 100% !important;
                margin-bottom: 1px !important;
                font-size: 11px !important;
                line-height: 1.4 !important;
            }

            .col-12 {
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }
        }

        #invoice-content table,
        #invoice-content th,
        #invoice-content td {
            padding: 3px 6px !important;
            margin: 0 !important;
            border-spacing: 0 !important;
            border-collapse: collapse !important;
        }

        #invoice-content th,
        #invoice-content td {
            vertical-align: middle !important;
        }

        #invoice-content .table {
            width: 100%;
        }

        /* Stronger border rules for bordered tables */
        #invoice-content .table-bordered {
            border: 1px solid #000 !important;
            border-collapse: collapse !important;
        }

        #invoice-content .table-bordered > thead > tr > th,
        #invoice-content .table-bordered > tbody > tr > th,
        #invoice-content .table-bordered > tfoot > tr > th,
        #invoice-content .table-bordered > thead > tr > td,
        #invoice-content .table-bordered > tbody > tr > td,
        #invoice-content .table-bordered > tfoot > tr > td {
            border: 1px solid #000 !important;
        }

        .invoice-meta {
            margin-top: 10px !important;
            padding: 6px 12px;
            min-width: 230px;
            display: inline-block;
            border: none;
            background: transparent;
        }

        .invoice-meta p {
            font-size: 20px !important;
        }

        /* Make Bill No clearly readable on printout */
        .invoice-meta .bill-number {
            font-size: 22px !important;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        /* Emphasize other meta values to same scale */
        .invoice-meta .meta-value {
            font-size: 18px !important;
            font-weight: 700;
        }

        /* Enlarge labels to match value scale */
        .invoice-meta .meta-label {
            font-size: 16px !important;
            font-weight: 700;
        }

        .invoice-meta .status-badge {
            font-size: 13px !important;
            padding: 3px 7px !important;
            font-weight: 700;
        }

        .summary-table td {
            padding: 1px 6px !important;
            border: none !important;
            line-height: 1.2;
        }

        .summary-label {
            text-align: right;
            font-weight: bold;
        }

        .summary-value {
            text-align: left;
            min-width: 80px;
        }

        .summary-wrapper {
            margin-left: auto;
            text-align: right;
            display: inline-block;
        }

        .summary-table {
            margin-left: auto;
        }

        /* Status Bar (according to sketch) */
        .status-bar {
            width: 100%;
            text-align: center;
            padding: 5px 0;
            margin: 10px 0 20px 0;
            font-size: 28px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 5px;
        }
        .status-rented {
            color: #000000ff; /* Black */
        }
        .status-returned {
            color: #198754; /* Green */
        }

        #invoice-content {
            position: relative;
            overflow: hidden;
            min-height: 800px;
        }

    </style>

</head>

<body data-layout="horizontal" data-topbar="colored">

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 no-print gap-2">
        <h4 class="mb-0"><?php echo $isReturnInvoice ? 'Equipment Return Invoice' : 'Equipment Rent Invoice'; ?></h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button onclick="window.print()" class="btn btn-success ms-2">Print / PDF</button>
            <button onclick="shareViaWhatsApp()" class="btn btn-success ms-2 no-print">
                <i class="uil uil-whatsapp"></i> WhatsApp
            </button>
        </div>
    </div>

    <div class="card" id="invoice-content">
        <div class="card-body">
            <!-- Company & Customer Info -->
            <div class="invoice-title">
                <?php
                function formatPhone($number) {
                    $number = preg_replace('/\D/', '', $number);
                    if (strlen($number) == 10) {
                        return sprintf("(%s) %s-%s", substr($number, 0, 3), substr($number, 3, 3), substr($number, 6));
                    }
                    return $number;
                }
                ?>
                
                <!-- Header Banner -->
                <div class="row mb-2">
                    <div class="col-12 text-center">
                        <img src="assets/images/rent-header.png" alt="P.S Ekamuthu Enterprises" class="rent-header-img">
                    </div>
                </div>

                <!-- Status Bar -->
                <div class="status-bar <?php echo ($EQUIPMENT_RENT->status === 'returned' ? 'status-returned' : 'status-rented'); ?>">
                    <?php echo htmlspecialchars(ucfirst($EQUIPMENT_RENT->status)); ?>
                </div>

                <!-- Info Grid -->
                <!-- Row 1: Name and Bill No -->
                <div class="row align-items-center mb-1">
                    <div class="col-7">
                        <div style="font-size:15px;">
                            <strong>Name : </strong><?php echo htmlspecialchars($CUSTOMER_MASTER->name); ?>
                        </div>
                    </div>
                    <div class="col-5 text-end">
                        <div style="font-size: 15px;">
                            <span style="font-size: 18px;"><strong>Bill No:</strong> <span style="font-size: 24px; font-weight: 800;"><?php echo htmlspecialchars($EQUIPMENT_RENT->bill_number); ?></span></span>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Address and Issued Date -->
                <div class="row align-items-start mb-1">
                    <div class="col-7">
                        <div style="font-size:15px;">
                            <strong>Address : </strong><?php echo !empty($CUSTOMER_MASTER->address) ? htmlspecialchars($CUSTOMER_MASTER->address) : '..................................................'; ?>
                        </div>
                    </div>
                    <div class="col-5 text-end">
                        <div style="font-size:15px;">
                            <strong>Issued Date: </strong><?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->rental_date)); ?>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Tel and Rented Date -->
                <div class="row align-items-start mb-1">
                    <div class="col-7">
                        <div style="font-size:15px;">
                            <strong>Tel: </strong><?php echo !empty($CUSTOMER_MASTER->mobile_number) ? formatPhone($CUSTOMER_MASTER->mobile_number) : '..................................................'; ?>
                        </div>
                    </div>
                    <div class="col-5 text-end">
                        <div style="font-size:15px;">
                            <?php if ($EQUIPMENT_RENT->rental_start_date): ?>
                                <strong>Rented Date: </strong><?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->rental_start_date)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Remaining Left-Side Info -->
                <div class="row mb-4">
                    <div class="col-7">
                        <div style="font-size:15px; line-height:2.0;">
                            <strong>WP No: </strong><?php echo !empty($CUSTOMER_MASTER->mobile_number_2) ? formatPhone($CUSTOMER_MASTER->mobile_number_2) : '..................................................'; ?><br>
                            <strong>NIC: </strong><?php echo !empty($CUSTOMER_MASTER->nic) ? htmlspecialchars($CUSTOMER_MASTER->nic) : '..................................................'; ?><br>
                            <strong>Work Site Address: </strong><?php echo !empty($EQUIPMENT_RENT->workplace_address) ? htmlspecialchars($EQUIPMENT_RENT->workplace_address) : '..................................................'; ?>
                        </div>
                    </div>
                    <div class="col-5 text-end">
                        <div style="font-size:15px; line-height:2.0;">
                            <?php if ($EQUIPMENT_RENT->received_date): ?>
                                <strong>Received Date: </strong><?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->received_date)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-centered print-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Equipment Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th class="text-end">Rate</th>
                                <th>Duration</th>
                                <th class="text-center">Bill Qty</th>
                                <th class="text-center">Issued Qty</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:13px;">
                            <?php
                            $row_num = 0;
                            foreach ($rent_items as $item):
                                $row_num++;
                            ?>
                                <tr>
                                    <td><?php echo str_pad($row_num, 2, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($item['equipment_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($item['sub_equipment_code'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($item['rent_type'] === 'month'): ?>
                                            <span class="badge bg-primary">Monthly</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Daily</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php 
                                        $qty = (float)($item['quantity'] ?? 1);
                                        if ($qty <= 0) $qty = 1;
                                        $rate = (float)($item['amount'] ?? 0) / $qty;
                                        echo number_format($rate, 2); 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo intval($item['duration']); 
                                        echo ($item['rent_type'] === 'month') ? ' Months' : ' Days';
                                        ?>
                                    </td>
                                    <?php
                                        $billQty = isset($item['bill_qty']) ? (float)$item['bill_qty'] : (float)($item['quantity'] ?? 0);
                                        $key = $item['equipment_id'] . '_' . ($item['sub_equipment_id'] ?? 'NULL');
                                        $issuedQty = isset($issuedMap[$key]) ? (float)$issuedMap[$key] : 0;
                                    ?>
                                    <td class="text-center"><?php echo intval($billQty); ?></td>
                                    <td class="text-center"><?php echo intval($issuedQty); ?></td>
                                    <td class="text-end"><?php echo number_format($item['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

                <?php if (!empty($return_rows)): ?>
                <!-- Returns Table -->
                <div class="table-responsive mt-3">
                    <h5 class="mt-3 mb-2">Returns</h5>
                    <table class="table table-bordered table-centered print-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Equipment</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Rental</th>
                                <th class="text-end">Extra Day</th>
                                <th class="text-end">Penalty</th>
                                <th class="text-end">Damage</th>
                                <th class="text-end">Extra Charge</th>
                                <th class="text-end">Settlement</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:13px;">
                            <?php foreach ($return_rows as $ret): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ret['return_date']); ?></td>
                                    <td><?php echo htmlspecialchars($ret['return_time'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($ret['equipment_name'] ?? '-'); ?></td>
                                    <td class="text-center"><?php echo intval($ret['return_qty'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo number_format(floatval($ret['rental_amount'] ?? 0), 2); ?></td>
                                    <td class="text-end"><?php echo number_format(floatval($ret['extra_day_amount'] ?? 0), 2); ?></td>
                                    <td class="text-end">
                                        <?php if (floatval($ret['penalty_amount'] ?? 0) > 0): ?>
                                            <span class="text-danger"><?php echo number_format(floatval($ret['penalty_amount']), 2); ?> (<?php echo intval($ret['penalty_percentage'] ?? 0); ?>%)</span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php 
                                        $displayDamage = floatval($ret['total_damage_display'] ?? $ret['damage_amount'] ?? 0);
                                        if ($displayDamage > 0): ?>
                                            <span class="text-danger"><?php echo number_format($displayDamage, 2); ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format(floatval($ret['extra_charge_amount'] ?? 0), 2); ?></td>
                                    <td class="text-end">
                                        <?php if (!empty($ret['additional_payment']) && floatval($ret['additional_payment']) > 0): ?>
                                            <span class="text-danger">Pay: Rs. <?php echo number_format(floatval($ret['additional_payment']), 2); ?></span>
                                        <?php elseif (!empty($ret['refund_amount']) && floatval($ret['refund_amount']) > 0): ?>
                                            <span class="text-success">Refund: Rs. <?php echo number_format(floatval($ret['refund_amount']), 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">No charge</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format(floatval($ret['customer_paid'] ?? 0), 2); ?></td>
                                    <td class="text-end">
                                        <?php if (floatval($ret['outstanding_amount'] ?? 0) > 0): ?>
                                            <span class="text-danger"><?php echo number_format(floatval($ret['outstanding_amount']), 2); ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Summary Section -->
                <div class="row mt-3">
                    <div class="col-12 d-flex justify-content-end">
                        <div class="summary-wrapper">
                            <table class="summary-table" style="width:auto;">
                            <tr>
                                <td class="summary-label"><?php echo htmlspecialchars($rent_label); ?></td>
                                <td class="summary-value"><?php echo number_format($hire_amount, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">Deposit Amount:</td>
                                <td class="summary-value"><?php echo number_format($total_deposit, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">Transport:</td>
                                <td class="summary-value"><?php echo number_format($transport_amount, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">Total Rental Amount:</td>
                                <td class="summary-value"><?php echo number_format($hire_amount + $transport_amount + $total_extra_charges, 2); ?></td>
                            </tr>
                            <?php if ($total_repair_cost > 0): ?>
                            <tr>
                                <td class="summary-label">Repair Cost Deducted:</td>
                                <td class="summary-value">-<?php echo number_format($total_repair_cost, 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="summary-label">Total Paid by Customer:</td>
                                <td class="summary-value"><?php echo number_format($total_customer_paid, 2); ?></td>
                            </tr>
                            <?php
                            $balanceLabel = $refund_balance > 0 ? 'Company Pay:' : 'Customer Pay:';
                            $balanceAmount = number_format(abs($refund_balance), 2);
                            ?>
                            <tr>
                                <td class="summary-label"><?php echo $balanceLabel; ?></td>
                                <td class="summary-value">
                                    <div class="d-flex flex-column align-items-end">
                                        <span class="fw-semibold"><?php echo $balanceAmount; ?></span>
                                        <?php if ($refund_balance < 0): ?>
                                            <span class="badge bg-success mt-1 align-self-end">Customer Pay</span>
                                        <?php elseif ($refund_balance > 0): ?>
                                            <span class="badge bg-primary mt-1 align-self-end">Company Pay</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php if (floatval($CUSTOMER_MASTER->rent_outstanding ?? 0) > 0): ?>
                            <tr>
                                <td class="summary-label">Customer Rent Outstanding:</td>
                                <td class="summary-value">
                                    <span class="text-danger"><?php echo number_format(floatval($CUSTOMER_MASTER->rent_outstanding), 2); ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <?php
                $rateDescriptor = 'per day / hour';
                if ($has_month && !$has_daily) {
                    $rateDescriptor = 'per month';
                } elseif ($has_month && $has_daily) {
                    $rateDescriptor = 'per day / month';
                }

                $customTerms = [
                    sprintf(
                        'The customer should make the full payment for the total number of days including the date of receiving equipment and the date of returning the equipment and amount of Rs. <strong>%s</strong> %s as per rental.',
                        number_format($hire_amount, 2),
                        $rateDescriptor
                    ),
                    sprintf(
                        'The customers should obtain receipt from the company by paying Rs. <strong>%s</strong> as the primary deposit, when collecting the equipment & should return it at the time of returning the equipment.',
                        number_format($total_deposit, 2)
                    )
                ];

                $skipRemarkPhrases = [
                    'the customer should make the full payment for the total number of days including the date of receiving equipment and the date of returning the equipment',
                    'the customers should obtain receipt from the company by paying'
                ];
                ?>

                <?php if (empty($return_rows)): ?>
                <!-- Remark Section (hide for return bills) -->
                <div class="mt-4">
                    <div style="border-top:2px solid #ccc; padding-top:12px;">
                        <strong>Terms & Conditions :</strong>
                        <div style="min-height:60px; border-bottom:2px solid #ccc; padding:8px 0; font-size:14px;">
                            <?php if (!empty($paymentRemarks) || !empty($customTerms)): ?>
                                <ul class="mb-0" style="padding-left:18px;">
                                    <?php foreach ($customTerms as $term): ?>
                                        <li><?php echo $term; ?></li>
                                    <?php endforeach; ?>
                                    <?php if (!empty($paymentRemarks)): ?>
                                        <?php foreach ($paymentRemarks as $remark): ?>
                                            <?php
                                            $rawRemark = trim($remark['remark'] ?? '');
                                            if ($rawRemark === '') {
                                                continue;
                                            }
                                            $normalized = mb_strtolower($rawRemark, 'UTF-8');
                                            $shouldSkip = false;
                                            foreach ($skipRemarkPhrases as $phrase) {
                                                if (strpos($normalized, $phrase) !== false) {
                                                    $shouldSkip = true;
                                                    break;
                                                }
                                            }
                                            if ($shouldSkip) {
                                                continue;
                                            }
                                            ?>
                                            <li><?php echo htmlspecialchars($rawRemark); ?></li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">No predefined remarks available.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Signature Section -->
                <div class="row mt-5">
                    <div class="col-12">
                        <table class="signature-table" style="width:100%;">
                            <tr>
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>Prepared By</strong></td>
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>Approved By</strong></td>
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>Customer Signature</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script>
    function shareViaWhatsApp() {
        const customerMobile = '<?php echo $customerMobile; ?>';
        const billNo = '<?php echo addslashes($EQUIPMENT_RENT->bill_number); ?>';
        const customerName = '<?php echo addslashes($CUSTOMER_MASTER->name); ?>';
        const companyName = '<?php echo addslashes($COMPANY_PROFILE->name); ?>';
        const netAmount = '<?php echo number_format($net_amount, 2); ?>';

        const currentUrl = window.location.href.split('#')[0];
        const pdfUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'wa=1';

        const message = `Dear ${customerName},\n\nYour equipment rent invoice ${billNo} from ${companyName} is ready.\n\nTotal Amount: Rs. ${netAmount}\n\nView/Print Invoice: ${pdfUrl}\n\nThank you for your business!`;

        const encodedMessage = encodeURIComponent(message);

        let whatsappUrl;
        if (customerMobile && customerMobile.length >= 10) {
            whatsappUrl = `https://wa.me/${customerMobile}?text=${encodedMessage}`;
        } else {
            whatsappUrl = `https://wa.me/?text=${encodedMessage}`;
        }

        window.open(whatsappUrl, '_blank');
    }

    // Hide controls and auto-trigger print when opened from WhatsApp (wa=1)
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const fromWhatsApp = params.get('wa') === '1';

        if (fromWhatsApp) {
            document.querySelectorAll('.no-print').forEach(el => {
                el.style.display = 'none';
            });

            // Auto-trigger print dialog after layout is ready
            setTimeout(function() {
                window.print();
            }, 500);
        }
    });

    // Trigger print on Enter
    document.addEventListener("keydown", function(e) {
        if (e.key === "Enter") {
            window.print();
        }
    });
</script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>
