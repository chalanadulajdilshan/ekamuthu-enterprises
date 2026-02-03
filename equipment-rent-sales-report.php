<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Equipment Rent Sales Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
            background-color: #f8f9fa;
        }
        @media print {
            .no-print, .page-title-box, footer, #page-topbar, .vertical-menu, .main-content .page-content .container-fluid .row:first-child {
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
        .summary-card-text {
            font-size: 1.2rem; 
            font-weight: bold;
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
                                <h4 class="mb-0">Equipment Sales & Revenue Report - විකුණුම් වාර්තාව</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card no-print">
                                <div class="card-body">
                                    <form id="reportForm">
                                        <div class="row align-items-end">
                                            <div class="col-md-3">
                                                <label for="fromDate" class="form-label">From Date</label>
                                                <input type="text" class="form-control date-picker" id="fromDate" name="fromDate" autocomplete="off">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="toDate" class="form-label">To Date</label>
                                                <input type="text" class="form-control date-picker" id="toDate" name="toDate" autocomplete="off">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="d-block">&nbsp;</label>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <div>
                                                        <button type="button" class="btn btn-primary" id="searchBtn">
                                                            <i class="mdi mdi-magnify me-1"></i> Search
                                                        </button>
                                                        <button type="button" class="btn btn-secondary" id="resetBtn">
                                                            <i class="mdi mdi-refresh me-1"></i> Reset
                                                        </button>
                                                        <button type="button" class="btn btn-success" id="printBtn">
                                                            <i class="mdi mdi-printer me-1"></i> Print
                                                        </button>
                                                        <button type="button" class="btn btn-warning" id="printReturnIncomeBtn">
                                                            <i class="mdi mdi-cash-multiple me-1"></i> Return Income
                                                        </button>
                                                    </div>
                                                    <div class="ms-auto">
                                                        <button type="button" class="btn btn-info" id="printDailyBtn">
                                                            <i class="mdi mdi-printer-settings me-1"></i>Daily Rental Report
                                                        </button>   
                                                        <button type="button" class="btn btn-info" id="printDailyReturnBtn">Daily Return Report</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                                    <div class="row" id="summarySection" style="display:none;">
                                <div class="col-md-2ish col-sm-6">
                                     <div class="card mini-stats-wid">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <p class="text-muted fw-medium mb-1">Total Deposit<br><small>මුළු තැන්පතු</small></p>
                                                    <h5 class="mb-0 text-primary" id="statTotalDeposit">0.00</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2ish col-sm-6">
                                     <div class="card mini-stats-wid">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <p class="text-muted fw-medium mb-1">Total Transport<br><small>මුළු ප්‍රවාහන</small></p>
                                                    <h5 class="mb-0" id="statTotalTransport">0.00</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2ish col-sm-6">
                                     <div class="card mini-stats-wid">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <p class="text-muted fw-medium mb-1">Total Additional Pmt<br><small>මුළු අමතර ගෙවීම්</small></p>
                                                    <h5 class="mb-0 text-danger" id="statTotalAdditional">0.00</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2ish col-sm-6">
                                     <div class="card mini-stats-wid">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <p class="text-muted fw-medium mb-1">Total Refund<br><small>මුළු ආපසු ගෙවීම්</small></p>
                                                    <h5 class="mb-0 text-success" id="statTotalRefund">0.00</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                     <div class="card mini-stats-wid bg-soft-light border">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <p class="text-muted fw-bold mb-1">NET REVENUE - ශුද්ධ ආදායම</p>
                                                    <h4 class="mb-0 text-dark" id="statTotalRevenue">0.00</h4>
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
                            </div>

                            <!-- Data Table -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="reportTable" class="table table-bordered table-striped dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>Bill No (බිල් අංකය)</th>
                                                    <th>Date (දිනය)</th>
                                                    <th>Customer (පාරිභෝගිකයා)</th>
                                                    <th>Items (අයිතම)</th>
                                                    <th class="text-end">Deposit (තැන්පතු)</th>
                                                    <th class="text-end">Transport (ප්‍රවාහන)</th>
                                                    <th class="text-end">Add. Pay (අමතර ගෙවීම්)</th>
                                                    <th class="text-end">Refund (ආපසු ගෙවීම්)</th>
                                                    <th class="text-end">Net Revenue (ශුද්ධ ආදායම)</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reportTableBody">
                                                <!-- Data via AJAX -->
                                            </tbody>
                                            <tfoot>
                                                <tr class="fw-bold bg-light">
                                                    <th colspan="4" class="text-end">TOTALS: (එකතුව:)</th>
                                                    <td id="tblTotalDeposit" class="text-end">0.00</td>
                                                    <td id="tblTotalTransport" class="text-end">0.00</td>
                                                    <td id="tblTotalAdditional" class="text-end">0.00</td>
                                                    <td id="tblTotalRefund" class="text-end">0.00</td>
                                                    <td id="tblTotalRevenue" class="text-end">0.00</td>
                                                </tr>
                                            </tfoot>
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
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="ajax/js/equipment-rent-sales-report.js?v=<?php echo time(); ?>"></script>

    <script>
        $(document).ready(function() {
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        });
    </script>
</body>
</html>
