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

// Fetch invoice remarks configured for the selected payment method
$INVOICE_REMARK = new InvoiceRemark();
$paymentRemarks = $INVOICE_REMARK->getRemarkByPaymentType($EQUIPMENT_RENT->payment_type_id ?? 0);
$remarksFallbackUsed = false;

// Fetch globally active Terms & Conditions (admin-managed)
$TC = new TermsCondition();
$termsConditions = $TC->getActive();

// If no remarks are mapped to this payment type, show all active remarks as a fallback
if (empty($paymentRemarks)) {
    $paymentRemarks = $INVOICE_REMARK->getActiveGroups();
    $remarksFallbackUsed = !empty($paymentRemarks);
}

// Get rent items
$rent_items = $EQUIPMENT_RENT->getItems();

// Determine rent label based on rent types (daily vs monthly vs mixed)
$rent_types = array_unique(array_map(function ($ri) {
    return $ri['rent_type'] ?? '';
}, $rent_items));
$has_month = in_array('month', $rent_types, true);
// Treat anything non-month as daily for label purposes
$has_daily = count($rent_types) === 0 ? false : ($has_month ? count($rent_types) > 1 : true);

if ($has_month && !$has_daily) {
    $rent_label = 'මසක කුලී මුදල:';
} elseif (!$has_month && $has_daily) {
    $rent_label = 'කුලී මුදල:';
} else {
    $rent_label = 'කුලී මුදල:';
}

