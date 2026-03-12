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
    <title>Transport Charges Report | <?php echo $companyName; ?> </title>

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

        .report-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .report-meta {
            font-size: 13px;
            color: #555;
            margin: 0;
        }

        .print-bill-box {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 16px;
            background: #fff;
        }

        .print-bill-summary td,
        .print-bill-summary th {
            padding: 4px 8px;
            font-size: 12px;
        }

        .print-bill-summary th {
            width: 140px;
        }

        .table-print-clean th,
        .table-print-clean td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        .table-print-clean thead th {
            background: #f5f5f5;
        }

        @media print {
            @page {
                margin: 10mm 10mm 12mm 10mm;
            }

            /* Hide chrome/navigation */
            .no-print,
            .page-title-box,
            footer,
            #page-topbar,
            .navbar-header,
            .navbar-brand-box,
            .vertical-menu,
            .dt-buttons,
            .dataTables_filter,
            .dataTables_paginate,
            .dataTables_length,
            .dataTables_info {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            /* Expand content */
            body {
                margin: 0 !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding-top: 0 !important;
            }

            .page-content {
                padding: 0 !important;
            }

            .page-content .container-fluid {
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #e0e0e0 !important;
                page-break-inside: avoid;
            }

            /* Table readability */
            #reportTable {
                width: 100% !important;
                font-size: 13px;
            }

            #reportTable thead th {
                background: #f1f1f1 !important;
            }

            body {
                background-color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Keep summary visible on print */
            #summarySection {
                display: block !important;
            }

            /* Remove DataTables chrome and use clean table */
            .dataTables_wrapper {
                overflow: visible !important;
            }
            .table-print-clean {
                width: 100% !important;
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
                                <h4 class="mb-0">Transport Charges Report - ප්‍රවාහන ගාස්තු වාර්තාව</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Print header / bill style info -->
                    <div class="row print-only mb-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="report-title mb-1"><?php echo $companyName; ?></h4>
                                    <p class="report-meta mb-0"><?php echo $companyAddress; ?></p>
                                    <p class="report-meta mb-0">Contact: <?php echo $companyContact; ?></p>
                                </div>
                                <div class="text-end">
                                    <h5 class="mb-1">Transport Charges Report</h5>
                                    <p class="report-meta mb-0">From: <span id="printFromDate">-</span></p>
                                    <p class="report-meta mb-0">To: <span id="printToDate">-</span></p>
                                    <p class="report-meta mb-0">Generated: <span id="printGenerated">-</span></p>
                                </div>
                            </div>
                            <hr class="mt-3 mb-2">
                            <div class="d-flex justify-content-between">
                                <p class="report-meta mb-0"><strong>Total Transport Cost:</strong> <span id="printTotalTransport">0.00</span></p>
                            </div>
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
                                            <div class="col-md-4">
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
                                                    <p class="text-muted fw-medium mb-1">Total Transport Cost</p>
                                                    <h4 class="mb-0" id="statTotalTransport">0.00</h4>
                                                </div>
                                                <div class="flex-shrink-0 align-self-center">
                                                    <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                                        <span class="avatar-title">
                                                            <i class="bx bx-car font-size-24"></i>
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
                                    <table id="reportTable" class="table table-bordered dt-responsive nowrap w-100 table-print-clean">
                                        <thead>
                                            <tr>
                                                <th>Bill Number</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Status</th>
                                                <th class="text-end">Transport Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Data via AJAX -->
                                        </tbody>
                                        <tfoot>
                                            <tr class="total-highlight">
                                                <th colspan="4" class="text-end">Total:</th>
                                                <td id="tblTotalTransport" class="text-end">0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
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
    <script src="ajax/js/transport-charges-report.js?v=<?php echo time(); ?>"></script>

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
