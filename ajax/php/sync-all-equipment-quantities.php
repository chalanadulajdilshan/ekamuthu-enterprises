<?php
// One-time script to sync all equipment quantities with their department stock totals

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

if (isset($_POST['sync_all'])) {
    $db = Database::getInstance();
    
    // Get all unique equipment IDs from sub_equipment table
    $query = "SELECT DISTINCT equipment_id FROM sub_equipment WHERE equipment_id IS NOT NULL AND equipment_id != ''";
    $result = $db->readQuery($query);
    
    $synced = 0;
    $errors = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $equipment_id = $row['equipment_id'];
        
        if (SubEquipment::syncEquipmentQuantity($equipment_id)) {
            $synced++;
        } else {
            $errors[] = "Failed to sync equipment ID: $equipment_id";
        }
    }
    
    echo json_encode([
        "status" => "success",
        "synced" => $synced,
        "errors" => $errors,
        "message" => "Synced $synced equipment records"
    ]);
    exit();
}

// Get sync preview - show what will be updated
if (isset($_POST['preview'])) {
    $db = Database::getInstance();
    
    $query = "SELECT 
                e.id,
                e.code,
                e.item_name,
                e.quantity as current_qty,
                COALESCE(SUM(se.qty), 0) as calculated_qty,
                (COALESCE(SUM(se.qty), 0) - e.quantity) as difference
              FROM equipment e
              LEFT JOIN sub_equipment se ON e.id = se.equipment_id
              GROUP BY e.id, e.code, e.item_name, e.quantity
              HAVING difference != 0
              ORDER BY e.code";
    
    $result = $db->readQuery($query);
    
    $preview = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $preview[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $preview,
        "count" => count($preview)
    ]);
    exit();
}
