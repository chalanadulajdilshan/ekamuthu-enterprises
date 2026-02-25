<!doctype html>
<?php
include 'class/include.php';

if (!isset($_SESSION)) {
    session_start();
}

$return_id = $_GET['id'] ?? '';

$US = new User($_SESSION['id']);
$COMPANY_PROFILE = new CompanyProfile($US->company_id);

// Get issue return note
$ISSUE_RETURN = new IssueReturnNote($return_id);

if (!$ISSUE_RETURN->id) {
    die('Issue Return Note not found');
}

// Get linked issue note
$ISSUE_NOTE = new IssueNote($ISSUE_RETURN->issue_note_id);
// Get linked rent invoice
$EQUIPMENT_RENT = new EquipmentRent($ISSUE_NOTE->rent_invoice_id);
$CUSTOMER_MASTER = new CustomerMaster($ISSUE_NOTE->customer_id);

// Get issue return note items
$note_items = $ISSUE_RETURN->getItems();

// Get department name
$departmentName = '-';
if ($ISSUE_RETURN->department_id) {
    $DEPARTMENT = new DepartmentMaster($ISSUE_RETURN->department_id);
    $departmentName = $DEPARTMENT->name;
}

?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Issue Return Note - <?php echo htmlspecialchars($ISSUE_RETURN->return_code); ?></title>
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
        <h4 class="mb-0">Warehouse Return Note - ගබඩා ආපසු ලබාගැනීමේ පත්‍රිකාව</h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button onclick="window.print()" class="btn btn-success ms-2">Print</button>
            <button onclick="downloadPDF()" class="btn btn-primary ms-2">PDF</button>
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
                    </div>
                    <div class="col-md-4 text-sm-start text-md-start">
                        <h3 style="font-weight:bold;font-size:18px;">Warehouse Return Note</h3>
                        <p class="mb-1 text-muted" style="font-size:14px;"><strong>Customer Name:</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->name); ?></p>
                        <p class="mb-1 text-muted" style="font-size:14px;"><strong>Contact:</strong> <?php echo !empty($CUSTOMER_MASTER->address) ? htmlspecialchars($CUSTOMER_MASTER->address) : '.................................'; ?></p>
                        <p class="mb-1 text-muted" style="font-size:14px;"><strong>Mobile:</strong> <?php echo !empty($CUSTOMER_MASTER->mobile_number) ? formatPhone($CUSTOMER_MASTER->mobile_number) : '.................................'; ?></p>
                    </div>

                    <div class="col-md-3 text-sm-start text-md-end">
                        <p class="mb-1" style="font-size:14px;"><strong>Return Note No:</strong> <?php echo htmlspecialchars($ISSUE_RETURN->return_code); ?></p>
                        <p class="mb-1" style="font-size:14px;"><strong>Issue Note Ref:</strong> <?php echo htmlspecialchars($ISSUE_NOTE->issue_note_code); ?></p>
                        <p class="mb-1" style="font-size:14px;"><strong>Invoice Ref:</strong> <?php echo htmlspecialchars($EQUIPMENT_RENT->bill_number); ?></p>
                        <p class="mb-1" style="font-size:14px;"><strong>Department:</strong> <?php echo htmlspecialchars($departmentName); ?></p>
                        <p class="mb-1" style="font-size:14px;"><strong>Return Date:</strong> <?php echo date('d M, Y', strtotime($ISSUE_RETURN->return_date)); ?></p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-centered">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Equipment Name - උපකරණ නම</th>
                                <th>Code - කේතය</th>
                                <th class="text-center">Issued Qty - නිකුත්</th>
                                <th class="text-center">Returned Qty - ලැබුණු</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:13px;">
                            <?php
                            $row_num = 0;
                            foreach ($note_items as $item):
                                $row_num++;
                            ?>
                                <tr>
                                    <td><?php echo str_pad($row_num, 2, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($item['equipment_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($item['sub_equipment_code'] ?? '-'); ?></td>
                                    <td class="text-center"><?php echo intval($item['issued_quantity'] ?? 0); ?></td>
                                    <td class="text-center"><strong><?php echo intval($item['return_quantity'] ?? 0); ?></strong></td>
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
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Remarks Section -->
                <?php if (!empty($ISSUE_RETURN->remarks)): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Remarks - සටහන:</strong><br>
                        <span style="font-size:13px;"><?php echo nl2br(htmlspecialchars($ISSUE_RETURN->remarks)); ?></span>
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
                                <td style="text-align:center;padding-top:50px;">_________________________<br><strong>Handed Over By<br>භාර දුන්නේ</strong></td>
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
            filename: 'Return_Note_<?php echo $ISSUE_RETURN->return_code; ?>.pdf',
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
