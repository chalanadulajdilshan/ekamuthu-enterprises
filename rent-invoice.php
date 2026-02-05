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
                        GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                        * ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) / (CASE WHEN eri.rent_type = 'month' THEN 30 ELSE 1 END))
                        * err.return_qty
                        + COALESCE(err.extra_day_amount, 0)
                        + COALESCE(err.damage_amount, 0)
                        + COALESCE(err.penalty_amount, 0)
                    ), 0) as total_charges
                    FROM equipment_rent_returns err
                    INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                    WHERE eri.rent_id = " . (int) $rent_id;
$chargesResult = $db->readQuery($chargesQuery);
$chargesData = mysqli_fetch_assoc($chargesResult);
$total_charges = floatval($chargesData['total_charges'] ?? 0);
$refund_balance = $total_deposit - $total_charges;

// Calculate net amount and outstanding
$hire_amount = $total_amount;
$net_amount = $total_amount + $total_deposit + $transport_amount;
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
            }

            #invoice-content, .card {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none;
            }

            .container {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
            }

            @page {
                size: auto;
                margin: 10mm;
            }
        }

        #invoice-content table,
        #invoice-content th,
        #invoice-content td {
            padding: 4px 8px !important;
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
            margin-top: 20px !important;
            padding: 6px 14px;
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
                            <p class="mb-1"><strong>Customer Name:</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->name); ?></p>
                            <p class="mb-1"><strong>Contact:</strong> <?php echo !empty($CUSTOMER_MASTER->address) ? htmlspecialchars($CUSTOMER_MASTER->address) : '.................................'; ?></p>
                            <p class="mb-1"><strong>Mobile:</strong> <?php echo !empty($CUSTOMER_MASTER->mobile_number) ? formatPhone($CUSTOMER_MASTER->mobile_number) : '.................................'; ?></p>
                            <p class="mb-1"><strong>WP No:</strong> <?php echo !empty($CUSTOMER_MASTER->mobile_number_2) ? formatPhone($CUSTOMER_MASTER->mobile_number_2) : '.................................'; ?></p>
                            <p class="mb-1"><strong>NIC:</strong> <?php echo !empty($CUSTOMER_MASTER->nic) ? htmlspecialchars($CUSTOMER_MASTER->nic) : '.................................'; ?></p>
                            <p class="mb-1"><strong>Workplace Address:</strong> <?php echo !empty($CUSTOMER_MASTER->workplace_address) ? htmlspecialchars($CUSTOMER_MASTER->workplace_address) : '.................................'; ?></p>
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
                            <p class="mb-1" style="font-size:14px;"><strong>Rental Date:</strong> <?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->rental_date)); ?></p>
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
                                <th class="text-end">Settlement</th>
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
                                    <td class="text-end">
                                        <?php if (!empty($ret['additional_payment']) && floatval($ret['additional_payment']) > 0): ?>
                                            <span class="text-danger">Pay: Rs. <?php echo number_format(floatval($ret['additional_payment']), 2); ?></span>
                                        <?php elseif (!empty($ret['refund_amount']) && floatval($ret['refund_amount']) > 0): ?>
                                            <span class="text-success">Refund: Rs. <?php echo number_format(floatval($ret['refund_amount']), 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">No charge</span>
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

                        <?php if (!empty($EQUIPMENT_RENT->remark)): ?>
                            <div class="mt-3">
                                <strong>Terms & Conditions - නියම සහ කොන්දේසි:</strong><br>
                                <span style="font-size:13px;"><?php echo nl2br(htmlspecialchars($EQUIPMENT_RENT->remark)); ?></span>
                            </div>
                        <?php endif; ?>
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
                                <td class="summary-label">පාරිභෝගික අයවැය (Customer Refund Balance):</td>
                                <td class="summary-value">
                                    <?php echo number_format($refund_balance, 2); ?>
                                    <?php if ($refund_balance < 0): ?>
                                        <span class="badge bg-danger ms-2">Customer Pay</span>
                                    <?php elseif ($refund_balance > 0): ?>
                                        <span class="badge bg-success ms-2">Refund</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
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
