<!doctype html>
<?php
include 'class/include.php';

if (!isset($_SESSION)) {
    session_start();
}

$id = $_GET['id'] ?? '';

$GATEPASS = new Gatepass($id);

if (!$GATEPASS->id) {
    die('Gatepass not found');
}

$EQUIPMENT_RENT = new EquipmentRent($GATEPASS->invoice_id);
$CUSTOMER = new CustomerMaster($EQUIPMENT_RENT->customer_id);
$GATEPASS_ITEMS = $GATEPASS->getItems();

?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Gate Pass - <?php echo htmlspecialchars($GATEPASS->gatepass_code); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'main-css.php' ?>
    <link href="https://unicons.iconscout.com/release/v4.0.8/css/line.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body, html {
                width: 100%;
                margin: 0;
                padding: 0;
                background-color: #fff !important;
            }

            #invoice-content, .card {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
            }

            .container {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
            }

            @page {
                size: A5;
                margin: 5mm;
            }
        }

        #invoice-content {
            background-color: #fffbef; /* Slight yellowish tint for paper look on screen */
            width: 148mm;
            margin: auto;
            min-height: 210mm;
            padding: 10mm;
            font-family: 'Inter', sans-serif;
            color: #1a1a1a;
        }

        @media print {
            #invoice-content {
                background-color: #fff !important;
                padding: 10mm;
                min-height: auto;
            }
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
            color: #000;
        }

        .header-subtitle {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .meta-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .gp-number {
            font-size: 18px;
            font-weight: bold;
            color: #444;
        }

        .gp-date {
            font-size: 13px;
            font-weight: bold;
        }

        .main-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            text-decoration: underline;
            margin-bottom: 15px;
        }

        .details-box {
            border: 2px solid #555;
            border-radius: 12px;
            padding: 15px;
            position: relative;
        }

        .form-row {
            margin-bottom: 5px;
            display: flex;
            align-items: baseline;
        }

        .form-label {
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            margin-right: 10px;
            color: #000;
        }

        .form-value {
            border-bottom: 2px dotted #888;
            flex-grow: 1;
            font-size: 13px;
            font-weight: bold;
            padding-left: 10px;
            color: #003366; /* Blueish ink look */
            min-height: 15px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .items-table th, .items-table td {
            border: 1px solid #333;
            padding: 6px 10px;
            text-align: left;
            font-size: 12px;
        }

        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .items-table td {
            font-weight: bold;
            color: #003366;
        }

        .signature-section {
            margin-top: 10px;
        }

        .signature-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            text-decoration: underline;
        }

        .authorization-text {
            margin-top: 15px;
            font-size: 12px;
            line-height: 1.6;
            color: #000;
        }

        .manager-signature {
            text-align: right;
            margin-top: 25px;
            padding-right: 40px;
        }

        .manager-line {
            display: inline-block;
            width: 200px;
            border-bottom: 2px dotted #888;
            margin-bottom: 5px;
        }

        .manager-label {
            display: block;
            width: 200px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin-left: auto;
        }
    </style>

</head>

<body data-layout="horizontal" data-topbar="colored">

<div class="container mt-4 no-print">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Gate Pass Preview</h4>
        <div class="d-flex gap-2">
             <a href="gatepass.php?rent_id=<?php echo $GATEPASS->invoice_id; ?>" class="btn btn-secondary">
                <i class="uil uil-arrow-left"></i> Back
            </a>
            <button onclick="window.print()" class="btn btn-success">
                <i class="uil uil-print"></i> Print
            </button>
            <button onclick="downloadPDF()" class="btn btn-primary">
                <i class="uil uil-file-download"></i> PDF
            </button>
        </div>
    </div>
</div>

