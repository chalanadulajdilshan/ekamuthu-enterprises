<?php
include 'class/include.php';
include 'auth.php';

RepFollowup::ensureSchema();
$MARKETING_EXECUTIVE = new MarketingExecutive(NULL);
$REPS = $MARKETING_EXECUTIVE->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Rep Visits & Collections | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        .table thead th { white-space: nowrap; }
        .summary-card { border-left: 4px solid #5b73e8; }
        .summary-card.success { border-left-color: #34c38f; }
        .summary-card.warning { border-left-color: #f1b44c; }
        .summary-card.danger { border-left-color: #f46a6a; }
        .proof-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 4px; }
        .wrap-cell { white-space: normal !important; max-width: 280px; }
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
                            <h4 class="mb-0">Rep Visits &amp; Collections</h4>
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
                                        <label for="fromDate" class="form-label">From</label>
                                        <input type="text" class="form-control date-picker" id="fromDate">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="toDate" class="form-label">To</label>
                                        <input type="text" class="form-control date-picker" id="toDate">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filterRep" class="form-label">Rep</label>
                                        <select class="form-select" id="filterRep">
                                            <option value="">All Reps</option>
                                            <?php foreach ($REPS as $rep) { ?>
                                                <option value="<?php echo $rep['id']; ?>"><?php echo htmlspecialchars($rep['full_name']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filterStatus" class="form-label">Payment Status</label>
                                        <select class="form-select" id="filterStatus">
                                            <option value="">All</option>
                                            <option value="no_proof">No Proof</option>
                                            <option value="proof_added">Proof Added</option>
                                            <option value="verified">Verified</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <button type="button" class="btn btn-primary" id="searchBtn"><i class="mdi mdi-magnify me-1"></i>Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card summary-card success"><div class="card-body py-3">
                            <p class="text-muted mb-1">Total Collected</p>
                            <h4 class="mb-0 text-success" id="sumCollected">0.00</h4>
                        </div></div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card"><div class="card-body py-3">
                            <p class="text-muted mb-1">Confirmed with Proof</p>
                            <h4 class="mb-0 text-primary" id="sumConfirmed">0.00</h4>
                        </div></div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card warning"><div class="card-body py-3">
                            <p class="text-muted mb-1">Without Proof (not confirmed)</p>
                            <h4 class="mb-0 text-warning" id="sumNoProof">0 / 0.00</h4>
                        </div></div>
                    </div>
                    <div class="col-md-3">
                        <div class="card summary-card danger"><div class="card-body py-3">
                            <p class="text-muted mb-1">Visits</p>
                            <h4 class="mb-0" id="sumVisits">0</h4>
                        </div></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabRepSummary" role="tab">By Rep</a></li>
                                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCollections" role="tab">Collections</a></li>
                                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabVisits" role="tab">Visits</a></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active" id="tabRepSummary" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped w-100">
                                                <thead>
                                                    <tr>
                                                        <th>Rep</th>
                                                        <th class="text-end">Visits</th>
                                                        <th class="text-end">Payments</th>
                                                        <th class="text-end">Collected</th>
                                                        <th class="text-end">Confirmed</th>
                                                        <th class="text-end">No Proof (Count)</th>
                                                        <th class="text-end">No Proof (Amount)</th>
                                                        <th class="text-end">Verified</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="repSummaryBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="tabCollections" role="tabpanel">
                                        <div class="table-responsive">
                                            <table id="collectionsTable" class="table table-bordered table-striped w-100">
                                                <thead>
                                                    <tr>
                                                        <th>Date / Time</th>
                                                        <th>Rep</th>
                                                        <th>Bill No</th>
                                                        <th>Customer</th>
                                                        <th>Method</th>
                                                        <th class="text-end">Amount</th>
                                                        <th>Proof</th>
                                                        <th>Status</th>
                                                        <th>Remarks</th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="tabVisits" role="tabpanel">
                                        <div class="table-responsive">
                                            <table id="visitsTable" class="table table-bordered table-striped w-100">
                                                <thead>
                                                    <tr>
                                                        <th>Date / Time</th>
                                                        <th>Rep</th>
                                                        <th>Bill No</th>
                                                        <th>Customer</th>
                                                        <th>Outcome</th>
                                                        <th>Promised Date</th>
                                                        <th>Remark</th>
                                                        <th>GPS</th>
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

            </div>
        </div>
        <?php include 'footer.php'; ?>
    </div>
</div>

<?php include 'main-js.php'; ?>
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="ajax/js/rep-activity-report.js?v=<?php echo time(); ?>"></script>
</body>
</html>
