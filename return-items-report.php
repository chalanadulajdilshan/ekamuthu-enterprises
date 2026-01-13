<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Return Items Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <style>
        /* Return report styling */
        .return-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .return-info-card h5 {
            margin-bottom: 10px;
            font-weight: 600;
        }

        .return-info-card .return-details {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Return type badges */
        .return-type-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
        }

        /* Total return amount styling */
        #totalReturnAmount {
            background-color: #dc3545 !important;
            color: #ffffff !important;
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
                                <h4 class="mb-0">Return Items Report</h4>
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
                                                        <div class="col-md-4">
                                                            <label for="fromDate" class="form-label fw-semibold text-muted mb-2">From Date</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control date-picker" id="fromDate" name="fromDate" placeholder="Select start date">
                                                                <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="toDate" class="form-label fw-semibold text-muted mb-2">To Date</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control date-picker" id="toDate" name="toDate" placeholder="Select end date">
                                                                <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 d-flex gap-3">
                                                            <button type="button" class="btn btn-outline-primary btn-sm" id="setToday">
                                                                <i class="mdi mdi-calendar-today me-1"></i> Today
                                                            </button>
                                                            <button id="exportToPdf" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-file-pdf me-1"></i> Export PDF
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <small class="text-muted"><i class="mdi mdi-information-outline me-1"></i> Select date range to view return items report</small>
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

                    <!-- Return Info Card (Hidden by default) -->
                    <div class="row" id="returnInfoSection" style="display: none;">
                        <div class="col-12">
                            <div class="return-info-card">
                                <h5 id="reportTitle">Return Items Report</h5>
                                <div class="return-details">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Date Range:</strong> <span id="dateRangeDisplay">-</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Returns:</strong> <span id="totalReturns">0</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Items:</strong> <span id="totalItems">0</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Amount:</strong> <span id="totalAmount">0.00</span>
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
                                                <th>Return No</th>
                                                <th>Return Date</th>
                                                <th>Invoice No</th>
                                                <th>Customer</th>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th class="text-end">Return Qty</th>
                                                <th class="text-end">Unit Price</th>
                                                <th class="text-end">Total Amount</th>
                                                <th>Return Reason</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Data will be loaded via AJAX -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="6" class="text-end">Total:</th>
                                                <td id="totalReturnQty" class="text-end">0</td>
                                                <td class="text-end">-</td>
                                                <td id="totalReturnAmount" class="text-end">0.00</td>
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

    <!-- Custom JS for Return Items Report -->
    <script src="ajax/js/return-items-report.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize the datepicker with proper configuration
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '1900:2099',
                showButtonPanel: true,
                showOn: 'focus',
                showAnim: 'fadeIn',
                buttonImageOnly: false
            });

            // Set to today's date and first day of month when clicking the Today button
            $('#setToday').click(function(e) {
                e.preventDefault();
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

                $('#toDate').datepicker('setDate', today);
                $('#fromDate').datepicker('setDate', firstDay);
            });

            // Reset form
            $('#resetBtn').click(function() {
                $('#fromDate').val('');
                $('#toDate').val('');
                $('#returnInfoSection').hide();
                // Clear the table if needed
                $('#reportTableBody').empty();
                $('.text-end').text('0.00');
                $('#totalReturnQty').text('0');
                $('#totalReturns').text('0');
                $('#totalItems').text('0');
            });

            // Validate date range
            $('.date-picker').change(function() {
                const fromDate = $('#fromDate').datepicker('getDate');
                const toDate = $('#toDate').datepicker('getDate');

                if (fromDate && toDate && fromDate > toDate) {
                    alert('From date cannot be after To date');
                    $(this).val('');
                }
            });

            // Initialize with current month as default
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            $('#fromDate').datepicker('setDate', firstDay);
            $('#toDate').datepicker('setDate', today);
        });
    </script>

</body>

</html>
