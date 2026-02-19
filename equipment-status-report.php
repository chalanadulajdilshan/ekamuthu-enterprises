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
                                        <div class="col-md-4">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" id="status_filter">
                                                <option value="">All</option>
                                                <option value="available">Available</option>
                                                <option value="rented">Rented</option>
                                                <option value="repair">Repair</option>
                                                <option value="damage">Damaged</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" id="btn-filter" class="btn btn-primary w-100">
                                                <i class="uil uil-filter me-1"></i> Filter
                                            </button>
                                        </div>
                                    </div>
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
                                                    <th class="text-end">Quantity</th>
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
