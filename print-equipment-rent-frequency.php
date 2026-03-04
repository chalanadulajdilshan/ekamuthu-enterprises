<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$from_date = $_GET['from_date'] ?? null;
$to_date = $_GET['to_date'] ?? null;

if (!$from_date || !$to_date) {
    die("Date range not specified!");
}

$db = Database::getInstance();
$from = $db->escapeString($from_date);
$to   = $db->escapeString($to_date);

$sql = "SELECT 
            cm.id AS customer_id,
            cm.code AS customer_code,
            cm.name AS customer_name,
            cm.mobile_number,
            COUNT(er.id) AS rent_count,
            MAX(er.rental_date) AS last_rental_date,
            SUM(er.deposit_total + er.transport_cost) AS base_amount
        FROM equipment_rent er
        JOIN customer_master cm ON er.customer_id = cm.id
        WHERE er.rental_date BETWEEN '$from' AND '$to'
        GROUP BY cm.id
        ORDER BY rent_count DESC, last_rental_date DESC";

$result = $db->readQuery($sql);

$data = [];
$grand_total_amount = 0;
$total_rentals = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $customerId = (int) $row['customer_id'];

    $extraSql = "SELECT 
                    COALESCE(SUM(err.additional_payment), 0) AS tot_additional,
                    COALESCE(SUM(err.refund_amount), 0) AS tot_refund
                 FROM equipment_rent er2
                 JOIN equipment_rent_items eri ON er2.id = eri.rent_id
                 JOIN equipment_rent_returns err ON eri.id = err.rent_item_id
                 WHERE er2.customer_id = $customerId AND er2.rental_date BETWEEN '$from' AND '$to'";

    $extraRes = mysqli_fetch_assoc($db->readQuery($extraSql));
    $additional = (float) ($extraRes['tot_additional'] ?? 0);
    $refund = (float) ($extraRes['tot_refund'] ?? 0);

    $baseAmount = (float) ($row['base_amount'] ?? 0);
    $netAmount = $baseAmount + $additional - $refund;
    $grand_total_amount += $netAmount;
    $total_rentals += (int) $row['rent_count'];

    $data[] = [
        'customer_code' => $row['customer_code'],
        'customer_name' => $row['customer_name'],
        'mobile_number' => $row['mobile_number'],
        'rent_count' => (int) $row['rent_count'],
        'last_rental_date' => $row['last_rental_date'],
        'total_amount' => $netAmount
    ];
}
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Equipment Rent Frequency Report - <?php echo $from_date; ?> to <?php echo $to_date; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    
    <?php include 'main-css.php' ?>

    <style>
        @media print {
            .no-print { display: none !important; }
            body, html { width: 100%; margin: 0; padding: 0; background: #fff; }
            .container { width: 100% !important; max-width: 100% !important; padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; }
            @page { size: A4 portrait; margin: 10mm; }
        }
        body { background: #fff; font-size: 14px; }
        .invoice-title h3 { font-size: 20px; font-weight: bold; }
        .table th { background-color: #f8f9fa !important; color: #000; font-weight: 600; font-size: 13px; text-transform: uppercase; }
        .table td { vertical-align: middle; font-size: 13px; }
        .summary-box { background-color: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 20px; }
        .summary-item { margin-bottom: 5px; }
        .summary-item strong { color: #495057; }
    </style>
</head>

<body>

    <div class="container mt-4">
        <div class="d-flex justify-content-end mb-4 no-print gap-2">
            <button onclick="window.print()" class="btn btn-success"><i class="mdi mdi-printer me-1"></i> Print</button>
            <button onclick="window.close()" class="btn btn-secondary"><i class="mdi mdi-close me-1"></i> Close</button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="invoice-title">
                    <div class="row">
                        <div class="col-sm-7">
                            <h3 class="font-size-18 font-weight-bold"><?php echo $COMPANY_PROFILE_DETAILS->name; ?></h3>
                            <p class="mb-1"><?php echo $COMPANY_PROFILE_DETAILS->address; ?></p>
                            <p class="mb-1"><?php echo $COMPANY_PROFILE_DETAILS->email; ?></p>
                            <p class="mb-1">Phone: <?php echo $COMPANY_PROFILE_DETAILS->mobile_number_1; ?></p>
                        </div>
                        <div class="col-sm-5 text-sm-end">
                            <h3 class="font-size-16">RENT FREQUENCY REPORT</h3>
                            <p class="mb-1"><strong>Period:</strong> <?php echo $from_date; ?> to <?php echo $to_date; ?></p>
                            <p class="mb-1"><strong>Generated:</strong> <?php echo date('d M, Y h:i A'); ?></p>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="summary-box">
                    <div class="row">
                        <div class="col-4">
                            <div class="summary-item"><strong>Total Customers:</strong> <?php echo count($data); ?></div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="summary-item"><strong>Total Rentals:</strong> <?php echo $total_rentals; ?></div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="summary-item"><strong>Total Amount:</strong> Rs. <?php echo number_format($grand_total_amount, 2); ?></div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-centered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Customer</th>
                                <th>Mobile</th>
                                <th class="text-center">Rentals</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-center">Last Rental</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($data) > 0): ?>
                                <?php foreach ($data as $index => $row): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo $row['customer_code']; ?></strong><br>
                                            <?php echo $row['customer_name']; ?>
                                        </td>
                                        <td><?php echo $row['mobile_number']; ?></td>
                                        <td class="text-center fw-bold"><?php echo $row['rent_count']; ?></td>
                                        <td class="text-end"><?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td class="text-center"><?php echo $row['last_rental_date']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No records found for this period.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 mb-5 no-print">
                     <p class="text-muted text-center">End of Report</p>
                </div>

                <div class="mt-5 mb-2 print-only" style="display:none;">
                    <div class="row">
                        <div class="col-6 text-center">
                            <p>__________________________</p>
                            <p>Authorized Signature</p>
                        </div>
                        <div class="col-6 text-center">
                            <p>__________________________</p>
                            <p>Date</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <style>
        @media print {
            .print-only { display: block !important; }
        }
    </style>
</body>
</html>
