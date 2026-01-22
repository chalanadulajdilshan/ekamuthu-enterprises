<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new repair job
if (isset($_POST['create'])) {
    $db = Database::getInstance();
    
    // Check if job code already exists
    $codeCheck = "SELECT id FROM repair_jobs WHERE job_code = '" . $db->escapeString($_POST['job_code']) . "'";
    $existing = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existing) {
        echo json_encode(["status" => "duplicate", "message" => "Job code already exists"]);
        exit();
    }

    $JOB = new RepairJob(null);
    $JOB->job_code = $_POST['job_code'];
    $JOB->item_type = $_POST['item_type'] ?? 'customer';
    $JOB->machine_code = $_POST['machine_code'] ?? '';
    $JOB->machine_name = $_POST['machine_name'] ?? '';
    $JOB->customer_name = $_POST['customer_name'] ?? '';
    $JOB->customer_address = $_POST['customer_address'] ?? '';
    $JOB->customer_phone = $_POST['customer_phone'] ?? '';
    $JOB->item_breakdown_date = !empty($_POST['item_breakdown_date']) ? $_POST['item_breakdown_date'] : null;
    $JOB->technical_issue = $_POST['technical_issue'] ?? '';
    $JOB->job_status = $_POST['job_status'] ?? 'pending';
    $JOB->repair_charge = $_POST['repair_charge'] ?? 0;
    $JOB->commission_percentage = $_POST['commission_percentage'] ?? 15;
    $JOB->commission_amount = $_POST['commission_amount'] ?? 0;
    $JOB->total_cost = 0;
    $JOB->remark = $_POST['remark'] ?? '';

    $job_id = $JOB->create();

    if ($job_id) {
        // Create repair items if provided
        $items = json_decode($_POST['items'] ?? '[]', true);
        foreach ($items as $item) {
            $ITEM = new RepairJobItem(null);
            $ITEM->job_id = $job_id;
            $ITEM->item_name = $item['item_name'] ?? '';
            $ITEM->quantity = $item['quantity'] ?? 1;
            $ITEM->unit_price = $item['unit_price'] ?? 0;
            $ITEM->total_price = $item['total_price'] ?? 0;
            $ITEM->create();
        }

        // Update total cost
        $JOB->id = $job_id;
        $JOB->updateTotalCost();

        // Increment document tracking
        (new DocumentTracking(null))->incrementDocumentId('repair_job');

        // Audit log
        $AUDIT_LOG = new AuditLog(null);
        $AUDIT_LOG->ref_id = $job_id;
        $AUDIT_LOG->ref_code = $_POST['job_code'];
        $AUDIT_LOG->action = 'CREATE';
        $AUDIT_LOG->description = 'CREATE REPAIR JOB #' . $_POST['job_code'];
        $AUDIT_LOG->user_id = $_SESSION['id'] ?? 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        echo json_encode(["status" => "success", "job_id" => $job_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create job"]);
    }
    exit();
}

// Update repair job
if (isset($_POST['update'])) {
    $JOB = new RepairJob($_POST['job_id']);
    
    if (!$JOB->id) {
        echo json_encode(["status" => "error", "message" => "Job not found"]);
        exit();
    }

    $JOB->job_code = $_POST['job_code'];
    $JOB->item_type = $_POST['item_type'] ?? 'customer';
    $JOB->machine_code = $_POST['machine_code'] ?? '';
    $JOB->machine_name = $_POST['machine_name'] ?? '';
    $JOB->customer_name = $_POST['customer_name'] ?? '';
    $JOB->customer_address = $_POST['customer_address'] ?? '';
    $JOB->customer_phone = $_POST['customer_phone'] ?? '';
    $JOB->item_breakdown_date = !empty($_POST['item_breakdown_date']) ? $_POST['item_breakdown_date'] : null;
    $JOB->technical_issue = $_POST['technical_issue'] ?? '';
    $JOB->job_status = $_POST['job_status'] ?? 'pending';
    $JOB->repair_charge = $_POST['repair_charge'] ?? 0;
    $JOB->commission_percentage = $_POST['commission_percentage'] ?? 15;
    $JOB->commission_amount = $_POST['commission_amount'] ?? 0;
    $JOB->remark = $_POST['remark'] ?? '';

    $res = $JOB->update();

    if ($res) {
        // Handle items - delete existing and recreate
        $ITEM = new RepairJobItem(null);
        $ITEM->deleteByJobId($JOB->id);

        $items = json_decode($_POST['items'] ?? '[]', true);
        foreach ($items as $item) {
            $NEW_ITEM = new RepairJobItem(null);
            $NEW_ITEM->job_id = $JOB->id;
            $NEW_ITEM->item_name = $item['item_name'] ?? '';
            $NEW_ITEM->quantity = $item['quantity'] ?? 1;
            $NEW_ITEM->unit_price = $item['unit_price'] ?? 0;
            $NEW_ITEM->total_price = $item['total_price'] ?? 0;
            $NEW_ITEM->create();
        }

        // Update total cost
        $JOB->updateTotalCost();

        // Audit log
        $AUDIT_LOG = new AuditLog(null);
        $AUDIT_LOG->ref_id = $_POST['job_id'];
        $AUDIT_LOG->ref_code = $_POST['job_code'];
        $AUDIT_LOG->action = 'UPDATE';
        $AUDIT_LOG->description = 'UPDATE REPAIR JOB #' . $_POST['job_code'];
        $AUDIT_LOG->user_id = $_SESSION['id'] ?? 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update job"]);
    }
    exit();
}

// Delete repair job
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $JOB = new RepairJob($_POST['id']);

    if ($JOB->id) {
        // Audit log
        $AUDIT_LOG = new AuditLog(null);
        $AUDIT_LOG->ref_id = $_POST['id'];
        $AUDIT_LOG->ref_code = $JOB->job_code;
        $AUDIT_LOG->action = 'DELETE';
        $AUDIT_LOG->description = 'DELETE REPAIR JOB #' . $JOB->job_code;
        $AUDIT_LOG->user_id = $_SESSION['id'] ?? 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        $res = $JOB->delete();
        echo json_encode(['status' => $res ? 'success' : 'error']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Job not found']);
    }
    exit();
}

// Filter for DataTable
if (isset($_POST['filter'])) {
    $JOB = new RepairJob(null);
    $result = $JOB->fetchForDataTable($_REQUEST);
    echo json_encode($result);
    exit();
}

// Get job details with items
if (isset($_POST['action']) && $_POST['action'] === 'get_job_details') {
    $job_id = $_POST['job_id'] ?? 0;

    if ($job_id) {
        $JOB = new RepairJob($job_id);
        $items = $JOB->getItems();

        echo json_encode([
            "status" => "success",
            "job" => [
                "id" => $JOB->id,
                "job_code" => $JOB->job_code,
                "item_type" => $JOB->item_type,
                "machine_code" => $JOB->machine_code,
                "machine_name" => $JOB->machine_name,
                "customer_name" => $JOB->customer_name,
                "customer_address" => $JOB->customer_address,
                "customer_phone" => $JOB->customer_phone,
                "item_breakdown_date" => $JOB->item_breakdown_date,
                "technical_issue" => $JOB->technical_issue,
                "job_status" => $JOB->job_status,
                "repair_charge" => $JOB->repair_charge,
                "commission_percentage" => $JOB->commission_percentage,
                "commission_amount" => $JOB->commission_amount,
                "total_cost" => $JOB->total_cost,
                "remark" => $JOB->remark
            ],
            "items" => $items
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Job ID required"]);
    }
    exit();
}

// Get new job code
if (isset($_POST['action']) && $_POST['action'] === 'get_new_code') {
    $DOCUMENT_TRACKING = new DocumentTracking(1);
    $lastId = $DOCUMENT_TRACKING->repair_job_id ?? 0;
    $newCode = 'RJ/' . ($_SESSION['id'] ?? '0') . '/0' . ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit();
}
