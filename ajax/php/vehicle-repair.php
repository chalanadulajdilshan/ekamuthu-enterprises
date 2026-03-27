<?php

include '../../class/include.php';

header('Content-Type: application/json');

if (isset($_POST['create'])) {
    $ref_no = $_POST['ref_no'] ?? '';
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $repair_type = $_POST['repair_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $repair_date = $_POST['repair_date'] ?? '';
    $amount = $_POST['amount'] ?? '0';
    $technician = $_POST['technician'] ?? '';
    $remark = $_POST['remark'] ?? '';

    if (empty($vehicle_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Vehicle is required']);
        exit();
    }

    $VEHICLE_REPAIR = new VehicleRepair(null);
    $VEHICLE_REPAIR->ref_no = $ref_no ?: 'VR/' . ($_SESSION['id'] ?? '0') . '/' . (time() - 1711500000); // Shorter ref
    $VEHICLE_REPAIR->vehicle_id = $vehicle_id;
    $VEHICLE_REPAIR->repair_type = $repair_type;
    $VEHICLE_REPAIR->description = $description;
    $VEHICLE_REPAIR->repair_date = $repair_date;
    $VEHICLE_REPAIR->amount = $amount;
    $VEHICLE_REPAIR->technician = $technician;
    $VEHICLE_REPAIR->remark = $remark;
    $VEHICLE_REPAIR->created_by = $_SESSION['id'];

    $result = $VEHICLE_REPAIR->create();

    if ($result) {
        echo json_encode(['status' => 'success', 'id' => $result]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save']);
    }
    exit();
}

if (isset($_POST['update'])) {
    $id = $_POST['id'] ?? '';
    $ref_no = $_POST['ref_no'] ?? '';
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $repair_type = $_POST['repair_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $repair_date = $_POST['repair_date'] ?? '';
    $amount = $_POST['amount'] ?? '0';
    $technician = $_POST['technician'] ?? '';
    $remark = $_POST['remark'] ?? '';

    if (empty($id) || empty($vehicle_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing id or vehicle']);
        exit();
    }

    $VEHICLE_REPAIR = new VehicleRepair($id);
    $VEHICLE_REPAIR->ref_no = $ref_no;
    $VEHICLE_REPAIR->vehicle_id = $vehicle_id;
    $VEHICLE_REPAIR->repair_type = $repair_type;
    $VEHICLE_REPAIR->description = $description;
    $VEHICLE_REPAIR->repair_date = $repair_date;
    $VEHICLE_REPAIR->amount = $amount;
    $VEHICLE_REPAIR->technician = $technician;
    $VEHICLE_REPAIR->remark = $remark;

    $result = $VEHICLE_REPAIR->update();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
    }
    exit();
}

if (isset($_POST['delete'])) {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing id']);
        exit();
    }
    $VEHICLE_REPAIR = new VehicleRepair($id);
    $result = $VEHICLE_REPAIR->delete();
    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete']);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit();
