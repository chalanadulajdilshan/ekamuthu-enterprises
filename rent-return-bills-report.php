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

        /* Modern DataTable Header */
        #reportTable thead th {
            background-color: #4e73df;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
            border: 1px solid rgba(255, 255, 255, 0.1);
            vertical-align: middle;
            padding: 15px 10px;
            white-space: nowrap;
        }

        #reportTable thead .sorting:before, 
        #reportTable thead .sorting:after,
        #reportTable thead .sorting_asc:before,
        #reportTable thead .sorting_asc:after,
        #reportTable thead .sorting_desc:before,
        #reportTable thead .sorting_desc:after {
            color: #ffffff !important;
            opacity: 0.5;
        }

        #reportTable thead .sorting_asc:before,
        #reportTable thead .sorting_desc:after {
            opacity: 1;
        }

        #reportTable tbody td {
            vertical-align: middle;
            font-size: 13.5px;
            color: #444;
            padding: 10px;
        }

        #reportTable tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.03);
            transition: background-color 0.2s ease;
        }

        .bill-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }

        .badge-rent {
            background-color: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .badge-return {
            background-color: #f1f8e9;
            color: #388e3c;
            border: 1px solid #dcedc8;
        }

        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 15px;
            border: 1px solid #d1d3e2;
        }

        .table-responsive {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Page Preloader -->
    <div id="page-preloader" class="preloader full-preloader">
        <div class="preloader-container">
            <div class="preloader-animation"></div>
        </div>
    </div>

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
                                    <form id="reportForm">
                                        <!-- Date Filter Section -->
                                        <div class="row g-3 align-items-end mb-4">
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
                                            <div class="col-md-2">
                                                <label for="billType" class="form-label fw-semibold text-muted mb-2">Bill Type</label>
                                                <select class="form-select" id="billType" name="billType">
                                                    <option value="all">All Bills</option>
                                                    <option value="rent">Rent Bills Only</option>
                                                    <option value="return">Return Bills Only</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="rentType" class="form-label fw-semibold text-muted mb-2">Rent Type</label>
                                                <select class="form-select" id="rentType" name="rentType">
                                                    <option value="all">All Rent Types</option>
                                                    <option value="day">Daily Rent</option>
                                                    <option value="month">Monthly Rent</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="d-flex justify-content-between align-items-end mb-2">
                                                    <label for="billNo" id="billNoLabel" class="form-label fw-semibold text-muted mb-0">Search Bill No</label>
                                                    <div class="form-check form-switch mb-0" style="padding-left: 2.5em;">
                                                        <input class="form-check-input" type="checkbox" id="searchItemsOnly" name="searchItemsOnly" style="cursor: pointer; transform: scale(0.9);">
                                                        <label class="form-check-label text-muted" for="searchItemsOnly" style="font-size: 0.85rem; cursor: pointer; user-select: none;">Search Items Only</label>
                                                    </div>
                                                </div>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="billNo" name="billNo" placeholder="Enter bill number">
                                                    <span class="input-group-text bg-light" id="billNoIcon"><i class="mdi mdi-receipt text-primary"></i></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row align-items-center mt-3 g-3">
                                            <div class="col-12 d-flex justify-content-between">
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-primary btn-sm px-4" id="searchBtn">
                                                        <i class="mdi mdi-magnify me-1"></i> Search
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm px-4" id="resetBtn">
                                                        <i class="mdi mdi-refresh me-1"></i> Reset
                                                    </button>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-outline-primary btn-sm px-3" id="setToday">
                                                        <i class="mdi mdi-calendar-today me-1"></i> Today
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm px-3" id="exportToPdf">
                                                        <i class="fas fa-file-pdf me-1"></i> Export PDF
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="reportTable" class="table table-bordered table-hover w-100">
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
                                                <th>Payment Type</th>
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
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                        </table>
                                    </div>
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

    <!-- jQuery UI Datepicker -->
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <!-- Custom JS for Rent & Return Bills Report -->
    <script src="ajax/js/rent-return-bills-report.js"></script>


</body>

</html>
