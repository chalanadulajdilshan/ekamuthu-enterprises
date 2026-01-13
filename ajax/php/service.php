<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Create a new service
if (isset($_POST['create'])) {

    $SERVICE = new Service(NULL); // Create a new Service object

    // Set the service details
    $SERVICE->service_code = $_POST['service_code'];
    $SERVICE->service_name = $_POST['service_name'];
    $SERVICE->service_price = $_POST['service_price'];

    // Attempt to create the service
    $res = $SERVICE->create();

    if ($res) {
        echo json_encode(["status" => 'success', "id" => $res]);
        exit();
    } else {
        echo json_encode(["status" => 'error']);
        exit();
    }
}

// Update service details
if (isset($_POST['update'])) {

    $SERVICE = new Service($_POST['service_id']); // Retrieve service by ID

    // Update service details
    $SERVICE->service_name = $_POST['service_name'];
    $SERVICE->service_price = $_POST['service_price'];

    $result = $SERVICE->update();

    if ($result) {
        echo json_encode(["status" => 'success']);
        exit();
    } else {
        echo json_encode(["status" => 'error']);
        exit();
    }
}

// Delete service
if (isset($_POST['delete']) && isset($_POST['service_id'])) {
    $SERVICE = new Service($_POST['service_id']);
    $result = $SERVICE->delete();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
if (isset($_POST['action']) && $_POST['action'] === 'get_service_price') {
    $service_id = $_POST['service_id'];

    $SERVICE = new Service($service_id);

    if ($SERVICE->id) {
        echo json_encode([
            'status' => 'success',
            'service_price' => $SERVICE->service_price,
            'service_id' => $SERVICE->id
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Service not found'
        ]);
    }
    exit();
}


?>
