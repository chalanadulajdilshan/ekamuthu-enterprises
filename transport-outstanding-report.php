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
    <title>Transport Outstanding Report | <?php echo $companyName; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $companyName; ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }

        .print-only { display: none; }

        .report-stat-item {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .report-stat-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 8px;
            display: block;
        }
        .report-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #212529;
        }

        .table thead th {
            background-color: #f1f3f5;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            border-bottom: 2px solid #dee2e6;
            vertical-align: middle;
        }
        .table tbody td { vertical-align: middle; }

        /* Print styles for A4 landscape (default) */
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm 10mm 12mm 10mm;
            }

            .no-print,
            .page-title-box,
            footer,
            #page-topbar,
            .dataTables_filter,
            .dataTables_paginate,
            .dataTables_length,
            .dataTables_info,
            .btn,
            button {
                display: none !important;
            }

            .print-only { 
                display: block !important; 
            }

            body {
                background-color: #fff !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: #000 !important;
            }

            .main-content { 
                margin-left: 0 !important; 
                padding-top: 0 !important; 
            }

            .page-content { 
                padding: 0 !important; 
            }

            .container-fluid {
                padding: 0 10mm !important;
            }

            .card { 
                box-shadow: none !important; 
                border: 1px solid #dee2e6 !important;
                page-break-inside: avoid;
            }

            .card-body {
                padding: 10px !important;
            }

            .table {
                font-size: 10px !important;
                border-collapse: collapse !important;
                width: 100% !important;
                page-break-inside: auto;
            }

            .table thead th {
                background-color: #f8f9fa !important;
                border: 1px solid #000 !important;
                padding: 4px !important;
                font-size: 9px !important;
                font-weight: 600 !important;
            }

            .table tbody td {
                border: 1px solid #000 !important;
                padding: 3px !important;
                font-size: 9px !important;
            }

            .table tfoot td {
                border: 1px solid #000 !important;
                padding: 4px !important;
                font-size: 10px !important;
                font-weight: 700 !important;
                background-color: #f1f1f1 !important;
            }

            .table tr {
                page-break-inside: avoid;
            }

            .report-stat-item {
                border: 1px solid #000 !important;
                padding: 8px !important;
                margin: 0 5px 10px 0 !important;
            }

            .report-stat-label {
                font-size: 9px !important;
            }

            .report-stat-value {
                font-size: 16px !important;
            }

            h4, h5, h6 {
                margin: 5px 0 !important;
            }

            /* Hide action column in print */
            .table thead th:last-child,
            .table tbody td:last-child,
            .table tfoot td:last-child {
                display: none !important;
            }
        }

        /* Optional: Portrait mode (can be activated via CSS class or media query) */
        @media print and (orientation: portrait) {
            @page {
                size: A4 portrait;
                margin: 10mm 8mm 12mm 8mm;
            }

            .table {
                font-size: 8px !important;
            }

            .table thead th,
            .table tbody td {
                font-size: 7px !important;
                padding: 2px !important;
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
                                <h4 class="mb-0"><i class="uil uil-truck me-1"></i>Transport Outstanding Report - ප්‍රවාහන හිඟ වාර්තාව</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Print header -->
                    <div class="row print-only mb-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-1 fw-bold"><?php echo $companyName; ?></h4>
                                    <p class="mb-0 small"><?php echo $companyAddress; ?></p>
                                    <p class="mb-0 small">Contact: <?php echo $companyContact; ?></p>
                                </div>
                                <div class="text-end">
                                    <h5 class="mb-1">Transport Outstanding Report</h5>
                                    <p class="mb-0 small">Generated: <?php echo date('Y-m-d H:i'); ?></p>
                                </div>
                            </div>
                            <hr class="mt-2 mb-2">
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-3" id="summarySection" style="display:none;">
                        <div class="col-md-3">
                            <div class="report-stat-item">
                                <span class="report-stat-label">Total Records</span>
                                <span class="report-stat-value" id="statTotalRecords">0</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="report-stat-item">
                                <span class="report-stat-label">Total Amount - මුළු මුදල</span>
                                <span class="report-stat-value" id="statTotalAmount">0.00</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="report-stat-item">
                                <span class="report-stat-label" style="color:#198754;">Settled - ගෙවූ</span>
                                <span class="report-stat-value" style="color:#198754;" id="statTotalSettled">0.00</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="report-stat-item">
                                <span class="report-stat-label" style="color:#dc3545;">Remaining - හිඟ</span>
                                <span class="report-stat-value" style="color:#dc3545;" id="statTotalRemaining">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <!-- Filter -->
                            <div class="card no-print">
                                <div class="card-body">
                                    <div class="row align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">Customer - පාරිභෝගිකයා</label>
                                            <div class="input-group">
                                                <input id="customer_code" type="text" placeholder="All Customers - සියලුම පාරිභෝගිකයින්" class="form-control" readonly>
                                                <input type="hidden" id="customer_id">
                                                <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#customerModal">
                                                    <i class="uil uil-search me-1"></i>
                                                </button>
                                                <button class="btn btn-secondary" type="button" id="clearCustomer">
                                                    <i class="uil uil-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="d-block">&nbsp;</label>
                                            <button type="button" class="btn btn-primary" id="searchBtn">
                                                <i class="uil uil-search me-1"></i> Search
                                            </button>
                                            <button type="button" class="btn btn-secondary" id="resetBtn">
                                                <i class="uil uil-redo me-1"></i> Reset
                                            </button>
                                            <button type="button" class="btn btn-success" id="printBtn">
                                                <i class="uil uil-print me-1"></i> Print
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Report Table -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="reportTable" class="table table-bordered table-hover dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>Customer</th>
                                                    <th>Bill No</th>
                                                    <th>Transport Date</th>
                                                    <th>Employee</th>
                                                    <th>Vehicle</th>
                                                    <th>Start</th>
                                                    <th>End</th>
                                                    <th class="text-end">Total</th>
                                                    <th class="text-end">Settled</th>
                                                    <th class="text-end">Remaining</th>
                                                    <th style="width:100px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reportTableBody">
                                                <tr><td colspan="11" class="text-center text-muted py-3">Click Search to load report</td></tr>
                                            </tbody>
                                            <tfoot>
                                                <tr class="fw-bold bg-light">
                                                    <td colspan="7" class="text-end">Totals:</td>
                                                    <td id="tblTotalAmount" class="text-end">0.00</td>
                                                    <td id="tblTotalSettled" class="text-end">0.00</td>
                                                    <td id="tblTotalRemaining" class="text-end">0.00</td>
                                                    <td></td>
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

    <!-- Customer Select Modal -->
    <div id="customerModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Customer - පාරිභෝගිකයා තෝරන්න</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table id="customerSelectTbl" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Mobile</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlement Modal (for this report page) -->
    <div id="ReportSettlementModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-soft-success">
                    <h5 class="modal-title"><i class="uil uil-money-bill me-1"></i>Transport Settlement - ප්‍රවාහන ගෙවීම්</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="report_settlement_transport_id">

                    <!-- Summary -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-body text-center py-2">
                                    <small class="text-muted d-block">Total Amount</small>
                                    <h5 class="mb-0" id="report_settlement_total">0.00</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border border-success">
                                <div class="card-body text-center py-2">
                                    <small class="text-muted d-block">Total Paid</small>
                                    <h5 class="mb-0 text-success" id="report_settlement_paid">0.00</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border border-warning">
                                <div class="card-body text-center py-2">
                                    <small class="text-muted d-block">Remaining</small>
                                    <h5 class="mb-0 text-warning" id="report_settlement_remaining">0.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Settlement Form -->
                    <div class="card border shadow-sm mb-3">
                        <div class="card-header bg-soft-success py-2">
                            <h6 class="mb-0 text-success"><i class="uil uil-plus-circle me-1"></i>Add Settlement Payment</h6>
                        </div>
                        <div class="card-body py-3">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="report_settlement_amount" placeholder="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control date-picker" id="report_settlement_date" value="<?php echo date('Y-m-d'); ?>" autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Remark</label>
                                    <input type="text" class="form-control" id="report_settlement_remark" placeholder="Optional remark">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-success w-100" id="btn-report-add-settlement">
                                        <i class="uil uil-plus me-1"></i>Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settlement History -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Remark</th>
                                    <th style="width:80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="reportSettlementsTableBody">
                                <tr><td colspan="5" class="text-center text-muted py-3">No settlements recorded yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'main-js.php'; ?>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="ajax/js/transport-outstanding-report.js?v=<?php echo time(); ?>"></script>

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
