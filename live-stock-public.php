<!doctype html>
<?php
include 'class/include.php';

// Fetch active company name for page title / branding
$company = new CompanyProfile();
$activeCompany = $company->getActiveCompany();
$companyName = !empty($activeCompany[0]['name']) ? $activeCompany[0]['name'] : 'Company';

// Fetch departments for the filter
$DEPARTMENT_MASTER = new DepartmentMaster();
$departments = $DEPARTMENT_MASTER->all();
?>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Live Stocks | <?php echo htmlspecialchars($companyName); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo htmlspecialchars($companyName); ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <!-- Select2 CSS -->
    <link href="assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        /* Tighten vertical spacing for the public page */
        body.live-stock-public .page-content {
            padding-top: 4px !important;
            padding-bottom: 12px !important;
        }
        body.live-stock-public #summary-cards {
            margin-bottom: 8px;
        }
        body.live-stock-public #summary-cards .card {
            margin-bottom: 6px;
        }
        body.live-stock-public #summary-cards .card-body {
            padding: 10px 12px;
        }
        body.live-stock-public .card-body {
            padding: 12px 14px;
        }
        body.live-stock-public .row.mb-3 {
            margin-bottom: 10px !important;
        }
    </style>
</head>

<body class="live-stock-public" data-layout="horizontal" data-topbar="colored">

    <div class="main-content">
        <div class="page-content live-stock-public">
            <div class="container-fluid">
                <!-- start page title -->
                <div class="row mb-3">
                    <div class="col-12 d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Live Stock</h4>
                        <a href="login.php" class="btn btn-secondary btn-sm">Back to Login</a>
                    </div>
                </div>
                <!-- end page title -->

                <!-- Summary Cards -->
                <div class="row mb-3" id="summary-cards">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Cost</h5>
                                <h3 class="card-text" id="total-cost">Loading...</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Invoice</h5>
                                <h3 class="card-text" id="total-invoice">Loading...</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Profit %</h5>
                                <h3 class="card-text" id="profit-percentage">Loading...</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <!-- Department Filter -->
                                <div class="row mb-3 align-items-end">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center" style="height: 100%;">
                                            <div class="me-2">
                                                <button id="exportToExcel" class="btn btn-primary me-2">
                                                    <i class="fas fa-file-excel me-1"></i> Export to Excel
                                                </button>
                                                <button id="exportToPdf" class="btn btn-warning">
                                                    <i class="fas fa-file-pdf me-1"></i> Export to PDF
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="filter_department_id" class="form-label">Filter by Department</label>
                                            <div class="input-group">
                                                <select class="form-control select2" id="filter_department_id" name="filter_department_id">
                                                    <option value="all" selected>Show All Departments</option>
                                                    <?php foreach ($departments as $department) : ?>
                                                        <option value="<?php echo $department['id']; ?>">
                                                            <?php echo htmlspecialchars($department['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered dt-responsive nowrap" id="stockTable" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:30px;"></th>
                                                <th>Item Code</th>
                                                <th>Item Description</th>
                                                <th>Department</th>
                                                <th>Category</th>
                                                <th>List Price</th>
                                                <th>Selling Price</th>
                                                <th>Quantity</th>
                                                <th>Stock Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be loaded by DataTables -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->
    </div>
    <!-- end main content-->

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="assets/libs/select2/js/select2.min.js"></script>
    <!-- Datatables -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <!-- Prevent navigation into Item Master (no permissions outside login) -->
    <script>
        window.ITEM_MASTER_PAGE_ID = 0;
    </script>

    <!-- Live Stock JS -->
    <script src="ajax/js/live-stock.js"></script>

</body>

</html>
