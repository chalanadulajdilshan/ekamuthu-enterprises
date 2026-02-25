<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

// Get new Return Note Code from Document Tracking
$DOCUMENT_TRACKING = new DocumentTracking(1);
$lastId = $DOCUMENT_TRACKING->issue_return_id ?? 0;
// RN/1/01
$return_note_code = ($lastId + 1);

?>
<head>
    <meta charset="utf-8" />
    <title>Warehouse Issue Return Note | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <div id="page-preloader" class="preloader full-preloader">
        <div class="preloader-container">
            <div class="preloader-animation"></div>
        </div>
    </div>

    <div id="layout-wrapper">
        <?php include 'navigation.php' ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="row mb-4">
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                            <a href="#" class="btn btn-success" id="new_return">
                                <i class="uil uil-plus me-1"></i> New Return
                            </a>
                            <button class="btn btn-primary" id="save_return">
                                <i class="uil uil-save me-1"></i> Save Return Note
                            </button>
                            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#ReturnHistoryModal">
                                <i class="uil uil-history me-1"></i> View History
                            </button>
                            <button class="btn btn-info" id="print_return" style="display: none;">
                                <i class="uil uil-print me-1"></i> Print Return Note
                            </button>
                        </div>
                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                             <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Warehouse Issue Return Note</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Main Form -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    <div class="avatar-title rounded-circle bg-soft-success text-success">
                                                        01
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center flex-grow-1">
                                                <div class="me-3">
                                                    <h5 class="font-size-16 mb-1">Items Return Note - උපකරණ ආපසු ලබාගැනීම</h5>
                                                    <p class="text-muted text-truncate mb-0">Record items returned against Issue Notes</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="p-4">
                                    <form id="return_note_form">
                                        <div class="row mb-4">
                                            <div class="col-md-3">
                                                <label class="form-label">Return Note Code - පත්‍රිකා අංකය</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="return_note_code" value="<?php echo $return_note_code ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Return Date - ආපසු ලැබුණු දිනය</label>
                                                <input type="text" class="form-control date-picker" id="return_date" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Select Issue Note - නිකුත් කිරීමේ පත්‍රිකාව</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="selected_issue_display" placeholder="Select an Issue Note..." readonly>
                                                    <input type="hidden" id="issue_note_id">
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#IssueNoteModal">
                                                        <i class="uil uil-search"></i> Select
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Department</label>
                                                <select class="form-select" id="department_id">
                                                    <option value="">Select Department...</option>
                                                    <?php
                                                    $DEPARTMENTS = new DepartmentMaster(null);
                                                    foreach ($DEPARTMENTS->all() as $dept) {
                                                        echo '<option value="' . $dept['id'] . '">' . $dept['name'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Customer Details</label>
                                                <input type="text" class="form-control mb-2" id="customer_name" readonly placeholder="Customer Name">
                                                <input type="text" class="form-control" id="customer_phone" readonly placeholder="Phone Number">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Remarks</label>
                                                <textarea class="form-control" id="remarks" rows="3" placeholder="Enter remarks..."></textarea>
                                            </div>
                                        </div>

                                        <!-- Items Table -->
                                        <div class="row">
                                            <div class="col-12">
                                                <h5 class="font-size-16 mb-3">Items to Return</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped" id="returnItemsTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 50px;">#</th>
                                                                <th>Item Name</th>
                                                                <th style="width: 120px;">Issued Qty</th>
                                                                <th style="width: 120px;">Prev Returned</th>
                                                                <th style="width: 120px;">Remaining</th>
                                                                <th style="width: 150px;">Return Now</th>
                                                                <th>Remarks</th>
                                                                <th style="width: 50px;">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr id="empty_row">
                                                                <td colspan="8" class="text-center py-4 text-muted">
                                                                    <i class="uil uil-box font-size-24 d-block mb-2"></i>
                                                                    Select an Issue Note to load items
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include 'footer.php' ?>
        </div>
    </div>

    <!-- Issue Note Selection Modal -->
    <div class="modal fade bs-example-modal-xl" id="IssueNoteModal" tabindex="-1" role="dialog" aria-labelledby="IssueNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="IssueNoteModalLabel">Select Issue Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="issueNoteTable" class="table table-bordered table-hover dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Issue Note No</th>
                                        <th>Ref Invoice</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Issue Return Note History Modal -->
    <div class="modal fade bs-example-modal-xl" id="ReturnHistoryModal" tabindex="-1" role="dialog" aria-labelledby="ReturnHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ReturnHistoryModalLabel">Return Note History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="returnHistoryTable" class="table table-bordered table-hover dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Return Note No</th>
                                        <th>Issue Note Ref</th>
                                        <th>Customer</th>
                                        <th>Department</th>
                                        <th>Date</th>
                                        <th>Remarks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <?php include 'main-js.php' ?>
    <script src="ajax/js/issue-return-note.js"></script>

    <script>
        $(window).on('load', function() {
            $('#page-preloader').fadeOut('slow', function() {
                $(this).remove();
            });
        });
    </script>
</body>
</html>
