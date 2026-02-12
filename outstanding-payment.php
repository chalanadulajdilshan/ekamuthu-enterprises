<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Outstanding Rent Payment | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .payment-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
        }
        .total-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #d9534f;
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
                                <h4 class="mb-0">Outstanding Rent Payment</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Customer Selection & List -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Select Customer</h5>
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input id="customer_code" name="customer_code" type="text" placeholder="Select Customer" class="form-control" readonly>
                                                <input type="hidden" id="customer_id" name="customer_id">
                                                <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#customerModal">
                                                    <i class="uil uil-search me-1"></i> Search
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <button id="refreshBtn" class="btn btn-secondary"><i class="uil uil-refresh"></i> Refresh List</button>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="outstandingTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 40px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                                        </div>
                                                    </th>
                                                    <th>Date</th>
                                                    <th>Bill No</th>
                                                    <th>Item</th>
                                                    <th class="text-end">Outstanding Amount</th>
                                                    <th class="text-end">Pay Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody id="outstandingTableBody">
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">Please select a customer to view outstanding items.</td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="4" class="text-end">Total Outstanding:</th>
                                                    <th class="text-end" id="totalOutstandingDisplay">0.00</th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Payment Details</h5>
                                    
                                    <div class="payment-box mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Selected Total:</span>
                                            <span class="total-display" id="selectedTotal">0.00</span>
                                        </div>
                                    </div>

                                    <form id="paymentForm">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Date</label>
                                            <input type="text" class="form-control date-picker" id="paymentDate" value="<?php echo date('Y-m-d'); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Payment Method</label>
                                            <select class="form-select" id="paymentMethod">
                                                <option value="1">Cash</option>
                                                <option value="2">Cheque</option>
                                                <option value="3">Bank Transfer</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Bank Details -->
                                        <div id="bankDetails" style="display: none;">
                                            <div class="mb-3">
                                                <label class="form-label">Bank</label>
                                                <select class="form-select" id="bank_id">
                                                    <option value="">Select Bank</option>
                                                    <?php
                                                    $BANK = new Bank(NULL);
                                                    foreach ($BANK->all() as $bank) {
                                                        echo '<option value="' . $bank['id'] . '">' . $bank['name'] . ' (' . $bank['code'] . ')</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Branch</label>
                                                <select class="form-select" id="branch_id" disabled>
                                                    <option value="">Select Bank First</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Cheque Specific -->
                                        <div id="chequeDetails" style="display: none;">
                                            <div class="mb-3">
                                                <label class="form-label">Cheque Date</label>
                                                <input type="text" class="form-control date-picker" id="chequeDate" placeholder="YYYY-MM-DD">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Cheque No</label>
                                                <input type="text" class="form-control" id="chequeNo" placeholder="Enter cheque number">
                                            </div>
                                        </div>

                                        <!-- Transfer Specific -->
                                        <div id="transferDetails" style="display: none;">
                                            <div class="mb-3">
                                                <label class="form-label">Transfer Date</label>
                                                <input type="text" class="form-control date-picker" id="transferDate" placeholder="YYYY-MM-DD">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Account No</label>
                                                <input type="text" class="form-control" id="accountNo" placeholder="Enter account number">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Reference No</label>
                                                <input type="text" class="form-control" id="refNo" placeholder="Enter reference number">
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="button" id="payBtn" class="btn btn-primary btn-lg" disabled>
                                                <i class="uil uil-check-circle me-1"></i> Pay Selected
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- Customer Modal -->
    <?php include 'customer-master-model.php'; ?>
    
    <?php include 'main-js.php'; ?>
    <script src="ajax/js/common.js"></script>
    
    <!-- Required Scripts -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
    
    <!-- Page Specific JS -->
    <script src="ajax/js/outstanding-payment.js"></script>

</body>
</html>
