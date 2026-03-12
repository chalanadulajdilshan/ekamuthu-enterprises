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
    $codeCheck = "SELECT id, rent_invoice_id FROM issue_notes WHERE issue_note_code = '" . $db->escapeString($_POST['issue_note_code']) . "'";
    $existing = mysqli_fetch_assoc($db->readQuery($codeCheck));

    $note_id = null;
    $is_append = false;

    if ($existing) {
        // Check if existing note is cancelled
        $checkCancelled = "SELECT issue_status FROM issue_notes WHERE id = " . (int)$existing['id'];
        $statusRes = mysqli_fetch_assoc($db->readQuery($checkCancelled));
        if ($statusRes['issue_status'] === 'cancelled') {
            echo json_encode(["status" => "error", "message" => "This Issue Note code belongs to a CANCELLED record and cannot be reused or updated."]);
            exit();
        }

        // If code exists and belongs to the SAME invoice, we are appending
        if ($existing['rent_invoice_id'] == $_POST['rent_invoice_id']) {
            $note_id = $existing['id'];
            $is_append = true;
        } else {
            // Duplicate code for a DIFFERENT invoice
            echo json_encode(["status" => "duplicate", "message" => "Issue Note code already exists for a different invoice"]);
            exit();
        }
    }

    // Normalize department
    $departmentId = isset($_POST['department_id']) ? trim($_POST['department_id']) : '';
    // If appending and no dept sent, fall back to existing note's department
    if ($is_append && (empty($departmentId) || $departmentId === '0')) {
        $existingNote = new IssueNote($note_id);
        $departmentId = $existingNote->department_id;
    }

    if (empty($departmentId) || $departmentId === '0') {
        echo json_encode(["status" => "error", "message" => "Department is required"]);
        exit();
    }

    // Validate selected department matches the invoice's item department(s)
    $deptCheckSql = "SELECT DISTINCT COALESCE(department_id, 0) AS dept_id FROM equipment_rent_items WHERE rent_id = " . (int)$_POST['rent_invoice_id'];
    $deptCheckRes = $db->readQuery($deptCheckSql);
    $deptIds = [];
    while ($row = mysqli_fetch_assoc($deptCheckRes)) {
        $deptIds[] = (int)$row['dept_id'];
    }

    $uniqueDeptIds = array_unique($deptIds);
    if (count($uniqueDeptIds) > 1) {
        echo json_encode(["status" => "error", "message" => "Invoice contains items from multiple departments. Issue Note must match a single department."]);
        exit();
    }

    $invoiceDeptId = $uniqueDeptIds[0] ?? 0;
    if ($invoiceDeptId > 0 && (int)$departmentId !== $invoiceDeptId) {
        // Fetch friendly department name for the message
        $deptName = '-';
        $deptNameQuery = "SELECT name FROM department_master WHERE id = " . (int)$invoiceDeptId . " LIMIT 1";
        $deptNameRes = mysqli_fetch_assoc($db->readQuery($deptNameQuery));
        if ($deptNameRes && !empty($deptNameRes['name'])) {
            $deptName = $deptNameRes['name'];
        }

        echo json_encode([
            "status" => "error", 
            "message" => "Issue Note department must match the invoice department (" . $deptName . ")."
        ]);
        exit();
    }

    // ... validation logic remains the same (it calculates total issued vs ordered) ...

    // Decode items first for validation
    $items = json_decode($_POST['items'] ?? '[]', true);

    // 1. Validation Phase: Check if issued quantity exceeds remaining balance
    foreach ($items as $item) {
        $eqId = (int)$item['equipment_id'];
        $subEqId = !empty($item['sub_equipment_id']) ? (int)$item['sub_equipment_id'] : 'NULL';
        $rentId = (int)$_POST['rent_invoice_id'];
        
        // Get total ordered (billed) for this line item
        $orderedQuery = "SELECT SUM(bill_qty) as ordered FROM equipment_rent_items 
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
                        AND (ini.sub_equipment_id = $subEqId OR ($subEqId IS NULL AND ini.sub_equipment_id IS NULL))
                        AND n.issue_status != 'cancelled'";
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

    // 2. Create Issue Note (Only if not appending). If appending, update department/remarks.
    if (!$is_append) {
        $NOTE = new IssueNote(null);
        $NOTE->issue_note_code = $_POST['issue_note_code'];
        $NOTE->rent_invoice_id = $_POST['rent_invoice_id'];
        $NOTE->customer_id = $_POST['customer_id'];
        $NOTE->issue_date = $_POST['issue_date'];
        $NOTE->issue_status = 'issued'; 
        $NOTE->department_id = (int)$departmentId;
        $NOTE->remarks = $_POST['remarks'] ?? '';
    
        $note_id = $NOTE->create();
    } else {
        // Ensure department stays in sync on subsequent appends
        $NOTE = new IssueNote($note_id);
        $NOTE->department_id = (int)$departmentId ?: $NOTE->department_id;
        $NOTE->remarks = $_POST['remarks'] ?? $NOTE->remarks;
        $NOTE->update();
    }
    // If appending, $note_id is already set from $existing['id']

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

        // 3.5 Update rent item qty & amount for no-sub-equipment items
        $rentId = (int)$_POST['rent_invoice_id'];
        foreach ($items as $item) {
            $eqId = (int)$item['equipment_id'];
            $subEqId = !empty($item['sub_equipment_id']) ? (int)$item['sub_equipment_id'] : null;
            $issuedQty = (float)$item['issued_quantity'];

            // Only for no sub equipment items
            if (empty($subEqId)) {
                // Find the matching rent item
                $rentItemQuery = "SELECT id, quantity, bill_qty, amount, deposit_amount, total_rent_amount, total_returned_qty, department_id 
                                  FROM equipment_rent_items 
                                  WHERE rent_id = $rentId 
                                  AND equipment_id = $eqId 
                                  AND sub_equipment_id IS NULL 
                                  LIMIT 1";
                $rentItemRes = mysqli_fetch_assoc($db->readQuery($rentItemQuery));

                if ($rentItemRes && $issuedQty != (float)$rentItemRes['quantity']) {
                    $billQty = (float)$rentItemRes['bill_qty'];
                    $oldAmount = (float)$rentItemRes['amount'];
                    $oldDeposit = (float)$rentItemRes['deposit_amount'];
                    $oldTotalRent = (float)$rentItemRes['total_rent_amount'];
                    $totalReturnedQty = (float)$rentItemRes['total_returned_qty'];

                    // Calculate per unit values based on bill_qty
                    $perUnitAmount = ($billQty > 0) ? ($oldAmount / $billQty) : 0;
                    $perUnitDeposit = ($billQty > 0) ? ($oldDeposit / $billQty) : 0;
                    $perUnitTotalRent = ($billQty > 0) ? ($oldTotalRent / $billQty) : 0;

                    // Calculate cumulative issued for this rent item (all notes, non-cancelled)
                    $issuedTotalQuery = "SELECT SUM(ini.issued_quantity) as issued 
                                         FROM issue_note_items ini 
                                         INNER JOIN issue_notes n ON ini.issue_note_id = n.id 
                                         WHERE n.rent_invoice_id = $rentId 
                                         AND ini.equipment_id = $eqId 
                                         AND ini.sub_equipment_id IS NULL 
                                         AND n.issue_status != 'cancelled'";
                    $issuedTotalRes = mysqli_fetch_assoc($db->readQuery($issuedTotalQuery));
                    $totalIssuedAll = (float)($issuedTotalRes['issued'] ?? 0);

                    // New values based on cumulative issued quantity
                    $newAmount = round($perUnitAmount * $totalIssuedAll, 2);
                    $newDeposit = round($perUnitDeposit * $totalIssuedAll, 2);
                    $newTotalRent = round($perUnitTotalRent * $totalIssuedAll, 2);
                    $newPendingQty = max(0, $totalIssuedAll - $totalReturnedQty);

                    // Adjust rented_qty by the delta between previous stored qty and cumulative issued
                    $previousQty = (float)$rentItemRes['quantity'];
                    $deptId = (int)$rentItemRes['department_id'];
                    $qtyDiff = $totalIssuedAll - $previousQty;
                    if ($qtyDiff !== 0 && !empty($deptId)) {
                        $restockSql = "UPDATE sub_equipment 
                                       SET rented_qty = GREATEST(0, rented_qty + ($qtyDiff)) 
                                       WHERE equipment_id = $eqId AND department_id = $deptId";
                        $db->readQuery($restockSql);
                    }

                    $updateSql = "UPDATE equipment_rent_items 
                                  SET quantity = $totalIssuedAll, 
                                      amount = $newAmount, 
                                      deposit_amount = $newDeposit, 
                                      total_rent_amount = $newTotalRent,
                                      pending_qty = $newPendingQty
                                  WHERE id = " . (int)$rentItemRes['id'];
                    $db->readQuery($updateSql);
                }
            }
        }

        // 4. Update Invoice Issuing Status
        
        // Calculate totals for the whole invoice
        // Use billed quantities (bill_qty) to preserve original order totals
        $totalOrderedSql = "SELECT SUM(bill_qty) as total FROM equipment_rent_items WHERE rent_id = $rentId";
        $totalIssuedSql = "SELECT SUM(ini.issued_quantity) as total 
                           FROM issue_note_items ini 
                           INNER JOIN issue_notes n ON ini.issue_note_id = n.id 
                           WHERE n.rent_invoice_id = $rentId
                           AND n.issue_status != 'cancelled'";
                           
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
                            AND note.issue_status != 'cancelled'
                            GROUP BY ini.equipment_id, ini.sub_equipment_id";
            $issuedResult = $db->readQuery($issuedQuery);
            $issuedMap = [];
            while ($row = mysqli_fetch_assoc($issuedResult)) {
                $key = $row['equipment_id'] . '_' . ($row['sub_equipment_id'] ?? 'NULL');
                $issuedMap[$key] = (float)$row['total_issued'];
            }

            // Get Issue History
            $historyQuery = "SELECT n.id, n.issue_note_code, n.issue_date, n.issue_status, n.created_at,
                             (SELECT SUM(issued_quantity) FROM issue_note_items WHERE issue_note_id = n.id) as total_qty,
                             (SELECT GROUP_CONCAT(CONCAT(e.item_name, ' - ', ini.issued_quantity) SEPARATOR '<br>') 
                              FROM issue_note_items ini 
                              JOIN equipment e ON ini.equipment_id = e.id 
                              WHERE ini.issue_note_id = n.id) as items_summary
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
                $orderedQty = (float)$item['bill_qty']; // Use billed quantity as ordered
                $remainingQty = max(0, $orderedQty - $alreadyIssued);

                // Calculate Return Date
                $rentalDate = $item['rental_date'];
                $duration = (float)$item['duration'];
                $rentType = $item['rent_type'];
                
                $unit = ($rentType === 'month') ? 'months' : 'days';
                $calculatedReturnDate = date('Y-m-d', strtotime($rentalDate . " + $duration $unit"));

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
                    'return_date' => $calculatedReturnDate
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
    // Return a simple incremental code without the "IN/8/0" style prefix
    $newCode = ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit();
}

