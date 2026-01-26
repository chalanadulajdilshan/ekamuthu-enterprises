<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
?>
<head>
    <meta charset="utf-8" />
    <title>Blacklisted Customers Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Blacklisted Customers List" name="description" />
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <!-- DataTables -->
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <style>
        td.details-control {
            background: url('assets/images/details_open.png') no-repeat center center;
            cursor: pointer;
            width: 30px;
            transition: all 0.3s;
        }
        tr.shown td.details-control {
            background: url('assets/images/details_close.png') no-repeat center center;
            transform: rotate(90deg);
        }
        .child-table {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">

    <!-- Page Preloader -->
    <div id="page-preloader" class="preloader full-preloader">
        <div class="preloader-container">
            <div class="preloader-animation"></div>
        </div>
    </div>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Blacklisted Customers Report</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Reports</a></li>
                                        <li class="breadcrumb-item active">Blacklisted Customers</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <table id="blacklisted-table" class="table table-bordered dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 20px;"></th> <!-- Expand icon -->
                                                <th>Code</th>
                                                <th>Name</th>
                                                <th>Mobile</th>
                                                <th>NIC</th>
                                                <th>Blacklist Reason</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Loaded via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?php include 'footer.php' ?>

        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/waypoints/lib/jquery.waypoints.min.js"></script>
    <script src="assets/libs/jquery.counterup/jquery.counterup.min.js"></script>

    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
    <script src="assets/libs/jszip/jszip.min.js"></script>
    <script src="assets/libs/pdfmake/build/pdfmake.min.js"></script>
    <script src="assets/libs/pdfmake/build/vfs_fonts.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.colVis.min.js"></script>
    <!-- Responsive examples -->
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    
    <!-- Sweet Alerts -->
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <!-- App js -->
    <script src="assets/js/app.js"></script>

    <!-- Custom JS -->
    <script src="ajax/js/report-blacklisted.js"></script>

    <!-- Page Preloader Script -->
    <script>
        $(window).on('load', function () {
            $('#page-preloader').fadeOut('slow', function () {
                $(this).remove();
            });
        });
    </script>
</body>
</html>
