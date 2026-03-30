<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Create a new Fuel Record
if (isset($_POST['create'])) {

    $FUEL = new VehicleFuel(NULL);

    $FUEL->vehicle_id = $_POST['vehicle_id'];
    $FUEL->fuel_amount = $_POST['fuel_amount'];
    $FUEL->liters = $_POST['liters'];
    $FUEL->date = $_POST['date'];

    $res = $FUEL->create();

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

// Update Fuel Record
if (isset($_POST['update'])) {

    $FUEL = new VehicleFuel($_POST['id']);

    $FUEL->vehicle_id = $_POST['vehicle_id'];
    $FUEL->fuel_amount = $_POST['fuel_amount'];
    $FUEL->liters = $_POST['liters'];
    $FUEL->date = $_POST['date'];

    $result = $FUEL->update();

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

// Delete Fuel Record
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $FUEL = new VehicleFuel($_POST['id']);
    $result = $FUEL->delete();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

?>
