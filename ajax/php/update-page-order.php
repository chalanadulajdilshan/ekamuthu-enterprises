<?php
include '../../class/include.php';

header('Content-Type: application/json');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['pageOrder']) || !is_array($input['pageOrder'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit();
}

$db = Database::getInstance();
$success = true;

// Start transaction
$db->readQuery("START TRANSACTION");

try {
    foreach ($input['pageOrder'] as $index => $pageId) {
        $pageId = (int)$pageId;
        $queue = $index + 1; // Start queue from 1
        
        $query = "UPDATE `pages` SET `queue` = $queue WHERE `id` = $pageId";
        $result = $db->readQuery($query);
        
        if (!$result) {
            throw new Exception("Failed to update page order for page ID: $pageId");
        }
    }
    
    // Commit transaction
    $db->readQuery("COMMIT");
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Page order updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->readQuery("ROLLBACK");
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
?>
