<!doctype html>
<?php
include 'class/include.php';

if (!isset($_SESSION)) {
    session_start();
}

$bill_param = $_GET['bill_no'] ?? '';
$US = new User($_SESSION['id']);
$COMPANY_PROFILE = new CompanyProfile($US->company_id);

// Handle both rent ID and bill number
if (is_numeric($bill_param)) {
    // It's an ID - use it directly
    $EQUIPMENT_RENT = new EquipmentRent($bill_param);
    $rent_id = $bill_param;
} else {
    // It's a bill number - look it up
    $EQUIPMENT_RENT_TEMP = new EquipmentRent(null);
    if ($EQUIPMENT_RENT_TEMP->getByBillNumber($bill_param)) {
        $EQUIPMENT_RENT = $EQUIPMENT_RENT_TEMP;
        $rent_id = $EQUIPMENT_RENT->id;
    } else {
        die('Rent record not found: ' . htmlspecialchars($bill_param));
    }
}

// Verify rent exists
if (!$EQUIPMENT_RENT->id) {
    die('Rent record not found');
}

$CUSTOMER_MASTER = new CustomerMaster($EQUIPMENT_RENT->customer_id);

// Get rent items
$rent_items = $EQUIPMENT_RENT->getItems();

// Calculate totals
$total_amount = 0;
foreach ($rent_items as $item) {
    $total_amount += floatval($item['amount']);
}

// Transport amount from record
$transport_amount = floatval($EQUIPMENT_RENT->transport_cost);
$total_deposit = floatval($EQUIPMENT_RENT->deposit_total);

// Calculate net amount and outstanding
$hire_amount = $total_amount;
$net_amount = $total_amount + $total_deposit + $transport_amount;
$total_outstanding = $net_amount; // For now, assuming full amount is outstanding

