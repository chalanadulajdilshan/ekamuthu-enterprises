<?php
include 'class/include.php';
include 'auth.php';

$VEHICLE = new Vehicle(null);
$vehicles = $VEHICLE->all();

// Check Access
$page_url = basename(__FILE__);
$db = Database::getInstance();
$page_id_query = "SELECT id FROM pages WHERE page_url = '$page_url'";
$page_id_res = mysqli_fetch_assoc($db->readQuery($page_id_query));
if ($page_id_res) {
    UserPermission::checkAccess($page_id_res['id']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Vehicle Repair Expenses Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

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
            background: linear-gradient(135deg, #323d4e 0%, #1c222b 100%);
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

        /* Total amount styling */
        .total-highlight {
            font-weight: bold;
            background-color: #f8f9fa;
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
                                <h4 class="mb-0">Vehicle Repair Expenses & Breakdown Report</h4>
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
                                                                <input type="date" class="form-control" id="fromDate" name="fromDate" value="<?php echo date('Y-m-01'); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="toDate" class="form-label fw-semibold text-muted mb-2">To Date - අවසාන දිනය</label>
                                                            <div class="input-group">
                                                                <input type="date" class="form-control" id="toDate" name="toDate" value="<?php echo date('Y-m-d'); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="vehicleId" class="form-label fw-semibold text-muted mb-2">Vehicle - වාහනය</label>
                                                            <select class="form-select" id="vehicleId" name="vehicleId">
                                                                <option value="all">All Vehicles</option>
                                                                <?php foreach ($vehicles as $vehicle) : ?>
                                                                    <option value="<?php echo $vehicle['id'] ?>"><?php echo htmlspecialchars($vehicle['vehicle_no']) ?> (<?php echo htmlspecialchars($vehicle['ref_no']) ?>)</option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <button type="button" class="btn btn-primary w-100" id="searchBtn">
                                                                <i class="mdi mdi-magnify me-1"></i> Search
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3 g-3 align-items-end">
                                            <div class="col-md-12 text-end">
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

                    <!-- Summary Cards -->
                    <div class="row" id="summarySection">
                        <div class="col-md-4">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Total Vehicles - මුළු වාහන සංඛ්‍යාව</p>
                                            <h4 class="mb-0" id="statVehicleCount">0</h4>
                                        </div>
                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                                <span class="avatar-title">
                                                    <i class="bx bxs-truck font-size-24"></i>
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
                                            <p class="text-muted fw-medium">Total Repair Expenses - මුළු අලුත්වැඩියා වියදම</p>
                                            <h4 class="mb-0" id="statTotalRepairExpenses">0.00</h4>
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
                        <div class="col-md-4">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Total Breakdown Count - මුළු බිඳවැටීම් ගණන</p>
                                            <h4 class="mb-0" id="statTotalBreakdownCount">0</h4>
                                        </div>
                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="mini-stat-icon avatar-sm rounded-circle bg-danger">
                                                <span class="avatar-title">
                                                    <i class="bx bx-error font-size-24"></i>
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
                                                <th>Vehicle Number - වාහන අංකය</th>
                                                <th>Ref No - යොමු අංකය</th>
                                                <th>Brand - වෙළඳ නාමය</th>
                                                <th>Model - මාදිලිය</th>
                                                <th class="text-center">Breakdown Count - බිඳවැටීම් ගණන</th>
                                                <th class="text-end">Total Repair Expenses - මුළු වියදම (Rs.)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Data will be loaded via AJAX -->
                                        </tbody>
                                        <tfoot>
                                            <tr class="total-highlight">
                                                <th colspan="4" class="text-end">Total:</th>
                                                <td id="tblTotalBreakdownCount" class="text-center">0</td>
                                                <td id="tblTotalRepairExpenses" class="text-end">0.00</td>
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

    <!-- Custom JS for Vehicle Repair Report -->
    <script>
        var page_id = '<?php echo $_GET['page_id'] ?? ''; ?>';
    </script>
    <script src="ajax/js/vehicle-repair-report.js?v=<?php echo time(); ?>"></script>

</body>

</html>
