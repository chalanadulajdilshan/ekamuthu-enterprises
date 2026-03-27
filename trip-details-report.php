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
    <title>Trip Details Report | <?php echo $companyName; ?> </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $companyName; ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        body {
            background-color: #f8f9fa;
        }

        .print-only {
            display: none;
        }

        #reportTable th, #reportTable td {
            border: 1px solid #dee2e6 !important;
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
            .dt-buttons,
            .navbar-header,
            .vertical-menu {
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
                border: 1px solid #000 !important;
                margin-bottom: 20px !important;
            }

            .table-print-clean {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .table-print-clean th {
                border: 1px solid #000 !important;
                padding: 6px 4px !important;
                font-size: 11px !important;
                background-color: #f1f1f1 !important;
            }

            .table-print-clean td {
                border: 1px solid #000 !important;
                padding: 4px !important;
                font-size: 10px !important;
            }
            .text-end {
                text-align: right !important;
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
                                <h4 class="mb-0">Full Trip Details Report</h4>
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
                                    <h3 class="mb-1">Full Trip Details Report</h3>
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
                                            <div class="col-md-2">
                                                <label for="fromDate" class="form-label">From Date</label>
                                                <input type="date" class="form-control" id="fromDate" name="fromDate" autocomplete="off">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="toDate" class="form-label">To Date</label>
                                                <input type="date" class="form-control" id="toDate" name="toDate" autocomplete="off">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="vehicleId" class="form-label">Vehicle</label>
                                                <select class="form-control select2" id="vehicleId" name="vehicleId">
                                                    <option value="">All Vehicles</option>
                                                    <?php
                                                    $VEHICLE = new Vehicle();
                                                    foreach ($VEHICLE->all() as $v) {
                                                        echo '<option value="' . $v['id'] . '">' . $v['vehicle_no'] . ' (' . $v['brand'] . ')</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="customerId" class="form-label">Customer</label>
                                                <select class="form-control select2" id="customerId" name="customerId">
                                                    <option value="">All Customers</option>
                                                    <?php
                                                    $CUSTOMER = new CustomerMaster();
                                                    foreach ($CUSTOMER->all() as $c) {
                                                        echo '<option value="' . $c['id'] . '">' . $c['code'] . ' - ' . $c['name'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="d-block">&nbsp;</label>
                                                <div class="btn-group w-100">
                                                    <button type="button" class="btn btn-primary" id="searchBtn">
                                                        <i class="mdi mdi-magnify"></i> Search
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="resetBtn">
                                                        <i class="mdi mdi-refresh"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="row" id="summarySection">
                                <div class="col-md-4">
                                     <div class="card mini-stats-wid no-print">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <p class="text-muted fw-medium mb-1">Total Toll</p>
                                                    <h4 class="mb-0" id="statTotalToll">0.00</h4>
                                                </div>
                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-success align-self-center">
                                                    <span class="avatar-title">
                                                        <i class="bx bx-receipt font-size-24"></i>
                                                    </span>
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
                                                    <p class="text-muted fw-medium mb-1">Total Helper Payment</p>
                                                    <h4 class="mb-0" id="statTotalHelper">0.00</h4>
                                                </div>
                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-warning align-self-center">
                                                    <span class="avatar-title">
                                                        <i class="bx bx-user-plus font-size-24"></i>
                                                    </span>
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
                                                    <p class="text-muted fw-medium mb-1">Total Trip Cost</p>
                                                    <h4 class="mb-0" id="statTotalCost">0.00</h4>
                                                </div>
                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-danger align-self-center">
                                                    <span class="avatar-title">
                                                        <i class="bx bx-money font-size-24"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                                        <h5 class="card-title">Trip Details</h5>
                                        <button type="button" class="btn btn-success" id="printBtn">
                                            <i class="mdi mdi-printer me-1"></i> Print Report
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table id="reportTable" class="table table-bordered dt-responsive nowrap w-100 table-print-clean">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Trip No</th>
                                                    <th>Category</th>
                                                    <th>Customer / Bill</th>
                                                    <th>Vehicle</th>
                                                    <th>Driver</th>
                                                    <th class="text-end">Start</th>
                                                    <th class="text-end">End</th>
                                                    <th class="text-end text-primary">KM</th>
                                                    <th>Location (Start / End)</th>
                                                    <th class="text-end">Toll</th>
                                                    <th class="text-end">Helper</th>
                                                    <th class="text-end">Trp Cost</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reportTableBody">
                                                <tr><td colspan="14" class="text-center">Select filters and search</td></tr>
                                            </tbody>
                                            <tfoot>
                                                <tr class="fw-bold bg-light">
                                                    <td colspan="10" class="text-end">Totals:</td>
                                                    <td id="tblTotalToll" class="text-end">0.00</td>
                                                    <td id="tblTotalHelper" class="text-end">0.00</td>
                                                    <td class="text-end"> - </td>
                                                    <td id="tblTotalCost" class="text-end">0.00</td>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="ajax/js/trip-details-report.js?v=<?php echo time(); ?>"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Select an option",
                allowClear: true
            });
        });
    </script>
</body>
</html>
