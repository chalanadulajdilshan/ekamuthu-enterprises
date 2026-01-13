<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$CASHBOOK = new Cashbook();
$BANK = new Bank();
$BRANCH = new Branch();

// Get specific date from URL (no range), defaulting to today
$selectedDate = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$dateFrom = $selectedDate;
$dateTo = $selectedDate;

// Get the last inserted transaction id
$lastId = $CASHBOOK->getLastID();
$ref_no = 'CB/' . str_pad(($lastId + 1), 5, '0', STR_PAD_LEFT);
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Cashbook | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <style>
        .balance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .balance-amount {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .cashbook-table {
            font-size: 0.9rem;
        }

        .cashbook-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .transaction-in {
            color: #28a745;
        }

        .transaction-out {
            color: #dc3545;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">
    <!-- Begin page -->
    <div id="layout-wrapper">
        <?php include 'navigation.php' ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex flex-wrap align-items-end gap-3">
                                        <div class="d-flex flex-wrap align-items-end gap-2">
                                            <div class="me-2">
                                                <label for="date" class="form-label">Date</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="uil uil-calendar-alt"></i></span>
                                                    <input type="text" class="form-control date-picker cashbook-date" id="date" name="date" autocomplete="off" value="<?php echo $selectedDate; ?>">
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap align-items-end gap-2">
                                                <button class="btn btn-primary" id="btn-filter">
                                                    <i class="uil uil-filter me-1"></i> Filter
                                                </button>
                                                <button class="btn btn-secondary" id="btn-reset-filter">
                                                    <i class="uil uil-redo me-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                        <div class="ms-auto d-flex flex-wrap align-items-end gap-2">
                                            <a href="#" class="btn btn-success btn-bank-action" id="btn-deposit" data-bs-toggle="modal" data-bs-target="#depositModal">
                                                <i class="uil uil-money-insert me-1"></i> Bank Deposit
                                            </a>
                                            <a href="#" class="btn btn-warning btn-bank-action" id="btn-withdrawal" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                                                <i class="uil uil-money-withdraw me-1"></i> Withdrawal
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Balance Card -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="balance-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Balance in Hand</h5>
                                    <?php if ($selectedDate): ?>
                                        <small class="text-light">
                                            Selected Date: <strong><?php echo date('d M Y', strtotime($selectedDate)); ?></strong>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="balance-amount" id="balance-in-hand">
                                    <?php
                                    $balance = $CASHBOOK->getBalanceInHand($dateFrom, $dateTo);
                                    echo number_format($balance, 2);
                                    ?>
                                </div>
                                <small>As of <?php echo date('d M Y, h:i A');
                                                if ($selectedDate) {
                                                    echo ' (Date: ' . date('d M Y', strtotime($selectedDate)) . ')';
                                                }
                                                ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Cashbook Transactions Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">Cashbook Transactions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover cashbook-table" id="cashbook-table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Account Type</th>
                                                    <th>Transaction</th>
                                                    <th>Description</th>
                                                    <th>Doc</th>
                                                    <th class="text-end">Debit</th>
                                                    <th class="text-end">Credit</th>
                                                    <th class="text-end">Balance</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cashbook-tbody">
                                                <?php
                                                $transactions = $CASHBOOK->getAllTransactionsDetailed($dateFrom, $dateTo);
                                                foreach ($transactions as $transaction) {
                                                    echo '<tr>';
                                                    echo '<td>' . $transaction['date'] . '</td>';
                                                    echo '<td>' . $transaction['account_type'] . '</td>';
                                                    echo '<td class="' . ($transaction['transaction'] == 'IN' ? 'transaction-in' : 'transaction-out') . '">' . $transaction['transaction'] . '</td>';
                                                    echo '<td>' . $transaction['description'] . '</td>';
                                                    echo '<td>' . $transaction['doc'] . '</td>';
                                                    echo '<td class="text-end">' . $transaction['debit'] . '</td>';
                                                    echo '<td class="text-end">' . $transaction['credit'] . '</td>';
                                                    echo '<td class="text-end"><strong>' . $transaction['balance'] . '</strong></td>';
                                                    echo '</tr>';
                                                }
                                                ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <td colspan="7" class="text-end"><strong>Balance in Hand:</strong></td>
                                                    <td class="text-end"><strong><?php echo number_format($balance, 2); ?></strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Deposit and Withdrawal Transactions -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">Bank Deposit & Withdrawal Transactions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="bank-transactions-table">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Ref No</th>
                                                    <th>Transaction Type</th>
                                                    <th>Bank</th>
                                                    <th>Branch</th>
                                                    <th class="text-end">Amount</th>
                                                    <th>Remark</th>
                                                </tr>
                                            </thead>
                                            <tbody id="bank-transactions-tbody">
                                                <?php
                                                $bankTransactions = $CASHBOOK->getByDate($dateFrom, $dateTo);
                                                if (empty($bankTransactions)) {
                                                    echo '<tr><td colspan="7" class="text-center py-4">No data available for the selected date range</td></tr>';
                                                } else {
                                                    foreach ($bankTransactions as $transaction) {
                                                        $typeClass = $transaction['transaction_type'] == 'deposit' ? 'badge bg-danger' : 'badge bg-success';
                                                        echo '<tr>';
                                                        echo '<td>' . date('d M Y, h:i A', strtotime($transaction['created_at'])) . '</td>';
                                                        echo '<td>' . $transaction['ref_no'] . '</td>';
                                                        echo '<td><span class="' . $typeClass . '">' . ucfirst($transaction['transaction_type']) . '</span></td>';
                                                        echo '<td>' . ($transaction['bank_name'] ?? 'N/A') . '</td>';
                                                        echo '<td>' . ($transaction['branch_name'] ?? 'N/A') . '</td>';
                                                        echo '<td class="text-end">' . number_format($transaction['amount'], 2) . '</td>';
                                                        echo '<td>' . ($transaction['remark'] ?? '') . '</td>';
                                                        echo '</tr>';
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div> <!-- container-fluid -->
        </div>

        <?php include 'footer.php' ?>

    </div>
    <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <!-- Bank Deposit Modal -->
    <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="depositModalLabel">Bank Deposit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deposit-form">
                        <div class="mb-3">
                            <label for="deposit-ref-no" class="form-label">Ref No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="deposit-ref-no" name="ref_no" value="<?php echo $ref_no; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="deposit-bank" class="form-label">Bank <span class="text-danger">*</span></label>
                            <select class="form-select" id="deposit-bank" name="bank_id" required>
                                <option value="">Select Bank</option>
                                <?php
                                $banks = $BANK->all();
                                foreach ($banks as $bank) {
                                    echo '<option value="' . $bank['id'] . '">' . htmlspecialchars($bank['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="deposit-branch" class="form-label">Branch <span class="text-danger">*</span></label>
                            <select class="form-select" id="deposit-branch" name="branch_id" required>
                                <option value="">Select Branch</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="deposit-amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="deposit-amount" name="amount" placeholder="Enter amount" required>
                            <small class="text-muted">Current balance: <span id="current-balance-deposit"><?php echo number_format($balance, 2); ?></span></small>
                        </div>
                        <div class="mb-3">
                            <label for="deposit-remark" class="form-label">Remark</label>
                            <textarea class="form-control" id="deposit-remark" name="remark" rows="3" placeholder="Enter remark (optional)"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="save-deposit">Save Deposit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank Withdrawal Modal -->
    <div class="modal fade" id="withdrawalModal" tabindex="-1" aria-labelledby="withdrawalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawalModalLabel">Withdrawal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="withdrawal-form">
                        <div class="mb-3">
                            <label for="withdrawal-ref-no" class="form-label">Ref No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="withdrawal-ref-no" name="ref_no" value="<?php echo $ref_no; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="withdrawal-amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="withdrawal-amount" name="amount" placeholder="Enter amount" required>
                            <small class="text-muted">Current balance: <span id="current-balance-withdrawal"><?php echo number_format($balance, 2); ?></span></small>
                        </div>
                        <div class="mb-3">
                            <label for="withdrawal-remark" class="form-label">Remark</label>
                            <textarea class="form-control" id="withdrawal-remark" name="remark" rows="3" placeholder="Enter remark (optional)"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="save-withdrawal">Save Withdrawal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <?php include 'main-js.php' ?>
    <script src="ajax/js/cashbook.js"></script>

</body>

</html>