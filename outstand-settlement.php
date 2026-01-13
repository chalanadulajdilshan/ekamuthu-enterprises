<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Outstanding Settlement | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css">
    <style>
        /* Main card styling */
        #outstandingTablesContainer>.card {
            border: 1px solid #000;
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        /* Status badge styling */
        .badge {
            padding: 0.5em 0.8em;
            font-size: 0.85em;
            font-weight: 500;
        }

        .summary-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
        }

        .summary-label {
            font-weight: 600;
        }

        /* Form elements */
        .form-label {
            margin-bottom: 0.25rem;
        }

        /* Date input group */
        .input-group.date {
            width: 100%;
        }

        .input-group-append {
            cursor: pointer;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Outstanding Settlement</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="settlementForm">
                                        <div class="row g-4 align-items-end">
                                            <div class="col-md-3">
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
                                                <label class="form-label" for="fromDate">From Date</label>
                                                <div class="input-group">
                                                    <input id="fromDate" name="from_date" type="text"
                                                        class="form-control date-picker" placeholder="Select date" autocomplete="off" required>
                                                    <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="toDate">To Date</label>
                                                <div class="input-group">
                                                    <input id="toDate" name="to_date" type="text"
                                                        class="form-control date-picker" placeholder="Select date" autocomplete="off" required>
                                                    <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                </div>
                                            </div>



                                            <div class="col-md-2">

                                                <select id="status" name="status" class="form-select">
                                                    <option value="">-- Select Status -- </option>
                                                    <option value="all">All</option>
                                                    <option value="settled">Settled</option>
                                                    <option value="unsettled">Unsettled</option>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <button type="button" id="viewBtn" class="btn btn-primary"><i class="mdi mdi-eye me-1"></i> View</button>
                                                <button type="button" id="resetBtn" class="btn btn-secondary"><i class="mdi mdi-refresh me-1"></i> Reset</button>
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


                                    <!-- Invoice Cards Container -->
                                    <div id="outstandingTablesContainer">
                                        <div class="text-muted text-center py-5">
                                            <i class="uil uil-invoice display-4"></i>
                                            <p class="mt-2">Select a customer to view outstanding invoices and receipts</p>
                                        </div>
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

    <?php include 'customer-master-model.php'; ?>
    <?php include 'main-js.php'; ?>

    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="assets/libs/daterangepicker/daterangepicker.min.js"></script>
    <script src="assets/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="ajax/js/outstand-settlement.js"></script>
    <script src="ajax/js/common.js"></script>

    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
    <script>
        $(function() {
            // Initialize the datepicker
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd' // or 'dd-mm-yy' as per your format
            });

            // Set default dates (first day of current month to today)
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

            // Format dates as YYYY-MM-DD
            const formatDate = (date) => {
                return $.datepicker.formatDate('yy-mm-dd', date);
            };

            // Set default dates
            $('#fromDate').val(formatDate(firstDay));
            $('#toDate').val(formatDate(today));

            // Validate date range
            $('#fromDate, #toDate').on('change', function() {
                const fromDate = new Date($('#fromDate').datepicker('getDate'));
                const toDate = new Date($('#toDate').datepicker('getDate'));

                if (fromDate && toDate && fromDate > toDate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Date Range',
                        text: 'From date cannot be after To date',
                        confirmButtonColor: '#3b5de7',
                    });
                    $(this).val('');
                    return false;
                }
            });
        });
    </script>
</body>

</html>