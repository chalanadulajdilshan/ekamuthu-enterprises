<?php
include '../../class/include.php';

header('Content-Type: application/json');

// Handle GET request - Load special permissions for a user
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['userId'])) {
        echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
        exit();
    }

    $userId = (int)$_GET['userId'];
    $specialPermission = new SpecialUserPermission();
    $permissions = $specialPermission->getByUser($userId);

    echo json_encode([
        'status' => 'success',
        'data' => $permissions
    ]);
    exit();
}

// Handle POST request - Save/Update/Delete special permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle status update
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        header('Content-Type: application/json');
        
        if (!isset($_POST['permission_id']) || !isset($_POST['status'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
            exit();
        }

        $permission = new SpecialUserPermission((int)$_POST['permission_id']);
        if (!$permission->id) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Permission not found']);
            exit();
        }

        // Ensure status is either 'active' or 'inactive'
        $newStatus = ($_POST['status'] === 'active') ? 'active' : 'inactive';
        $permission->status = $newStatus;
        
        $result = $permission->update();
        
        if ($result === false) {
            error_log("Failed to update permission ID: " . $permission->id . " to status: " . $newStatus);
            http_response_code(500);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Database error while updating status'
            ]);
            exit();
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Status updated successfully',
            'data' => [
                'id' => $permission->id,
                'status' => $newStatus
            ]
        ]);
        exit();
    }

    // Handle create new permission
    if (isset($_POST['permission_name'])) {
        if (!isset($_POST['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
            exit();
        }

        $specialPermission = new SpecialUserPermission();
        $specialPermission->user_id = (int)$_POST['user_id'];
        $specialPermission->permission_name = $_POST['permission_name'];
        $specialPermission->status = 'active';

        $result = $specialPermission->create();

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'id' => $specialPermission->id,
                    'permission_name' => $specialPermission->permission_name,
                    'status' => $specialPermission->status
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to create permission'
            ]);
        }
        exit();
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

// If request method is not GET or POST
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);