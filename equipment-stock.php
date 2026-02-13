<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$EQUIPMENT = new Equipment(NULL);
?>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Equipment Stock |
        <?php echo $COMPANY_PROFILE_DETAILS->name ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <style>
        .highlight-text {
            background-color: yellow;
            font-weight: bold;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Equipment Stock</h4>
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Equipment Stock</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <!-- Summary Cards -->
                    <div class="row mb-4" id="summary-cards">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Equipment</h5>
                                    <h3 class="card-text" id="total-equipment">Loading...</h3>
                                </div>
                            </div>
                        </div>


                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-end mb-3">
                                        <div class="form-check form-switch form-switch-lg" dir="ltr">
                                            <input class="form-check-input" type="checkbox" id="searchSubOnly">
                                            <label class="form-check-label fw-bold ps-2 mb-0 align-middle" for="searchSubOnly">Search Sub Equipment Only</label>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered dt-responsive nowrap"
                                            id="equipmentStockTable"
                                            style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:30px;"></th>
                                                    <th>Code</th>
                                                    <th>Item Name</th>
                                                    <th>Category</th>
                                                    <th>Serial Number</th>
                                                    <th>Size</th>
                                                    <th>Value</th>


                                                    <th>Quantity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded by DataTables -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- Rent Details Modal (from Equipment Stock) -->
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
                                <tr><td colspan="8" class="text-center text-muted">No items</td></tr>
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
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Datatables -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <!-- Equipment Stock JS -->
    <script src="ajax/js/equipment-stock.js"></script>

</body>

</html>