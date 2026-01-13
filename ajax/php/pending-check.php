<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'filter') {
    $date    = isset($_POST['date']) ? trim($_POST['date']) : null;
    $date_to = isset($_POST['date_to']) ? trim($_POST['date_to']) : null;



$PAYMENT_REYMWNT_RECIPT = new PaymentReceiptMethod(null);
$checks = $PAYMENT_REYMWNT_RECIPT->getByDateRange($date, $date_to);



    echo json_encode([
        'status'  => 'success',
        'date'    => $date,
        'date_to' => $date_to,
        'checks'  => $checks,
    ]);
    exit;
}

echo json_encode([
    'status'  => 'error',
    'message' => 'Invalid request',
]);
exit;

?>