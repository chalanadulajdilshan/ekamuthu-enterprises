<?php
include 'class/include.php';
include 'auth.php';

$MARKETING_EXECUTIVE = new MarketingExecutive(NULL);
$REPS = $MARKETING_EXECUTIVE->getActiveExecutives();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Overdue Rent Follow-up | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        .table thead th { white-space: nowrap; }
        .summary-card { border-left: 4px solid #5b73e8; }
        .summary-card.danger { border-left-color: #f46a6a; }
        .summary-card.warning { border-left-color: #f1b44c; }
        .summary-card.muted { border-left-color: #74788d; }
        .late-badge { font-size: 12px; white-space: nowrap; }
        .items-list { margin: 0; padding-left: 16px; font-size: 12px; }
        .bulk-bar { background: #f8f9fa; border-radius: 4px; padding: 10px; }
        .location-cell { max-width: 220px; white-space: normal !important; }
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
                            <h4 class="mb-0">Overdue Rent Follow-up</h4>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-2">
                                        <label for="asOfDate" class="form-label">As of Date</label>
                                        <input type="text" class="form-control date-picker" id="asOfDate">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filterLocation" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="filterLocation" list="locationList" placeholder="Type location...">
                                        <datalist id="locationList"></datalist>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filterLate" class="form-label">Late Period</label>
                                        <select class="form-select" id="filterLate">
                                            <option value="">All</option>
                                            <option value="1-7">1 - 7 days</option>
                                            <option value="8-30">8 - 30 days</option>
                                            <option value="31-90">1 - 3 months</option>
                                            <option value="91-180">3 - 6 months</option>
                                            <option value="181-365">6 - 12 months</option>
                                            <option value="366-">Over 1 year</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filterRep" class="form-label">Rep</label>
                                        <select class="form-select" id="filterRep">
                                            <option value="">All</option>
                                            <option value="none">Unassigned</option>
                                            <?php foreach ($REPS as $rep) { ?>
                                                <option value="<?php echo $rep['id']; ?>"><?php echo htmlspecialchars($rep['full_name']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <button type="button" class="btn btn-primary me-1" id="searchBtn"><i class="mdi mdi-magnify me-1"></i>Search</button>
                                        <button type="button" class="btn btn-secondary" id="resetBtn"><i class="mdi mdi-refresh me-1"></i>Reset</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card summary-card danger">
                            <div class="card-body py-3">
                                <p class="text-muted mb-1">Follow-up Bills</p>
                                <h4 class="mb-0" id="sumFollowup">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card warning">
                            <div class="card-body py-3">
                                <p class="text-muted mb-1">Not Assigned to a Rep</p>
                                <h4 class="mb-0" id="sumUnassigned">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card muted">
                            <div class="card-body py-3">
                                <p class="text-muted mb-1">No Follow-up Needed</p>
                                <h4 class="mb-0" id="sumNoFollowup">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card">
                            <div class="card-body py-3">
                                <p class="text-muted mb-1">Projected Outstanding (Follow-up)</p>
                                <h4 class="mb-0 text-danger" id="sumProjected">0.00</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#tabFollowup" role="tab">
                                            Follow-up Required <span class="badge bg-danger ms-1" id="tabFollowupCount">0</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#tabNoFollowup" role="tab">
                                            No Follow-up <span class="badge bg-secondary ms-1" id="tabNoFollowupCount">0</span>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Follow-up required -->
                                    <div class="tab-pane active" id="tabFollowup" role="tabpanel">
                                        <div class="bulk-bar d-flex flex-wrap align-items-center gap-2 mb-3">
                                            <span class="fw-semibold me-2"><span id="followupSelectedCount">0</span> selected</span>
                                            <select class="form-select form-select-sm w-auto" id="assignRep">
                                                <option value="">-- Select Rep --</option>
                                                <?php foreach ($REPS as $rep) { ?>
                                                    <option value="<?php echo $rep['id']; ?>"><?php echo htmlspecialchars($rep['code'] . ' - ' . $rep['full_name']); ?></option>
                                                <?php } ?>
                                            </select>
                                            <button type="button" class="btn btn-sm btn-primary" id="assignBtn"><i class="uil uil-user-check me-1"></i>Assign to Rep</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="unassignBtn"><i class="uil uil-user-times me-1"></i>Remove Rep</button>
                                            <button type="button" class="btn btn-sm btn-outline-dark ms-md-auto" id="markNoFollowupBtn"><i class="uil uil-ban me-1"></i>Mark No Follow-up</button>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="followupTable" class="table table-bordered table-striped w-100">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center"><input type="checkbox" class="form-check-input select-all" data-table="followupTable"></th>
                                                        <th>Bill No</th>
                                                        <th>Customer</th>
                                                        <th>Location</th>
                                                        <th>Pending</th>
                                                        <th>Due Date</th>
                                                        <th>Late</th>
                                                        <th class="text-end">Paid</th>
                                                        <th class="text-end">Projected O/S</th>
                                                        <th class="text-end">Recorded O/S</th>
                                                        <th>Rep</th>
                                                        <th class="text-center">Details</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- No follow-up -->
                                    <div class="tab-pane" id="tabNoFollowup" role="tabpanel">
                                        <div class="bulk-bar d-flex flex-wrap align-items-center gap-2 mb-3">
                                            <span class="fw-semibold me-2"><span id="noFollowupSelectedCount">0</span> selected</span>
                                            <button type="button" class="btn btn-sm btn-danger" id="markFollowupBtn"><i class="uil uil-redo me-1"></i>Move to Follow-up</button>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="noFollowupTable" class="table table-bordered table-striped w-100">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center"><input type="checkbox" class="form-check-input select-all" data-table="noFollowupTable"></th>
                                                        <th>Bill No</th>
                                                        <th>Customer</th>
                                                        <th>Location</th>
                                                        <th>Pending</th>
                                                        <th>Due Date</th>
                                                        <th>Late</th>
                                                        <th class="text-end">Paid</th>
                                                        <th class="text-end">Projected O/S</th>
                                                        <th class="text-end">Recorded O/S</th>
                                                        <th>Reason</th>
                                                        <th class="text-center">Details</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payments Modal -->
                <div class="modal fade" id="paymentsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Bill Details - <span id="payBillNo"></span></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-1"><strong>Customer:</strong> <span id="payCustomer">-</span></p>
                                <p class="mb-3"><strong>Late:</strong> <span id="payLate">-</span></p>
                                <div id="paymentsBody"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
<script src="ajax/js/rent-overdue-followup.js?v=<?php echo time(); ?>"></script>
</body>
</html>
