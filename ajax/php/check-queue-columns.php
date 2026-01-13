<?php
include '../../class/include.php';

header('Content-Type: application/json');

// Function to check and add queue column to a table
function checkAndAddQueueColumn($table, $afterColumn) {
    $db = Database::getInstance();
    
    // Check if queue column exists
    $result = $db->readQuery("SHOW COLUMNS FROM `$table` LIKE 'queue'");
    
    if (mysqli_num_rows($result) == 0) {
        // Add queue column if it doesn't exist
        $alterQuery = "ALTER TABLE `$table` ADD COLUMN `queue` INT DEFAULT 0 AFTER `$afterColumn`";
        if ($db->readQuery($alterQuery)) {
            // Initialize queue values to match current ID order
            $updateQuery = "UPDATE `$table` SET `queue` = `id`";
            $db->readQuery($updateQuery);
            return [
                'success' => true,
                'message' => "Successfully added and initialized queue column in $table table."
            ];
        } else {
            return [
                'success' => false,
                'message' => "Error adding queue column to $table: " . mysqli_error($db->DB_CON)
            ];
        }
    } else {
        return [
            'success' => true,
            'message' => "Queue column already exists in $table table."
        ];
    }
}

// Initialize response array
$response = [
    'success' => true,
    'messages' => []
];

// Check and update pages table
$result = checkAndAddQueueColumn('pages', 'page_url');
$response['messages'][] = $result['message'];
if (!$result['success']) {
    $response['success'] = false;
}

// Check and update page_categories table
$result = checkAndAddQueueColumn('page_categories', 'is_active');
$response['messages'][] = $result['message'];
if (!$result['success']) {
    $response['success'] = false;
}

echo json_encode($response);
?>
