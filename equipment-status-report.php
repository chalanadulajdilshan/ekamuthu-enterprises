<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
?>

<head>
    <meta charset="utf-8" />
    <title>Equipment Status Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'main-css.php' ?>
    <link href="assets/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
</head>

<body data-layout="horizontal" data-topbar="colored">

    <div id="layout-wrapper">
        <?php include 'navigation.php' ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Equipment Status Report</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Filter by Status</h5>
                                    <div class="row align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" id="status_filter">
                                                <option value="">All</option>
                                                <option value="available">Available</option>
                                                <option value="rented">Rented</option>
                                                <option value="repair">Repair</option>
                                                <option value="damage">Damaged</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Date From</label>
                                            <input type="date" class="form-control" id="from_date" value="<?php echo date('Y-m-01'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Date To</label>
                                            <input type="date" class="form-control" id="to_date" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" id="btn-filter" class="btn btn-primary w-100">
                                                <i class="uil uil-filter me-1"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2 col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Total Equipment</h6>
                                    <h4 class="mb-0" id="count-total">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <div class="card border-success border-start border-3">
                                <div class="card-body">
                                    <h6 class="text-success mb-2">Available</h6>
                                    <h4 class="mb-0" id="count-available">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card border-primary border-start border-3">
                                <div class="card-body">
                                    <h6 class="text-primary mb-2">Rented</h6>
                                    <h4 class="mb-0" id="count-rented">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <div class="card border-danger border-start border-3">
                                <div class="card-body">
                                    <h6 class="text-danger mb-2">Damaged</h6>
                                    <h4 class="mb-0" id="count-damaged">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card border-warning border-start border-3">
                                <div class="card-body">
                                    <h6 class="text-warning mb-2">Repair</h6>
                                    <h4 class="mb-0" id="count-repair">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Content -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%" id="status_report_table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Code</th>
                                                    <th>Item Name</th>
                                                    <th>Category</th>
                                                    <th>Department</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Cur. Qty</th>
                                                    <th class="text-end">Rented (Period)</th>
                                                    <th class="text-end">Returned (Period)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

    <!-- Rent Details Modal -->
    <div class="modal fade" id="rentDetailsModal" tabindex="-1" aria-labelledby="rentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rentDetailsModalLabel">Rent Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Bill Number</label>
                            <div id="rd-bill" class="fw-bold text-dark">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Customer</label>
                            <div id="rd-customer" class="fw-bold text-dark">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Status</label>
                            <div id="rd-status" class="fw-bold text-dark">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Rental Date</label>
                            <div id="rd-rental-date" class="text-dark">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Received Date</label>
                            <div id="rd-received-date" class="text-dark">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Payment Method</label>
                            <div id="rd-payment" class="text-dark">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Transport Cost</label>
                            <div id="rd-transport" class="text-dark">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Deposit Total</label>
                            <div id="rd-deposit" class="text-dark">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted mb-1">Refund Balance</label>
                            <div id="rd-refund" class="text-dark">-</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted mb-1">Remark</label>
                            <div id="rd-remark" class="text-dark">-</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="rentItemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Equipment</th>
                                    <th>Sub Code</th>
                                    <th>Qty</th>
                                    <th>Returned</th>
                                    <th>Type</th>
                                    <th>Duration</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="9" class="text-center text-muted">No items</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="rd-open-full" class="btn btn-primary" target="_blank">View Full Rent</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Rented Invoices Modal (List) -->
    <div class="modal fade" id="rentInvoicesModal" tabindex="-1" aria-labelledby="rentInvoicesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="rentInvoicesModalLabel">Rented Invoices</h5>
                        <p class="text-muted mb-0" id="ri-equipment-name"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0" id="rentInvoicesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Bill No</th>
                                    <th>Customer</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Returned</th>
                                    <th>Rented Date</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Repair Details Modal -->
    <div class="modal fade" id="repairDetailsModal" tabindex="-1" aria-labelledby="repairDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="repairDetailsModalLabel">Repair Job Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label text-muted mb-1">Job Code</label>
                            <div id="rp-job-code" class="fw-bold text-dark">-</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted mb-1">Status</label>
                            <div id="rp-status" class="fw-bold text-dark">-</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted mb-1">Machine</label>
                            <div id="rp-machine" class="text-dark">-</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted mb-1">Breakdown Date</label>
                            <div id="rp-date" class="text-dark">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted mb-1">Technical Issue</label>
                            <div id="rp-issue" class="text-dark">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted mb-1">Remark</label>
                            <div id="rp-remark" class="text-dark">-</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label text-muted mb-1">Is Outsource?</label>
                            <div id="rp-is-outsource" class="text-dark">-</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted mb-1">Outsource Name</label>
                            <div id="rp-outsource-name" class="text-dark">-</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted mb-1">Repair Charge</label>
                            <div id="rp-charge" class="fw-bold text-dark">-</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted mb-1">Total Cost</label>
                            <div id="rp-total-cost" class="fw-bold text-primary">-</div>
                        </div>
                    </div>

                    <h6 class="font-size-14 mb-3">Repair Items / Parts Used</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="repairItemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center text-muted">No items recorded</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="rp-open-full" class="btn btn-primary" target="_blank">View Full Repair Job</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php' ?>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Datatables -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script> 
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <?php include 'main-js.php' ?>
    
    <script src="ajax/js/equipment-status-report.js?v=<?php echo time(); ?>"></script>
</body>
</html>
