<?php
include 'class/include.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Outstanding Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom styles to match print report */
        .report-summary-box {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .report-stat-item {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .report-stat-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 8px;
            display: block;
        }
        .report-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #212529;
        }
        .text-danger-custom { color: #dc3545; }
        .text-success-custom { color: #198754; }

        /* Table Styling Overrides */
        .table thead th {
            background-color: #f1f3f5;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 2px solid #dee2e6;
            vertical-align: middle;
        }
        .table tbody td {
            vertical-align: middle;
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
                                <h4 class="mb-0">Outstanding Report</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            
                            <!-- Summary Cards -->
                            <div class="report-summary-box">
                                <div class="report-stat-item">
                                    <span class="report-stat-label">Total Rent</span>
                                    <span class="report-stat-value" id="cardTotalRent">Rs. 0.00</span>
                                </div>
                                <div class="report-stat-item">
                                    <span class="report-stat-label">Total Paid</span>
                                    <span class="report-stat-value text-success-custom" id="cardTotalPaid">Rs. 0.00</span>
                                </div>
                                <div class="report-stat-item">
                                    <span class="report-stat-label">Total Outstanding</span>
                                    <span class="report-stat-value text-danger-custom" id="cardTotalBalance">Rs. 0.00</span>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Filter Report</h5>
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Customer</label>
                                            <div class="input-group">
                                                <input id="customer_code" name="customer_code" type="text" placeholder="Select Customer (Optional)" class="form-control" readonly>
                                                <input type="hidden" id="customer_id" name="customer_id">
                                                <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#customerModal">
                                                    <i class="uil uil-search me-1"></i>
                                                </button>
                                                <button class="btn btn-secondary" type="button" id="clearCustomer">
                                                    <i class="uil uil-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end flex-wrap gap-2">
                                            <button id="generateBtn" class="btn btn-primary"><i class="uil uil-file-alt"></i> Generate Report</button>
                                            <button id="printBtn" class="btn btn-success"><i class="uil uil-print"></i> Print (Detailed)</button>
                                            <button id="printSummaryBtn" class="btn btn-outline-success"><i class="uil uil-print"></i> Print (Summary)</button>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="reportTable">
                                            <thead>
                                                <tr>
                                                    <th style="width:40px;"></th>
                                                    <th>Invoice No</th>
                                                    <th>Date</th>
                                                    <th>Payment Type</th>
                                                    <th>Customer Name</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Total Rent Amount</th>
                                                    <th class="text-end">Total Paid Amount</th>
                                                    <th class="text-end">Balance (Outstanding)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data loaded via AJAX -->
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="6" class="text-end">Total:</th>
                                                    <th class="text-end" id="totalRent">0.00</th>
                                                    <th class="text-end" id="totalPaid">0.00</th>
                                                    <th class="text-end" id="totalBalance">0.00</th>
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

    <!-- Bill Detail Modal -->
    <div class="modal fade" id="billDetailModal" tabindex="-1" aria-labelledby="billDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="billDetailModalLabel">Bill Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Invoice No:</strong> <span id="billModalInvoice">-</span></div>
                        <div class="col-md-3"><strong>Date:</strong> <span id="billModalDate">-</span> <small class="text-muted" id="billModalDayCount"></small></div>
                        <div class="col-md-3"><strong>Payment Type:</strong> <span id="billModalPayment">-</span></div>
                        <div class="col-md-3"><strong>Status:</strong> <span id="billModalStatus">-</span></div>
                        <div class="col-md-6"><strong>Customer:</strong> <span id="billModalCustomer">-</span></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between"><span>Total Rent</span><span id="billModalTotalRent">0.00</span></div>
                                <div class="d-flex justify-content-between"><span>Total Paid</span><span id="billModalTotalPaid">0.00</span></div>
                                <div class="d-flex justify-content-between fw-bold"><span>Balance</span><span id="billModalBalance">0.00</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between"><span>Recorded Outstanding</span><span id="billModalRecordedTotal">0.00</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between"><span>Projected Outstanding</span><span id="billModalProjectedTotal">0.00</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold mb-2">Bill Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalItems">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-center">Duration</th>
                                                <th class="text-center">Type</th>
                                                <th class="text-center">Returned</th>
                                                <th class="text-center">Pending</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="7" class="text-muted">No items found.</td></tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="7" class="text-end">Total</th>
                                                <th class="text-end" id="billModalItemsTotal">0.00</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold mb-2">Recorded Outstanding</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalRecorded">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Return Date</th>
                                                <th>Item</th>
                                                <th class="text-end">Outstanding</th>
                                                <th class="text-end">Paid</th>
                                                <th>Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="5" class="text-muted">No recorded outstanding entries.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold mb-2">Payment History</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalPayments">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Receipt No</th>
                                                <th class="text-end">Amount</th>
                                                <th>Method / Reference</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="4" class="text-muted">No payments recorded.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <h6 class="fw-bold mb-2 mt-3">Deposit Payments</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalDeposits">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Remark</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="3" class="text-muted">No deposit payments recorded.</td></tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-end">Total</th>
                                                <th class="text-end" id="billModalDepositTotal">0.00</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold mb-2">Return History</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="billModalReturns">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Item</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Rental</th>
                                                <th class="text-end">Extra</th>
                                                <th class="text-end">Penalty</th>
                                                <th class="text-end">Damage</th>
                                                <th class="text-end">Extra Charge</th>
                                                <th class="text-end">Repair</th>
                                                <th class="text-end">Settlement</th>
                                                <th class="text-end">Paid</th>
                                                <th class="text-end">Outstanding</th>
                                                <th>Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="14" class="text-muted">No return history found.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="billModalPrint"><i class="uil uil-print me-1"></i> Print</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Modal -->
    <?php include 'customer-master-model.php'; ?>

    <?php include 'main-js.php'; ?>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="ajax/js/common.js"></script>
    
    <!-- Page Specific JS -->
    <script src="ajax/js/outstanding-report.js"></script>

</body>
</html>
