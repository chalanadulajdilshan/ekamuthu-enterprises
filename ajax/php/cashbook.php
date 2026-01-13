<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Handle Bank Deposit
if (isset($_POST['create_deposit'])) {
    $CASHBOOK = new Cashbook();
    
    $currentBalance = $CASHBOOK->getBalanceInHand();
    $depositAmount = (float)$_POST['amount'];
    
    // Validate that deposit amount doesn't exceed current balance
    if ($depositAmount > $currentBalance) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Deposit amount cannot exceed current balance of ' . number_format($currentBalance, 2)
        ]);
        exit();
    }
    
    if ($depositAmount <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Deposit amount must be greater than zero'
        ]);
        exit();
    }
    
    $CASHBOOK->ref_no = $_POST['ref_no'] ?? '';
    $CASHBOOK->transaction_type = 'deposit';
    $CASHBOOK->bank_id = (int)$_POST['bank_id'];
    $CASHBOOK->branch_id = (int)$_POST['branch_id'];
    $CASHBOOK->amount = $depositAmount;
    $CASHBOOK->remark = $_POST['remark'] ?? '';
    
    $result = $CASHBOOK->create();
    
    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Bank deposit saved successfully',
            'new_balance' => number_format($CASHBOOK->getBalanceInHand(), 2)
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to save bank deposit'
        ]);
    }
    exit();
}

// Handle Bank Withdrawal
if (isset($_POST['create_withdrawal'])) {
    $CASHBOOK = new Cashbook();
    
    $withdrawalAmount = (float)$_POST['amount'];
    $currentBalance   = $CASHBOOK->getBalanceInHand();

    if ($withdrawalAmount <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Withdrawal amount must be greater than zero'
        ]);
        exit();
    }
    
    // Prevent withdrawal from exceeding current balance in hand
    if ($withdrawalAmount > $currentBalance) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Withdrawal amount cannot exceed current balance of ' . number_format($currentBalance, 2)
        ]);
        exit();
    }
    
    $CASHBOOK->ref_no = $_POST['ref_no'] ?? '';
    $CASHBOOK->transaction_type = 'withdrawal';
    $CASHBOOK->bank_id = 0; // Not required for withdrawal
    $CASHBOOK->branch_id = 0; // Not required for withdrawal
    $CASHBOOK->amount = $withdrawalAmount;
    $CASHBOOK->remark = $_POST['remark'] ?? '';
    
    $result = $CASHBOOK->create();
    
    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Bank withdrawal saved successfully',
            'new_balance' => number_format($CASHBOOK->getBalanceInHand(), 2)
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to save bank withdrawal'
        ]);
    }
    exit();
}

// Get next reference number
if (isset($_POST['get_ref_no'])) {
    $CASHBOOK = new Cashbook();
    $lastId = $CASHBOOK->getLastID();
    $ref_no = 'CB/' . str_pad(($lastId + 1), 5, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'status' => 'success',
        'ref_no' => $ref_no
    ]);
    exit();
}

// Get branches by bank ID
if (isset($_POST['get_branches'])) {
    $bankId = (int)$_POST['bank_id'];
    $BRANCH = new Branch();
    
    $db = Database::getInstance();
    $query = "SELECT * FROM `branches` WHERE `bank_id` = $bankId ORDER BY `name` ASC";
    $result = $db->readQuery($query);
    
    $branches = [];
    while ($row = mysqli_fetch_array($result)) {
        $branches[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'branches' => $branches
    ]);
    exit();
}

// Get current balance
if (isset($_POST['get_balance'])) {
    $CASHBOOK = new Cashbook();
    $balance = $CASHBOOK->getBalanceInHand();
    
    echo json_encode([
        'status' => 'success',
        'balance' => number_format($balance, 2),
        'balance_raw' => $balance
    ]);
    exit();
}

// Refresh transactions
if (isset($_POST['refresh_transactions'])) {
    $CASHBOOK = new Cashbook();
    $transactions = $CASHBOOK->getAllTransactionsDetailed();
    $balance = $CASHBOOK->getBalanceInHand();
    
    echo json_encode([
        'status' => 'success',
        'transactions' => $transactions,
        'balance' => number_format($balance, 2),
        'balance_raw' => $balance
    ]);
    exit();
}

// Get bank transactions only
if (isset($_POST['get_bank_transactions'])) {
    $CASHBOOK = new Cashbook();
    $bankTransactions = $CASHBOOK->all();
    
    echo json_encode([
        'status' => 'success',
        'transactions' => $bankTransactions
    ]);
    exit();
}
