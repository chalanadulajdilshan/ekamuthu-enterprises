<!doctype html>
<?php
include 'class/include.php';

if (!isset($_SESSION)) {
    session_start();
}

$note_id = $_GET['id'] ?? '';

$US = new User($_SESSION['id']);
$COMPANY_PROFILE = new CompanyProfile($US->company_id);

// Get issue note
$ISSUE_NOTE = new IssueNote($note_id);

if (!$ISSUE_NOTE->id) {
    die('Issue Note not found');
}

// Get linked rent invoice
$EQUIPMENT_RENT = new EquipmentRent($ISSUE_NOTE->rent_invoice_id);
$CUSTOMER_MASTER = new CustomerMaster($ISSUE_NOTE->customer_id);

// Get issue note items
$note_items = $ISSUE_NOTE->getItems();

// Get department name
$departmentName = '-';
if ($ISSUE_NOTE->department_id) {
    $DEPARTMENT = new DepartmentMaster($ISSUE_NOTE->department_id);
    $departmentName = $DEPARTMENT->name;
}

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
    <title>Issue Note - <?php echo htmlspecialchars($ISSUE_NOTE->issue_note_code); ?></title>
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
    </style>

</head>

<body data-layout="horizontal" data-topbar="colored">

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 no-print gap-2">
        <h4 class="mb-0">Warehouse Issue Note - ගබඩා නිකුත් කිරීමේ පත්‍රිකාව</h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button onclick="window.print()" class="btn btn-success ms-2">Print</button>
            <button onclick="downloadPDF()" class="btn btn-primary ms-2">PDF</button>
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

                <!-- Title - Top Center -->
                <div style="text-align:center;margin-bottom:10px;">
                    <h3 style="font-weight:bold;font-size:20px;margin:0;">ගබඩා නිකුත් කිරීමේ පත්‍රිකාව</h3>
                </div>

                <!-- Header layout using table for print compatibility -->
                <table style="width:100%;border:none;border-collapse:collapse;margin-bottom:15px;">
                    <tr>
                        <td style="vertical-align:top;width:40%;padding:0;border:none;">
                            <p style="font-weight:bold;font-size:18px;margin:0 0 4px 0;"><?php echo htmlspecialchars($COMPANY_PROFILE->name); ?></p>
                            <p style="font-size:13px;margin:0 0 4px 0;color:#6c757d;"><?php echo htmlspecialchars($COMPANY_PROFILE->address); ?></p>
                            <p style="font-size:13px;margin:0 0 4px 0;color:#6c757d;"><?php echo htmlspecialchars($COMPANY_PROFILE->email); ?> | <?php echo formatPhone($COMPANY_PROFILE->mobile_number_1); ?></p>
                        </td>
                        <td style="vertical-align:top;text-align:right;padding:0;border:none;">
                            <p style="font-size:14px;margin:0 0 3px 0;"><strong>Customer Name:</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->name); ?></p>
                            <p style="font-size:14px;margin:0 0 3px 0;"><strong>Contact:</strong> <?php echo !empty($CUSTOMER_MASTER->address) ? htmlspecialchars($CUSTOMER_MASTER->address) : '.................................'; ?></p>
                            <p style="font-size:14px;margin:0 0 3px 0;"><strong>Mobile:</strong> <?php echo !empty($CUSTOMER_MASTER->mobile_number) ? formatPhone($CUSTOMER_MASTER->mobile_number) : '.................................'; ?></p>
                            <p style="font-size:14px;margin:0 0 3px 0;"><strong>Issue Note No:</strong> <?php echo htmlspecialchars($ISSUE_NOTE->issue_note_code); ?></p>
                            <p style="font-size:14px;margin:0 0 3px 0;"><strong>Invoice Ref:</strong> <?php echo htmlspecialchars($EQUIPMENT_RENT->bill_number); ?></p>
                            <p style="font-size:14px;margin:0 0 3px 0;"><strong>Department:</strong> <?php echo htmlspecialchars($departmentName); ?></p>
                            <p style="font-size:14px;margin:0 0 3px 0;"><strong>Issue Date:</strong> <?php echo date('d M, Y', strtotime($ISSUE_NOTE->issue_date)); ?></p>
                            <p style="font-size:14px;margin:0 0 3px 0;">
                                <strong>Status:</strong> 
                                <?php if ($ISSUE_NOTE->issue_status === 'issued'): ?>
                                    <span style="background:#198754;color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;">Issued</span>
                                <?php elseif ($ISSUE_NOTE->issue_status === 'cancelled'): ?>
                                    <span style="background:#dc3545;color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;">Cancelled</span>
                                <?php else: ?>
                                    <span style="background:#ffc107;color:#000;padding:2px 8px;border-radius:4px;font-size:12px;">Pending</span>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-centered">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>උපකරණ නම</th>
                                <th>කේතය</th>
                                <th>වර්ගය</th>
                                <th>ආපසු දින</th>
                                <th class="text-center">ඇණවුම්</th>
                                <th class="text-center">නිකුත්</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:13px;">
                            <?php
                            $row_num = 0;
                            foreach ($note_items as $item):
                                $row_num++;
                                // Calculate Return Date
                                $rentalDate = $EQUIPMENT_RENT->rental_date;
                                $duration = (float)$item['duration'];
                                $rentType = $item['rent_type'];
                                $unit = ($rentType === 'month') ? 'months' : 'days';
                                $returnDate = date('Y-m-d', strtotime($rentalDate . " + $duration $unit"));
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
                                    <td class="text-center"><?php echo $returnDate; ?></td>
                                    <td class="text-center"><?php echo intval($item['ordered_quantity'] ?? 0); ?></td>
                                    <td></td>
                                    <td><?php echo htmlspecialchars($item['remarks'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Empty rows for writing additional items -->
                            <?php for ($i = count($note_items); $i < 5; $i++): ?>
                                <tr>
                                    <td>&nbsp;</td>
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

                <!-- Terms & Conditions -->
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><strong>Terms & Conditions - නියම සහ කොන්දේසි:</strong></h6>
                        <ul style="padding-left:20px;margin-bottom:0;font-size:13px;">
                            <li>Equipment should be returned in good condition - උපකරණ හොඳ තත්වයේ ආපසු දිය යුතුය</li>
                            <li>Customer is responsible for any damage - හානි සඳහා පාරිභෝගිකයා වගකිව යුතුය</li>
                            <li>Deposit is refundable upon return - ආපසු දීමෙන් පසු තැන්පතුව ආපසු ලැබේ</li>
                            <li>Late returns will incur additional charges - ප්‍රමාද ආපසු දීම් සඳහා අමතර ගාස්තු අය කරයි</li>
                        </ul>
                    </div>
                </div>

                <!-- Remarks Section -->
                <?php if (!empty($ISSUE_NOTE->remarks)): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Remarks - සටහන:</strong><br>
                        <span style="font-size:13px;"><?php echo nl2br(htmlspecialchars($ISSUE_NOTE->remarks)); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Signature Section -->
                <div class="row mt-5">
                    <div class="col-12">
                        <table style="width:100%;">
                            <tr>
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>Prepared By<br>සකස් කළේ</strong></td>
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>Warehouse Keeper<br>ගබඩා භාරකරු</strong></td>
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>Received By<br>ලබාගත්තේ</strong></td>
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
            filename: 'Issue_Note_<?php echo $ISSUE_NOTE->issue_note_code; ?>.pdf',
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
