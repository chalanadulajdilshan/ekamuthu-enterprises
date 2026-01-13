<?php
header('Content-Type: application/json');
require_once('../../class/Database.php');

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get the action
    $action = $_POST['action'] ?? '';

    if ($action === 'get_old_outstanding_report') {
        $customerId = $_POST['customer_id'] ?? '';

        $db = Database::getInstance();

        // Build the query for customers with old outstanding amounts
        $query = "SELECT
                    cm.id,
                    cm.code,
                    cm.name,
                    cm.name_2,
                    cm.mobile_number,
                    cm.old_outstanding
                  FROM
                    customer_master cm
                  WHERE
                    cm.is_active = 1 AND
                    cm.old_outstanding > 0";

        // Add customer filter if provided
        if (!empty($customerId)) {
            $query .= " AND cm.id = " . (int)$customerId;
        }

        $query .= " ORDER BY cm.name ASC";

        $result = $db->readQuery($query);
        if (!$result) {
            throw new Exception('Error executing query: ' . mysqli_error($db->DB_CON));
        }
        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            // Add customer data to results
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
