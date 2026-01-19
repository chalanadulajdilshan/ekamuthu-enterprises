<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$id = '';
if(isset($_GET['id'])) {
    $id = $_GET['id'];
}
$CUSTOMER = new CustomerMaster($id);
?>
<head>
    <meta charset="utf-8" />
    <title>Manage Old Outstanding | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

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

                    <!-- Breadcrumbs -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Manage Old Outstanding - <?php echo $CUSTOMER->name; ?> (<?php echo $CUSTOMER->code; ?>)</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="customer-master.php">Customer Master</a></li>
                                        <li class="breadcrumb-item active">Old Outstanding</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Add Record</h4>
                                    <form id="oldOutstandingForm">
                                        <input type="hidden" id="customer_id" name="detail_customer_id" value="<?php echo $id; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="detail_invoice_no" class="form-label">Invoice No <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="detail_invoice_no" name="detail_invoice_no" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="detail_date" class="form-label">Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="detail_date" name="detail_date" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="detail_amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control" id="detail_amount" name="detail_amount" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="detail_status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select" id="detail_status" name="detail_status" required>
                                                <option value="Not Paid" selected>Not Paid</option>
                                                <option value="Paid">Paid</option>
                                            </select>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="button" class="btn btn-primary" id="saveRecord">
                                                <i class="uil uil-save me-1"></i> Add Record
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Existing Records</h4>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="recordsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Invoice No</th>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Loading via JS -->
                                            </tbody>
                                        </table>
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


    <?php include 'main-js.php' ?>
    
    <!-- Page Specific JS -->
    <script src="ajax/js/manage-old-outstanding.js"></script>

</body>
</html>
