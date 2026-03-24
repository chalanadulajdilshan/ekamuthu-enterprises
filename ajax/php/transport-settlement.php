<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Add a settlement payment to a transport
if ($action === 'add_settlement') {
    $transportId = isset($_POST['transport_id']) ? (int)$_POST['transport_id'] : 0;
    $settlementDate = $_POST['settlement_date'] ?? date('Y-m-d');
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $remark = trim($_POST['remark'] ?? '');

    if ($transportId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid transport ID']);
        exit;
    }

    if ($amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Amount must be greater than 0']);
        exit;
    }

    $TD = new TransportDetail($transportId);
    if (!$TD->id) {
        echo json_encode(['status' => 'error', 'message' => 'Transport not found']);
        exit;
    }

    if ($TD->payment_method !== 'credit') {
        echo json_encode(['status' => 'error', 'message' => 'Only credit transport can have settlements']);
        exit;
    }

    // Check remaining amount
    $status = TransportSettlement::getSettlementStatus($transportId);
    $remaining = floatval($status['remaining_amount'] ?? $TD->total_amount);
    
    if ($amount > $remaining) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Settlement amount (' . number_format($amount, 2) . ') exceeds remaining amount (' . number_format($remaining, 2) . ')'
        ]);
        exit;
    }

    $TS = new TransportSettlement();
    $TS->transport_id = $transportId;
    $TS->settlement_date = $settlementDate;
    $TS->amount = $amount;
    $TS->remark = $remark;

    $settlementId = $TS->create();

    if ($settlementId) {
        $newStatus = TransportSettlement::getSettlementStatus($transportId);
        echo json_encode([
            'status' => 'success',
            'message' => 'Settlement added successfully',
            'settlement_id' => $settlementId,
            'settlement_status' => $newStatus
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add settlement']);
    }
    exit;
}

// Get settlement history for a transport
if ($action === 'get_settlements') {
    $transportId = isset($_POST['transport_id']) ? (int)$_POST['transport_id'] : 0;

    if ($transportId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid transport ID']);
        exit;
    }

    $settlements = TransportSettlement::getByTransportId($transportId);
    $status = TransportSettlement::getSettlementStatus($transportId);

    echo json_encode([
        'status' => 'success',
        'settlements' => $settlements,
        'settlement_status' => $status
    ]);
    exit;
}

// Delete a settlement payment
if ($action === 'delete_settlement') {
    $settlementId = isset($_POST['settlement_id']) ? (int)$_POST['settlement_id'] : 0;

    if ($settlementId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid settlement ID']);
        exit;
    }

    $TS = new TransportSettlement($settlementId);
    if (!$TS->id) {
        echo json_encode(['status' => 'error', 'message' => 'Settlement not found']);
        exit;
    }

    if ($TS->delete()) {
        echo json_encode(['status' => 'success', 'message' => 'Settlement deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete settlement']);
    }
    exit;
}

// Get transport details with settlement info by rent ID
if ($action === 'get_transport_by_rent') {
    $rentId = isset($_POST['rent_id']) ? (int)$_POST['rent_id'] : 0;

    if ($rentId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid rent ID']);
        exit;
    }

    $transports = TransportDetail::getByRentId($rentId);
    $unsettledCredit = TransportDetail::getUnsettledCreditByRentId($rentId);

    echo json_encode([
        'status' => 'success',
        'transports' => $transports,
        'unsettled_credit' => $unsettledCredit
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
