<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create
if (isset($_POST['create'])) {
    $TC = new TermsCondition(NULL);
    $TC->sort_order = $_POST['sort_order'] ?? 0;
    $TC->description = $_POST['description'] ?? '';
    $TC->is_active = isset($_POST['is_active']) ? 1 : 0;

    $result = $TC->create();

    if ($result) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create"]);
    }
    exit();
}

// Update
if (isset($_POST['update'])) {
    $TC = new TermsCondition($_POST['id']);
    $TC->sort_order = $_POST['sort_order'] ?? 0;
    $TC->description = $_POST['description'] ?? '';
    $TC->is_active = isset($_POST['is_active']) ? 1 : 0;

    $result = $TC->update();

    if ($result) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    exit();
}

// Delete
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $TC = new TermsCondition($_POST['id']);
    $result = $TC->delete();

    if ($result) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    exit;
}

// Filter for DataTable
if (isset($_POST['filter'])) {
    $TC = new TermsCondition(NULL);
    $result = $TC->fetchForDataTable($_REQUEST);
    echo json_encode($result);
    exit;
}

// Get details
if (isset($_POST['action']) && $_POST['action'] === 'get_details') {
    $id = $_POST['id'] ?? 0;

    if ($id) {
        $TC = new TermsCondition($id);
        echo json_encode([
            "status" => "success",
            "data" => [
                "id" => $TC->id,
                "sort_order" => $TC->sort_order,
                "description" => $TC->description,
                "is_active" => $TC->is_active
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "ID required"]);
    }
    exit;
}
