<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

// Get new Issue Note Code
$DOCUMENT_TRACKING = new DocumentTracking(1);
$lastId = $DOCUMENT_TRACKING->issue_note_id ?? 0;
// $issue_note_code = 'IN/' . ($_SESSION['id'] ?? '0') . '/0' . ($lastId + 1);

$issue_note_code = ($lastId + 1);

?>
<head>
    <meta charset="utf-8" />
    <title>Warehouse Issue Note | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
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
                            <a href="#" class="btn btn-success" id="new_note">
                                <i class="uil uil-plus me-1"></i> New Note
                            </a>
                            <button class="btn btn-primary" id="save_note">
                                <i class="uil uil-save me-1"></i> Save Issue Note
                            </button>
                            <button class="btn btn-info" id="print_note" style="display: none;">
                                <i class="uil uil-print me-1"></i> Print
                            </button>
                        </div>
                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                             <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Warehouse Issue Note</li>
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
                                                    <div class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                        01
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center flex-grow-1">
                                                <div class="me-3">
                                                    <h5 class="font-size-16 mb-1">Warehouse Issue Note</h5>
                                                    <p class="text-muted text-truncate mb-0">Issue items based on confirmed Rent Invoices</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="p-4">
                                    <form id="issue_note_form">
                                        <div class="row mb-4">
                                            <div class="col-md-3">
                                                <label class="form-label">Issue Note Code - පත්‍රිකා අංකය</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="issue_note_code" value="<?php echo $issue_note_code ?>"  >
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#IssueNoteHistoryModal">
                                                        <i class="uil uil-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Issue Date - නිකුත් කරන දිනය</label>
                                                <input type="text" class="form-control date-picker" id="issue_date" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Select Rent Invoice - ඉන්වොයිසිය තෝරන්න</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="selected_invoice_display" placeholder="Select a Rent Invoice..." readonly>
                                                    <input type="hidden" id="rent_invoice_id">
                                                    <input type="hidden" id="customer_id">
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#RentInvoiceModal">
                                                        <i class="uil uil-search"></i> Select
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" id="issue_status">
                                                    <option value="issued" selected>Issued</option>
                                                    <option value="pending">Pending</option>
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
                                                <h5 class="font-size-16 mb-3">Items to Issue</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped" id="issueItemsTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 50px;">#</th>
                                                                <th>Item Name</th>
                                                                <th>Rent Type</th>
                                                                <th style="width: 100px;">Ordered</th>
                                                                <th style="width: 100px;">Issued</th>
                                                                <th style="width: 100px;">Remaining</th>
                                                                <th style="width: 120px;">Issue Now</th>
                                                                <th>Remarks</th>
                                                                <th style="width: 50px;">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Items will be loaded here via AJAX -->
                                                            <tr id="empty_row">
                                                                <td colspan="9" class="text-center py-4 text-muted">
                                                                    <i class="uil uil-box font-size-24 d-block mb-2"></i>
                                                                    Select a Rent Invoice to load items
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Issue History Section -->
                                        <div class="row mt-4" id="issueHistoryContainer" style="display:none;">
                                            <div class="col-12">
                                                <h5 class="font-size-16 mb-3">Previous Issue Notes for this Invoice</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover mb-0" id="issueHistoryTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Issue Note No</th>
                                                                <th>Status</th>
                                                                <th>Total Issued</th>
                                                                <th>Created At</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
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

    <!-- Rent Invoice Selection Modal -->
    <div class="modal fade bs-example-modal-xl" id="RentInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="RentInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="RentInvoiceModalLabel">Select Rent Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="rentInvoiceTable" class="table table-bordered table-hover dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Ref No</th>
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

    <!-- Issue Note History Modal -->
    <div class="modal fade bs-example-modal-xl" id="IssueNoteHistoryModal" tabindex="-1" role="dialog" aria-labelledby="HistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="HistoryModalLabel">Issue Note History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="issueNoteHistoryTable" class="table table-bordered table-hover dt-responsive nowrap w-100">
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

    <div class="rightbar-overlay"></div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <?php include 'main-js.php' ?>
    <script src="ajax/js/issue-note.js"></script>

    <script>
        $(window).on('load', function() {
            $('#page-preloader').fadeOut('slow', function() {
                $(this).remove();
            });
        });
    </script>
</body>
</html>
