<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Create a new supplier payment
if (isset($_POST['create'])) {
    $response = [
        'status' => 'error',
        'message' => 'An error occurred while processing your request.'
    ];

    try {
        // Get common data from the form
        $arn_no = isset($_POST['arn_id']) ? $_POST['arn_id'] : '';
        $supplier_id = isset($_POST['supplier_id']) ? $_POST['supplier_id'] : '';
        
        // Check if payment methods are provided
        if (!isset($_POST['paymentType']) || !is_array($_POST['paymentType'])) {
            throw new Exception('No payment methods provided');
        }

        $successCount = 0;
        $paymentIds = [];
        $totalAmount = 0;

        // Process each payment method
        foreach ($_POST['paymentType'] as $index => $paymentTypeId) {
            $amount = isset($_POST['amount'][$index]) ? floatval($_POST['amount'][$index]) : 0;
            
            if ($amount <= 0) {
                continue; // Skip invalid amounts
            }

            $PAYMENT = new SupplierPayment(NULL);
            
            // Set common fields
            $PAYMENT->arn_id = $arn_no;
            $PAYMENT->supplier_id = $supplier_id;
            $PAYMENT->payment_type_id = $paymentTypeId;
            $PAYMENT->amount = $amount;
            $PAYMENT->is_settle = 1;
            
            // Handle cheque specific fields
            if ($paymentTypeId == '2') { // Cheque payment
                $PAYMENT->cheq_no = isset($_POST['chequeNumber'][$index]) ? $_POST['chequeNumber'][$index] : '';
                $PAYMENT->bank_id = isset($_POST['chequeBank'][$index]) ? $_POST['chequeBank'][$index] : '';
                $PAYMENT->cheq_date = isset($_POST['chequeDate'][$index]) ? $_POST['chequeDate'][$index] : date('Y-m-d');
            } else {
                $PAYMENT->cheq_no = '';
                $PAYMENT->bank_id = '';
                $PAYMENT->cheq_date = '0000-00-00';
            }
            
            // Create the payment record
            $paymentId = $PAYMENT->create();
            
            if ($paymentId) {
                $successCount++;
                $paymentIds[] = $paymentId;
                $totalAmount += $amount;
            }
        }

        if ($successCount > 0) {
            $response = [
                'status' => 'success',
                'message' => 'Payment(s) processed successfully',
                'payment_count' => $successCount,
                'total_amount' => $totalAmount,
                'payment_ids' => $paymentIds
            ];
            $ARN = new ArnMaster($arn_no);
            $ARN->paid_amount = $totalAmount;
            
            $ARN->update();
        } else {
            $response['message'] = 'Failed to process any payments';
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Update supplier payment
if (isset($_POST['update'])) {
    $response = [
        'status' => 'error',
        'message' => 'An error occurred while updating the payment.'
    ];

    try {
        if (!isset($_POST['payment_id']) || empty($_POST['payment_id'])) {
            throw new Exception('Payment ID is required');
        }

        $PAYMENT = new SupplierPayment($_POST['payment_id']);
        
        // Update common fields
        if (isset($_POST['arn_no'])) $PAYMENT->arn_id = $_POST['arn_no'];
        
        // Update payment type specific fields
        if (isset($_POST['paymentTypeId'])) {
            $PAYMENT->payment_type_id = $_POST['paymentTypeId'];
            
            // Reset cheque related fields if not a cheque payment
            if ($_POST['paymentTypeId'] != '2') {
                $PAYMENT->cheq_no = '';
                $PAYMENT->bank_id = '';
                $PAYMENT->cheq_date = '0000-00-00';
            }
        }
        
        // Update amount if provided
        if (isset($_POST['amount'])) {
            $PAYMENT->amount = floatval($_POST['amount']);
        }
        
        // Update cheque details if provided and payment type is cheque
        if (isset($_POST['paymentTypeId']) && $_POST['paymentTypeId'] == '2') {
            if (isset($_POST['chequeNumber'])) $PAYMENT->cheq_no = $_POST['chequeNumber'];
            if (isset($_POST['chequeBank'])) $PAYMENT->bank_id = $_POST['chequeBank'];
            if (isset($_POST['chequeDate'])) $PAYMENT->cheq_date = $_POST['chequeDate'];
        }
        
        // Update the record
        $result = $PAYMENT->update();
        
        if ($result) {
            $response = [
                'status' => 'success',
                'message' => 'Payment updated successfully',
                'payment_id' => $PAYMENT->id
            ];
        } else {
            $response['message'] = 'Failed to update payment';
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Delete supplier payment
if (isset($_POST['delete'])) {
    $response = [
        'status' => 'error',
        'message' => 'An error occurred while deleting the payment.'
    ];

    try {
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            throw new Exception('Payment ID is required');
        }

        $PAYMENT = new SupplierPayment($_POST['id']);
        
        // Check if payment exists
        if (!$PAYMENT->id) {
            throw new Exception('Payment not found');
        }
        
        $result = $PAYMENT->delete();
        
        if ($result) {
            $response = [
                'status' => 'success',
                'message' => 'Payment deleted successfully'
            ];
        } else {
            $response['message'] = 'Failed to delete payment';
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

?>
