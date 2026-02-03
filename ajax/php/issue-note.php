<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new issue note
if (isset($_POST['create'])) {
    $db = Database::getInstance();
    
    // Check if code exists
    $codeCheck = "SELECT id FROM issue_notes WHERE issue_note_code = '" . $db->escapeString($_POST['issue_note_code']) . "'";
    $existing = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existing) {
        echo json_encode(["status" => "duplicate", "message" => "Issue Note code already exists"]);
        exit();
    }

    $NOTE = new IssueNote(null);
    $NOTE->issue_note_code = $_POST['issue_note_code'];
    $NOTE->rent_invoice_id = $_POST['rent_invoice_id'];
    $NOTE->customer_id = $_POST['customer_id'];
    $NOTE->issue_date = $_POST['issue_date'];
    $NOTE->issue_status = $_POST['issue_status'] ?? 'pending';
    $NOTE->remarks = $_POST['remarks'] ?? '';

    $note_id = $NOTE->create();

    if ($note_id) {
        // Create items
        $items = json_decode($_POST['items'] ?? '[]', true);
        foreach ($items as $item) {
            $ITEM = new IssueNoteItem(null);
            $ITEM->issue_note_id = $note_id;
            $ITEM->equipment_id = $item['equipment_id'];
            $ITEM->sub_equipment_id = $item['sub_equipment_id'] ?? null;
            $ITEM->ordered_quantity = $item['ordered_quantity'];
            $ITEM->issued_quantity = $item['issued_quantity'];
            $ITEM->rent_type = $item['rent_type'];
            $ITEM->duration = $item['duration'];
            $ITEM->remarks = $item['remarks'] ?? '';
            $ITEM->create();
        }

        // Increment document ID
        (new DocumentTracking(null))->incrementDocumentId('issue_note');

        // Audit log
        $AUDIT_LOG = new AuditLog(null);
        $AUDIT_LOG->ref_id = $note_id;
        $AUDIT_LOG->ref_code = $_POST['issue_note_code'];
        $AUDIT_LOG->action = 'CREATE';
        $AUDIT_LOG->description = 'CREATE ISSUE NOTE #' . $_POST['issue_note_code'];
        $AUDIT_LOG->user_id = $_SESSION['id'] ?? 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        echo json_encode(["status" => "success", "id" => $note_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create Issue Note"]);
    }
    exit();
}

// Get invoice details for loading
if (isset($_POST['action']) && $_POST['action'] === 'get_invoice_details') {
    $invoice_id = $_POST['invoice_id'] ?? 0;

    if ($invoice_id) {
        $RENT = new EquipmentRent($invoice_id);
        
        if ($RENT->id) {
            $RENT_ITEMS = new EquipmentRentItem(null);
            $items = $RENT_ITEMS->getByRentId($RENT->id);
            
            // Format items for frontend
            $formattedItems = [];
            foreach ($items as $item) {
                $formattedItems[] = [
                    'equipment_id' => $item['equipment_id'],
                    'sub_equipment_id' => $item['sub_equipment_id'],
                    'equipment_name' => $item['equipment_name'],
                    'sub_equipment_code' => $item['sub_equipment_code'],
                    'quantity' => $item['quantity'],
                    'rent_type' => $item['rent_type'],
                    'duration' => $item['duration'],
                    'rental_date' => $item['rental_date'],
                    'return_date' => $item['return_date']
                ];
            }

            // Get customer info
            $CUSTOMER = new CustomerMaster($RENT->customer_id);

            echo json_encode([
                "status" => "success",
                "invoice" => [
                    "id" => $RENT->id,
                    "bill_number" => $RENT->bill_number,
                    "customer_id" => $RENT->customer_id,
                    "customer_name" => $CUSTOMER->name,
                    "customer_phone" => $CUSTOMER->mobile_number,
                    "rental_date" => $RENT->rental_date
                ],
                "items" => $formattedItems
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invoice not found"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invoice ID required"]);
    }
    exit();
}

// Get new code
if (isset($_POST['action']) && $_POST['action'] === 'get_new_code') {
    $DOCUMENT_TRACKING = new DocumentTracking(1);
    $lastId = $DOCUMENT_TRACKING->issue_note_id ?? 0;
    $newCode = 'IN/' . ($_SESSION['id'] ?? '0') . '/0' . ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit();
}

// Create new Issue Note from Invoice
// (Used when creating directly from Rent Master if needed, but primary flow is load -> save)
if (isset($_POST['action']) && $_POST['action'] === 'create_from_invoice') {
    // ... logic similar to create, but auto-populating
}

// Get Issue Note Details
if (isset($_POST['action']) && $_POST['action'] === 'get_issue_note_details') {
    $note_id = $_POST['note_id'] ?? 0;
    
    if ($note_id) {
        $ISSUE_NOTE = new IssueNote($note_id);
        $items = $ISSUE_NOTE->getItems();
        
        // Get relatedrent invoice details for display
        $RENT = new EquipmentRent($ISSUE_NOTE->rent_invoice_id);
        $CUSTOMER = new CustomerMaster($ISSUE_NOTE->customer_id);
        
        echo json_encode([
            "status" => "success",
            "note" => [
                "id" => $ISSUE_NOTE->id,
                "issue_note_code" => $ISSUE_NOTE->issue_note_code,
                "rent_invoice_id" => $ISSUE_NOTE->rent_invoice_id,
                "rent_invoice_ref" => $RENT->bill_number,
                "customer_id" => $ISSUE_NOTE->customer_id,
                "customer_name" => $CUSTOMER->name,
                "customer_phone" => $CUSTOMER->mobile_number,
                "issue_date" => $ISSUE_NOTE->issue_date,
                "issue_status" => $ISSUE_NOTE->issue_status,
                "remarks" => $ISSUE_NOTE->remarks
            ],
            "items" => $items
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Issue Note ID required"]);
    }
    exit;
}

// Fetch Issue Notes for DataTable (History)
if (isset($_POST['filter'])) {
    $ISSUE_NOTE = new IssueNote(NULL);
    $result = $ISSUE_NOTE->fetchForDataTable($_REQUEST);
    echo json_encode($result);
    exit;
}
