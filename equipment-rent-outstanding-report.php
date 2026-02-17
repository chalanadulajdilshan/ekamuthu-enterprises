<?php
include 'class/include.php';
include 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Rented Item Outstanding Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        .overdue-badge { background: #f8d7da; color: #b71c1c; }
        .table thead th { white-space: nowrap; }
        .summary-card { border-left: 4px solid #f46a6a; }
    </style>
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
                            <h4 class="mb-0">Rented Item Outstanding Report</h4>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="reportForm">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label for="customer_code" class="form-label">Customer</label>
                                            <div class="input-group">
                                                <input id="customer_code" name="customer_code" type="text" placeholder="Select Customer" class="form-control" readonly>
                                                <input type="hidden" id="customer_id" name="customer_id">
                                                <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#customerModal">
                                                    <i class="uil uil-search me-1"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="asOfDate" class="form-label">As of Date</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control date-picker" id="asOfDate" name="asOfDate">
                                                <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                            </div>
                                        </div>
                                        <div class="col-md-5 text-md-end">
                                            <button type="button" class="btn btn-primary me-1" id="searchBtn"><i class="mdi mdi-magnify me-1"></i>Search</button>
                                            <button type="button" class="btn btn-secondary" id="resetBtn"><i class="mdi mdi-refresh me-1"></i>Reset</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="row gy-3" id="summaryRow" style="display:none;">
                    <div class="col-md-4">
                        <div class="card summary-card h-100">
                            <div class="card-body">
                                <p class="text-muted mb-1">Projected Outstanding (based on overdue days)</p>
                                <h4 class="mb-0 text-danger" id="totalProjected">0.00</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card summary-card h-100 border-start border-warning">
                            <div class="card-body">
                                <p class="text-muted mb-1">Recorded Outstanding (from return settlements)</p>
                                <h4 class="mb-0 text-warning" id="totalRecorded">0.00</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0 h-100 d-flex align-items-center">
                            <div>
                                <strong>Tip:</strong> Use the Details button per bill to see outstanding breakdown and payment history.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="reportTable" class="table table-bordered table-striped dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Bill No</th>
                                                <th>Customer</th>
                                                <th>Item</th>
                                                <th>Rental Date</th>
                                                <th>Due Date</th>
                                                <th class="text-end">Pending Qty</th>
                                                <th class="text-end">Overdue Days</th>
                                                <th class="text-end">Daily Rate</th>
                                                <th class="text-end">Projected Outstanding</th>
                                                <th class="text-end">Recorded Outstanding</th>
                                                <th class="text-center">Details</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <tr><td colspan="11" class="text-center">Use filters and click Search</td></tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold bg-light">
                                                <th colspan="9" class="text-end">TOTAL (Projected / Recorded)</th>
                                                <th class="text-end text-danger" id="tblTotalProjected">0.00</th>
                                                <th class="text-end text-warning" id="tblTotalRecorded">0.00</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Modal -->
                <div class="modal fade" id="rentDetailsModal" tabindex="-1" aria-labelledby="rentDetailsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="rentDetailsModalLabel">Outstanding & Payments - <span id="detailsBillNo"></span></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Customer:</strong> <span id="detailsCustomer">-</span></p>
                                        <p class="mb-1"><strong>Projected Outstanding:</strong> Rs. <span id="detailsProjected">0.00</span></p>
                                        <p class="mb-1"><strong>Recorded Outstanding:</strong> Rs. <span id="detailsRecorded">0.00</span></p>
                                    </div>
                                </div>

                                <h6 class="fw-bold">Recorded Outstanding Breakdown</h6>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Return Date</th>
                                                <th>Item</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Additional Charge</th>
                                                <th class="text-end">Paid</th>
                                                <th class="text-end">Outstanding</th>
                                                <th>Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody id="outstandingDetailsBody">
                                            <tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <h6 class="fw-bold">Payment History</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Receipt No</th>
                                                <th>Method</th>
                                                <th class="text-end">Amount</th>
                                                <th>Reference</th>
                                            </tr>
                                        </thead>
                                        <tbody id="paymentHistoryBody">
                                            <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
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

            </div>
        </div>
        <?php include 'footer.php'; ?>
    </div>
</div>

<?php include 'customer-master-model.php'; ?>
<?php include 'main-js.php'; ?>
<script src="ajax/js/common.js"></script>
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="ajax/js/equipment-rent-outstanding-report.js?v=<?php echo time(); ?>"></script>
</body>
</html>
