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
        .equipment-row { background-color: #f0f4f8 !important; font-weight: 600; cursor: pointer; }
        .equipment-row:hover { background-color: #e2e8f0 !important; }
        .sub-equipment-row { background-color: #fff !important; }
        .sub-equipment-row td:nth-child(2) { padding-left: 35px !important; }
        .toggle-btn { width: 26px; height: 26px; padding: 0; font-size: 14px; font-weight: bold; line-height: 24px; border-radius: 4px; }
        .profit-positive { color: #28a745; }
        .profit-negative { color: #dc3545; }
        #reportTable tbody tr td { vertical-align: middle; }
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
                                        <div class="row align-items-end g-3">
                                            <div class="col-md-3">
                                                <label for="fromDate" class="form-label">From Date</label>
                                                <input type="text" class="form-control date-picker" id="fromDate" name="fromDate" autocomplete="off">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="toDate" class="form-label">To Date</label>
                                                <input type="text" class="form-control date-picker" id="toDate" name="toDate" autocomplete="off">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="equipmentCode" class="form-label">Equipment Code</label>
                                                <input type="text" class="form-control" id="equipmentCode" name="equipmentCode" placeholder="e.g. 021" autocomplete="off">
                                            </div>
                                            <div class="col-md-3">
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
                                <div class="col-lg-2 col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">කුලියට දුන් එකතුව ප්‍රමාණය</p><h5 class="mb-0" id="sumRentedQty">0</h5></div></div>
                                </div>
                                <div class="col-lg-2 col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">එකතුව කුලී අගය</p><h5 class="mb-0 text-primary" id="sumRentValue">0.00</h5></div></div>
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">එකතුව අලුත්වැඩියා වියදම</p><h5 class="mb-0 text-danger" id="sumRepairCost">0.00</h5></div></div>
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">එකතුව ලාභය</p><h5 class="mb-0 text-success" id="sumProfit">0.00</h5></div></div>
                                </div>
                                <div class="col-lg-2 col-md-3 col-sm-6">
                                    <div class="card mini-stats-wid"><div class="card-body"><p class="text-muted fw-medium mb-1">අගය එකතුව</p><h5 class="mb-0" id="sumValue">0.00</h5></div></div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="reportTable" class="table table-bordered dt-responsive nowrap w-100 mb-0">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th style="width:30px;"></th>
                                                    <th>අයිතම</th>
                                                    <th class="text-end">අගය</th>
                                                    <th class="text-end">කුලියට දුන් ප්‍රමාණය</th>
                                                    <th class="text-end">මුලු දින</th>
                                                    <th class="text-end">කුලී අගය</th>
                                                    <th class="text-end">අලුත්වැඩියා කල ප්‍රමාණය</th>
                                                    <th class="text-end">අලුත්වැඩියා වියදම</th>
                                                    <th class="text-end">ලාභය</th>
                                                    <th class="text-end">ආයෝජනයන් මත ප්‍රතිලාභ ප්‍රතිශතය (ROI)</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reportTableBody"></tbody>
                                            <tfoot>
                                                <tr class="fw-bold bg-light">
                                                    <th></th>
                                                    <th class="text-end">TOTAL</th>
                                                    <th class="text-end" id="footValue">-</th>
                                                    <th class="text-end" id="footRentedQty">-</th>
                                                    <th class="text-end" id="footBillableDays">-</th>
                                                    <th class="text-end" id="footRentValue">-</th>
                                                    <th class="text-end" id="footRepairQty">-</th>
                                                    <th class="text-end" id="footRepairCost">-</th>
                                                    <th class="text-end" id="footProfit">-</th>
                                                    <th class="text-end" id="footRoi">-</th>
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
