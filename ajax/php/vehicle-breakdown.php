<?php
include_once(dirname(__FILE__) . '/../../class/include.php');

if (isset($_POST['create'])) {
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $breakdown_date = $_POST['breakdown_date'] ?? '';
    $issue_description = $_POST['description'] ?? '';
    $resolved_date = $_POST['resolved_date'] ?? '';
    $status = $_POST['status'] ?? 'Pending';

    if (empty($vehicle_id) || empty($breakdown_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Vehicle and Breakdown Date are required.']);
        exit();
    }

    $VB = new VehicleBreakdown();
    $VB->vehicle_id = $vehicle_id;
    $VB->breakdown_date = $breakdown_date;
    $VB->issue_description = $issue_description;
    $VB->resolved_date = $resolved_date ?: null;
    $VB->status = $status;
    $VB->created_by = $_SESSION['id'] ?? 0;

    $id = $VB->create();
    if ($id) {
        echo json_encode(['status' => 'success', 'message' => 'Breakdown recorded successfully.', 'id' => $id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to record breakdown.']);
    }
    exit();
}

if (isset($_POST['update'])) {
    $id = $_POST['id'] ?? '';
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $breakdown_date = $_POST['breakdown_date'] ?? '';
    $issue_description = $_POST['description'] ?? '';
    $resolved_date = $_POST['resolved_date'] ?? '';
    $status = $_POST['status'] ?? 'Pending';

    if (empty($id) || empty($vehicle_id) || empty($breakdown_date)) {
        echo json_encode(['status' => 'error', 'message' => 'ID, Vehicle, and Breakdown Date are required.']);
        exit();
    }

    $VB = new VehicleBreakdown($id);
    $VB->vehicle_id = $vehicle_id;
    $VB->breakdown_date = $breakdown_date;
    $VB->issue_description = $issue_description;
    $VB->resolved_date = $resolved_date ?: null;
    $VB->status = $status;

    if ($VB->update()) {
        echo json_encode(['status' => 'success', 'message' => 'Breakdown updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update breakdown.']);
    }
    exit();
}

if (isset($_POST['delete'])) {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID is required.']);
        exit();
    }

    $VB = new VehicleBreakdown($id);
    if ($VB->delete()) {
        echo json_encode(['status' => 'success', 'message' => 'Breakdown deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete breakdown.']);
    }
    exit();
}
?>
