<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$id = $_GET['id'] ?? null;
$REPAIR_JOB = new RepairJob($id);

if (!$REPAIR_JOB->id) {
    die("Repair Job not found!");
}

$items = $REPAIR_JOB->getItems(); // Fetch repair items
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Repair Job Bill - <?php echo $REPAIR_JOB->job_code; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    
    <!-- Using same CSS as invoice for consistency -->
    <?php include 'main-css.php' ?>

    <style>
        @media print {
            .no-print { display: none !important; }
            body, html { width: 100%; margin: 0; padding: 0; background: #fff; }
            .container { width: 100% !important; max-width: 100% !important; padding: 0 !important; }
            .card { border: none !important; shadow: none !important; }
            /* Hide URL printing in some browsers */
            @page { margin: 10mm; }
        }
        body { background: #fff; }
        .invoice-title h3 { font-size: 20px; font-weight: bold; }
        .table th { background-color: #f8f9fa !important; color: #000; font-weight: 600; }
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
        }
        .badge-pending { background-color: #dc3545 !important; color: white !important; } /* Red */
        .badge-in_progress { background-color: #ffc107 !important; color: black !important; } /* Yellow/Orange */
        .badge-completed { background-color: #28a745 !important; color: white !important; } /* Green */
        .badge-delivered { background-color: #0d6efd !important; color: white !important; } /* Blue */
        .badge-cannot_repair { background-color: #6c757d !important; color: white !important; } /* Grey */
        @media print {
            .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
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
                            <h3 class="font-size-16 font-weight-bold"><?php echo $COMPANY_PROFILE_DETAILS->name; ?></h3>
                            <p class="mb-1 text-muted"><?php echo $COMPANY_PROFILE_DETAILS->address; ?></p>
                            <p class="mb-1 text-muted"><?php echo $COMPANY_PROFILE_DETAILS->email; ?></p>
                            <p class="mb-1 text-muted">Phone: <?php echo $COMPANY_PROFILE_DETAILS->mobile_number_1; ?></p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h3 class="font-size-16">REPAIR JOB BILL</h3>
                            <p class="mb-1"><strong>Job Code:</strong> <?php echo $REPAIR_JOB->job_code; ?></p>
                            <p class="mb-1"><strong>Date:</strong> <?php echo date('d M, Y', strtotime($REPAIR_JOB->created_at)); ?></p>
                            <p class="mb-1"><strong>Status:</strong> 
                                <?php 
                                    $status_classes = [
                                        'pending' => 'badge-pending',
                                        'in_progress' => 'badge-in_progress',
                                        'completed' => 'badge-completed',
                                        'delivered' => 'badge-delivered',
                                        'cannot_repair' => 'badge-cannot_repair'
                                    ];
                                    $status_class = $status_classes[$REPAIR_JOB->job_status] ?? 'badge-secondary';
                                    $status_label = ucfirst(str_replace('_', ' ', $REPAIR_JOB->job_status));
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                            </p>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row">
                    <div class="col-sm-6">
                        <h5 class="font-size-14 mb-3">Customer Details:</h5>
                        <p class="mb-1"><strong>Name:</strong> <?php echo $REPAIR_JOB->customer_name; ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?php echo $REPAIR_JOB->customer_phone; ?></p>
                        <p class="mb-1"><strong>Address:</strong> <?php echo $REPAIR_JOB->customer_address; ?></p>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <?php if ($REPAIR_JOB->job_status != 'pending'): ?>
                        <h5 class="font-size-14 mb-3">Item Details:</h5>
                        <p class="mb-1"><strong>Machine/Item:</strong> <?php echo $REPAIR_JOB->machine_name; ?></p>
                        <?php if ($REPAIR_JOB->machine_code): ?>
                            <p class="mb-1"><strong>Code:</strong> <?php echo $REPAIR_JOB->machine_code; ?></p>
                        <?php endif; ?>
                        <p class="mb-1"><strong>Reason:</strong> <?php echo $REPAIR_JOB->technical_issue; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive mt-4">
                    <table class="table table-nowrap table-centered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 70px;">#</th>
                                <th>Description</th>
                                <th class="text-end" style="width: 120px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Spare parts details hidden as per request -->
                            
                            <?php if ($REPAIR_JOB->job_status == 'pending'): ?>
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <strong>Machine/Item:</strong> <?php echo $REPAIR_JOB->machine_name; ?> <br>
                                        <strong>Reason:</strong> <?php echo $REPAIR_JOB->technical_issue; ?>
                                    </td>
                                    <td class="text-end">-</td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td>1</td>
                                    <td>Repair Job Service Charge</td>
                                    <td class="text-end"><?php echo number_format($REPAIR_JOB->total_cost, 2); ?></td>
                                </tr>
                                
                                <tr class="bg-light">
                                    <td colspan="2" class="text-end fw-bold font-size-16">Total Amount</td>
                                    <td class="text-end fw-bold font-size-16"><?php echo number_format($REPAIR_JOB->total_cost, 2); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                 <div class="mt-5 mb-5">
                    <div class="row">
                        <div class="col-4 text-center">
                            <p>__________________________</p>
                            <p>Prepared By</p>
                        </div>
                         <div class="col-4 text-center">
                            <p>__________________________</p>
                            <p>Checked By</p>
                        </div>
                        <div class="col-4 text-center">
                            <p>__________________________</p>
                            <p>Customer Signature</p>
                        </div>
                    </div>
                </div>
                
                 <div class="footer text-center mt-5 no-print">
                    <p class="text-muted">Thank you for your business!</p>
                </div>

            </div>
        </div>
    </div>
    
    <script>
        // Auto print on load if requested (optional)
        // window.print();
    </script>
</body>
</html>
