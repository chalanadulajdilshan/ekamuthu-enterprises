<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Create a new Service Income
if (isset($_POST['create'])) {

    $SERVICE_INCOME = new ServiceIncome(NULL); // New service income object

    $SERVICE_INCOME->name = $_POST['name'];
    $SERVICE_INCOME->amount = $_POST['amount'];
    $SERVICE_INCOME->remark = $_POST['remark'];

    $res = $SERVICE_INCOME->create();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}

// Update Service Income
if (isset($_POST['update'])) {

    $SERVICE_INCOME = new ServiceIncome($_POST['id']); // Load service income by ID

    $SERVICE_INCOME->name = $_POST['name'];
    $SERVICE_INCOME->amount = $_POST['amount'];
    $SERVICE_INCOME->remark = $_POST['remark'];

    $res = $SERVICE_INCOME->update();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}

// Delete Service Income
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $SERVICE_INCOME = new ServiceIncome($_POST['id']);
    $res = $SERVICE_INCOME->delete();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}