// Get customer mobile number for WhatsApp
$customerMobile = !empty($CUSTOMER_MASTER->mobile_number) ? $CUSTOMER_MASTER->mobile_number : '';
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
            <button onclick="window.print()" class="btn btn-success ms-2">Print</button>
            <button onclick="downloadPDF()" class="btn btn-primary ms-2">PDF</button>
            <button onclick="shareViaWhatsApp()" class="btn btn-success ms-2 no-print">
                <i class="uil uil-whatsapp"></i> WhatsApp
            </button>
        </div>
    </div>

    <div class="card" id="invoice-content">
        <div class="card-body">
            <!-- Company & Customer Info -->
            <div class="invoice-title">
                <div class="row mb-4">
                    <?php
                    function formatPhone($number) {
                        $number = preg_replace('/\D/', '', $number);
                        if (strlen($number) == 10) {
                            return sprintf("(%s) %s-%s", substr($number, 0, 3), substr($number, 3, 3), substr($number, 6));
                        }
                        return $number;
                    }
                    ?>
                    <div class="col-md-5 text-muted">
                        <p class="mb-1" style="font-weight:bold;font-size:18px;"><?php echo htmlspecialchars($COMPANY_PROFILE->name); ?></p>
                        <p class="mb-1" style="font-size:13px;"><?php echo htmlspecialchars($COMPANY_PROFILE->address); ?></p>
                        <p class="mb-1" style="font-size:13px;"><?php echo htmlspecialchars($COMPANY_PROFILE->email); ?> | <?php echo formatPhone($COMPANY_PROFILE->mobile_number_1); ?></p>
                        <p class="mb-1" style="font-size:13px;">VAT Registration No: <?php echo htmlspecialchars($COMPANY_PROFILE->vat_number); ?></p>
                    </div>
                    <div class="col-md-4 text-sm-start text-md-start">
                        <h3 style="font-weight:bold;font-size:18px;">උපකරණ කුලී ඉන්වොයිසිය</h3>
                        <p class="mb-1 text-muted" style="font-size:14px;"><strong>Customer Name:</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->name); ?></p>
                        <p class="mb-1 text-muted" style="font-size:14px;"><strong>Contact:</strong> <?php echo !empty($CUSTOMER_MASTER->address) ? htmlspecialchars($CUSTOMER_MASTER->address) : '.................................'; ?></p>
                        <p class="mb-1 text-muted" style="font-size:14px;"><strong>Mobile:</strong> <?php echo !empty($CUSTOMER_MASTER->mobile_number) ? formatPhone($CUSTOMER_MASTER->mobile_number) : '.................................'; ?></p>
                        <p class="mb-1 text-muted" style="font-size:14px;"><strong>NIC:</strong> <?php echo !empty($CUSTOMER_MASTER->nic) ? htmlspecialchars($CUSTOMER_MASTER->nic) : '.................................'; ?></p>
                    </div>

                    <div class="col-md-3 text-sm-start text-md-end">
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
                                </tr>
                            <?php endforeach; ?>

                            <!-- Empty rows for writing additional items -->
                            <?php for ($i = count($rent_items); $i < 5; $i++): ?>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Section -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6><strong>Terms & Conditions - නියම සහ කොන්දේසි:</strong></h6>
                        <ul style="padding-left:20px;margin-bottom:0;font-size:13px;">
                            <li>Equipment should be returned in good condition - උපකරණ හොඳ තත්වයේ ආපසු දිය යුතුය</li>
                            <li>Customer is responsible for any damage - හානි සඳහා පාරිභෝගිකයා වගකිව යුතුය</li>
                            <li>Deposit is refundable upon return - ආපසු දීමෙන් පසු තැන්පතුව ආපසු ලැබේ</li>
                            <li>Late returns will incur additional charges - ප්‍රමාද ආපසු දීම් සඳහා අමතර ගාස්තු අය කරයි</li>
                        </ul>

                        <?php if (!empty($EQUIPMENT_RENT->remark)): ?>
                            <div class="mt-3">
                                <strong>Remark - සටහන:</strong><br>
                                <span style="font-size:13px;"><?php echo nl2br(htmlspecialchars($EQUIPMENT_RENT->remark)); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <table class="summary-table" style="width:100%;">
                            <tr>
                                <td class="summary-label">මුළු කුලී මුදල:</td>
                                <td class="summary-value"><?php echo number_format($hire_amount, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">තැන්පතු මුදල:</td>
                                <td class="summary-value"><?php echo number_format($total_deposit, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">ප්‍රවාහනය:</td>
                                <td class="summary-value"><?php echo number_format($transport_amount, 2); ?></td>
                            </tr>
                            <tr style="border-top:2px solid #333;">
                                <td class="summary-label" style="font-size:16px;"><strong>ශුද්ධ මුදල:</strong></td>
                                <td class="summary-value" style="font-size:16px;"><strong><?php echo number_format($net_amount, 2); ?></strong></td>
                            </tr>
                            <tr>
                                <td class="summary-label text-danger">මුළු හිඟ මුදල:</td>
                                <td class="summary-value text-danger"><strong><?php echo number_format($total_outstanding, 2); ?></strong></td>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const element = document.getElementById('invoice-content');
        const opt = {
            margin: 0.5,
            filename: 'Rent_Invoice_<?php echo $EQUIPMENT_RENT->bill_number; ?>.pdf',
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 2
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait'
            }
        };
        html2pdf().set(opt).from(element).save();
    }

    function shareViaWhatsApp() {
        const customerMobile = '<?php echo $customerMobile; ?>';
        const billNo = '<?php echo addslashes($EQUIPMENT_RENT->bill_number); ?>';
        const customerName = '<?php echo addslashes($CUSTOMER_MASTER->name); ?>';
        const companyName = '<?php echo addslashes($COMPANY_PROFILE->name); ?>';
        const netAmount = '<?php echo number_format($net_amount, 2); ?>';
        
        const currentUrl = window.location.href;

        const message = `Dear ${customerName},\n\nYour equipment rent invoice ${billNo} from ${companyName} is ready.\n\nTotal Amount: Rs. ${netAmount}\n\nView Invoice: ${currentUrl}\n\nThank you for your business!`;

        const encodedMessage = encodeURIComponent(message);

        let whatsappUrl;
        if (customerMobile && customerMobile.length >= 10) {
            whatsappUrl = `https://wa.me/${customerMobile}?text=${encodedMessage}`;
        } else {
            whatsappUrl = `https://wa.me/?text=${encodedMessage}`;
        }

        window.open(whatsappUrl, '_blank');
    }

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
