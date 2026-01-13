<?php
header('Content-Type: application/json');
require_once('../../class/Database.php');

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'get_old_outstanding') {
        $customerId = $_POST['customer_id'] ?? '';

        if (empty($customerId)) {
            throw new Exception('Customer ID is required');
        }

        $db = Database::getInstance();

        $query = "SELECT
                    id, code, name, name_2, mobile_number, old_outstanding
                  FROM customer_master
                  WHERE id = ? AND is_active = 1 AND old_outstanding > 0";

        $stmt = $db->DB_CON->prepare($query);
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => $row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'name_2' => $row['name_2'],
                'mobile_number' => $row['mobile_number'],
                'old_outstanding' => (float)$row['old_outstanding']
            ];
        }

        $response = [
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $data
        ];

    } elseif ($action === 'settle_old_outstanding') {
        $customerId = $_POST['customer_id'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $remarks = $_POST['remarks'] ?? '';

        if (empty($customerId) || !is_numeric($amount) || $amount <= 0) {
            throw new Exception('Invalid input parameters');
        }

        $db = Database::getInstance();

        // Start transaction
        $db->DB_CON->begin_transaction();

        try {
            // Get current old outstanding
            $query = "SELECT old_outstanding FROM customer_master WHERE id = ? AND is_active = 1 FOR UPDATE";
            $stmt = $db->DB_CON->prepare($query);
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if (!$row) {
                throw new Exception('Customer not found');
            }

            $currentOutstanding = (float)$row['old_outstanding'];

            if ($amount > $currentOutstanding) {
                throw new Exception('Settlement amount exceeds current old outstanding');
            }

            $newOutstanding = $currentOutstanding - $amount;

            // Update old outstanding
            $updateQuery = "UPDATE customer_master SET old_outstanding = ? WHERE id = ?";
            $updateStmt = $db->DB_CON->prepare($updateQuery);
            $updateStmt->bind_param('di', $newOutstanding, $customerId);
            $updateStmt->execute();

            // Log the settlement (you might want to create a settlement log table)
            // For now, we'll just update the amount

            $db->DB_CON->commit();

            $response = [
                'status' => 'success',
                'message' => 'Old outstanding settled successfully. Remaining amount: ' . number_format($newOutstanding, 2),
                'data' => ['remaining_outstanding' => $newOutstanding]
            ];

        } catch (Exception $e) {
            $db->DB_CON->rollback();
            throw $e;
        }

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => []
    ];
}

echo json_encode($response);
?>
