<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a new daily income
if (isset($_POST['create'])) {
    try {
        // Validate required fields
        if (empty($_POST['date']) || empty($_POST['amount'])) {
            echo json_encode([
                "status" => 'error',
                "message" => 'Missing required fields'
            ]);
            exit();
        }

        $DAILY_INCOME = new DailyIncome(NULL);

        $DAILY_INCOME->date = $_POST['date'];
        $DAILY_INCOME->amount = $_POST['amount'];
        $DAILY_INCOME->remark = isset($_POST['remark']) ? $_POST['remark'] : '';

        $res = $DAILY_INCOME->create();

        if ($res) {
            echo json_encode([
                "status" => 'success',
                "message" => 'Daily income created successfully',
                "id" => $res
            ]);
        } else {
            echo json_encode([
                "status" => 'error',
                "message" => 'Failed to create daily income'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            "status" => 'error',
            "message" => 'Exception: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Update daily income
if (isset($_POST['update'])) {
    try {
        // Validate required fields
        if (empty($_POST['id']) || empty($_POST['date']) || empty($_POST['amount'])) {
            echo json_encode([
                "status" => 'error',
                "message" => 'Missing required fields for update'
            ]);
            exit();
        }

        $DAILY_INCOME = new DailyIncome($_POST['id']);

        if (!$DAILY_INCOME->id) {
            echo json_encode([
                "status" => 'error',
                "message" => 'Daily income not found'
            ]);
            exit();
        }

        $DAILY_INCOME->date = $_POST['date'];
        $DAILY_INCOME->amount = $_POST['amount'];
        $DAILY_INCOME->remark = isset($_POST['remark']) ? $_POST['remark'] : '';

        $result = $DAILY_INCOME->update();

        if ($result) {
            echo json_encode([
                "status" => 'success',
                "message" => 'Daily income updated successfully'
            ]);
        } else {
            echo json_encode([
                "status" => 'error',
                "message" => 'Failed to update daily income'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            "status" => 'error',
            "message" => 'Exception: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Delete daily income
if (isset($_POST['delete']) && isset($_POST['id'])) {
    try {
        if (empty($_POST['id'])) {
            echo json_encode([
                "status" => 'error',
                "message" => 'No daily income ID provided'
            ]);
            exit();
        }

        $daily_income = new DailyIncome($_POST['id']);

        if (!$daily_income->id) {
            echo json_encode([
                "status" => 'error',
                "message" => 'Daily income not found'
            ]);
            exit();
        }

        $result = $daily_income->delete();

        if ($result) {
            echo json_encode([
                "status" => 'success',
                "message" => 'Daily income deleted successfully'
            ]);
        } else {
            echo json_encode([
                "status" => 'error',
                "message" => 'Failed to delete daily income'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            "status" => 'error',
            "message" => 'Exception: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Fetch daily income records by date range
if (isset($_POST['fetch_records'])) {
    try {
        if (empty($_POST['date_from']) || empty($_POST['date_to'])) {
            echo json_encode([
                "status" => 'error',
                "message" => 'Missing required date range fields'
            ]);
            exit();
        }

        $dateFrom = $_POST['date_from'];
        $dateTo = $_POST['date_to'];

        $DAILY_INCOME = new DailyIncome(NULL);
        $records = $DAILY_INCOME->getIncomeByDateRange($dateFrom, $dateTo);
        $totalAmount = $DAILY_INCOME->getTotalIncome($dateFrom, $dateTo);

        echo json_encode([
            "status" => 'success',
            "records" => $records,
            "total_amount" => number_format($totalAmount, 2)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status" => 'error',
            "message" => 'Exception: ' . $e->getMessage()
        ]);
    }
    exit();
}

// If no valid action is found
echo json_encode([
    "status" => 'error',
    "message" => 'No valid action specified'
]);
