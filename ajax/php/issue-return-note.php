<?php

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(1);
ini_set('display_errors', 1);

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

// Create new issue return note
if (isset($_POST['create'])) {
    $db = Database::getInstance();
    
    // Check if code exists
    $codeCheck = "SELECT id FROM issue_returns WHERE return_code = '" . $db->escapeString($_POST['return_code']) . "'";
    $existing = mysqli_fetch_assoc($db->readQuery($codeCheck));

    if ($existing && !isset($_POST['return_id'])) {
        echo json_encode(["status" => "duplicate", "message" => "Return Note code already exists"]);
        exit();
    }

    $items = json_decode($_POST['items'] ?? '[]', true);
    $return_id = $_POST['return_id'] ?? null;

    // 1. Validation Phase
    foreach ($items as $item) {
        $issueNoteId = (int)$_POST['issue_note_id'];
        $eqId = (int)$item['equipment_id'];
        $subEqId = !empty($item['sub_equipment_id']) ? (int)$item['sub_equipment_id'] : 'NULL';
        
        // Get total issued for this specific issue note line item
        $issuedQuery = "SELECT SUM(issued_quantity) as issued FROM issue_note_items 
                         WHERE issue_note_id = $issueNoteId 
                         AND equipment_id = $eqId 
                         AND (sub_equipment_id = $subEqId OR ($subEqId IS NULL AND sub_equipment_id IS NULL))";
        $issuedRes = mysqli_fetch_assoc($db->readQuery($issuedQuery));
        $totalIssued = (float)($issuedRes['issued'] ?? 0);
        
        // Get total returned so far for this issue note
        $returnedQuery = "SELECT SUM(iri.return_quantity) as returned 
                          FROM issue_return_items iri
                          INNER JOIN issue_returns r ON iri.return_id = r.id
                          WHERE r.issue_note_id = $issueNoteId 
                          AND iri.equipment_id = $eqId 
                          AND (iri.sub_equipment_id = $subEqId OR ($subEqId IS NULL AND iri.sub_equipment_id IS NULL))";
        $returnedRes = mysqli_fetch_assoc($db->readQuery($returnedQuery));
        $totalReturnedSoFar = (float)($returnedRes['returned'] ?? 0);
        
        $newReturn = (float)$item['return_quantity'];
        
        if (($totalReturnedSoFar + $newReturn) > $totalIssued) {
            echo json_encode([
                "status" => "error", 
                "message" => "Cannot return $newReturn for Item ID $eqId. Already returned: $totalReturnedSoFar. Total Issued: $totalIssued."
            ]);
            exit();
        }
    }
    
    // 2. Create or Load Return Note
    if ($return_id) {
        $RETURN = new IssueReturnNote($return_id);
        // Sync return date if updated
        $RETURN->return_date = $_POST['return_date'];
        $RETURN->department_id = $_POST['department_id'] ?? $RETURN->department_id;
        $RETURN->remarks = $_POST['remarks'] ?? $RETURN->remarks;
        $RETURN->update();
    } else {
        $RETURN = new IssueReturnNote(null);
        $RETURN->return_code = $_POST['return_code'];
        $RETURN->issue_note_id = $_POST['issue_note_id'];
        $RETURN->return_date = $_POST['return_date'];
        $RETURN->department_id = $_POST['department_id'] ?? null;
        $RETURN->remarks = $_POST['remarks'] ?? '';
        $return_id = $RETURN->create();
    }

    if ($return_id) {
        // 3. Create Items
        foreach ($items as $item) {
            if ($item['return_quantity'] > 0) {
                $ITEM = new IssueReturnNoteItem(null);
                $ITEM->return_id = $return_id;
                $ITEM->equipment_id = $item['equipment_id'];
                $ITEM->sub_equipment_id = $item['sub_equipment_id'] ?? null;
                $ITEM->issued_quantity = $item['issued_quantity'];
                $ITEM->return_quantity = $item['return_quantity'];
                $ITEM->remarks = $item['remarks'] ?? '';
                $ITEM->create();
            }
        }

        // 4. Update Document Tracking
        (new DocumentTracking(null))->incrementDocumentId('issue_return');

        // 5. Audit Log
        $AUDIT_LOG = new AuditLog(null);
        $AUDIT_LOG->ref_id = $return_id;
        $AUDIT_LOG->ref_code = $_POST['return_code'];
        $AUDIT_LOG->action = 'CREATE';
        $AUDIT_LOG->description = 'CREATE ISSUE RETURN NOTE #' . $_POST['return_code'];
        $AUDIT_LOG->user_id = $_SESSION['id'] ?? 0;
        $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
        $AUDIT_LOG->create();

        echo json_encode(["status" => "success", "id" => $return_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create Return Note"]);
    }
    exit();
}

// Get issue note details for return loading
if (isset($_POST['action']) && $_POST['action'] === 'get_issue_details') {
    $note_id = $_POST['issue_note_id'] ?? 0;

    if ($note_id) {
        $NOTE = new IssueNote($note_id);
        
        if ($NOTE->id) {
            $items = $NOTE->getItems();
            $db = Database::getInstance();

            // Fetch previously returned quantities for this issue note
            $returnedQuery = "SELECT iri.equipment_id, iri.sub_equipment_id, SUM(iri.return_quantity) as total_returned
                              FROM issue_return_items iri
                              INNER JOIN issue_returns ret ON iri.return_id = ret.id
                              WHERE ret.issue_note_id = " . (int)$note_id . "
                              GROUP BY iri.equipment_id, iri.sub_equipment_id";
            $returnedResult = $db->readQuery($returnedQuery);
            $returnedMap = [];
            while ($row = mysqli_fetch_assoc($returnedResult)) {
                $key = $row['equipment_id'] . '_' . ($row['sub_equipment_id'] ?? 'NULL');
                $returnedMap[$key] = (float)$row['total_returned'];
            }

            // Fetch existing return notes for this issue note (to support appending)
            $historySql = "SELECT * FROM issue_returns WHERE issue_note_id = " . (int)$note_id . " ORDER BY id DESC";
            $historyRes = $db->readQuery($historySql);
            $history = [];
            while ($h = mysqli_fetch_assoc($historyRes)) {
                $history[] = $h;
            }

            // Format items
            $formattedItems = [];
            foreach ($items as $item) {
                $key = $item['equipment_id'] . '_' . ($item['sub_equipment_id'] ? $item['sub_equipment_id'] : 'NULL');
                $alreadyReturned = $returnedMap[$key] ?? 0;
                $issuedQty = (float)$item['issued_quantity'];
                $remainingQty = max(0, $issuedQty - $alreadyReturned);

                $formattedItems[] = [
                    'equipment_id' => $item['equipment_id'],
                    'sub_equipment_id' => $item['sub_equipment_id'],
                    'equipment_name' => $item['equipment_name'],
                    'sub_equipment_code' => $item['sub_equipment_code'],
                    'rent_type' => $item['rent_type'],
                    'issued_quantity' => $issuedQty,
                    'already_returned' => $alreadyReturned,
                    'remaining_quantity' => $remainingQty
                ];
            }

            $CUSTOMER = new CustomerMaster($NOTE->customer_id);

            echo json_encode([
                "status" => "success",
                "note" => [
                    "id" => $NOTE->id,
                    "issue_note_code" => $NOTE->issue_note_code,
                    "customer_name" => $CUSTOMER->name,
                    "customer_phone" => $CUSTOMER->mobile_number,
                    "issue_date" => $NOTE->issue_date
                ],
                "items" => $formattedItems,
                "history" => $history
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Issue Note not found"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Issue Note ID required"]);
    }
    exit();
}

// Get new code
if (isset($_POST['action']) && $_POST['action'] === 'get_new_code') {
    $DOCUMENT_TRACKING = new DocumentTracking(1);
    $lastId = $DOCUMENT_TRACKING->issue_return_id ?? 0;
    $newCode = 'RN/' . ($_SESSION['id'] ?? '0') . '/0' . ($lastId + 1);

echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit();
}

// Get Return Note Details
if (isset($_POST['action']) && $_POST['action'] === 'get_return_note_details') {
    $return_id = $_POST['return_id'] ?? 0;
    
    if ($return_id) {
        $RETURN = new IssueReturnNote($return_id);
        $items = $RETURN->getItems();
        
        $ISSUE_NOTE = new IssueNote($RETURN->issue_note_id);
        $CUSTOMER = new CustomerMaster($ISSUE_NOTE->customer_id);
        
        echo json_encode([
            "status" => "success",
            "return" => [
                "id" => $RETURN->id,
                "return_code" => $RETURN->return_code,
                "issue_note_id" => $RETURN->issue_note_id,
                "issue_note_code" => $ISSUE_NOTE->issue_note_code,
                "customer_name" => $CUSTOMER->name,
                "customer_phone" => $CUSTOMER->mobile_number,
                "department_id" => $RETURN->department_id,
                "return_date" => $RETURN->return_date,
                "remarks" => $RETURN->remarks
            ],
            "items" => $items
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Return Note ID required"]);
    }
    exit;
}

// Fetch Return Notes for DataTable (History)
if (isset($_POST['filter'])) {
    $RETURN = new IssueReturnNote(NULL);
    $result = $RETURN->fetchForDataTable($_REQUEST);
    echo json_encode($result);
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
