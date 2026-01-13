<?php
header('Content-Type: application/json; charset=UTF-8');

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
    
    // Connect to company_erp database and get next payment date
    $erpDb = CompanyErpDatabase::getInstance();
    $nextPaymentDate = $erpDb->getNextPaymentDate($customerId);
    
    // Fallback or additional info: Project Start Date
    $projectStartDate = $erpDb->getProjectStartDate($customerId);
    
    // Ensure we have a next payment date
    if (!$nextPaymentDate) {
        // If no explicit next payment date column data, maybe fallback to old logic?
        // But user asked to use the column. If column is empty, we might need to fallback.
        // Let's implement a fallback to the old calculation if the column is null/empty.
        if ($projectStartDate) {
           $nextDueDateObj = $erpDb->getNextPaymentDueDate($projectStartDate);
           if ($nextDueDateObj) {
               $nextPaymentDate = $nextDueDateObj->format('Y-m-d');
           }
        }
    }
    
    if (!$nextPaymentDate) {
         // If still no date, we can't calculate countdown
         echo json_encode([
            'status' => 'error',
            'message' => 'Next payment date not found for customer ID: ' . $customerId
        ]);
        exit;
    }
    
    // Calculate days until next payment
    $daysUntilPayment = $erpDb->getDaysRemaining($nextPaymentDate);
    
    // Format the date for display
    $nextDueDateObj = new DateTime($nextPaymentDate);
    
    // Determine if we should show warning (10 days or less)
    $showWarning = ($daysUntilPayment !== null && $daysUntilPayment <= 10);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'customer_id' => $customerId,
            'project_start_date' => $projectStartDate,
            'next_due_date' => $nextPaymentDate,
            'next_due_date_formatted' => $nextDueDateObj->format('F j, Y'),
            'days_until_payment' => $daysUntilPayment,
            'show_warning' => $showWarning
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
