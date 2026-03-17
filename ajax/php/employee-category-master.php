<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create a new employee category
if (isset($_POST['create'])) {

    $EMPLOYEE_CATEGORY = new EmployeeCategoryMaster(NULL);

    // Set employee category details
    $EMPLOYEE_CATEGORY->code = $_POST['code'];
    $EMPLOYEE_CATEGORY->name = $_POST['name'];
    $EMPLOYEE_CATEGORY->remark = $_POST['remark'];
    $EMPLOYEE_CATEGORY->is_active = isset($_POST['is_active']) ? 1 : 0;

    // Attempt to create
    $res = $EMPLOYEE_CATEGORY->create();

    if ($res) {
        echo json_encode(['status' => 'success']);
        exit();
    } else {
        echo json_encode(['status' => 'error']);
        exit();
    }
}

// Update employee category
if (isset($_POST['update'])) {

    $EMPLOYEE_CATEGORY = new EmployeeCategoryMaster($_POST['id']);

    // Update details
    $EMPLOYEE_CATEGORY->code = $_POST['code'];
    $EMPLOYEE_CATEGORY->name = $_POST['name'];
    $EMPLOYEE_CATEGORY->remark = $_POST['remark'];
    $EMPLOYEE_CATEGORY->is_active = isset($_POST['is_active']) ? 1 : 0;

    $result = $EMPLOYEE_CATEGORY->update();

    if ($result) {
        echo json_encode(['status' => 'success']);
        exit();
    } else {
        echo json_encode(['status' => 'error']);
        exit();
    }
}

// Delete employee category
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $EMPLOYEE_CATEGORY = new EmployeeCategoryMaster($_POST['id']);
    $result = $EMPLOYEE_CATEGORY->delete();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}

?>
