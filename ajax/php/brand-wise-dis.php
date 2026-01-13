<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Create a new discount
if (isset($_POST['create'])) {

    $DISCOUNT = new BrandWiseDis(NULL); // New discount object

    $DISCOUNT->brand_id = $_POST['brand_id'];
    $DISCOUNT->category_id = $_POST['category'];
    $DISCOUNT->discount_percent_01 = $_POST['discount_percent_01'];
    $DISCOUNT->discount_percent_02 = $_POST['discount_percent_02'];
    $DISCOUNT->discount_percent_03 = $_POST['discount_percent_03']; 

    $res = $DISCOUNT->create();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}

// Update discount
if (isset($_POST['update'])) {

    $DISCOUNT = new BrandWiseDis($_POST['dis_id']); // Load discount by ID

    $DISCOUNT->brand_id = $_POST['brand_id'];
    $DISCOUNT->category_id = $_POST['category'];
    $DISCOUNT->discount_percent_01 = $_POST['discount_percent_01'];
    $DISCOUNT->discount_percent_02 = $_POST['discount_percent_02'];
    $DISCOUNT->discount_percent_03 = $_POST['discount_percent_03'];   

    $res = $DISCOUNT->update();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}

// Delete discount
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $DISCOUNT = new BrandWiseDis($_POST['id']);
    $res = $DISCOUNT->delete();

    if ($res) {
        echo json_encode(["status" => 'success']);
    } else {
        echo json_encode(["status" => 'error']);
    }
    exit();
}
