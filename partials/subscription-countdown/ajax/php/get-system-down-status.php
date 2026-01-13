<?php
// Set content type first to prevent any output before headers
header('Content-Type: application/json; charset=UTF-8');

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Include main database for company_profile
require_once __DIR__ . '/../../../../class/include.php';

// Include the CompanyErpDatabase class
require_once __DIR__ . '/../../class/CompanyErpDatabase.php';

try {
    // Get the active company profile
    $mainDb = Database::getInstance();
    $query = "SELECT customer_id FROM company_profile WHERE is_active = 1 LIMIT 1";
    $result = $mainDb->readQuery($query);

    if (!$result || mysqli_num_rows($result) === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No active company profile found'
        ]);
        exit;
    }

    $companyProfile = mysqli_fetch_assoc($result);
    $customerId = $companyProfile['customer_id'];

    if (empty($customerId)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No customer ID configured in company profile'
        ]);
        exit;
    }

    // Connect to company_erp database and get system down status
    $erpDb = CompanyErpDatabase::getInstance();
    $systemDownStatus = (int)$erpDb->getSystemDownStatus($customerId);
    
    // Check if subscription has expired and we need to force system down
    // "when time ends" means the payment date has passed.
    $nextPaymentDate = $erpDb->getNextPaymentDate($customerId);
    
    if ($nextPaymentDate) {
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $dueDate = new DateTime($nextPaymentDate);
        $dueDate->setTime(0, 0, 0);
        
        // If today is strictly AFTER the due date, lock it.
        // Example: Due Jan 1st. On Jan 1st, it's fine. On Jan 2nd, it's overdue.
        if ($today > $dueDate) {
            // Check if not already down to avoid redundant updates
            if ($systemDownStatus !== 1) {
                // Update DB to system_down = 1
                $erpDb->updateSystemDownStatus($customerId, 1);
                $systemDownStatus = 1;
            }
        }
    }

    if ($systemDownStatus === null) {
        // This case might be rare now if we cast to int above, but good generic check
        echo json_encode([
            'status' => 'error',
            'message' => 'System down status not found for customer ID: ' . $customerId
        ]);
        exit;
    }

    // Save system down status in session with type casting
    $_SESSION['system_down_status'] = $systemDownStatus;
    $_SESSION['system_down_last_updated'] = date('Y-m-d H:i:s');

    // Ensure session is saved before sending response
    session_write_close();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'customer_id' => $customerId,
            'system_down' => $systemDownStatus,
            'session_saved' => true
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
