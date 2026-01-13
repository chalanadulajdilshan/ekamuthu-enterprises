<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Old Outstanding Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <style>
        /* Target only the Old Outstanding column in the report table */
        #reportTable thead th.old-outstanding-column,
        #reportTable tbody td.old-outstanding-column {
            background-color: #fff3cd !important;
        }

        /* Style for total old outstanding cell */
        #totalOldOutstanding {
            background-color: #ff9800 !important;
            color: #ffffff !important;
        }

        .old-outstanding-text {
            color: #856404;
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
                                <h4 class="mb-0">Old Outstanding Report</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="reportForm">
                                        <div class="row">
                                            <!-- Customer Filter -->
                                            <div class="col-md-4">
                                                <label for="customerCode" class="form-label">Customer</label>
                                                <div class="input-group mb-3">
                                                    <input id="customer_code" name="customer_code" type="text"
                                                        placeholder="Select Customer" class="form-control" readonly>
                                                    <input type="hidden" id="customer_id" name="customer_id">
                                                    <button class="btn btn-info" type="button"
                                                        data-bs-toggle="modal" data-bs-target="#oldOutstandingCustomerModal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
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
                                    <table id="reportTable" class="table table-bordered dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Customer Code</th>
                                                <th>Customer Name</th>
                                                <th>Mobile Number</th>
                                                <th class="text-end old-outstanding-column">Old Outstanding</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Data will be loaded via AJAX -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <td id="totalOldOutstanding" class="text-danger text-end old-outstanding-column">0.00</td>
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

    <?php include 'old-outstanding-customer-model.php'; ?>
    <?php include 'main-js.php'; ?>

    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="assets/libs/daterangepicker/daterangepicker.min.js"></script>
    <!-- jQuery UI Datepicker -->
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script src="ajax/js/old-outstanding-report.js"></script>

    <script>
        $(document).ready(function() {
            // Handle customer selection from modal
            $(document).on('click', '.select-customer', function(e) {
                e.preventDefault();
                const customerId = $(this).data('id');
                const customerCode = $(this).data('code');

                $('#customer_id').val(customerId);
                $('#customer_code').val(customerCode);
                $('#oldOutstandingCustomerModal').modal('hide');
            });

            // Reset form
            $('#resetBtn').click(function() {
                $('#customer_id').val('');
                $('#customer_code').val('');
                $('#reportTableBody').empty();
                $('.text-danger').text('0.00');
            });
        });
    </script>

</body>

</html>
