<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
?>

<head>
    <meta charset="utf-8" />
    <title>Repair Summary Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'main-css.php' ?>
    <link href="assets/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #print-area, #print-area * {
                visibility: visible;
            }
            #print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .page-content {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">

    <div id="layout-wrapper">
        <div class="no-print">
            <?php include 'navigation.php' ?>
        </div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row no-print">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Repair Summary Report - අලුත්වැඩියා වාර්තාව</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="row mb-4 no-print">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Filter by Date</h5>
                                    <form id="filter-form" class="row align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">From Date</label>
                                            <input type="text" class="form-control date-picker" id="from_date" 
                                                   value="<?php echo date('Y-m-01'); ?>" placeholder="Select Start Date">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">To Date</label>
                                            <input type="text" class="form-control date-picker" id="to_date" 
                                                   value="<?php echo date('Y-m-d'); ?>" placeholder="Select End Date">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" id="btn-filter" class="btn btn-primary w-100">
                                                <i class="uil uil-filter me-1"></i> Generate Report
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Content -->
                    <div class="row" id="print-area">
                        <div class="col-md-10 offset-md-1">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <!-- Print Header -->
                                    <div class="d-none d-print-block text-center mb-4">
                                        <h3><?php echo $COMPANY_PROFILE_DETAILS->name; ?></h3>
                                        <h5>Repair Summary Report</h5>
                                        <p class="mb-0">
                                            From: <span id="print_from_date"></span> To: <span id="print_to_date"></span>
                                        </p>
                                    </div>

                                    <h4 class="card-title text-center mb-4 text-decoration-underline d-print-none">Repair Summary Report</h4>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Description - විස්තරය</th>
                                                    <th class="text-end">Amount (Rs.) / Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Financials -->
                                                <tr>
                                                    <td>
                                                        <strong>Total cost borne by institution for outside repairs</strong><br>
                                                        <small class="text-muted">පිට රෙපෙයාර් සඳහා ආයතනයෙන් දැරූ මුළු මුදල</small>
                                                    </td>
                                                    <td class="text-end fw-bold text-danger" id="outsource_cost">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>15% Commission Amount</strong><br>
                                                        <small class="text-muted">15% කොමිස් මුදල</small>
                                                    </td>
                                                    <td class="text-end fw-bold text-success" id="commission">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Total Repair Income</strong><br>
                                                        <small class="text-muted">සම්පූර්ණ රෙපෙයාර් මුළු මුදල</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="total_income">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Profit received by institution</strong><br>
                                                        <small class="text-muted">ආයතනයට ලැබෙන ලාභය</small>
                                                    </td>
                                                    <td class="text-end fw-bold text-primary fs-5" id="total_profit">0.00</td>
                                                </tr>
                                                
                                                <!-- Counts -->
                                                <tr class="table-light"><td colspan="2"></td></tr>

                                                <tr>
                                                    <td>
                                                        <strong>Total Machines (Outside + In-house)</strong><br>
                                                        <small class="text-muted">පිට රෙපෙයාර් සහ PS රෙපෙයාර් මුළු මැෂින් ගණන</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="total_machines">0</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Total Outside Machines</strong><br>
                                                        <small class="text-muted">පිට මැෂින් මුළු ගණන</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="total_outsource_machines">0</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Total In-house Machines</strong><br>
                                                        <small class="text-muted">ආයතනය තුළ අලුත්වැඩියා කරන ලද මැෂින් ගණන</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="total_in_house_machines">0</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Taken back as cannot repair</strong><br>
                                                        <small class="text-muted">සැදීමට නොහැකි නිසා රැගෙන ගිය ගණන</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="cannot_repair">0</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Pending</strong><br>
                                                        <small class="text-muted">තවම පරීක්ෂා කර නොමැති ගණන</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="pending">0</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Checking</strong><br>
                                                        <small class="text-muted">පරීක්ෂා කරමින් පවතින ගණන</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="checking">0</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>In Progress</strong><br>
                                                        <small class="text-muted">අලුත්වැඩියා කරමින් පවතින ගණන</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="in_progress">0</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Repaired but NOT Taken</strong><br>
                                                        <small class="text-muted">සාදා රැගෙන නොගිය ගණන</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="repaired_not_taken">0</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Repaired and TAKEN (Delivered)</strong><br>
                                                        <small class="text-muted">සාදා රැගෙන ගිය මැෂින් ගණන</small>
                                                    </td>
                                                    <td class="text-end fw-bold" id="repaired_taken">0</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="text-end mt-3 no-print">
                                        <button onclick="window.print()" class="btn btn-secondary">
                                            <i class="uil uil-print me-1"></i> Print Report
                                        </button>
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
    <script src="assets/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <?php include 'main-js.php' ?>
    <script src="ajax/js/repair-report.js?v=<?php echo time(); ?>"></script>
    
    <script>
        $(document).ready(function() {
            $('.date-picker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
        });
    </script>
</body>
</html>
