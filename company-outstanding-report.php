<?php
include 'class/include.php';
include 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Company Outstanding Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <style>
        #reportTable thead th.outstanding-column,
        #reportTable tbody td.outstanding-column {
            background-color: #fff7e6 !important;
        }

        #totalCompanyOutstanding {
            background-color: #f59e0b !important;
            color: #ffffff !important;
        }

        /* Print friendly */
        @media print {
            #printArea {
                margin-top: 10px;
            }

            .no-print,
            nav,
            .navbar,
            .left-side-menu,
            #topnav,
            footer,
            .page-title-box,
            .card .card-body form,
            .card .card-body .no-print-row {
                display: none !important;
            }

            body {
                background: #fff;
            }

            table {
                border-collapse: collapse !important;
            }

            th,
            td {
                border: 1px solid #ddd !important;
                padding: 6px !important;
            }
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">
    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Company Outstanding Report</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="reportForm" class="no-print">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-3">
                                                <label for="billNo" class="form-label">Bill Number</label>
                                                <input type="text" id="billNo" name="billNo" class="form-control" placeholder="Search by bill no">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="fromDate" class="form-label">From Date</label>
                                                <div class="input-group" id="datepicker1">
                                                    <input type="text" class="form-control date-picker" id="fromDate" name="fromDate">
                                                    <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="toDate" class="form-label">To Date</label>
                                                <div class="input-group" id="datepicker2">
                                                    <input type="text" class="form-control date-picker" id="toDate" name="toDate">
                                                    <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="companyOnly" checked>
                                                    <label class="form-check-label" for="companyOnly">Company Outstanding only</label>
                                                </div>
                                                <div class="d-flex gap-2 no-print-row">
                                                    <button type="button" class="btn btn-primary" id="searchBtn"><i class="mdi mdi-magnify me-1"></i> Search</button>
                                                    <button type="button" class="btn btn-secondary" id="resetBtn"><i class="mdi mdi-refresh me-1"></i> Reset</button>
                                                    <button type="button" class="btn btn-outline-dark" id="printBtn"><i class="mdi mdi-printer me-1"></i> Print</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
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
                                                <th>#ID</th>
                                                <th>Bill</th>
                                                <th>Customer</th>
                                                <th>Issue Date</th>
                                                <th>Received Date</th>
                                                <th class="text-end">Items</th>
                                                <th>Status</th>
                                                <th class="text-end outstanding-column">Company Outstanding</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody"></tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="7" class="text-end">Total Company Outstanding:</th>
                                                <td id="totalCompanyOutstanding" class="text-end outstanding-column">0.00</td>
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
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="ajax/js/company-outstanding-report.js"></script>
    <script>
        $(function () {
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '1900:2099',
                showButtonPanel: true,
                showOn: 'focus',
                showAnim: 'fadeIn'
            });
        });
    </script>
</body>

</html>
