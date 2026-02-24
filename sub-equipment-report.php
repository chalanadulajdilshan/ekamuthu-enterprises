<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';
?>

<head>
    <meta charset="utf-8" />
    <title>Sub Equipment Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <style>
        /* Tighter layout for print */
        @media print {
            @page {
                margin: 10mm;
            }

            body {
                background: #fff !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print,
            #layout-wrapper > header,
            #layout-wrapper .navbar-header,
            #layout-wrapper .topnav,
            #layout-wrapper footer {
                display: none !important;
            }

            .main-content,
            .page-content,
            .container-fluid,
            .card,
            .card-body {
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }

            .card-header {
                padding: 8px 0 4px 0 !important;
                border: none !important;
                margin-bottom: 6px;
            }

            h5.card-title {
                margin: 0 0 4px 0 !important;
                font-size: 16px;
            }

            #reportInfo {
                margin: 0 !important;
                font-size: 12px;
            }

            table#reportTable {
                width: 100% !important;
                font-size: 11px;
            }

            table#reportTable th,
            table#reportTable td {
                padding: 4px 6px !important;
                vertical-align: top;
            }

            table#reportTable th {
                background: #f1f1f1 !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            table#reportTable tr {
                page-break-inside: avoid;
            }

            /* Hide image column and other non-essential items on print */
            table#reportTable th.no-print,
            table#reportTable td.no-print {
                display: none !important;
            }

            /* Footer rows spacing */
            table#reportTable tfoot td {
                padding-top: 6px !important;
                font-size: 11px;
            }
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Page Preloader -->
    <div id="page-preloader" class="preloader full-preloader no-print">
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
                    
                    <!-- Page Title -->
                    <div class="row mb-4 no-print">
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                            <a href="sub-equipment-master.php" class="btn btn-secondary">
                                <i class="uil uil-arrow-left me-1"></i> Back
                            </a>
                            <button class="btn btn-primary" id="filterBtn">
                                <i class="uil uil-filter me-1"></i> Filter
                            </button>
                            <button class="btn btn-success" id="printBtn">
                                <i class="uil uil-print me-1"></i> Print Report
                            </button>
                            <button class="btn btn-info" id="exportExcelBtn">
                                <i class="uil uil-file-download me-1"></i> Export Excel
                            </button>
                        </div>
                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="equipment-master.php">Equipment Master</a></li>
                                <li class="breadcrumb-item active">Sub Equipment Report</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Filter Card -->
                    <div class="card mb-4 no-print">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Report Filters</h5>
                        </div>
                        <div class="card-body">
                            <form id="filterForm">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="date_from" class="form-label">Date From</label>
                                        <input type="text" id="date_from" name="date_from" class="form-control" placeholder="yyyy-mm-dd">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date_to" class="form-label">Date To</label>
                                        <input type="text" id="date_to" name="date_to" class="form-control" placeholder="yyyy-mm-dd">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="condition_filter" class="form-label">Condition</label>
                                        <select id="condition_filter" name="condition_filter" class="form-select">
                                            <option value="">All Conditions</option>
                                            <option value="new">New</option>
                                            <option value="used">Used</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="equipment_filter" class="form-label">Equipment</label>
                                        <select id="equipment_filter" name="equipment_filter" class="form-select">
                                            <option value="">All Equipment</option>
                                            <?php
                                            $EQUIPMENT = new Equipment(NULL);
                                            foreach ($EQUIPMENT->all() as $equip) {
                                                echo '<option value="' . $equip['id'] . '">' . htmlspecialchars($equip['code'] . ' - ' . $equip['item_name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mt-3">
                                        <label for="brand_filter" class="form-label">Brand</label>
                                        <input type="text" id="brand_filter" name="brand_filter" class="form-control" placeholder="Enter brand">
                                    </div>
                                    <div class="col-md-3 mt-3">
                                        <label for="status_filter" class="form-label">Status</label>
                                        <select id="status_filter" name="status_filter" class="form-select">
                                            <option value="">All Status</option>
                                            <option value="available">Available</option>
                                            <option value="rent">Rent</option>
                                            <option value="damage">Damage</option>
                                            <option value="repair">Repair</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Report Results -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Sub Equipment Report</h5>
                            <p class="text-muted mb-0" id="reportInfo">Showing all sub-equipment items</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="reportTable" class="table table-bordered table-hover w-100">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Code</th>
                                            <th>Equipment</th>
                                            <th>Department</th>
                                            <th>Purchase Date</th>
                                            <th>Value</th>
                                            <th>Brand</th>
                                            <th>Company/Customer</th>
                                            <th>Condition</th>
                                            <th class="no-print">Image</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reportTableBody">
                                        <tr>
                                            <td colspan="11" class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="5" class="text-end">Total Value:</td>
                                            <td id="totalValue">0.00</td>
                                            <td colspan="5"></td>
                                        </tr>
                                        <tr>
                                            <td colspan="11">
                                                <strong>New Items:</strong> <span id="newCount">0</span> | 
                                                <strong>Used Items:</strong> <span id="usedCount">0</span> | 
                                                <strong>Total Items:</strong> <span id="totalCount">0</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include 'footer.php' ?>

        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <?php include 'main-js.php' ?>

    <script src="ajax/js/sub-equipment-report.js?v=20260224"></script>

    <script>
        $(window).on('load', function () {
            $('#page-preloader').fadeOut('slow', function () {
                $(this).remove();
            });
        });
    </script>

</body>

</html>
