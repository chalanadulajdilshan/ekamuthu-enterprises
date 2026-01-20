<!doctype html>
<?php
include 'class/include.php';

if (!isset($_SESSION)) {
    session_start();
}

$id = $_GET['id'];
$US = new User($_SESSION['id']);
$COMPANY_PROFILE = new CompanyProfile($US->company_id);

$QUOTATION = new EquipmentRentQuotation($id);
$CUSTOMER_MASTER = new CustomerMaster($QUOTATION->customer_id);
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Equipment Rent Quotation | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Unicons CDN -->
    <link href="https://unicons.iconscout.com/release/v4.0.8/css/line.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Icons -->
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <!-- App CSS -->
    <link href="assets/css/app.min.css" rel="stylesheet" />

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .container {
                min-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .card {
                position: relative !important;
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                left: 0 !important;
                top: 0 !important;
            }

            .card-body {
                padding: 0 !important;
            }
            
            .col-sm-6 {
                width: 50% !important;
                float: left !important;
            }
            
            .text-sm-end {
                text-align: right !important;
            }

            @page {
                margin: 10mm;
                size: auto;
            }

            body.print-a4 {
                width: 210mm !important;
            }

            body.print-a3 {
                width: 297mm;
            }

            body.print-a5 {
                width: 148mm;
            }

            body.print-letter {
                width: 8.5in;
            }

            body.print-legal {
                width: 8.5in;
            }

            body.print-tabloid {
                width: 11in;
            }

            body.print-dotmatrix {
                width: 9.5in;
            }
        }
    </style>
</head>

<body class="print-a4" data-layout="horizontal" data-topbar="colored">

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <h4>Equipment Rent Quotation Print</h4>
            <div>
                <select id="printFormat" class="form-select d-inline w-auto" onchange="setPrintFormat(this.value)">
                    <option value="a4" selected>A4</option>
                    <option value="a3">A3</option>
                    <option value="a5">A5</option>
                    <option value="letter">Letter</option>
                    <option value="legal">Legal</option>
                    <option value="tabloid">Tabloid</option>
                    <option value="dotmatrix">Dot Matrix</option>
                </select>
                <button onclick="window.print()" class="btn btn-success ms-2">Print</button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="invoice-title">

                    <div class="col-sm-6 text-sm-end float-end">
                        <p><strong>Quotation No:</strong> #<?php echo $QUOTATION->quotation_number ?></p>
                        <p><strong>Date:</strong>
                            <?php echo date('d M, Y', strtotime($QUOTATION->rental_date)); ?></p>
                    </div>
                    <div class="mb-4">
                        <img src="./uploads/company-logos/<?php echo $COMPANY_PROFILE->image_name ?>" alt="logo" style="height:60px; width:auto;">
                    </div>

                    <div class="row mb-4">
                        <!-- Left: Company Info -->
                        <div class="col-sm-6">
                            <div class="text-muted">
                                <p class="mb-1"><i
                                        class="uil uil-building me-1"></i><?php echo $COMPANY_PROFILE->name ?></p>
                                <p class="mb-1"><i
                                        class="uil uil-map-marker me-1"></i><?php echo $COMPANY_PROFILE->address ?></p>
                                <p class="mb-1"><i
                                        class="uil uil-envelope-alt me-1"></i><?php echo $COMPANY_PROFILE->email ?></p>
                                <p><i class="uil uil-phone me-1"></i><?php echo $COMPANY_PROFILE->mobile_number_1 ?></p>
                            </div>
                        </div>

                        <!-- Right: Billed To -->
                        <div class="col-sm-6 text-sm-end">
                            <h6>Billed To:</h6>
                            <p><?php echo $CUSTOMER_MASTER->name ?><br><?php echo $CUSTOMER_MASTER->address ?>
                                <br><?php echo $CUSTOMER_MASTER->mobile_number ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-centered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Equipment</th>
                                <th>Sub Equipment</th>
                                <th>Rent Type</th>
                                <th>Duration</th>
                                <th class="text-center">Qty</th>
                                <th>Period</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $QUOTATION_ITEM = new EquipmentRentQuotationItem(null);
                            $items = $QUOTATION_ITEM->getByQuotationId($id);

                            $totalAmount = 0;

                            foreach ($items as $key => $item) {
                                $key++;
                                $amount = (float) $item['amount'];
                                $totalAmount += $amount;
                                
                                $period = $item['rental_date'];
                                if ($item['return_date']) {
                                    $period .= ' to ' . $item['return_date'];
                                }
                                ?>

                                <tr>
                                    <td><?php echo $key; ?></td>
                                    <td><?php echo $item['equipment_name'] . ' (' . $item['equipment_code'] . ')'; ?></td>
                                    <td><?php echo $item['sub_equipment_code']; ?></td>
                                    <td><?php echo ucfirst($item['rent_type']); ?></td>
                                    <td><?php echo (float)$item['duration'] . ' ' . ($item['rent_type'] == 'month' ? 'Months' : 'Days'); ?></td>
                                    <td class="text-center"><?php echo intval($item['quantity'] ?? 1); ?></td>
                                    <td><?php echo $period; ?></td>
                                    <td class="text-end"><?php echo number_format($amount, 2); ?></td>
                                </tr>
                            <?php } ?>

                            <!-- Totals section -->
                            <tr>
                                <td colspan="4" rowspan="3" style="vertical-align: top;">
                                    <?php if (!empty($QUOTATION->remark)) { ?>
                                        <h6><strong>Remarks:</strong></h6>
                                        <p><?php echo nl2br($QUOTATION->remark); ?></p>
                                    <?php } ?>
                                    
                                    <h6><strong>Terms & Conditions:</strong></h6>
                                    <ul style="padding-left: 20px; margin-bottom: 0;">
                                        <li>Total payment is required upon confirmation.</li>
                                        <li>Equipment must be returned in the same condition.</li>
                                        <li>Late returns may incur additional charges.</li>
                                    </ul>
                                </td>

                                <td colspan="2" class="text-end"><strong>Total Amount:</strong></td>
                                <td class="text-end"><strong><?php echo number_format($totalAmount, 2); ?></strong></td>
                            </tr>

                            <!-- Signature line -->
                            <tr>
                                <td colspan="7" style="padding-top: 50px;">
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="text-align: center;">
                                                _________________________<br>
                                                <strong>Prepared By</strong>
                                            </td>
                                            <td style="text-align: center;">
                                                _________________________<br>
                                                <strong>Approved By</strong>
                                            </td>
                                            <td style="text-align: center;">
                                                _________________________<br>
                                                <strong>Customer Signature</strong>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Apply print format on load
        window.onload = function () {
            setPrintFormat('a4');
        };

        function setPrintFormat(format) {
            const formats = [
                'a4', 'a3', 'a5',
                'letter', 'legal',
                'tabloid', 'dotmatrix'
            ];
            document.body.className = document.body.className
                .split(' ')
                .filter(c => !formats.map(f => 'print-' + f).includes(c))
                .join(' ')
                .trim();

            document.body.classList.add('print-' + format);
        }

        document.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                window.print();
            }
        });
    </script>
</body>

</html>
