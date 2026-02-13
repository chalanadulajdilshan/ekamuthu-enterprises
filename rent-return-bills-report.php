<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Rent & Return Bills Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <style>
        /* Custom styling for the report */
        .report-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .report-info-card h5 {
            margin-bottom: 10px;
            font-weight: 600;
        }

        .report-info-card .report-details {
            font-size: 14px;
            opacity: 0.9;
        }

        .bill-type-badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .badge-rent {
            background-color: #28a745;
            color: white;
        }

        .badge-return {
            background-color: #dc3545;
            color: white;
        }

        .badge-issue-return {
            background-color: #ffc107;
            color: #000;
        }

        #totalAmount {
            background-color: #667eea !important;
            color: #ffffff !important;
            font-weight: bold;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Begin page -->
    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Rent & Return Bills Report</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="reportForm">
                                        <!-- Date Filter Section -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="card-body p-3">
                                                    <div class="row g-3 align-items-end">
                                                        <div class="col-md-3">
                                                            <label for="fromDate" class="form-label fw-semibold text-muted mb-2">From Date</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control date-picker" id="fromDate" name="fromDate" placeholder="Select start date">
                                                                <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="toDate" class="form-label fw-semibold text-muted mb-2">To Date</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control date-picker" id="toDate" name="toDate" placeholder="Select end date">
                                                                <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="billType" class="form-label fw-semibold text-muted mb-2">Bill Type</label>
                                                            <select class="form-select" id="billType" name="billType">
                                                                <option value="all">All Bills</option>
                                                                <option value="rent">Rent Bills Only</option>
                                                                <option value="return">Return Bills Only</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="billNo" class="form-label fw-semibold text-muted mb-2">Bill No</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control" id="billNo" name="billNo" placeholder="Enter bill number">
                                                                <span class="input-group-text bg-light"><i class="mdi mdi-receipt text-primary"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-center mt-3 g-3">
                                                        <div class="col-md-6">
                                                            <small class="text-muted"><i class="mdi mdi-information-outline me-1"></i> Select date range or enter a bill number to filter rent and return bills</small>
                                                        </div>
                                                        <div class="col-md-6 d-flex gap-2 justify-content-md-end">
                                                            <button type="button" class="btn btn-outline-primary btn-sm" id="setToday">
                                                                <i class="mdi mdi-calendar-today me-1"></i> Today
                                                            </button>
                                                            <button id="exportToPdf" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-file-pdf me-1"></i> Export PDF
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-primary me-1" id="searchBtn">
                                                    <i class="mdi mdi-magnify me-1"></i> Search
                                                </button>
                                                <button type="button" class="btn btn-secondary" id="resetBtn">
                                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Info Card (Hidden by default) -->
                    <div class="row" id="reportInfoSection" style="display: none;">
                        <div class="col-12">
                            <div class="report-info-card">
                                <h5 id="reportTitle">Rent & Return Bills Report</h5>
                                <div class="report-details">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Date Range:</strong> <span id="dateRangeDisplay">-</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Bills:</strong> <span id="totalBills">0</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Rent Bills:</strong> <span id="totalRentBills">0</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Return Bills:</strong> <span id="totalReturnBills">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <table id="reportTable" class="table table-bordered dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 30px;"></th>
                                                <th>Bill Type</th>
                                                <th>Bill No</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Tel</th>
                                                <th>Address</th>
                                                <th>NIC</th>
                                                <th>Day Count</th>
                                                <th>Rent Date</th>
                                                <th>Return Date</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Deposit</th>
                                                <th class="text-end">Profit</th>
                                                <th class="text-end">Extra Amount</th>
                                                <th class="text-end">Refund / Cash In</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Data will be loaded via AJAX -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="11" class="text-end fw-bold">Totals</td>
                                                <td id="totalQty" class="text-end fw-bold">0</td>
                                                <td id="totalDeposit" class="text-end fw-bold">0.00</td>
                                                <td id="totalAmount" class="text-end fw-bold">0.00</td>
                                                <td id="totalExtraAmount" class="text-end fw-bold">0.00</td>
                                                <td id="totalProfit" class="text-end fw-bold">0.00</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?php include 'footer.php'; ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <?php include 'main-js.php'; ?>

    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="assets/libs/daterangepicker/daterangepicker.min.js"></script>
    <!-- jQuery UI Datepicker -->
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <!-- Custom JS for Rent & Return Bills Report -->
    <script src="ajax/js/rent-return-bills-report.js"></script>


</body>

</html>
