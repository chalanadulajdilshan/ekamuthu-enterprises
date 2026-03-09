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
    <!-- Required datatable js -->
    <script src="assets/libs/moment/min/moment.min.js"></script>
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
                                                        <div class="col-md-3">
                                                            <label for="employeeFilter" class="form-label fw-semibold text-muted mb-2">Employee - සේවකයා</label>
                                                            <select class="form-select" id="employeeFilter" name="employeeFilter">
                                                                <option value="all">All Employees</option>
                                                                <?php
                                                                $EMPLOYEE = new EmployeeMaster();
                                                                foreach ($EMPLOYEE->all() as $employee) {
                                                                    echo '<option value="' . $employee['id'] . '">' . $employee['name'] . ' (' . $employee['code'] . ')</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3 g-3 align-items-end">
                                            <div class="col-md-4">
                                                <label for="searchQuery" class="form-label fw-semibold text-muted mb-2">Search Machine/Code - යන්ත්‍රය/කේතය සෙවීම</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="mdi mdi-magnify text-primary"></i></span>
                                                    <input type="text" class="form-control" id="searchQuery" name="searchQuery" placeholder="Search by Machine Name, Equipment Code or Job Code...">
                                                </div>
                                            </div>
                                            <div class="col-md-8">
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
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                        <!-- Total Commission Hidden -->
                        <div class="col-md-3" style="display: none;">
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
                         <div class="col-md-4">
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

                    <div class="row mt-4" id="employeeSummaryRow" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Employee Productivity Summary - සේවක කාර්යක්ෂමතාවය</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-centered table-nowrap mb-0" id="employeeSummaryTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Employee Name - නම</th>
                                                    <th class="text-center">Pending - පොරොත්තු</th>
                                                    <th class="text-center">In Progress - ප්‍රගතියේ</th>
                                                    <th class="text-center">Completed - සම්පූර්ණයි</th>
                                                    <th class="text-center">Delivered - භාර දෙන ලදී</th>
                                                    <th class="text-center">Cannot Repair - අලුත්වැඩියා කළ නොහැක</th>
                                                    <th class="text-center fw-bold">Total - මුළු</th>
                                                </tr>
                                            </thead>
                                            <tbody id="employeeSummaryBody">
                                                <!-- Dynamic Content -->
                                            </tbody>
                                        </table>
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
                                                <th>Breakdown Date - දිනය</th>
                                                <th>Complete Date - අවසන් කළ දිනය</th>
                                                <th>Customer - පාරිභෝගික</th>
                                                <th>Machine/Item - යන්ත්‍රය</th>
                                                <th>Code - කේතය</th>
                                                <th>Status - තත්ත්වය</th>
                                                <th>Employee - සේවකයා</th>
                                                <th class="text-end">Repair Charge - ගාස්තුව</th>
                                                <th class="text-end">Commission - කොමිස්</th>
                                                <th class="text-end">Item Cost - අයිතම</th>
                                                <th class="text-end">Total Cost - මුළු</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Data will be loaded via AJAX -->
                                        </tbody>
                                        <tfoot>
                                            <tr class="total-highlight">
                                                <th colspan="8" class="text-end">Total:</th>
                                                <td id="tblTotalRepairCharge" class="text-end">0.00</td>
                                                <td id="tblTotalCommission" class="text-end">0.00</td>
                                                <td id="tblTotalItemCost" class="text-end">0.00</td>
                                                <td id="tblTotalCost" class="text-end">0.00</td>
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
    <script src="assets/libs/moment/min/moment.min.js"></script>
    <script src="assets/libs/daterangepicker/daterangepicker.min.js"></script>

    <!-- Custom JS for Repair Job Report -->
    <script src="ajax/js/repair-job-report.js?v=<?php echo time(); ?>"></script>

    <script>
        $(document).ready(function() {
            // Initialize with Status = All Statuses
            $('#statusFilter').val('all');
        });
    </script>

</body>

</html>