<div id="invoice-content">
    <!-- Header -->
    <div class="header-title">පී. එස්. එකමුතු එන්ටර්ප්‍රයිසිස්</div>
    <div class="header-subtitle">
        අංක 50, හිල් වීදිය, දෙහිවල. දුරකථන : 4201659, 077 6358281
    </div>

    <!-- Meta Info -->
    <div class="meta-section">
        <div class="gp-number"><?php echo htmlspecialchars($GATEPASS->gatepass_code); ?></div>
        <div class="gp-date">බිල්පත් අංකය (Bill No) : <span style="border-bottom: 2px dotted #888; min-width: 100px; display: inline-block; text-align: center;"><?php echo htmlspecialchars($EQUIPMENT_RENT->bill_number); ?></span></div>
        <div class="gp-date">දිනය : <span style="border-bottom: 2px dotted #888; min-width: 100px; display: inline-block; text-align: center;"><?php echo date('d/m/Y', strtotime($GATEPASS->gatepass_date)); ?></span></div>
    </div>

    <!-- Title -->
    <div class="main-title">ගබඩා සංවීතයෙන් උපකරණ නිකුත් කිරීමේ අවසර පත්‍රය</div>

    <!-- Details Box -->
    <div class="details-box">
        <div class="form-row">
            <div class="form-label">නම :</div>
            <div class="form-value">
                <?php 
                echo htmlspecialchars($GATEPASS->name); 
                if ($CUSTOMER->mobile_number) {
                    echo " (" . htmlspecialchars($CUSTOMER->mobile_number) . ")";
                }
                ?>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-label">ලිපිනය :</div>
            <div class="form-value"><?php echo htmlspecialchars($GATEPASS->address); ?></div>
        </div>

        <div class="form-row">
            <div class="form-label">හැඳුනුම්පත් අංකය :</div>
            <div class="form-value"><?php echo htmlspecialchars($GATEPASS->id_number); ?></div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>විස්තරය</th>
                    <th style="width: 100px; text-align: center;">ප්‍රමාණය</th>
                    <th>කරුණු</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($GATEPASS_ITEMS as $index => $item): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $index + 1; ?></td>
                        <td>
                            <?php 
                            echo htmlspecialchars($item['equipment_name']); 
                            if ($item['sub_equipment_code']) {
                                echo " (" . htmlspecialchars($item['sub_equipment_code']) . ")";
                            }
                            ?>
                        </td>
                        <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                        <td><?php echo htmlspecialchars($item['remarks'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($GATEPASS_ITEMS)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">උපකරණ ඇතුළත් කර නොමැත (No items listed)</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>


        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-title">නිකුත් කරන්නාගේ</div>
            <div class="form-row">
                <div class="form-label">නම :</div>
                <div class="form-value"><?php echo htmlspecialchars($GATEPASS->issued_by); ?></div>
            </div>
            <div class="form-row">
                <div class="form-label">අත්සන :</div>
                <div class="form-value" style="color: transparent;">____________________</div>
            </div>
        </div>

        <!-- Authorization Text -->
        <div class="authorization-text">
            ඉහත සඳහන් යන්ත්‍රය / උපකරණ නිකුත් කිරීමට අවසර දෙමි.
        </div>

        <!-- Manager Signature -->
        <div class="manager-signature">
            <span class="manager-line"></span>
            <span class="manager-label">කළමනාකරු</span>
        </div>
    </div>
    
    <!-- Footer Internal Note (Bill Ref) -->
    <div style="margin-top: 20px; font-size: 10px; color: #888; text-align: right;" class="no-print">
        Bill Ref: <?php echo htmlspecialchars($EQUIPMENT_RENT->bill_number); ?> (Internal Ref)
    </div>
</div>

<!-- JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const element = document.getElementById('invoice-content');
        const opt = {
            margin: [5, 5, 5, 5],
            filename: 'Gatepass_<?php echo $GATEPASS->gatepass_code; ?>.pdf',
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 2,
                useCORS: true
            },
            jsPDF: {
                unit: 'mm',
                format: 'a5',
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
