<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Create a new service item
if (isset($_POST['create'])) {

    $ITEM = new ServiceItem(NULL); // Create a new ServiceItem object

    // Set the service item details
    $ITEM->item_code = $_POST['item_code'];
    $ITEM->item_name = $_POST['item_name'];
    $ITEM->cost = $_POST['cost'];
    $ITEM->selling_price = $_POST['selling_price'];
    $ITEM->qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

    // Attempt to create the service item
    $res = $ITEM->create();

    if ($res) {
        echo json_encode(["status" => 'success', "id" => $res]);
        exit();
    } else {
        echo json_encode(["status" => 'error']);
        exit();
    }
}

// Update service item details
if (isset($_POST['update'])) {

    $ITEM = new ServiceItem($_POST['item_id']); // Retrieve service item by ID

    // Update service item details
    $ITEM->item_code = $_POST['item_code'];
    $ITEM->item_name = $_POST['item_name'];
    $ITEM->cost = $_POST['cost'];
    $ITEM->selling_price = $_POST['selling_price'];
    $ITEM->qty = $_POST['qty'];

    $result = $ITEM->update();

    if ($result) {
        echo json_encode(["status" => 'success']);
        exit();
    } else {
        echo json_encode(["status" => 'error']);
        exit();
    }
}

// Delete service item
if (isset($_POST['delete']) && isset($_POST['item_id'])) {
    $ITEM = new ServiceItem($_POST['item_id']);
    $result = $ITEM->delete();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'get_service_item_cost') {
    $service_id = $_POST['selectedId'];
    error_log("Looking up service with ID: " . $service_id); // Add this line

    $SERVICE_ITEM = new ServiceItem();
    $services = $SERVICE_ITEM->getByCode($service_id);


    if (!empty($services)) {
        // Get the first matching service
        $service = $services[0];
        echo json_encode([
            'status' => 'success',
            'service_cost' => $service['cost'],
            'service_selling_price' => $service['selling_price'],
            'service_qty' => $service['qty']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Service not found'
        ]);
    }
    exit();
}
