<?php
include 'class/include.php';
include 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Customer Rental Frequency Report | <?php echo $COMPANY_PROFILE_DETAILS->name; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name; ?>" name="author" />
    <?php include 'main-css.php'; ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        @page { margin: 6mm 10mm 10mm 10mm; }
        body { margin: 10px 14px; }
        .print-only { display: none; }
        .print-header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }
        .print-header .company-name { font-size: 22px; font-weight: 700; text-transform: uppercase; }
        .print-header .company-meta { font-size: 13px; margin-top: 4px; }
        .print-title { font-size: 18px; font-weight: 700; text-align: center; margin: 12px 0 6px; }
        .print-sub { font-size: 13px; text-align: center; margin-bottom: 14px; }
        .summary-inline { display: flex; flex-wrap: wrap; gap: 18px; justify-content: center; margin-bottom: 12px; font-size: 14px; }
        .summary-inline .stat { font-weight: 700; }
        @media print {
            .no-print, .page-title-box, footer, #page-topbar, .vertical-menu, .main-content .page-content .container-fluid .row:first-child { display: none !important; }
            html, body { margin: 0 !important; padding: 0 !important; }
            .main-content { margin-left: 0 !important; padding-top: 0 !important; }
            .page-content { padding: 0 !important; }
            .container-fluid { padding: 0 !important; }
            .card { box-shadow: none !important; border: none !important; }
            .card-body { padding: 0 !important; }
            #printArea { padding: 0 !important; margin: 0 !important; }
            body { background-color: #fff !important; }
            .print-only { display: block !important; }
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
                                <h4 class="mb-0">Customer Rental Frequency - වැඩිම වාර ගණනින් කුලියටගත් පාරිභෝගික වාර්තාව</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card no-print">
                                <div class="card-body">
                                    <form id="freqReportForm">
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
                                                    <button type="button" class="btn btn-primary" id="searchBtn"><i class="mdi mdi-magnify me-1"></i> Search</button>
                                                    <button type="button" class="btn btn-secondary" id="resetBtn"><i class="mdi mdi-refresh me-1"></i> Reset</button>
                                                    <button type="button" class="btn btn-success" id="printBtn"><i class="mdi mdi-printer me-1"></i> Print</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div id="printArea">
                                        <div class="print-only">
                                            <div class="print-header">
                                                <div class="company-name"><?php echo $COMPANY_PROFILE_DETAILS->name ?? 'Company Name'; ?></div>
                                                <div class="company-meta">
                                                    <?php echo $COMPANY_PROFILE_DETAILS->address ?? ''; ?><br>
                                                    Tel: <?php echo $COMPANY_PROFILE_DETAILS->phone_number ?? ''; ?> | Email: <?php echo $COMPANY_PROFILE_DETAILS->email ?? ''; ?>
                                                </div>
                                            </div>
                                            <div class="print-title">Customer Rental Frequency Report</div>
                                            <div class="print-sub" id="printDateRange">&nbsp;</div>
                                            <div class="summary-inline">
                                                <div><span class="stat">Total Customers:</span> <span id="statTotalCustomers">0</span></div>
                                                <div><span class="stat">Total Rentals:</span> <span id="statTotalRentals">0</span></div>
                                                <div><span class="stat">Total Amount:</span> <span id="statTotalAmount">0.00</span></div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="frequencyTable" class="table table-bordered table-striped dt-responsive nowrap w-100">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Customer Code</th>
                                                        <th>Customer Name</th>
                                                        <th>Mobile</th>
                                                        <th class="text-center">Total Rentals</th>
                                                        <th class="text-end">Total Amount</th>
                                                        <th>Last Rental Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="frequencyTableBody">
                                                    <tr><td colspan="6" class="text-center">Loading...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
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
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="ajax/js/equipment-rent-frequency-report.js?v=<?php echo time(); ?>"></script>
    <script>
        $(function() {
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        });
    </script>
</body>
</html>
