<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$employee_id = !empty($_GET['employee_id']) ? (int) $_GET['employee_id'] : null;

$employee_name = "All Employees";
if ($employee_id) {
    $EMP = new EmployeeMaster($employee_id);
    $employee_name = $EMP->name;
}

$data = TransportDetail::getByDateRange($from_date, $to_date, $employee_id);

$total_deliver = 0;
$total_pickup = 0;
$total_amount = 0;
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Transport Summary - <?php echo $from_date; ?> to <?php echo $to_date; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    
    <!-- Using same CSS as invoice for consistency -->
    <?php include 'main-css.php' ?>

    <style>
        @media print {
            .no-print { display: none !important; }
            body, html { width: 100%; margin: 0; padding: 0; background: #fff; }
            .container { width: 100% !important; max-width: 100% !important; padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; }
            @page { margin: 10mm; size: landscape; }
        }
        body { background: #fff; }
        .invoice-title h3 { font-size: 22px; font-weight: bold; }
        .table th, .table td { border: 1px solid #000 !important; }
        .table th { background-color: #f8f9fa !important; color: #000; font-weight: 600; font-size: 13px; }
        .table td { font-size: 12px; }
        .report-header-title { font-size: 20px; font-weight: bold; }
    </style>
</head>

<body>

    <div class="container mt-4">
        <!-- Print / Close Buttons -->
        <div class="d-flex justify-content-end mb-4 no-print gap-2">
            <button onclick="window.print()" class="btn btn-success"><i class="mdi mdi-printer me-1"></i> Print</button>
            <button onclick="window.close()" class="btn btn-secondary"><i class="mdi mdi-close me-1"></i> Close</button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="invoice-title">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 style="font-size: 24px;"><?php echo $COMPANY_PROFILE_DETAILS->name; ?></h3>
                            <p class="mb-1 text-muted"><?php echo $COMPANY_PROFILE_DETAILS->address; ?></p>
                            <p class="mb-1 text-muted"><?php echo $COMPANY_PROFILE_DETAILS->email; ?></p>
                            <p class="mb-1 text-muted">Phone: <?php echo $COMPANY_PROFILE_DETAILS->mobile_number_1; ?></p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h3 class="report-header-title">TRANSPORT SUMMARY REPORT</h3>
                            <h4 class="font-size-16">ප්‍රවාහන සාරාංශ වාර්තාව</h4>
                            <p class="mb-1"><strong>From - සිට:</strong> <?php echo date('d M, Y', strtotime($from_date)); ?></p>
                            <p class="mb-1"><strong>To - දක්වා:</strong> <?php echo date('d M, Y', strtotime($to_date)); ?></p>
                            <?php if ($employee_id): ?>
                                <p class="mb-1"><strong>Employee - සේවකයා:</strong> <?php echo $employee_name; ?></p>
                            <?php endif; ?>
                            <p class="mb-1"><strong>Generated - සැකසූ දිනය:</strong> <?php echo date('d M, Y h:i A'); ?></p>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-centered mb-0">
                        <thead>
                            <tr>
                                <th>Date - දිනය</th>
                                <th>Bill No - බිල් අංකය</th>
                                <th>Vehicle - වාහනය</th>
                                <th>Employee - සේවකයා</th>
                                <th>Start - ආරම්භය</th>
                                <th>End - අවසානය</th>
                                <th class="text-end">Deliver - බෙදාහැරීම</th>
                                <th class="text-end">Pickup - පටවාගැනීම</th>
                                <th class="text-end">Total - එකතුව</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($data) > 0): ?>
                                <?php foreach ($data as $row): 
                                    $deliver = floatval($row['deliver_amount']);
                                    $pickup = floatval($row['pickup_amount']);
                                    $row_total = floatval($row['total_amount']);
                                    
                                    $total_deliver += $deliver;
                                    $total_pickup += $pickup;
                                    $total_amount += $row_total;
                                ?>
                                    <tr>
                                        <td><?php echo $row['transport_date']; ?></td>
                                        <td><?php echo $row['bill_number'] ?? '-'; ?></td>
                                        <td><?php echo $row['vehicle_no'] ? $row['vehicle_no'] . ' (' . $row['vehicle_brand'] . ')' : '-'; ?></td>
                                        <td><?php echo $row['employee_name'] ?? '-'; ?></td>
                                        <td><?php echo $row['start_location'] ?? '-'; ?></td>
                                        <td><?php echo $row['end_location'] ?? '-'; ?></td>
                                        <td class="text-end"><?php echo number_format($deliver, 2); ?></td>
                                        <td class="text-end"><?php echo number_format($pickup, 2); ?></td>
                                        <td class="text-end"><?php echo number_format($row_total, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="bg-light fw-bold">
                                    <td colspan="6" class="text-end font-size-16">Totals - එකතුව :</td>
                                    <td class="text-end font-size-14"><?php echo number_format($total_deliver, 2); ?></td>
                                    <td class="text-end font-size-14"><?php echo number_format($total_pickup, 2); ?></td>
                                    <td class="text-end font-size-16"><?php echo number_format($total_amount, 2); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No transport details found for this date range.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                 <div class="mt-5 mb-5">
                    <div class="row">
                        <div class="col-4 text-center">
                            <p>__________________________</p>
                            <p>Prepared By - සකස් කළේ</p>
                        </div>
                         <div class="col-4 text-center">
                            <p>__________________________</p>
                            <p>Checked By - පරීක්ෂා කළේ</p>
                        </div>
                        <div class="col-4 text-center">
                            <p>__________________________</p>
                            <p>Authorized By - අනුමත කළේ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto print on load
        window.onload = function() {
            // window.print();
        };
    </script>
</body>
</html>
