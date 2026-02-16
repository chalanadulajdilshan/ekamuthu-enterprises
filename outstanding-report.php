<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Outstanding Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body data-layout="horizontal" data-topbar="colored">

    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Outstanding Report</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Filter Report</h5>
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Customer</label>
                                            <div class="input-group">
                                                <input id="customer_code" name="customer_code" type="text" placeholder="Select Customer (Optional)" class="form-control" readonly>
                                                <input type="hidden" id="customer_id" name="customer_id">
                                                <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#customerModal">
                                                    <i class="uil uil-search me-1"></i>
                                                </button>
                                                <button class="btn btn-secondary" type="button" id="clearCustomer">
                                                    <i class="uil uil-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button id="generateBtn" class="btn btn-primary"><i class="uil uil-file-alt"></i> Generate Report</button>
                                            <button id="printBtn" class="btn btn-success ms-2"><i class="uil uil-print"></i> Print Report</button>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="reportTable">
                                            <thead>
                                                <tr>
                                                    <th>Customer Name</th>
                                                    <th class="text-end">Total Rent Amount</th>
                                                    <th class="text-end">Total Paid Amount</th>
                                                    <th class="text-end">Balance (Outstanding)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data loaded via AJAX -->
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th class="text-end">Total:</th>
                                                    <th class="text-end" id="totalRent">0.00</th>
                                                    <th class="text-end" id="totalPaid">0.00</th>
                                                    <th class="text-end" id="totalBalance">0.00</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Customer Modal -->
    <?php include 'customer-master-model.php'; ?>

    <?php include 'main-js.php'; ?>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="ajax/js/common.js"></script>
    
    <!-- Page Specific JS -->
    <script src="ajax/js/outstanding-report.js"></script>

</body>
</html>
