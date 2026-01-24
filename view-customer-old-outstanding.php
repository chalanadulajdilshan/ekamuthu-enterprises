<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
?>
<head>
    <meta charset="utf-8" />
    <title>View Old Outstanding | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <!-- Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
            padding-left: 12px;
        }
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
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
                                <h4 class="mb-0">View Customer Old Outstanding</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="customer-master.php">Customer Master</a></li>
                                        <li class="breadcrumb-item active">View Old Outstanding</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Selection -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <label class="form-label font-size-16">Select Customer</label>
                                    <div class="input-group">
                                        <input type="hidden" id="customer_select"> <!-- Hidden ID -->
                                        <input type="text" class="form-control" id="customer_name_display" placeholder="Select a customer..." readonly>
                                        <button class="btn btn-primary" type="button" id="btnSelectCustomer">
                                            <i class="uil uil-search me-1"></i> Find Customer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php include 'old-outstanding-customer-model.php'; ?>

                    <!-- Customer Details Card (Hidden initially) -->
                    <div id="customer_details_section" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="uil uil-user me-2"></i>Customer Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <p class="mb-2"><strong>Customer Code:</strong></p>
                                                <p id="customer_code_display" class="text-muted">-</p>
                                            </div>
                                            <div class="col-md-3">
                                                <p class="mb-2"><strong>Name:</strong></p>
                                                <p id="customer_name_detail" class="text-muted">-</p>
                                            </div>
                                            <div class="col-md-3">
                                                <p class="mb-2"><strong>NIC:</strong></p>
                                                <p id="customer_nic_display" class="text-muted">-</p>
                                            </div>
                                            <div class="col-md-3">
                                                <p class="mb-2"><strong>Mobile:</strong></p>
                                                <p id="customer_mobile_display" class="text-muted">-</p>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <p class="mb-2"><strong>Address:</strong></p>
                                                <p id="customer_address_display" class="text-muted">-</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards (Hidden initially) -->
                    <div id="summary_section" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mini-stats-wid">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <p class="text-muted fw-medium">Total Old Outstanding</p>
                                                <h4 class="mb-0" id="stat_total">0.00</h4>
                                            </div>
                                            <div class="flex-shrink-0 align-self-center">
                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                                    <span class="avatar-title">
                                                        <i class="uil uil-bill font-size-24"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mini-stats-wid">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <p class="text-muted fw-medium">Total Paid</p>
                                                <h4 class="mb-0 text-success" id="stat_paid">0.00</h4>
                                            </div>
                                            <div class="flex-shrink-0 align-self-center">
                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                                                    <span class="avatar-title">
                                                        <i class="uil uil-check-circle font-size-24"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mini-stats-wid">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <p class="text-muted fw-medium">Payable Balance</p>
                                                <h4 class="mb-0 text-danger" id="stat_payable">0.00</h4>
                                            </div>
                                            <div class="flex-shrink-0 align-self-center">
                                                <div class="mini-stat-icon avatar-sm rounded-circle bg-danger">
                                                    <span class="avatar-title">
                                                        <i class="uil uil-exclamation-triangle font-size-24"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Details Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Outstanding Details</h4>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered dt-responsive nowrap" id="detailsTable" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Invoice No</th>
                                                        <th>Date</th>
                                                        <th>Status</th>
                                                        <th class="text-end">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Loaded via AJAX -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Payment Collection Section -->
                        <div class="row">
                            <div class="col-lg-5">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Add Payment</h4>
                                        <form id="paymentForm">
                                            <input type="hidden" id="pay_customer_id" name="pay_customer_id">
                                            
                                            <div class="mb-3">
                                                <label for="pay_invoice_id" class="form-label">Select Invoice</label>
                                                <select class="form-select select2" id="pay_invoice_id" name="pay_invoice_id" style="width: 100%;" required>
                                                    <option value="">Select Invoice...</option>
                                                    <!-- Loaded via JS -->
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="pay_date" class="form-label">Date</label>
                                                <input type="date" class="form-control" id="pay_date" name="pay_date" value="<?php echo date('Y-m-d'); ?>" readonly>
                                            </div>

                                            <div class="mb-3">
                                                <label for="pay_amount" class="form-label">Amount</label>
                                                <input type="number" step="0.01" class="form-control" id="pay_amount" name="pay_amount" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="pay_remark" class="form-label">Remark</label>
                                                <textarea class="form-control" id="pay_remark" name="pay_remark" rows="2"></textarea>
                                            </div>

                                            <div class="text-end">
                                                <button type="button" class="btn btn-success" id="btnPay">
                                                    <i class="uil uil-money-bill me-1"></i> Pay Now
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Payment History</h4>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered dt-responsive nowrap" id="paymentHistoryTable" style="width: 100%;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Invoice No</th>
                                                        <th>Amount</th>
                                                        <th>Remark</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Loaded via JS -->
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
    <script src="ajax/js/view-customer-old-outstanding.js"></script>

</body>
</html>
