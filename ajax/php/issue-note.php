<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(1);
ini_set('display_errors', 1);

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

    // Decode items first for validation
    $items = json_decode($_POST['items'] ?? '[]', true);

    // 1. Validation Phase: Check if issued quantity exceeds remaining balance
    foreach ($items as $item) {
        $eqId = (int)$item['equipment_id'];
        $subEqId = !empty($item['sub_equipment_id']) ? (int)$item['sub_equipment_id'] : 'NULL';
        $rentId = (int)$_POST['rent_invoice_id'];
        
        // Get total ordered for this line item
        $orderedQuery = "SELECT SUM(quantity) as ordered FROM equipment_rent_items 
                         WHERE rent_id = $rentId 
                         AND equipment_id = $eqId 
                         AND (sub_equipment_id = $subEqId OR ($subEqId IS NULL AND sub_equipment_id IS NULL))";
        $orderedRes = mysqli_fetch_assoc($db->readQuery($orderedQuery));
        $totalOrdered = (float)($orderedRes['ordered'] ?? 0);
        
        // Get total issued so far (excluding current being created)
        $issuedQuery = "SELECT SUM(ini.issued_quantity) as issued 
                        FROM issue_note_items ini
                        INNER JOIN issue_notes n ON ini.issue_note_id = n.id
                        WHERE n.rent_invoice_id = $rentId 
                        AND ini.equipment_id = $eqId 
                        AND (ini.sub_equipment_id = $subEqId OR ($subEqId IS NULL AND ini.sub_equipment_id IS NULL))";
        $issuedRes = mysqli_fetch_assoc($db->readQuery($issuedQuery));
        $totalIssuedSoFar = (float)($issuedRes['issued'] ?? 0);
        
        $newIssued = (float)$item['issued_quantity'];
        
        // Enforce validation: New + Previous Issued <= Total Billed
        if (($totalIssuedSoFar + $newIssued) > $totalOrdered) {
            echo json_encode([
                "status" => "error", 
                "message" => "Cannot issue $newIssued for Item ID $eqId. Perviously issued: $totalIssuedSoFar. Total Billed: $totalOrdered."
            ]);
            exit();
        }
    }

    // 2. Create Issue Note
    $NOTE = new IssueNote(null);
    $NOTE->issue_note_code = $_POST['issue_note_code'];
    $NOTE->rent_invoice_id = $_POST['rent_invoice_id'];
    $NOTE->customer_id = $_POST['customer_id'];
    $NOTE->issue_date = $_POST['issue_date'];
    $NOTE->issue_status = 'issued'; 
    $NOTE->remarks = $_POST['remarks'] ?? '';

    $note_id = $NOTE->create();

    if ($note_id) {
        // 3. Create Items
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

        // 4. Update Invoice Issuing Status
        $rentId = (int)$_POST['rent_invoice_id'];
        
        // Calculate totals for the whole invoice
        $totalOrderedSql = "SELECT SUM(quantity) as total FROM equipment_rent_items WHERE rent_id = $rentId";
        $totalIssuedSql = "SELECT SUM(ini.issued_quantity) as total 
                           FROM issue_note_items ini 
                           INNER JOIN issue_notes n ON ini.issue_note_id = n.id 
                           WHERE n.rent_invoice_id = $rentId";
                           
        $totOrd = (float)mysqli_fetch_assoc($db->readQuery($totalOrderedSql))['total'];
        $totIss = (float)mysqli_fetch_assoc($db->readQuery($totalIssuedSql))['total'];
        
        $newDocStatus = 0; // Not Issued
        if ($totIss > 0) {
            if ($totIss >= $totOrd) {
                $newDocStatus = 2; // Fully Issued
            } else {
                $newDocStatus = 1; // Partially Issued
            }
        }
        
        $db->readQuery("UPDATE equipment_rent SET issue_status = $newDocStatus WHERE id = $rentId");

        // 5. Audit & Tracking
        (new DocumentTracking(null))->incrementDocumentId('issue_note');

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
            
            // Fetch previously issued quantities for this invoice
            $db = Database::getInstance();
            $issuedQuery = "SELECT ini.equipment_id, ini.sub_equipment_id, SUM(ini.issued_quantity) as total_issued
                            FROM issue_note_items ini
                            INNER JOIN issue_notes note ON ini.issue_note_id = note.id
                            WHERE note.rent_invoice_id = " . (int)$invoice_id . "
                            GROUP BY ini.equipment_id, ini.sub_equipment_id";
            $issuedResult = $db->readQuery($issuedQuery);
            $issuedMap = [];
            while ($row = mysqli_fetch_assoc($issuedResult)) {
                $key = $row['equipment_id'] . '_' . ($row['sub_equipment_id'] ?? 'NULL');
                $issuedMap[$key] = (float)$row['total_issued'];
            }

            // Get Issue History
            $historyQuery = "SELECT n.id, n.issue_note_code, n.issue_date, n.issue_status, n.created_at,
                             (SELECT SUM(issued_quantity) FROM issue_note_items WHERE issue_note_id = n.id) as total_qty
                             FROM issue_notes n
                             WHERE n.rent_invoice_id = " . (int)$invoice_id . " 
                             ORDER BY n.id DESC";
            $historyResult = $db->readQuery($historyQuery);
            $history = [];
            while ($row = mysqli_fetch_assoc($historyResult)) {
                $history[] = $row;
            }

            // Format items for frontend
            $formattedItems = [];
            foreach ($items as $item) {
                // Determine already issued qty
                $key = $item['equipment_id'] . '_' . ($item['sub_equipment_id'] ? $item['sub_equipment_id'] : 'NULL');
                $alreadyIssued = $issuedMap[$key] ?? 0;
                $orderedQty = (float)$item['quantity'];
                $remainingQty = max(0, $orderedQty - $alreadyIssued);

                $formattedItems[] = [
                    'equipment_id' => $item['equipment_id'],
                    'sub_equipment_id' => $item['sub_equipment_id'],
                    'equipment_name' => $item['equipment_name'],
                    'sub_equipment_code' => $item['sub_equipment_code'],
                    'quantity' => $orderedQty,          // Total Billed Qty
                    'already_issued' => $alreadyIssued, // Previously Issued
                    'remaining_quantity' => $remainingQty, // Available based on balance
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
                    "rental_date" => $RENT->rental_date,
                    "issue_status" => $RENT->issue_status ?? 0 // 0=Pending, 1=Partial, 2=Full
                ],
                "items" => $formattedItems,
                "history" => $history
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