// Collect return rows across items for print
$return_rows = [];
foreach ($rent_items as $ritem) {
    if (empty($ritem['id'])) continue;
    $itemReturns = EquipmentRentReturn::getByRentItemId($ritem['id']);
    foreach ($itemReturns as $ret) {
        $return_rows[] = array_merge($ret, [
            'equipment_name' => $ritem['equipment_name'] ?? '-',
            'equipment_code' => $ritem['equipment_code'] ?? '-',
            'sub_equipment_code' => $ritem['sub_equipment_code'] ?? '-',
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

// Calculate total customer paid across all returns
$total_customer_paid = 0;
$total_extra_charges = 0;
$total_repair_cost = 0;
foreach ($return_rows as $rr) {
    $total_customer_paid += floatval($rr['customer_paid'] ?? 0);
    $total_extra_charges += floatval($rr['extra_charge_amount'] ?? 0);
    $total_repair_cost += floatval($rr['repair_cost'] ?? 0);
}

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
            border-top-width: 0 !important;
            border-style: none !important;
        }

        .invoice-meta {
            margin-top: 10px !important;
            padding: 4px 10px;
            min-width: 230px;
            display: inline-block;
            border: none;
            background: transparent;
        }

        .summary-table td {
            padding: 4px 12px !important;
        }

        .summary-label {
            text-align: right;
            font-weight: bold;
        }

        .summary-value {
            text-align: right;
            min-width: 120px;
        }
    </style>

</head>

<body data-layout="horizontal" data-topbar="colored">

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 no-print gap-2">
        <h4 class="mb-0">උපකරණ කුලී ඉන්වොයිසිය</h4>
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
                
                <!-- Centered Title -->
                <div class="row mb-3">
                    <div class="col-12 text-center">
                        <h3 style="font-weight:bold;font-size:22px;border-bottom:3px solid #444; padding-bottom:2px; margin-bottom:0; display:inline-block;">උපකරණ කුලී ඉන්වොයිසිය</h3>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 text-sm-start text-md-start">
                        <div style="font-size:15px; line-height:1.6;">
                            <p class="mb-1"><strong>නම :</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->name); ?></p>
                            <p class="mb-1"><strong>දැනට පදිංචි ලිපිනය :</strong> <?php echo !empty($CUSTOMER_MASTER->address) ? htmlspecialchars($CUSTOMER_MASTER->address) : '.................................'; ?></p>
                            <p class="mb-1"><strong>Mobile:</strong> <?php echo !empty($CUSTOMER_MASTER->mobile_number) ? formatPhone($CUSTOMER_MASTER->mobile_number) : '.................................'; ?></p>
                            <p class="mb-1"><strong>WP No:</strong> <?php echo !empty($CUSTOMER_MASTER->mobile_number_2) ? formatPhone($CUSTOMER_MASTER->mobile_number_2) : '.................................'; ?></p>
                            <p class="mb-1"><strong>NIC:</strong> <?php echo !empty($CUSTOMER_MASTER->nic) ? htmlspecialchars($CUSTOMER_MASTER->nic) : '.................................'; ?></p>
                            <p class="mb-1"><strong>වැඩ බිමේ ලිපිනය:</strong> <?php echo !empty($EQUIPMENT_RENT->workplace_address) ? htmlspecialchars($EQUIPMENT_RENT->workplace_address) : '.................................'; ?></p>
                            <?php if (!empty($CUSTOMER_MASTER->guarantor_address)): ?>
                                <p class="mb-1"><strong>Guarantor Address:</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->guarantor_address); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6 text-sm-start text-md-end">
                        <div class="text-muted mb-3">
                            <p class="mb-1" style="font-weight:bold;font-size:18px;"><?php echo htmlspecialchars($COMPANY_PROFILE->name); ?></p>
                            <p class="mb-1" style="font-size:13px;"><?php echo htmlspecialchars($COMPANY_PROFILE->address); ?></p>
                            <p class="mb-1" style="font-size:13px;"><?php echo htmlspecialchars($COMPANY_PROFILE->email); ?> | <?php echo formatPhone($COMPANY_PROFILE->mobile_number_1); ?></p>
                            <p class="mb-1" style="font-size:13px;">VAT Registration No: <?php echo htmlspecialchars($COMPANY_PROFILE->vat_number); ?></p>
                        </div>
                        <div class="invoice-meta">
                            <p class="mb-1" style="font-size:14px;"><strong>Bill No:</strong> <?php echo htmlspecialchars($EQUIPMENT_RENT->bill_number); ?></p>
                            <p class="mb-1" style="font-size:14px;"><strong>නිකුත් කරන දිනය:</strong> <?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->rental_date)); ?></p>
                            <?php if ($EQUIPMENT_RENT->rental_start_date): ?>
                                <p class="mb-1" style="font-size:14px;"><strong>කුලියට ගත් දිනය:</strong> <?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->rental_start_date)); ?></p>
                            <?php endif; ?>
                            <?php if ($EQUIPMENT_RENT->received_date): ?>
                                <p class="mb-1" style="font-size:14px;"><strong>Received Date:</strong> <?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->received_date)); ?></p>
                            <?php endif; ?>
                            <p class="mb-1" style="font-size:14px;">
                                <strong>Status:</strong> 
                                <?php if ($EQUIPMENT_RENT->status === 'rented'): ?>
                                    <span class="badge bg-warning">Rented</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Returned</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-centered">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>උපකරණ නම</th>
                                <th>කේතය</th>
                                <th>වර්ගය</th>
                                <th>කාල සීමාව</th>
                                <th class="text-center">ප්‍රමාණය</th>
                                <th class="text-end">මුදල</th>
                                <th class="text-end">තැන්පත් කල යුතු මුදල</th>
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
                                    <td>
                                        <?php 
                                        echo intval($item['duration']); 
                                        echo ($item['rent_type'] === 'month') ? ' Months' : ' Days';
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo intval($item['quantity'] ?? 1); ?></td>
                                    <td class="text-end"><?php echo number_format($item['amount'], 2); ?></td>
                                    <td class="text-end"><?php echo number_format(floatval($item['deposit_amount'] ?? 0), 2); ?></td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

                <?php if (!empty($return_rows)): ?>
                <!-- Returns Table -->
                <div class="table-responsive mt-3">
                    <h5 class="mt-3 mb-2">Returns</h5>
                    <table class="table table-bordered table-centered">
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
                                    <td class="text-end"><?php echo number_format(floatval($ret['damage_amount'] ?? 0), 2); ?></td>
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
                    <div class="col-md-6">
                        <!-- Reserved space -->
                    </div>
                    <div class="col-md-6">
                        <table class="summary-table" style="width:100%;">
                            <tr>
                                <td class="summary-label"><?php echo htmlspecialchars($rent_label); ?></td>
                                <td class="summary-value"><?php echo number_format($hire_amount, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">තැන්පත් කල මුදල:</td>
                                <td class="summary-value"><?php echo number_format($total_deposit, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">ප්‍රවාහනය:</td>
                                <td class="summary-value"><?php echo number_format($transport_amount, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">Total Rent Amount:</td>
                                <td class="summary-value"><?php echo number_format($hire_amount + $transport_amount + $total_extra_charges, 2); ?></td>
                            </tr>
                            <?php if ($total_repair_cost > 0): ?>
                            <tr>
                                <td class="summary-label">Repair Cost Deducted:</td>
                                <td class="summary-value">-<?php echo number_format($total_repair_cost, 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="summary-label">පාරිභෝගිකයා ගෙවූ මුදල :</td>
                                <td class="summary-value"><?php echo number_format($total_customer_paid, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">Pay to Customer (Customer Refund Balance):</td>
                                <td class="summary-value">
                                    <?php echo number_format($refund_balance, 2); ?>
                                    <?php if ($refund_balance < 0): ?>
                                        <span class="badge bg-success ms-2">Customer Pay</span>
                                    <?php elseif ($refund_balance > 0): ?>
                                        <span class="badge bg-danger ms-2">Refund</span>
                                    <?php endif; ?>
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

                <?php
                $rateDescriptor = 'per day / hour';
                if ($has_month && !$has_daily) {
                    $rateDescriptor = 'per month';
                } elseif ($has_month && $has_daily) {
                    $rateDescriptor = 'per day / month';
                }

                $customTerms = [
                    sprintf(
                        'The customer should make the full payment for the total number of days including the date of receiving equipment and the date of returning the equipment and amount of Rs. %s %s as per rental.',
                        number_format($hire_amount, 2),
                        $rateDescriptor
                    ),
                    sprintf(
                        'The customers should obtain receipt from the company by paying Rs. %s as the primary deposit, when collecting the equipment & should return it at the time of returning the equipment.',
                        number_format($total_deposit, 2)
                    )
                ];

                $skipRemarkPhrases = [
                    'the customer should make the full payment for the total number of days including the date of receiving equipment and the date of returning the equipment',
                    'the customers should obtain receipt from the company by paying'
                ];
                ?>

                <!-- Remark Section -->
                <div class="mt-4">
                    <div style="border-top:2px solid #ccc; padding-top:12px;">
                        <strong>Terms & Conditions :</strong>
                        <div style="min-height:60px; border-bottom:2px solid #ccc; padding:8px 0; font-size:14px;">
                            <?php if (!empty($paymentRemarks) || !empty($customTerms) || !empty($termsConditions)): ?>
                                <ul class="mb-0" style="padding-left:18px;">
                                    <?php foreach ($customTerms as $term): ?>
                                        <li><?php echo htmlspecialchars($term); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (!empty($termsConditions)): ?>
                                        <?php foreach ($termsConditions as $tc): ?>
                                            <?php if (!empty($tc['description'])): ?>
                                                <li><?php echo nl2br(htmlspecialchars($tc['description'])); ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
                                <span class="text-muted">No predefined remarks available for this payment type.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="row mt-5">
                    <div class="col-12">
                        <table style="width:100%;">
                            <tr>
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>සකස් කළේ</strong></td>
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>අනුමත කළේ</strong></td>
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>පාරිභෝගික අත්සන</strong></td>
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
