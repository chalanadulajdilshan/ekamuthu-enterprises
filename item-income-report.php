<?php
include 'class/include.php';
include 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Item Wise Income Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        @media print {
            .no-print, .page-title-box, footer, #page-topbar, .vertical-menu, .main-content .page-content .container-fluid .row:first-child { display: none !important; }
            .main-content { margin-left: 0 !important; padding-top: 0 !important; }
            .card { box-shadow: none !important; border: none !important; }
            body { background-color: #fff !important; }
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
                                <h4 class="mb-0">Item Wise Income Report</h4>
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

                            <div class="row" id="summarySection" style="display:none;">
                                <div class="col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">Rental</p><h5 class="mb-0" id="sumRental">0.00</h5></div></div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">Extra Day</p><h5 class="mb-0" id="sumExtraDay">0.00</h5></div></div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">Penalty</p><h5 class="mb-0" id="sumPenalty">0.00</h5></div></div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">Additional</p><h5 class="mb-0 text-success" id="sumAdditional">0.00</h5></div></div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">Damage</p><h5 class="mb-0 text-danger" id="sumDamage">0.00</h5></div></div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="reportTable" class="table table-bordered table-striped dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>Item / Sub Equipment</th>
                                                    <th class="text-end">Returned Qty</th>
                                                    <th class="text-end">Rental</th>
                                                    <th class="text-end">Extra Day</th>
                                                    <th class="text-end">Penalty</th>
                                                    <th class="text-end">Additional</th>
                                                    <th class="text-end">Damage</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reportTableBody"></tbody>
                                            <tfoot>
                                                <tr class="fw-bold bg-light">
                                                    <th class="text-end">TOTAL</th>
                                                    <td></td>
                                                    <td class="text-end" id="sumRentalFoot">-</td>
                                                    <td class="text-end" id="sumExtraDayFoot">-</td>
                                                    <td class="text-end" id="sumPenaltyFoot">-</td>
                                                    <td class="text-end" id="sumAdditionalFoot">-</td>
                                                    <td class="text-end" id="sumDamageFoot">-</td>
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
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="ajax/js/item-income-report.js?v=<?php echo time(); ?>"></script>
    <script>
        $(function() {
            $(".date-picker").datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true });
        });
    </script>
</body>
</html>
