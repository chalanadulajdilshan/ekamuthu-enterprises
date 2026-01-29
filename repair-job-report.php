<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Repair Jobs Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <style>
        /* Report styling */
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

        /* Status badges */
        .badge-pending { background-color: #f1b44c; }
        .badge-in_progress { background-color: #556ee6; }
        .badge-completed { background-color: #34c38f; }
        .badge-delivered { background-color: #50a5f1; }
        .badge-cannot_repair { background-color: #f46a6a; }

        /* Total amount styling */
        .total-highlight {
            font-weight: bold;
            background-color: #f8f9fa;
        }
    </style>
    <style media="print">
        @page {
            size: landscape;
            margin: 10mm;
        }

        /* Hide everything by default */
        body * {
            visibility: hidden;
        }

        /* Show the main container and its children */
        .container-fluid,
        .container-fluid * {
            visibility: visible;
        }

        /* Position the container at the top left */
        .container-fluid {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Explicitly hide navigation, header, footer, etc. */
        #page-topbar,
        .vertical-menu,
        .main-content .page-content .container-fluid .row:first-child, /* Page Title Row */
        .card-body form,
        .footer,
        .navbar-header,
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate {
            display: none !important;
        }

        /* Print Header Styling */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
            visibility: visible !important;
            position: relative; 
        }
        
        .print-header h2 { margin: 0; font-size: 24px; font-weight: bold; color: #000; }
        .print-header p { margin: 5px 0; font-size: 14px; color: #000; }
        
        /* Adjust card styling for print */
        .card {
            border: none !important;
            box-shadow: none !important;
            margin-bottom: 0 !important;
        }

        .card-body {
            padding: 0 !important;
        }

        /* Ensure table fits and looks good */
        .table-responsive {
            overflow: visible !important;
        }
        
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10px !important; /* Reduce font size for print */
        }
        table td, table th {
            padding: 4px !important; /* Reduce padding */
            border: 1px solid #000 !important;
        }
        
        /* Make Summary Cards look cleaner */
        #summarySection { 
            display: flex !important; 
            flex-wrap: wrap; 
            margin-bottom: 20px;
        }
        #summarySection .col-md-3 {
            flex: 0 0 25%;
            max-width: 25%;
            padding: 0 5px;
        }
        .mini-stats-wid .card-body {
            border: 1px solid #ddd;
            padding: 10px !important;
        }
        
        /* Ensure text is black */
        body, p, h1, h2, h3, h4, h5, h6, td, th {
            color: #000 !important;
        }
    </style>
<?php
// Get Repair Job Page ID for redirection
$db = Database::getInstance();
$query = "SELECT id FROM pages WHERE page_url = 'repair-job.php' LIMIT 1";
$result = $db->readQuery($query);
$repairJobPageId = 0;
if ($row = mysqli_fetch_assoc($result)) {
    $repairJobPageId = $row['id'];
}
?>

<script>
    var repairJobPageId = <?php echo $repairJobPageId; ?>;
</script>
</head>
<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Begin page -->
    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Print Header -->
                    <div class="d-none d-print-block print-header">
                        <h2><?php echo $COMPANY_PROFILE_DETAILS->name ?></h2>
                        <p><?php echo $COMPANY_PROFILE_DETAILS->address ?></p>
                        <p>Phone: <?php echo $COMPANY_PROFILE_DETAILS->mobile_number_1 ?> | Email: <?php echo $COMPANY_PROFILE_DETAILS->email ?></p>
                        <hr style="border-top: 2px solid #000;">
                        <h4 class="mt-3">Repair Jobs Report - අලුත්වැඩියා රැකියා වාර්තාව</h4>
                        <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
                    </div>

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Repair Jobs Report - අලුත්වැඩියා රැකියා වාර්තාව</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="reportForm">
                                        <!-- Filters Section -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="card-body p-3">
                                                    <div class="row g-3 align-items-end">
                                                        <div class="col-md-3">
                                                            <label for="fromDate" class="form-label fw-semibold text-muted mb-2">From Date - ආරම්භක දිනය</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control date-picker" id="fromDate" name="fromDate" placeholder="Select start date">
                                                                <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="toDate" class="form-label fw-semibold text-muted mb-2">To Date - අවසාන දිනය</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control date-picker" id="toDate" name="toDate" placeholder="Select end date">
                                                                <span class="input-group-text bg-light"><i class="mdi mdi-calendar text-primary"></i></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="statusFilter" class="form-label fw-semibold text-muted mb-2">Status - තත්ත්වය</label>
                                                            <select class="form-select" id="statusFilter" name="statusFilter">
                                                                <option value="all">All Statuses</option>
                                                                <option value="pending">Pending</option>
                                                                <option value="in_progress">In Progress</option>
                                                                <option value="completed">Completed</option>
                                                                <option value="delivered">Delivered</option>
                                                                <option value="cannot_repair">Cannot Repair</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 d-flex gap-3">
                                                            <button type="button" class="btn btn-outline-primary btn-sm" id="setToday">
                                                                <i class="mdi mdi-calendar-today me-1"></i> Today
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <small class="text-muted"><i class="mdi mdi-information-outline me-1"></i> Select filters to view report</small>
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
                                                <button type="button" class="btn btn-success ms-1" id="printBtn">
                                                    <i class="mdi mdi-printer me-1"></i> Print
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards (Hidden by default) -->
                    <div class="row" id="summarySection" style="display: none;">
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Total Jobs - මුළු රැකියා</p>
                                            <h4 class="mb-0" id="statTotalJobs">0</h4>
                                        </div>
                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                                <span class="avatar-title">
                                                    <i class="bx bx-wrench font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Total Revenue - මුළු ආදායම</p>
                                            <h4 class="mb-0" id="statTotalRevenue">0.00</h4>
                                        </div>
                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                                                <span class="avatar-title">
                                                    <i class="bx bx-dollar font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Total Commission - මුළු කොමිස් මුදල</p>
                                            <h4 class="mb-0" id="statTotalCommission">0.00</h4>
                                        </div>
                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="mini-stat-icon avatar-sm rounded-circle bg-warning">
                                                <span class="avatar-title">
                                                    <i class="bx bx-user-voice font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                         <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Repair Charges - අලුත්වැඩියා ගාස්තු</p>
                                            <h4 class="mb-0" id="statRepairCharges">0.00</h4>
                                        </div>
                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                                                <span class="avatar-title">
                                                    <i class="bx bx-money font-size-24"></i>
                                                </span>
                                            </div>
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
                                                <th>Job Code - කේතය</th>
                                                <th>Date - දිනය</th>
                                                <th>Customer - පාරිභෝගික</th>
                                                <th>Machine/Item - යන්ත්‍රය</th>
                                                <th>Status - තත්ත්වය</th>
                                                <th class="text-end">Repair Charge - ගාස්තුව</th>
                                                <th class="text-end">Commission - කොමිස්</th>
                                                <th class="text-end">Item Cost - අයිතම</th>
                                                <th class="text-end">Total Cost - මුළු</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Data will be loaded via AJAX -->
                                        </tbody>
                                        <tfoot>
                                            <tr class="total-highlight">
                                                <th colspan="5" class="text-end">Total:</th>
                                                <td id="tblTotalRepairCharge" class="text-end">0.00</td>
                                                <td id="tblTotalCommission" class="text-end">0.00</td>
                                                <td id="tblTotalItemCost" class="text-end">0.00</td>
                                                <td id="tblTotalCost" class="text-end">0.00</td>
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

    <!-- Custom JS for Repair Job Report -->
    <script src="ajax/js/repair-job-report.js?v=<?php echo time(); ?>"></script>

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
            
             // Initialize with Status = Pending and Empty Dates (for All Pending)
            $('#statusFilter').val('pending');
            // Ensure dates are empty
            $('#fromDate').val('');
            $('#toDate').val('');
        });
    </script>

</body>

</html>
