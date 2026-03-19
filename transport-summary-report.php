<?php
include 'class/include.php';
include 'auth.php';

$companyName = '';
$companyAddress = '';
$companyContact = '';
if (isset($COMPANY_PROFILE_DETAILS) && is_object($COMPANY_PROFILE_DETAILS)) {
    $detailsArr = get_object_vars($COMPANY_PROFILE_DETAILS);
    $companyName = $detailsArr['name'] ?? '';
    $companyAddress = $detailsArr['address'] ?? '';
    if (!empty($detailsArr['contact'])) {
        $companyContact = $detailsArr['contact'];
    } elseif (!empty($detailsArr['phone'])) {
        $companyContact = $detailsArr['phone'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Transport Summary Report | <?php echo $companyName; ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $companyName; ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .print-only {
            display: none;
        }

        #reportTable th, #reportTable td {
            border: 1px solid #000 !important;
        }

        .report-title {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
        }

        .report-meta {
            font-size: 13px;
            color: #555;
            margin: 0;
        }

        @media print {
            @page {
                margin: 10mm 10mm 12mm 10mm;
                size: landscape;
            }

            .no-print,
            .page-title-box,
            footer,
            #page-topbar,
            .dataTables_filter,
            .dataTables_paginate,
            .dataTables_length,
            .dataTables_info,
            .dt-buttons {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            body {
                background-color: #fff !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .main-content {
                margin-left: 0 !important;
                padding-top: 0 !important;
            }

            .page-content {
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #e0e0e0 !important;
            }

            .table-print-clean {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .table-print-clean th {
                border: 1px solid #000 !important;
                padding: 6px 4px !important;
                font-size: 13px !important;
                background-color: #f1f1f1 !important;
            }

            .table-print-clean td {
                border: 1px solid #000 !important;
                padding: 4px !important;
                font-size: 12px !important;
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
                                <h2 class="mb-0">Transport Summary Report - ප්‍රවාහන සාරාංශ වාර්තාව</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Print header -->
                    <div class="row print-only mb-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="report-title mb-1"><?php echo $companyName; ?></h4>
                                    <p class="report-meta mb-0"><?php echo $companyAddress; ?></p>
                                    <p class="report-meta mb-0">Contact: <?php echo $companyContact; ?></p>
                                </div>
                                <div class="text-end">
                                    <h3 class="mb-1">Transport Summary Report - ප්‍රවාහන සාරාංශ වාර්තාව</h3>
                                    <p class="report-meta mb-0 fs-5">From: <span id="printFromDate">-</span> To: <span id="printToDate">-</span></p>
                                    <p class="report-meta mb-0">Generated: <span id="printGenerated">-</span></p>
                                </div>
                            </div>
                            <hr class="mt-2 mb-2">
                        </div>
                    </div>

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
                                            <div class="col-md-3">
                                                <label for="employeeId" class="form-label">Employee - සේවකයා</label>
                                                <select class="form-control" id="employeeId" name="employeeId">
                                                    <option value="">All Employees - සියලුම සේවකයින්</option>
                                                    <?php
                                                    $EMPLOYEE = new EmployeeMaster();
                                                    foreach ($EMPLOYEE->all() as $emp) {
                                                        echo '<option value="' . $emp['id'] . '">' . $emp['name'] . ' (' . $emp['code'] . ')</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="d-block">&nbsp;</label>
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

                            <div class="row" id="summarySection" style="display:none;">
                                <div class="col-md-4">
                                     <div class="card mini-stats-wid no-print">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <p class="text-muted fw-medium mb-1">Total Deliver Amt - බෙදාහැරීමේ එකතුව</p>
                                                    <h4 class="mb-0" id="statTotalDeliver">0.00</h4>
                                                </div>
                                                <div class="flex-shrink-0 align-self-center">
                                                    <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                                        <span class="avatar-title">
                                                            <i class="bx bx-down-arrow-alt font-size-24"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                     <div class="card mini-stats-wid no-print">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <p class="text-muted fw-medium mb-1">Total Pickup Amt - පටවාගැනීමේ එකතුව</p>
                                                    <h4 class="mb-0" id="statTotalPickup">0.00</h4>
                                                </div>
                                                <div class="flex-shrink-0 align-self-center">
                                                    <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                                                        <span class="avatar-title">
                                                            <i class="bx bx-up-arrow-alt font-size-24"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                     <div class="card mini-stats-wid no-print">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <p class="text-muted fw-medium mb-1">Total Amount - මුළු එකතුව</p>
                                                    <h4 class="mb-0" id="statTotalAmount">0.00</h4>
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

                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="reportTable" class="table table-bordered dt-responsive nowrap w-100 table-print-clean">
                                            <thead>
                                                <tr>
                                                    <th>Date - දිනය</th>
                                                    <th>Bill No - බිල් අංකය</th>
                                                    <th>Vehicle - වාහනය</th>
                                                    <th>Employee - සේවකයා</th>
                                                    <th>Start - ආරම්භය</th>
                                                    <th>End - අවසානය</th>
                                                    <th class="text-end">Deliver Amt - බෙදාහැරීම</th>
                                                    <th class="text-end">Pickup Amt - පටවාගැනීම</th>
                                                    <th class="text-end">Total - එකතුව</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reportTableBody">
                                                <!-- Data via AJAX -->
                                            </tbody>
                                            <tfoot>
                                                <tr class="fw-bold bg-light">
                                                    <td colspan="6" class="text-end">Totals - එකතුව :</td>
                                                    <td id="tblTotalDeliver" class="text-end">0.00</td>
                                                    <td id="tblTotalPickup" class="text-end">0.00</td>
                                                    <td id="tblTotalAmount" class="text-end">0.00</td>
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
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="ajax/js/transport-summary-report.js?v=<?php echo time(); ?>"></script>

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
