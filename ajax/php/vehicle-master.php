<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Create a new Vehicle
if (isset($_POST['create'])) {

    $VEHICLE = new Vehicle(NULL);

    // Set the vehicle details
    $VEHICLE->ref_no = $_POST['ref_no'];
    $VEHICLE->vehicle_no = $_POST['vehicle_no'];
    $VEHICLE->brand = $_POST['brand'];
    $VEHICLE->model = $_POST['model'];
    $VEHICLE->type = $_POST['type'];
    $VEHICLE->chassis_no = $_POST['chassis_no'];
    $VEHICLE->engine_no = $_POST['engine_no'];
    $VEHICLE->start_meter = $_POST['start_meter'];

    // Attempt to create the Vehicle
    $res = $VEHICLE->create();

    if ($res) {
        $result = [
            "status" => 'success'
        ];
        echo json_encode($result);
        exit();
    } else {
        $result = [
            "status" => 'error'
        ];
        echo json_encode($result);
        exit();
    }
}

// Update Vehicle details
if (isset($_POST['update'])) {

    $VEHICLE = new Vehicle($_POST['id']);

    // Update vehicle details
    $VEHICLE->ref_no = $_POST['ref_no'];
    $VEHICLE->vehicle_no = $_POST['vehicle_no'];
    $VEHICLE->brand = $_POST['brand'];
    $VEHICLE->model = $_POST['model'];
    $VEHICLE->type = $_POST['type'];
    $VEHICLE->chassis_no = $_POST['chassis_no'];
    $VEHICLE->engine_no = $_POST['engine_no'];
    $VEHICLE->start_meter = $_POST['start_meter'];

    // Attempt to update the Vehicle
    $result = $VEHICLE->update();

    if ($result) {
        $result = [
            "status" => 'success'
        ];
        echo json_encode($result);
        exit();
    } else {
        $result = [
            "status" => 'error'
        ];
        echo json_encode($result);
        exit();
    }
}

// Delete Vehicle
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $VEHICLE = new Vehicle($_POST['id']);
    $result = $VEHICLE->delete();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}

?>
