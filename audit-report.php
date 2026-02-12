<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Audit Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        @media print {
            .no-print, .page-title-box, footer, #page-topbar, .vertical-menu {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding-top: 0 !important;
            }
            .card {
                box-shadow: none !important;
                border: none !important;
            }
            body { 
                background-color: #fff !important; 
            }
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">

    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Rent/Return Bill Audit Report - බිල්පත් විගණන වාර්තාව</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card no-print">
                                <div class="card-body">
                                    <form id="reportForm">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-2">
                                                <label for="fromDate" class="form-label">From Date</label>
                                                <input type="text" class="form-control date-picker" id="fromDate" name="fromDate" autocomplete="off">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="toDate" class="form-label">To Date</label>
                                                <input type="text" class="form-control date-picker" id="toDate" name="toDate" autocomplete="off">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="user_id" class="form-label">Created By (User)</label>
                                                <select class="form-select" id="user_id" name="user_id">
                                                    <option value="">All Users</option>
                                                    <?php
                                                    $USER = new User(null);
                                                    $activeUsers = $USER->getActiveUsers();
                                                    foreach ($activeUsers as $user) {
                                                        echo "<option value='{$user['id']}'>{$user['name']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-5">
                                                <button type="button" class="btn btn-primary" id="searchBtn">
                                                    <i class="mdi mdi-magnify me-1"></i> Search
                                                </button>
                                                <button type="button" class="btn btn-secondary" id="resetBtn">
                                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                                </button>
                                                <button type="button" class="btn btn-success" id="printBtn">
                                                    <i class="mdi mdi-printer me-1"></i> Print
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="auditTable" class="table table-bordered table-striped dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Type (වර්ගය)</th>
                                                    <th>Bill No (බිල් අංකය)</th>
                                                    <th>Date (දිනය)</th>
                                                    <th>Customer (පාරිභෝගිකයා)</th>
                                                    <th>Created By (සකස් කළේ)</th>
                                                    <th>Created At (සකස් කළ වේලාව)</th>
                                                </tr>
                                            </thead>
                                            <tbody id="auditTableBody">
                                                <!-- Data via AJAX -->
                                            </tbody>
                                        </table>
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

    <?php include 'main-js.php'; ?>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
    <script src="assets/libs/jszip/jszip.min.js"></script>
    <script src="assets/libs/pdfmake/build/pdfmake.min.js"></script>
    <script src="assets/libs/pdfmake/build/vfs_fonts.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="ajax/js/audit-report.js"></script>

</body>

</html>
