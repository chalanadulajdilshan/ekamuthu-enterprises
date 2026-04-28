<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$id = $_GET['id'] ?? '';

$TRIP = new TripManagement($id);

if (!$TRIP->id) {
    die('Trip not found');
}

// Get related data
$VEHICLE = new Vehicle($TRIP->vehicle_id);
$DRIVER = new EmployeeMaster($TRIP->employee_id);
$CUSTOMER = null;
$BILL = null;

if ($TRIP->trip_category === 'customer') {
    if ($TRIP->invoice_type === 'invoice' && $TRIP->bill_id) {
        $BILL = new EquipmentRent($TRIP->bill_id);
        $CUSTOMER = new CustomerMaster($BILL->customer_id);
    } elseif ($TRIP->invoice_type === 'non_invoice' && $TRIP->customer_id) {
        $CUSTOMER = new CustomerMaster($TRIP->customer_id);
    }
}

$customer_display = '-';
if ($CUSTOMER) {
    $customer_display = ($CUSTOMER->code ?: '') . ' - ' . ($CUSTOMER->name ?: '');
}

?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Trip Print - <?php echo $TRIP->trip_number; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    
    <?php include 'main-css.php' ?>

    <style>
        @media print {
            .no-print { display: none !important; }
            body, html { width: 100%; margin: 0; padding: 0; background: #fff; overflow: hidden; }
            .container { width: 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
            .card { border: none !important; box-shadow: none !important; margin: 0 !important; }
            .card-body { padding: 5mm !important; }
            @page { margin: 5mm; size: auto; }
            hr { margin: 5px 0 !important; }
        }
        body { background: #fff; font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }
        .invoice-title h3 { font-size: 18px; font-weight: bold; }
        .table th, .table td { border: 1px solid #000 !important; padding: 4px 8px !important; }
        .table th { background-color: #f8f9fa !important; color: #000; font-weight: 600; font-size: 12px; }
        .table td { font-size: 12px; }
        .report-header-title { font-size: 16px; font-weight: bold; text-decoration: underline; }
        .info-row { margin-bottom: 4px; display: flex; }
        .info-label { font-weight: bold; width: 130px; font-size: 12px; }
        .info-value { border-bottom: 1px dotted #000; flex-grow: 1; font-size: 12px; }
    </style>
</head>

<body>

    <div class="container mt-4">
        <!-- Print / Close Buttons -->
        <div class="d-flex justify-content-end mb-4 no-print gap-2">
            <button onclick="window.print()" class="btn btn-success"><i class="mdi mdi-printer me-1"></i> Print</button>
            <button onclick="window.close()" class="btn btn-secondary"><i class="mdi mdi-close me-1"></i> Close</button>
        </div>

        <div class="card border">
            <div class="card-body">
                <div class="invoice-title">
                    <div class="row">
                        <div class="col-sm-6">
                            <?php if ($BILL && $BILL->bank_slip): ?>
                                <div class="mb-2">
                                    <img src="<?php echo $BILL->bank_slip; ?>" alt="Rent Bill" style="max-height: 100px; max-width: 100%; border: 1px solid #ddd; padding: 2px;">
                                </div>
                            <?php endif; ?>
                            <h3 class="mb-1"><?php echo $COMPANY_PROFILE_DETAILS->name; ?></h3>
                            <p class="mb-0 small text-muted"><?php echo $COMPANY_PROFILE_DETAILS->address; ?></p>
                            <p class="mb-0 small text-muted">Phone: <?php echo $COMPANY_PROFILE_DETAILS->mobile_number_1; ?></p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h3 class="report-header-title">TRIP DETAILS</h3>
                            <p class="mb-1"><strong>Trip No:</strong> <?php echo $TRIP->trip_number; ?></p>
                            <p class="mb-1"><strong>Date:</strong> <?php echo date('d M, Y', strtotime($TRIP->transport_date)); ?></p>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row">
                    <div class="col-6">
                        <div class="info-row">
                            <div class="info-label">Category:</div>
                            <div class="info-value"><?php echo ucfirst($TRIP->trip_category); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Customer:</div>
                            <div class="info-value"><?php echo $customer_display; ?></div>
                        </div>
                        <?php if ($BILL): ?>
                        <div class="info-row">
                            <div class="info-label">Bill No:</div>
                            <div class="info-value"><?php echo $BILL->bill_number; ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6">
                        <div class="info-row">
                            <div class="info-label">Vehicle:</div>
                            <div class="info-value"><?php echo ($VEHICLE->vehicle_no ?: '-') . ' (' . ($VEHICLE->brand ?: '') . ')'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Driver:</div>
                            <div class="info-value"><?php echo $DRIVER->name ?: '-'; ?></div>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-6">
                        <div class="info-row">
                            <div class="info-label">Start Location:</div>
                            <div class="info-value"><?php echo $TRIP->start_location ?: '-'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">End Location:</div>
                            <div class="info-value"><?php echo $TRIP->end_location ?: '-'; ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-row">
                            <div class="info-label">Start Meter:</div>
                            <div class="info-value"><?php echo number_format($TRIP->start_meter, 2); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">End Meter:</div>
                            <div class="info-value"><?php echo $TRIP->end_meter ? number_format($TRIP->end_meter, 2) : '-'; ?></div>
                        </div>
                        <?php if ($TRIP->end_meter): ?>
                        <div class="info-row">
                            <div class="info-label">Distance (KM):</div>
                            <div class="info-value fw-bold"><?php echo number_format($TRIP->end_meter - $TRIP->start_meter, 2); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <table class="table table-bordered mt-2">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Transport Amount</td>
                            <td class="text-end"><?php echo number_format($TRIP->transport_amount, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Transport Cost (Paid/Payable)</td>
                            <td class="text-end"><?php echo number_format($TRIP->pay_amount, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Toll Payments</td>
                            <td class="text-end"><?php echo number_format($TRIP->toll, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Helper Payments</td>
                            <td class="text-end"><?php echo number_format($TRIP->helper_payment, 2); ?></td>
                        </tr>
                        <tr class="fw-bold bg-light">
                            <td>Total Trip Cost</td>
                            <td class="text-end"><?php echo number_format($TRIP->total_cost, 2); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="row mt-2">
                    <div class="col-12">
                        <p><strong>Remark:</strong> <?php echo $TRIP->remark ?: '-'; ?></p>
                    </div>
                </div>

                <div class="mt-4 mb-1">
                    <div class="row">
                        <div class="col-6 text-center">
                            <p class="mb-0">__________________________</p>
                            <p>Driver Signature</p>
                        </div>
                        <div class="col-6 text-center">
                            <p class="mb-0">__________________________</p>
                            <p>Authorized Signature</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            // window.print();
        };
    </script>
</body>
</html>