// Cancel Issue Note
if (isset($_POST['action']) && $_POST['action'] === 'cancel_note') {
    $note_id = $_POST['note_id'] ?? 0;
    $db = Database::getInstance();

    if ($note_id) {
        $NOTE = new IssueNote($note_id);
        if ($NOTE->id) {
            if ($NOTE->issue_status === 'cancelled') {
                echo json_encode(["status" => "error", "message" => "This Issue Note is already cancelled"]);
                exit();
            }
            $NOTE->issue_status = 'cancelled';
            if ($NOTE->update()) {
                // Restore rent item qty to bill_qty for no-sub-equipment items
                $rentId = (int)$NOTE->rent_invoice_id;
                
                // Get cancelled note's items
                $cancelledItemsSql = "SELECT equipment_id, sub_equipment_id, issued_quantity 
                                      FROM issue_note_items WHERE issue_note_id = " . (int)$note_id;
                $cancelledItemsRes = $db->readQuery($cancelledItemsSql);
                while ($cItem = mysqli_fetch_assoc($cancelledItemsRes)) {
                    if (empty($cItem['sub_equipment_id'])) {
                        $cEqId = (int)$cItem['equipment_id'];
                        // Restore quantity and amount to bill_qty values
                        $restoreSql = "UPDATE equipment_rent_items 
                                       SET quantity = bill_qty, 
                                           amount = CASE WHEN bill_qty > 0 THEN ROUND((amount / GREATEST(quantity, 1)) * bill_qty, 2) ELSE amount END,
                                           deposit_amount = CASE WHEN bill_qty > 0 THEN ROUND((deposit_amount / GREATEST(quantity, 1)) * bill_qty, 2) ELSE deposit_amount END,
                                           total_rent_amount = CASE WHEN bill_qty > 0 THEN ROUND((total_rent_amount / GREATEST(quantity, 1)) * bill_qty, 2) ELSE total_rent_amount END,
                                           pending_qty = bill_qty - COALESCE(total_returned_qty, 0)
                                       WHERE rent_id = $rentId 
                                       AND equipment_id = $cEqId 
                                       AND sub_equipment_id IS NULL";
                        $db->readQuery($restoreSql);
                    }
                }

                // Recalculate Invoice Issuing Status
                
                $totalOrderedSql = "SELECT SUM(quantity) as total FROM equipment_rent_items WHERE rent_id = $rentId";
                $totalIssuedSql = "SELECT SUM(ini.issued_quantity) as total 
                                   FROM issue_note_items ini 
                                   INNER JOIN issue_notes n ON ini.issue_note_id = n.id 
                                   WHERE n.rent_invoice_id = $rentId
                                   AND n.issue_status != 'cancelled'";
                                   
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

                // Audit Log
                $AUDIT_LOG = new AuditLog(null);
                $AUDIT_LOG->ref_id = $NOTE->id;
                $AUDIT_LOG->ref_code = $NOTE->issue_note_code;
                $AUDIT_LOG->action = 'CANCEL';
                $AUDIT_LOG->description = 'CANCELLED ISSUE NOTE #' . $NOTE->issue_note_code;
                $AUDIT_LOG->user_id = $_SESSION['id'] ?? 0;
                $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
                $AUDIT_LOG->create();

                echo json_encode(["status" => "success", "message" => "Issue Note cancelled successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update status"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Issue Note not found"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Issue Note ID required"]);
    }
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
                "department_id" => $ISSUE_NOTE->department_id,
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
