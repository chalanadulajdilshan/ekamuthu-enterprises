<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action == 'save') {
    $db = Database::getInstance();
    
    // Decode items
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    if (empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Please include at least one item.']);
        exit;
    }

    $gatepass = new Gatepass();
    $gatepass->gatepass_code = $_POST['gatepass_code'];
    $gatepass->invoice_id = $_POST['invoice_id'];
    $gatepass->name = $_POST['name'];
    $gatepass->address = $_POST['address'];
    $gatepass->id_number = $_POST['id_number'];
    
    // Summary for equipment_type (backward compatibility)
    $item_summaries = [];
    foreach($items as $item) {
        $item_summaries[] = $item['equipment_name'] . ' (' . $item['quantity'] . ')';
    }
    $gatepass->equipment_type = implode(", ", $item_summaries);
    $gatepass->serial_no = ''; // Now tracked per item if needed, leaving blank for main table
    
    $gatepass->issued_by = $_POST['issued_by'];
    $gatepass->gatepass_date = $_POST['gatepass_date'];

    $gp_id = $gatepass->create();

    if ($gp_id) {
        // Save items
        foreach ($items as $item) {
            $GP_ITEM = new GatepassItem(null);
            $GP_ITEM->gatepass_id = $gp_id;
            $GP_ITEM->equipment_id = $item['equipment_id'];
            $GP_ITEM->sub_equipment_id = $item['sub_equipment_id'] ?? null;
            $GP_ITEM->quantity = $item['quantity'];
            $GP_ITEM->remarks = $item['remarks'] ?? '';
            $GP_ITEM->create();
        }

        // Increment the document tracker
        (new DocumentTracking(null))->incrementDocumentId('gatepass');
        
        echo json_encode(['status' => 'success', 'message' => 'Gate pass saved successfully', 'id' => $gp_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save gate pass']);
    }
    exit;
} elseif ($action == 'get_invoice_details') {
    $invoice_id = $_POST['invoice_id'] ?? 0;

    if ($invoice_id) {
        $RENT = new EquipmentRent($invoice_id);
        if ($RENT->id) {
            $RENT_ITEMS = new EquipmentRentItem(null);
            $items = $RENT_ITEMS->getByRentId($RENT->id);
            
            // Fetch previously issued quantities for this invoice (via Gatepass)
            $db = Database::getInstance();
            $issuedQuery = "SELECT gi.equipment_id, gi.sub_equipment_id, SUM(gi.quantity) as total_issued
                            FROM gatepass_items gi
                            INNER JOIN gatepass g ON gi.gatepass_id = g.id
                            WHERE g.invoice_id = " . (int)$invoice_id . "
                            GROUP BY gi.equipment_id, gi.sub_equipment_id";
            $issuedResult = $db->readQuery($issuedQuery);
            $issuedMap = [];
            while ($row = mysqli_fetch_assoc($issuedResult)) {
                $key = $row['equipment_id'] . '_' . ($row['sub_equipment_id'] ?? 'NULL');
                $issuedMap[$key] = (float)$row['total_issued'];
            }

            $formattedItems = [];
            foreach ($items as $item) {
                $key = $item['equipment_id'] . '_' . ($item['sub_equipment_id'] ? $item['sub_equipment_id'] : 'NULL');
                $alreadyIssued = $issuedMap[$key] ?? 0;
                $orderedQty = (float)$item['quantity'];
                $remainingQty = max(0, $orderedQty - $alreadyIssued);

                $formattedItems[] = [
                    'equipment_id' => $item['equipment_id'],
                    'sub_equipment_id' => $item['sub_equipment_id'],
                    'equipment_name' => $item['equipment_name'],
                    'sub_equipment_code' => $item['sub_equipment_code'],
                    'quantity' => $orderedQty,
                    'already_issued' => $alreadyIssued,
                    'remaining_quantity' => $remainingQty,
                    'rent_type' => $item['rent_type']
                ];
            }

            $CUSTOMER = new CustomerMaster($RENT->customer_id);

            echo json_encode([
                "status" => "success",
                "invoice" => [
                    "id" => $RENT->id,
                    "bill_number" => $RENT->bill_number,
                    "customer_id" => $RENT->customer_id,
                    "customer_name" => $CUSTOMER->name,
                    "customer_address" => $CUSTOMER->address,
                    "customer_nic" => $CUSTOMER->nic
                ],
                "items" => $formattedItems
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invoice not found"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invoice ID required"]);
    }
    exit;
} elseif ($action == 'get_new_code') {
    $DOCUMENT_TRACKING = new DocumentTracking(1);
    $lastId = $DOCUMENT_TRACKING->gatepass_id ?? 0;
    $newCode = 'GP/' . ($_SESSION['id'] ?? '0') . '/0' . ($lastId + 1);

    echo json_encode([
        "status" => "success",
        "code" => $newCode
    ]);
    exit;
} elseif ($action == 'delete') {
    $id = $_POST['id'];
    $gatepass = new Gatepass($id);

    if ($gatepass->delete()) {
        // Also delete items
        $GP_ITEM = new GatepassItem(null);
        $GP_ITEM->deleteByGatepassId($id);
        echo json_encode(['status' => 'success', 'message' => 'Gate pass deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete gate pass']);
    }
    exit;
} elseif ($action == 'filter') {
    $searchTerm = $_POST['search'] ?? '';
    $gatepass = new Gatepass();
    $list = $gatepass->filter($searchTerm);
    echo json_encode(['status' => 'success', 'data' => $list]);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}
