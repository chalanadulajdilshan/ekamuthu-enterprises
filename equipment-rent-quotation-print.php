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

// Get active terms & conditions
$TC = new TermsCondition(null);
$termsConditions = $TC->getActive();
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
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            color: #000;
        }

        .quotation-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
            background: #fff;
        }

        .quotation-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .company-header {
            margin-bottom: 15px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-details p {
            margin: 0;
            line-height: 1.5;
            font-size: 12px;
        }

        .quotation-for {
            margin: 10px 0 15px 0;
            font-size: 13px;
        }

        .quotation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 12px;
        }

        .quotation-table th,
        .quotation-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: left;
        }

        .quotation-table th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .quotation-table td.text-right {
            text-align: right;
        }

        .quotation-table td.text-center {
            text-align: center;
        }

        .quotation-table .total-row td {
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .bank-guarantee {
            font-weight: bold;
            margin: 15px 0 10px 0;
            font-size: 13px;
        }

        .terms-section {
            margin: 15px 0;
        }

        .terms-section ol {
            padding-left: 20px;
            margin: 5px 0;
        }

        .terms-section ol li {
            margin-bottom: 3px;
            font-size: 12px;
            line-height: 1.5;
        }

        .terms-section .bold-term {
            font-weight: bold;
        }

        .quotation-validity {
            font-size: 12px;
            margin: 5px 0;
        }

        .goods-notice {
            font-weight: bold;
            font-size: 12px;
            margin: 5px 0;
        }

        .account-details {
            text-align: center;
            margin: 20px 0 15px 0;
            font-size: 13px;
        }

        .contact-footer {
            text-align: center;
            font-size: 12px;
            margin: 10px 0;
        }

        .thank-you {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-top: 15px;
        }

        .company-stamp {
            margin-top: 15px;
        }

        .date-section {
            text-align: right;
            font-size: 13px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .quotation-container {
                padding: 0 !important;
                max-width: 100% !important;
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

    <!-- Print Controls -->
    <div class="container mt-4 no-print">
        <div class="d-flex justify-content-between align-items-center mb-3">
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
    </div>

    <!-- Quotation Content -->
    <div class="quotation-container">

        <!-- Title -->
        <div class="quotation-title">Quotation</div>

        <!-- Header: Company Info + Date -->
        <div class="row company-header">
            <div class="col-8 company-details">
                <p class="company-name"><?php echo $COMPANY_PROFILE->name; ?></p>
                <p><?php echo $COMPANY_PROFILE->address; ?></p>
                <p>&nbsp;</p>
                <p>Phone (<?php echo substr($COMPANY_PROFILE->mobile_number_1, 0, 3); ?>) <?php echo substr($COMPANY_PROFILE->mobile_number_1, 3); ?></p>
                <?php if (!empty($COMPANY_PROFILE->mobile_number_2)) { ?>
                    <p>Contact :<?php echo $COMPANY_PROFILE->mobile_number_2; ?></p>
                <?php } ?>
                <p>Email: <?php echo $COMPANY_PROFILE->email; ?></p>
                <p>Web: www.psekamuthuenterprises.com</p>
            </div>
            <div class="col-4 date-section">
                <p><strong>Date</strong> &nbsp;&nbsp;&nbsp;&nbsp;
                    <?php echo date('d/m/Y', strtotime($QUOTATION->rental_date)); ?></p>
            </div>
        </div>

        <!-- Quotation For -->
        <div class="quotation-for">
            <strong>Quotation for :</strong> &nbsp;&nbsp;&nbsp;
            <?php echo $CUSTOMER_MASTER->name; ?>
        </div>

        <!-- Items Table -->
        <table class="quotation-table">
            <thead>
                <tr>
                    <th>DESCRIPTION</th>
                    <th>Unit rent per<br>day</th>
                    <th>No. of Units</th>
                    <th>Day per</th>
                    <th>Rent per<br>Month</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $QUOTATION_ITEM = new EquipmentRentQuotationItem(null);
                $items = $QUOTATION_ITEM->getByQuotationId($id);

                $totalUnitRent = 0;
                $totalUnits = 0;
                $totalDayPer = 0;
                $totalRentPerMonth = 0;

                foreach ($items as $key => $item) {
                    $unitRentPerDay = (float) ($item['rent_one_day'] ?? 0);
                    $noOfUnits = intval($item['quantity'] ?? 1);
                    $dayPer = $unitRentPerDay * $noOfUnits;

                    // Always use saved item amount for print (supports manual overrides)
                    $rentPerMonth = (float) ($item['amount'] ?? 0);

                    $totalUnitRent += $unitRentPerDay;
                    $totalUnits += $noOfUnits;
                    $totalDayPer += $dayPer;
                    $totalRentPerMonth += $rentPerMonth;

                    $description = $item['equipment_name'];
                    ?>
                    <tr>
                        <td><?php echo $description; ?></td>
                        <td class="text-right"><?php echo number_format($unitRentPerDay, 2); ?></td>
                        <td class="text-center"><?php echo $noOfUnits; ?></td>
                        <td class="text-right"><?php echo number_format($dayPer, 2); ?></td>
                        <td class="text-right"><strong><?php echo number_format($rentPerMonth, 2); ?></strong></td>
                    </tr>
                <?php } ?>

                <!-- Total Row -->
                <tr class="total-row">
                    <td><strong>Total rental</strong></td>
                    <td class="text-right"><?php echo number_format($totalUnitRent, 2); ?></td>
                    <td class="text-center"><?php echo $totalUnits; ?></td>
                    <td class="text-right"><?php echo number_format($totalDayPer, 2); ?></td>
                    <td class="text-right"><strong><?php echo number_format($totalRentPerMonth, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <?php
        $transportCost = floatval($QUOTATION->transport_cost ?? 0);
        $manualDeposit = floatval($QUOTATION->deposit_total ?? 0);
        $grandTotal = $totalRentPerMonth + $transportCost + $manualDeposit;
        ?>

        <table class="quotation-table" style="max-width: 320px; margin-left:auto;">
            <tbody>
                <tr>
                    <td><strong>Transport</strong></td>
                    <td class="text-right"><?php echo number_format($transportCost, 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Deposit</strong></td>
                    <td class="text-right"><?php echo number_format($manualDeposit, 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td><strong>Grand Total</strong></td>
                    <td class="text-right"><strong><?php echo number_format($grandTotal, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Bank Guarantee -->
        <p class="bank-guarantee">Terms & Conditions :</p>

        <!-- Terms & Conditions -->
        <?php if (!empty($termsConditions)) { ?>
            <div class="terms-section">
                <ol>
                    <?php foreach ($termsConditions as $index => $tc) { ?>
                        <li><?php echo nl2br(htmlspecialchars($tc['description'])); ?></li>
                    <?php } ?>
                </ol>
            </div>
        <?php } ?>

        <!-- Account Details -->
        <div class="account-details">
            <p>Account Details <?php echo $COMPANY_PROFILE->name; ?></p>
            <p>1580016235 -Commercial Bank- Dehiwala Branch</p>
        </div>

        <!-- Company Stamp & Contact -->
        <div class="row" style="margin-top: 20px;">
            <div class="col-6 company-stamp">
            </div>
            <div class="col-6">
                <div class="contact-footer">
                    <p>If you have any questions concerning this quotation</p>
                    <p>Please contact <?php echo $COMPANY_PROFILE->mobile_number_1; ?></p>
                </div>
                <div class="thank-you">THANK YOU FOR YOUR BUSINESS!</div>
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
