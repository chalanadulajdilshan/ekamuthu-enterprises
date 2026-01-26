<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
?>

<head>

    <meta charset="utf-8" />
    <title>Loyalty Customers | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>

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

        <?php include 'navigation.php' ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Loyalty Customers</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <!-- Filter Section -->
                                    <div class="row mb-4">
                                        <div class="col-md-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" value="<?php echo date('Y-m-01'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="end_date" value="<?php echo date('Y-m-t'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="min_sales" class="form-label">Minimum Sales (Rs.)</label>
                                            <input type="number" class="form-control" id="min_sales" placeholder="0.00" min="0" step="0.01">
                                        </div>
                                        <div class="col-md-3 align-self-end">
                                            <button id="filterBtn" class="btn btn-primary w-100">
                                                <i class="uil uil-filter"></i> Filter
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Table -->
                                    <table id="loyaltyCustomerTable" class="table table-bordered dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 20px;"></th>
                                                <th>#</th>
                                                <th>Code</th>
                                                <th>Customer Name</th>
                                                <th>Mobile Number</th>
                                                <th class="text-center">Total Bills</th>
                                                <th class="text-end">Total Amount (Rs.)</th>
                                                <th class="text-center">Points</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>

                </div> 
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?php include 'footer.php' ?>

        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <!-- Add Points Modal -->
    <div class="modal fade" id="loyaltyPointsModal" tabindex="-1" role="dialog" aria-labelledby="loyaltyPointsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loyaltyPointsModalLabel">Manage Loyalty Points</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loyaltyPointsForm">
                        <input type="hidden" id="point_customer_id" name="customer_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <input type="text" class="form-control" id="point_customer_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="point_type" name="type">
                                <option value="earn">Add Points (Earn)</option>
                                <option value="redeem">Redeem Points</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" class="form-control" id="point_value" name="points" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description / Referance</label>
                            <textarea class="form-control" id="point_description" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="savePointsBtn">Save Transaction</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    
    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <script src="ajax/js/loyalty-customers.js"></script>

    <!-- Page Preloader Script -->
    <script>
        $(window).on('load', function() {
            $('#page-preloader').fadeOut('slow', function() {
                $(this).remove();
            });
        });
    </script>

</body>

</html>
