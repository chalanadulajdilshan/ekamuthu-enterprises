<?php

class Cashbook
{
    public $id;
    public $ref_no;
    public $transaction_type; // 'deposit' or 'withdrawal'
    public $bank_id;
    public $branch_id;
    public $amount;
    public $remark;
    public $created_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `cashbook_transactions` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->ref_no = $result['ref_no'];
                $this->transaction_type = $result['transaction_type'];
                $this->bank_id = $result['bank_id'];
                $this->branch_id = $result['branch_id'];
                $this->amount = $result['amount'];
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $ref_no = mysqli_real_escape_string($db->DB_CON, $this->ref_no);
        $transaction_type = mysqli_real_escape_string($db->DB_CON, $this->transaction_type);
        $bank_id = (int) $this->bank_id;
        $branch_id = (int) $this->branch_id;
        $amount = (float) $this->amount;
        $remark = mysqli_real_escape_string($db->DB_CON, $this->remark);

        $query = "INSERT INTO `cashbook_transactions` (
            `ref_no`, `transaction_type`, `bank_id`, `branch_id`, `amount`, `remark`, `created_at`
        ) VALUES (
            '$ref_no', '$transaction_type', '$bank_id', '$branch_id', '$amount', '$remark', NOW()
        )";

        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        }
        return false;
    }

    public function update()
    {
        $db = Database::getInstance();
        $ref_no = mysqli_real_escape_string($db->DB_CON, $this->ref_no);
        $transaction_type = mysqli_real_escape_string($db->DB_CON, $this->transaction_type);
        $bank_id = (int) $this->bank_id;
        $branch_id = (int) $this->branch_id;
        $amount = (float) $this->amount;
        $remark = mysqli_real_escape_string($db->DB_CON, $this->remark);
        $id = (int) $this->id;

        $query = "UPDATE `cashbook_transactions` SET 
            `ref_no` = '$ref_no',
            `transaction_type` = '$transaction_type',
            `bank_id` = '$bank_id',
            `branch_id` = '$branch_id',
            `amount` = '$amount',
            `remark` = '$remark'
            WHERE `id` = '$id'";

        return $db->readQuery($query);
    }

    public function delete()
    {
        $id = (int) $this->id;
        $query = "DELETE FROM `cashbook_transactions` WHERE `id` = '$id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT 
            ct.*,
            b.name as bank_name,
            br.name as branch_name
            FROM `cashbook_transactions` ct
            LEFT JOIN `banks` b ON ct.bank_id = b.id
            LEFT JOIN `branches` br ON ct.branch_id = br.id
            ORDER BY ct.created_at DESC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array = [];
        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }

    public function getByDate($dateFrom, $dateTo)
    {
        $dateFrom = $dateFrom . " 00:00:00";
        $dateTo   = $dateTo . " 23:59:59";

        

        $query = "
    SELECT 
        ct.*,
        b.name AS bank_name,
        br.name AS branch_name
    FROM cashbook_transactions ct
    LEFT JOIN banks b ON ct.bank_id = b.id
    LEFT JOIN branches br ON ct.branch_id = br.id
    WHERE ct.created_at BETWEEN '$dateFrom' AND '$dateTo'
    ORDER BY ct.created_at DESC
";
 
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array = [];
        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }

    public function getLastID()
    {
        $query = "SELECT * FROM `cashbook_transactions` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result && isset($result['id'])) {
            return $result['id'];
        }
        return 0;
    }

    // Get opening balance from company profile
    public function getOpeningBalance()
    {
        $query = "SELECT cashbook_opening_balance FROM `company_profile` WHERE `is_active` = 1 LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));

        if ($result && isset($result['cashbook_opening_balance'])) {
            return (float) $result['cashbook_opening_balance'];
        }
        return 0;
    }

    // Get total cash IN from various sources
    public function getTotalCashIn($dateFrom = null, $dateTo = null)
    {
        $db = Database::getInstance();

        // Build base WHERE for sales_invoice with flexible date handling
        $where = "WHERE 1=1";

        if ($dateFrom && $dateTo) {
            $dateFrom = mysqli_real_escape_string($db->DB_CON, $dateFrom);
            $dateTo   = mysqli_real_escape_string($db->DB_CON, $dateTo);
            $where   .= " AND DATE(si.invoice_date) BETWEEN '$dateFrom' AND '$dateTo'";
        } elseif ($dateTo) {
            $dateTo = mysqli_real_escape_string($db->DB_CON, $dateTo);
            $where .= " AND DATE(si.invoice_date) <= '$dateTo'";
        } elseif ($dateFrom) {
            $dateFrom = mysqli_real_escape_string($db->DB_CON, $dateFrom);
            $where   .= " AND DATE(si.invoice_date) >= '$dateFrom'";
        }

        // Cash from sales invoices (payment_type = 1 means cash)
        $queryCashInvoices = "SELECT COALESCE(SUM(grand_total), 0) as total 
                              FROM `sales_invoice` si
                              $where AND si.payment_type = 1 AND si.is_cancel = 0";
        $resultCash = mysqli_fetch_array($db->readQuery($queryCashInvoices));
        $totalCashInvoices = (float) $resultCash['total'];

        // Cash from payment receipts (customer payments)
        $wherePayment = str_replace('si.invoice_date', 'pr.entry_date', $where);
        $queryPaymentReceipts = "SELECT COALESCE(SUM(prm.amount), 0) as total 
                                 FROM `payment_receipt` pr
                                 INNER JOIN `payment_receipt_method` prm ON prm.receipt_id = pr.id
                                 $wherePayment AND prm.payment_type_id = 1";
        $resultPayment = mysqli_fetch_array($db->readQuery($queryPaymentReceipts));
        $totalPaymentReceipts = (float) $resultPayment['total'];

        // Cash from daily income
        $whereIncome = str_replace('si.invoice_date', 'di.date', $where);
        $queryDailyIncome = "SELECT COALESCE(SUM(amount), 0) as total 
                            FROM `daily_income` di
                            $whereIncome";
        $resultIncome = mysqli_fetch_array($db->readQuery($queryDailyIncome));
        $totalDailyIncome = (float) $resultIncome['total'];

        // Cash from equipment rent (Rent Income)
        $whereRent = str_replace('si.invoice_date', 'er.rental_date', $where);
        $queryRent = "SELECT COALESCE(SUM(total_rent_amount + transport_cost), 0) as total
                      FROM `equipment_rent` er
                      $whereRent AND er.is_cancelled = 0";
        $resultRent = mysqli_fetch_array($db->readQuery($queryRent));
        $totalRent = (float) $resultRent['total'];

        // Deposit Payments
        $whereDepositPayment = str_replace('si.invoice_date', 'dp.payment_date', $where);
        $queryDepositPayments = "SELECT COALESCE(SUM(amount), 0) as total FROM `deposit_payments` dp $whereDepositPayment";
        $resultDepositPayments = mysqli_fetch_array($db->readQuery($queryDepositPayments));
        $totalDepositPayments = (float) $resultDepositPayments['total'];

        // Rent Return - Customer Paid at return time (IN)
        $whereReturnIn = str_replace('si.invoice_date', 'err.return_date', $where);
        $queryReturnIn = "SELECT COALESCE(SUM(initial_customer_paid), 0) as total FROM `equipment_rent_returns` err $whereReturnIn";
        $resultReturnIn = mysqli_fetch_array($db->readQuery($queryReturnIn));
        $totalReturnIn = (float) $resultReturnIn['total'];

        // Rent Return - Late Customer Outstanding Settlement (IN)
        $whereReturnLateIn = str_replace('si.invoice_date', 'err.updated_at', $where);
        $queryReturnLateIn = "SELECT COALESCE(SUM(err.customer_paid - err.initial_customer_paid), 0) as total
                              FROM `equipment_rent_returns` err
                              $whereReturnLateIn AND (err.customer_paid - err.initial_customer_paid) > 0";
        $resultReturnLateIn = mysqli_fetch_array($db->readQuery($queryReturnLateIn));
        $totalReturnLateIn = (float) $resultReturnLateIn['total'];

        // Cash from transport details
        $whereTransport = str_replace('si.invoice_date', 'td.transport_date', $where);
        $queryTransport = "SELECT COALESCE(SUM(total_amount), 0) as total
                           FROM `transport_details` td
                           $whereTransport";
        $resultTransport = mysqli_fetch_array($db->readQuery($queryTransport));
        $totalTransport = (float) $resultTransport['total'];

        // Cash from repair jobs
        $whereRepair = str_replace('si.invoice_date', 'rj.created_at', $where);
        $queryRepair = "SELECT COALESCE(SUM(total_cost), 0) as total
                        FROM `repair_jobs` rj
                        $whereRepair";
        $resultRepair = mysqli_fetch_array($db->readQuery($queryRepair));
        $totalRepair = (float) $resultRepair['total'];

        return $totalCashInvoices
            + $totalPaymentReceipts
            + $totalDailyIncome
            + $totalRent
            + $totalDepositPayments
            + $totalReturnIn
            + $totalReturnLateIn
            + $totalTransport
            + $totalRepair;
    }

    // Get total cash OUT from various sources
    public function getTotalCashOut($dateFrom = null, $dateTo = null)
    {
        $db = Database::getInstance();

        // Build base WHERE for expenses with flexible date handling
        $where = "WHERE 1=1";

        if ($dateFrom && $dateTo) {
            $dateFrom = mysqli_real_escape_string($db->DB_CON, $dateFrom);
            $dateTo   = mysqli_real_escape_string($db->DB_CON, $dateTo);
            $where   .= " AND DATE(e.expense_date) BETWEEN '$dateFrom' AND '$dateTo'";
        } elseif ($dateTo) {
            $dateTo = mysqli_real_escape_string($db->DB_CON, $dateTo);
            $where .= " AND DATE(e.expense_date) <= '$dateTo'";
        } elseif ($dateFrom) {
            $dateFrom = mysqli_real_escape_string($db->DB_CON, $dateFrom);
            $where   .= " AND DATE(e.expense_date) >= '$dateFrom'";
        }

        // Cash from expenses
        $queryExpenses = "SELECT COALESCE(SUM(amount), 0) as total 
                         FROM `expenses` e
                         $where";
        $resultExpenses = mysqli_fetch_array($db->readQuery($queryExpenses));
        $totalExpenses = (float) $resultExpenses['total'];

        // Cash for supplier payments
        $whereSupplier = str_replace('e.expense_date', 'prs.entry_date', $where);
        $querySupplierPayments = "SELECT COALESCE(SUM(prms.amount), 0) as total 
                                  FROM `payment_receipt_supplier` prs
                                  INNER JOIN `payment_receipt_method_supplier` prms ON prms.receipt_id = prs.id
                                  $whereSupplier AND prms.payment_type_id = 1";
        $resultSupplier = mysqli_fetch_array($db->readQuery($querySupplierPayments));
        $totalSupplierPayments = (float) $resultSupplier['total'];

        // Cash for ARN (purchase returns) - only cash ARN, not credit
        $whereArn = str_replace('e.expense_date', 'am.entry_date', $where);
        $queryArn = "SELECT COALESCE(SUM(total_arn_value), 0) as total 
                    FROM `arn_master` am
                    $whereArn AND (am.is_cancelled IS NULL OR am.is_cancelled = 0) AND am.supplier_id != 0 AND am.purchase_type = 1";
        $resultArn = mysqli_fetch_array($db->readQuery($queryArn));
        $totalArn = (float) $resultArn['total'];

        $whereSalesReturn = str_replace('e.expense_date', 'sr.return_date', $where);
        $querySalesReturn = "SELECT COALESCE(SUM(sr.total_amount), 0) as total 
                             FROM `sales_return` sr
                             $whereSalesReturn";
        $resultSalesReturn = mysqli_fetch_array($db->readQuery($querySalesReturn));
        $totalSalesReturn = (float) $resultSalesReturn['total'];

        // Bank deposits (remove from cash)
        $whereDeposit = str_replace('e.expense_date', 'created_at', $where);
        $queryDeposits = "SELECT COALESCE(SUM(amount), 0) as total 
                         FROM `cashbook_transactions`
                         $whereDeposit AND transaction_type = 'deposit'";
        $resultDeposit = mysqli_fetch_array($db->readQuery($queryDeposits));
        $totalDeposits = (float) $resultDeposit['total'];

        // Bank withdrawals (treat as out / reduce cash)
        $whereWithdrawals = str_replace('e.expense_date', 'created_at', $where);
        $queryWithdrawals = "SELECT COALESCE(SUM(amount), 0) as total 
                         FROM `cashbook_transactions`
                         $whereWithdrawals AND transaction_type = 'withdrawal'";
        $resultWithdrawals = mysqli_fetch_array($db->readQuery($queryWithdrawals));
        $totalWithdrawals = (float) $resultWithdrawals['total'];

        // Rent Return - Company Refunded (OUT) - Initial
        $whereReturnOut = str_replace('e.expense_date', 'err.return_date', $where);
        $queryReturnOut = "SELECT COALESCE(SUM(initial_company_refund_paid), 0) as total FROM `equipment_rent_returns` err $whereReturnOut";
        $resultReturnOut = mysqli_fetch_array($db->readQuery($queryReturnOut));
        $totalReturnOut = (float) $resultReturnOut['total'];

        // Rent Return - Company Refunded (OUT) - Late
        $whereReturnLateOut = str_replace('e.expense_date', 'err.updated_at', $where);
        $queryReturnLateOut = "SELECT COALESCE(SUM(company_refund_paid - initial_company_refund_paid), 0) as total FROM `equipment_rent_returns` err $whereReturnLateOut AND (company_refund_paid - initial_company_refund_paid) > 0";
        $resultReturnLateOut = mysqli_fetch_array($db->readQuery($queryReturnLateOut));
        $totalReturnLateOut = (float) $resultReturnLateOut['total'];

        return $totalExpenses + $totalSupplierPayments + $totalArn + $totalSalesReturn + $totalDeposits + $totalWithdrawals + $totalReturnOut + $totalReturnLateOut;
    }

    // Get balance in hand
    public function getBalanceInHand($dateFrom = null, $dateTo = null)
    {
        // Get all transactions and return the final balance
        $transactions = $this->getAllTransactionsDetailed($dateFrom, $dateTo);

        if (empty($transactions)) {
            return $this->getOpeningBalance();
        }

        // Get the last transaction's balance
        $lastTransaction = end($transactions);
        $balance = (float)str_replace(',', '', $lastTransaction['balance']);

        return $balance;
    }

    // Get all transactions with details
    public function getAllTransactionsDetailed($dateFrom = null, $dateTo = null)
    {
        $db = Database::getInstance();
        $transactions = [];

        // Base opening balance from company profile
        $openingBalance = $this->getOpeningBalance();

        // Find the earliest CASH SALES date as the cashbook start date
        $queryEarliest = "SELECT MIN(invoice_date) as first_date 
                          FROM sales_invoice 
                          WHERE payment_type = 1 AND is_cancel = 0";

        $resultEarliest = mysqli_fetch_array($db->readQuery($queryEarliest));
        $firstTransactionDate = $resultEarliest['first_date'] ?? null;

        // If a specific date is provided and it's AFTER the first transaction date,
        // get the previous day's closing balance as this day's opening
        if ($dateFrom && $dateTo && $firstTransactionDate) {
            $prevDate = date('Y-m-d', strtotime($dateFrom . ' -1 day'));

            // Only calculate previous balance if the selected date is AFTER the first transaction day
            if ($prevDate >= $firstTransactionDate) {
                // Get previous day's closing balance by calling this method recursively
                $prevDayTransactions = $this->getAllTransactionsDetailed($prevDate, $prevDate);

                if (!empty($prevDayTransactions)) {
                    // Get the last transaction's balance (which is the closing balance)
                    $lastTransaction = end($prevDayTransactions);
                    $openingBalance = (float)str_replace(',', '', $lastTransaction['balance']);
                }
            }
            // If selected date IS the first transaction date, opening = company opening (no change)
        }

        // Store opening balance row separately (will be added at the top after sorting)
        $openingBalanceRow = [
            'date' => $dateFrom ? date('Y-m-d', strtotime($dateFrom)) : '',
            'account_type' => 'CASH',
            'transaction' => 'IN',
            'description' => 'Opening Balance',
            'doc' => '',
            'debit' => number_format($openingBalance, 2),
            'credit' => '0.00',
            'balance' => number_format($openingBalance, 2),
            'sort_date' => $dateFrom ? date('Y-m-d 00:00:00', strtotime($dateFrom)) : '0000-00-00 00:00:00',
            'is_opening' => true
        ];

        $runningBalance = $openingBalance;
        $where = " WHERE 1=1";

        if ($dateFrom && $dateTo) {
            $dateFrom = mysqli_real_escape_string($db->DB_CON, $dateFrom);
            $dateTo = mysqli_real_escape_string($db->DB_CON, $dateTo);
            $where .= " AND DATE(invoice_date) BETWEEN '$dateFrom' AND '$dateTo'";
        }

        // Cash sales invoices
        $query = "SELECT invoice_date as date, invoice_no as doc, grand_total as amount, 'Cash Sale' as description
                  FROM sales_invoice 
                  $where AND payment_type = 1 AND is_cancel = 0
                  ORDER BY invoice_date ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance += (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'IN',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => number_format($row['amount'], 2),
                'credit' => '0.00',
                'balance' => number_format($runningBalance, 2),
                'sort_date' => $row['date']
            ];
        }

        // Payment receipts
        $wherePayment = str_replace('invoice_date', 'entry_date', $where);
        $query = "SELECT 
                      pr.entry_date as date, 
                      pr.created_at as time,
                      pr.receipt_no as doc, 
                      (
                          SELECT COALESCE(SUM(prm.amount), 0)
                          FROM payment_receipt_method prm
                          WHERE prm.receipt_id = pr.id
                            AND prm.payment_type_id = 1
                      ) as amount, 
                      CONCAT('Payment from ', cm.name) as description
                  FROM payment_receipt pr
                  LEFT JOIN customer_master cm ON pr.customer_id = cm.id
                  $wherePayment
                  HAVING amount > 0
                  ORDER BY pr.entry_date ASC, pr.created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance += (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'IN',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => number_format($row['amount'], 2),
                'credit' => '0.00',
                'balance' => number_format($runningBalance, 2),
                'sort_date' => (date('Y-m-d', strtotime($row['date'])) == date('Y-m-d', strtotime($row['time']))) ? $row['time'] : $row['date']
            ];
        }

        // Daily income
        $whereIncome = str_replace('invoice_date', 'date', $where);
        $query = "SELECT date, created_at, CONCAT('DI-', id) as doc, amount, COALESCE(remark, 'Daily Income') as description
                  FROM daily_income
                  $whereIncome
                  ORDER BY date ASC, created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance += (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'IN',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => number_format($row['amount'], 2),
                'credit' => '0.00',
                'balance' => number_format($runningBalance, 2),
                'sort_date' => (date('Y-m-d', strtotime($row['date'])) == date('Y-m-d', strtotime($row['created_at']))) ? $row['created_at'] : $row['date']
            ];
        }

        // Rent Income removed: equipment rent income should be recorded on return (see rent return entries below)
        $whereRent = str_replace('invoice_date', 'rental_date', $where);

        // Deposit Payments
        $whereDepositPayment = str_replace('invoice_date', 'dp.payment_date', $where);
        $query = "SELECT dp.payment_date as date, dp.created_at as time, CONCAT('DEP-', dp.rent_id) as doc, dp.amount, COALESCE(NULLIF(dp.remark, ''), 'Rent Deposit') as description
                  FROM deposit_payments dp
                  $whereDepositPayment AND dp.amount > 0
                  ORDER BY dp.payment_date ASC, dp.created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance += (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'IN',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => number_format($row['amount'], 2),
                'credit' => '0.00',
                'balance' => number_format($runningBalance, 2),
                'sort_date' => (date('Y-m-d', strtotime($row['date'])) == date('Y-m-d', strtotime($row['time']))) ? $row['time'] : $row['date']
            ];
        }

        // Rent Transport Cost
        $query = "SELECT er.rental_date as date, er.bill_number as doc, er.transport_cost as amount, 'Rent Transport Income' as description, pt.name as payment_type
                  FROM equipment_rent er
                  LEFT JOIN payment_type pt ON er.payment_type_id = pt.id
                  $whereRent AND er.is_cancelled = 0 AND er.transport_cost > 0
                  ORDER BY er.rental_date ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance += (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => $row['payment_type'] ?? 'CASH',
                'transaction' => 'IN',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => number_format($row['amount'], 2),
                'credit' => '0.00',
                'balance' => number_format($runningBalance, 2),
                'sort_date' => $row['date']
            ];
        }

        // Transport Details Income
        $whereTransport = str_replace('invoice_date', 'transport_date', $where);
        $query = "SELECT transport_date as date, created_at as time, CONCAT('TRN-', rent_id) as doc, total_amount as amount, 'Additional Transport Income' as description
                  FROM transport_details
                  $whereTransport AND total_amount > 0
                  ORDER BY transport_date ASC, created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance += (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'IN',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => number_format($row['amount'], 2),
                'credit' => '0.00',
                'balance' => number_format($runningBalance, 2),
                'sort_date' => (date('Y-m-d', strtotime($row['date'])) == date('Y-m-d', strtotime($row['time']))) ? $row['time'] : $row['date']
            ];
        }

        // Repair Income
        $whereRepair = str_replace('invoice_date', 'created_at', $where);
        $query = "SELECT created_at as date, job_code as doc, total_cost as amount, 'Repair Income' as description
                  FROM repair_jobs
                  $whereRepair AND total_cost > 0
                  ORDER BY created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance += (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d H:i:s', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'IN',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => number_format($row['amount'], 2),
                'credit' => '0.00',
                'balance' => number_format($runningBalance, 2),
                'sort_date' => $row['date']
            ];
        }

        // Rent Return Customer Paid (IN)
        $whereReturnIn = str_replace('invoice_date', 'err.return_date', $where);
        $query = "SELECT err.return_date as date, err.created_at as time, CONCAT('RTN-', err.id) as doc, err.initial_customer_paid as amount, 'Rent Return Customer Payment' as description
                  FROM equipment_rent_returns err
                  $whereReturnIn AND err.initial_customer_paid > 0
                  ORDER BY err.return_date ASC, err.created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance += (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'IN',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => number_format($row['amount'], 2),
                'credit' => '0.00',
                'balance' => number_format($runningBalance, 2),
                'sort_date' => (date('Y-m-d', strtotime($row['date'])) == date('Y-m-d', strtotime($row['time']))) ? $row['time'] : $row['date']
            ];
        }

        // Rent Return Late Customer Outstanding Settlement (IN)
        $whereReturnLateIn = str_replace('invoice_date', 'err.updated_at', $where);
        $query = "SELECT err.updated_at as date, CONCAT('RTN-', err.id) as doc, (err.customer_paid - err.initial_customer_paid) as amount, 'Rent Return Outstanding Settlement' as description
                  FROM equipment_rent_returns err
                  $whereReturnLateIn AND (err.customer_paid - err.initial_customer_paid) > 0
                  ORDER BY err.updated_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance += (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d H:i:s', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'IN',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => number_format($row['amount'], 2),
                'credit' => '0.00',
                'balance' => number_format($runningBalance, 2),
                'sort_date' => $row['date']
            ];
        }

        // Rent Return Company Refund Paid (OUT)
        $query = "SELECT err.return_date as date, err.created_at as time, CONCAT('RTN-', err.id) as doc, err.initial_company_refund_paid as amount, 'Company Outstanding Settlement' as description
                  FROM equipment_rent_returns err
                  $whereReturnIn AND err.initial_company_refund_paid > 0
                  ORDER BY err.return_date ASC, err.created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance -= (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'OUT',
                'description' => 'Company Refund',
                'doc' => $row['doc'],
                'debit' => '0.00',
                'credit' => number_format($row['amount'], 2),
                'balance' => number_format($runningBalance, 2),
                'sort_date' => (date('Y-m-d', strtotime($row['date'])) == date('Y-m-d', strtotime($row['time']))) ? $row['time'] : $row['date']
            ];
        }

        // Rent Return Late Company Refund Paid (OUT)
        $whereReturnLateOut = str_replace('invoice_date', 'err.updated_at', $where);
        $query = "SELECT err.updated_at as date, CONCAT('RTN-', err.id) as doc, (err.company_refund_paid - err.initial_company_refund_paid) as amount, 'Late Company Outstanding Settlement' as description
                  FROM equipment_rent_returns err
                  $whereReturnLateOut AND (err.company_refund_paid - err.initial_company_refund_paid) > 0
                  ORDER BY err.updated_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance -= (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d H:i:s', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'OUT',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => '0.00',
                'credit' => number_format($row['amount'], 2),
                'balance' => number_format($runningBalance, 2),
                'sort_date' => $row['date']
            ];
        }

        $whereSalesReturn = str_replace('invoice_date', 'sr.return_date', $where);
        $query = "SELECT sr.return_date as date, sr.created_at as time, sr.return_no as doc, sr.total_amount as amount,
                         CONCAT('Sales Return - ', COALESCE(cm.name, '')) as description
                  FROM sales_return sr
                  LEFT JOIN sales_invoice si ON sr.invoice_id = si.id
                  LEFT JOIN customer_master cm ON sr.customer_id = cm.id
                  $whereSalesReturn
                  ORDER BY sr.return_date ASC, sr.created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance -= (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'OUT',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => '0.00',
                'credit' => number_format($row['amount'], 2),
                'balance' => number_format($runningBalance, 2),
                'sort_date' => (date('Y-m-d', strtotime($row['date'])) == date('Y-m-d', strtotime($row['time']))) ? $row['time'] : $row['date']
            ];
        }

        // Expenses
        $whereExpense = str_replace('invoice_date', 'expense_date', $where);
        $query = "SELECT e.expense_date as date, e.code as doc, e.amount, 
                  CONCAT('Expense - ', et.name) as description
                  FROM expenses e
                  LEFT JOIN expenses_type et ON e.expense_type_id = et.id
                  $whereExpense
                  ORDER BY e.expense_date ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance -= (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'OUT',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => '0.00',
                'credit' => number_format($row['amount'], 2),
                'balance' => number_format($runningBalance, 2),
                'sort_date' => $row['date']
            ];
        }

        $whereArnDetail = str_replace('invoice_date', 'am.entry_date', $where);
        $query = "SELECT 
                        am.entry_date as date,
                        am.created_at as time,
                        am.arn_no as doc,
                        am.total_arn_value as amount,
                        CONCAT('ARN Purchase - ', COALESCE(cm.name, '')) as description
                  FROM arn_master am
                  LEFT JOIN customer_master cm ON am.supplier_id = cm.id
                  $whereArnDetail AND (am.is_cancelled IS NULL OR am.is_cancelled = 0) AND am.supplier_id != 0 AND am.purchase_type = 1
                  ORDER BY am.entry_date ASC, am.created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance -= (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'OUT',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => '0.00',
                'credit' => number_format($row['amount'], 2),
                'balance' => number_format($runningBalance, 2),
                'sort_date' => (date('Y-m-d', strtotime($row['date'])) == date('Y-m-d', strtotime($row['time']))) ? $row['time'] : $row['date']
            ];
        }

        // Supplier payments
        $whereSupplier = str_replace('invoice_date', 'entry_date', $where);
        $query = "SELECT 
                      prs.entry_date as date, 
                      prs.created_at as time,
                      prs.receipt_no as doc, 
                      (
                          SELECT COALESCE(SUM(prms.amount), 0)
                          FROM payment_receipt_method_supplier prms
                          WHERE prms.receipt_id = prs.id
                            AND prms.payment_type_id = 1
                      ) as amount,
                      CONCAT('Payment to Supplier') as description
                  FROM payment_receipt_supplier prs
                  $whereSupplier
                  HAVING amount > 0
                  ORDER BY prs.entry_date ASC, prs.created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance -= (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'OUT',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => '0.00',
                'credit' => number_format($row['amount'], 2),
                'balance' => number_format($runningBalance, 2),
                'sort_date' => (date('Y-m-d', strtotime($row['date'])) == date('Y-m-d', strtotime($row['time']))) ? $row['time'] : $row['date']
            ];
        }

        // Bank deposits
        $whereDeposit = str_replace('invoice_date', 'ct.created_at', $where);
        $query = "SELECT ct.created_at as date, ct.ref_no as doc, ct.amount, 
                  CONCAT('Bank Deposit - ', b.name, ' (', br.name, ')') as description
                  FROM cashbook_transactions ct
                  LEFT JOIN banks b ON ct.bank_id = b.id
                  LEFT JOIN branches br ON ct.branch_id = br.id
                  $whereDeposit AND ct.transaction_type = 'deposit'
                  ORDER BY ct.created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance -= (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d H:i:s', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'OUT',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => '0.00',
                'credit' => number_format($row['amount'], 2),
                'balance' => number_format($runningBalance, 2),
                'sort_date' => $row['date']
            ];
        }

        // Withdrawals
        $whereWithdrawal = str_replace('invoice_date', 'ct.created_at', $where);
        $query = "SELECT ct.created_at as date, ct.ref_no as doc, ct.amount, 
                  CONCAT(
                        'Bank Withdrawal - ',
                        COALESCE(NULLIF(b.name, ''), 'Cash Drawer'),
                        CASE WHEN ct.remark IS NOT NULL AND ct.remark <> '' THEN CONCAT(' | ', ct.remark) ELSE '' END
                  ) as description
                  FROM cashbook_transactions ct
                  LEFT JOIN banks b ON ct.bank_id = b.id
                  $whereWithdrawal AND ct.transaction_type = 'withdrawal'
                  ORDER BY ct.created_at ASC";
        $result = $db->readQuery($query);
        while ($row = mysqli_fetch_array($result)) {
            $runningBalance -= (float)$row['amount'];
            $transactions[] = [
                'date' => date('Y-m-d H:i:s', strtotime($row['date'])),
                'account_type' => 'CASH',
                'transaction' => 'OUT',
                'description' => $row['description'],
                'doc' => $row['doc'],
                'debit' => '0.00',
                'credit' => number_format($row['amount'], 2),
                'balance' => number_format($runningBalance, 2),
                'sort_date' => $row['date']
            ];
        }

        // Sort by date and time
        usort($transactions, function ($a, $b) {
            $dateA = $a['sort_date'];
            $dateB = $b['sort_date'];
            
            // Normalize to full timestamp for comparison if only date is present
            if (strlen($dateA) === 10) $dateA .= ' 00:00:00';
            if (strlen($dateB) === 10) $dateB .= ' 00:00:00';
            
            return strcmp($dateA, $dateB);
        });

        // Recalculate running balance after sorting, starting from opening balance
        $runningBalance = $openingBalance;
        foreach ($transactions as &$transaction) {
            // Get the debit and credit amounts (remove formatting)
            $debit = (float)str_replace(',', '', $transaction['debit']);
            $credit = (float)str_replace(',', '', $transaction['credit']);

            // Update running balance
            $runningBalance += $debit - $credit;

            // Update the balance in the transaction
            $transaction['balance'] = number_format($runningBalance, 2);
        }

        // Add opening balance row at the very top
        array_unshift($transactions, $openingBalanceRow);

        return $transactions;
    }
}

