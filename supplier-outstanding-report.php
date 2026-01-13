<?php 
include 'class/include.php'; 
include 'auth.php'; 
?>  

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Supplier Outstanding Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />

    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">

    <style>
        /* Target only the Payable Outstanding column */
        #reportTable thead th.outstanding-column,
        #reportTable tbody td.outstanding-column {
            background-color: #ffebee !important;
        }

        /* Style for total outstanding cell */
        #totalOutstanding {
            background-color: #eb4034 !important;
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
                                <h4 class="mb-0">Supplier Outstanding Report</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <!-- Filter Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="reportForm">
                                        <div class="row">
                                            <!-- Supplier Filter -->
                                            <div class="col-md-4">
                                                <label for="supplier_code" class="form-label">Supplier</label>
                                                <div class="input-group mb-3">
                                                    <input id="code" name="supplier_code" type="text"
                                                        placeholder="Select Supplier" class="form-control" readonly>
                                                    <input type="hidden" id="customer_id" name="customer_id">
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#AllSupplierModal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Date Filter -->
                                            <div class="col-md-6">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <label for="fromDate" class="form-label">From Date</label>
                                                        <div class="input-group" id="datepicker1">
                                                            <input type="text" class="form-control date-picker" id="fromDate" name="fromDate">
                                                            <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="toDate" class="form-label">To Date</label>
                                                        <div class="input-group" id="datepicker2">
                                                            <input type="text" class="form-control date-picker" id="toDate" name="toDate">
                                                            <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2 d-flex align-items-end">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" id="setToday">Today</button>
                                                    </div>
                                                </div>
                                                <small class="text-muted">Leave dates empty to show all records</small>
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

                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <table id="reportTable" class="table table-bordered dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>GRN No</th>
                                                <th>Date</th>
                                                <th>Supplier</th>
                                                <th class="text-end">GRN Amount</th>
                                                <th class="text-end">Paid Amount</th>
                                                <th class="text-end outstanding-column">Payable Outstanding</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Data will be loaded via AJAX -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <td id="totalInvoice" class="text-danger text-end">0.00</td>
                                                <td id="totalPaid" class="text-danger text-end">0.00</td>
                                                <td id="totalOutstanding" class="text-danger text-end outstanding-column">0.00</td>
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

    <?php include 'supplier-master-model.php'; ?>
    <?php include 'main-js.php'; ?>

    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="assets/libs/daterangepicker/daterangepicker.min.js"></script>

    <!-- jQuery UI Datepicker -->
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <!-- Custom JS for Supplier Outstanding Report -->
    <script src="ajax/js/common.js"></script>
    <script src="ajax/js/supplier-outstanding-report.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize datepicker
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '1900:2099',
                showButtonPanel: true,
                showOn: 'focus',
                showAnim: 'fadeIn'
            });

            // Set default date range (current month)
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            $('#fromDate').datepicker('setDate', firstDay);
            $('#toDate').datepicker('setDate', today);

            // "Today" button action
            $('#setToday').click(function (e) {
                e.preventDefault();
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                $('#toDate').datepicker('setDate', today);
                $('#fromDate').datepicker('setDate', firstDay);
            });

            // Reset form
            $('#resetBtn').click(function () {
                $('#supplier_id').val('');
                $('#code').val('');
                $('#fromDate').val('');
                $('#toDate').val('');
                $('#reportTableBody').empty();
                $('.text-danger').text('0.00');
            });

            // Validate date range
            $('.date-picker').change(function () {
                const fromDate = $('#fromDate').datepicker('getDate');
                const toDate = $('#toDate').datepicker('getDate');
                if (fromDate && toDate && fromDate > toDate) {
                    alert('From date cannot be after To date');
                    $(this).val('');
                }
            });
        });
    </script>

</body>
</html>
