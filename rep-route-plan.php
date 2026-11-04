<?php
include 'class/include.php';
include 'auth.php';

RepFollowup::ensureSchema();
$MARKETING_EXECUTIVE = new MarketingExecutive(NULL);
$REPS = $MARKETING_EXECUTIVE->getActiveExecutives();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Rep Route Plan | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        .table thead th { white-space: nowrap; }
        .stop-no { width: 30px; height: 30px; border-radius: 50%; background: #5b73e8; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; }
        .stop-no.done { background: #34c38f; }
        .wrap-cell { white-space: normal; max-width: 260px; }
        .available-list { max-height: 560px; overflow-y: auto; }
        @media print {
            .no-print, #page-topbar, .topnav, footer, .footer { display: none !important; }
            .main-content, .page-content { margin: 0 !important; padding: 0 !important; }
            .col-lg-7 { width: 100% !important; }
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
                            <h4 class="mb-0">Rep Route Plan</h4>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row no-print">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label for="routeRep" class="form-label">Rep</label>
                                        <select class="form-select" id="routeRep">
                                            <option value="">-- Select Rep --</option>
                                            <?php foreach ($REPS as $rep) { ?>
                                                <option value="<?php echo $rep['id']; ?>"><?php echo htmlspecialchars($rep['code'] . ' - ' . $rep['full_name']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="routeDate" class="form-label">Route Date</label>
                                        <div class="input-group">
                                            <button class="btn btn-light" type="button" id="prevDay" title="Previous day"><i class="mdi mdi-chevron-left"></i></button>
                                            <input type="text" class="form-control date-picker text-center" id="routeDate">
                                            <button class="btn btn-light" type="button" id="nextDay" title="Next day"><i class="mdi mdi-chevron-right"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-5 text-md-end">
                                        <button type="button" class="btn btn-primary" id="loadBtn"><i class="mdi mdi-magnify me-1"></i>Load</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Route -->
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <h5 class="mb-0" id="routeTitle">Route</h5>
                                    <div class="no-print d-flex flex-wrap gap-2" id="routeActions" style="display:none !important;">
                                        <span id="mapsButtons" class="d-flex flex-wrap gap-2"></span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="mdi mdi-printer me-1"></i>Print</button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center">#</th>
                                                <th>Bill / Customer</th>
                                                <th>Location</th>
                                                <th>Late</th>
                                                <th class="text-end">Outstanding</th>
                                                <th>Done</th>
                                                <th class="text-center no-print">Order</th>
                                            </tr>
                                        </thead>
                                        <tbody id="stopsBody">
                                            <tr><td colspan="7" class="text-center text-muted">Select a rep and date</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add stops -->
                    <div class="col-lg-5 no-print">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-1">Add Bills to Route</h5>
                                <p class="text-muted small mb-2">Bills assigned to this rep (assign bills from Overdue Rent Follow-up).</p>
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" class="form-control form-control-sm" id="availableSearch" placeholder="Filter by customer, bill, location...">
                                    <button type="button" class="btn btn-sm btn-primary text-nowrap" id="addStopsBtn"><i class="uil uil-plus me-1"></i>Add Selected</button>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="selectAllAvailable">
                                    <label class="form-check-label small" for="selectAllAvailable">Select all shown</label>
                                </div>
                                <div class="available-list list-group" id="availableList">
                                    <div class="text-center text-muted small py-3">Select a rep and date</div>
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
<script src="ajax/js/rep-route-plan.js?v=<?php echo time(); ?>"></script>
</body>
</html>
