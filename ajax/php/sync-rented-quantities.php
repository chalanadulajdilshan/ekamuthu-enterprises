<?php
// Script to sync sub_equipment.rented_qty with actual active rental records for bulk items

include dirname(__FILE__) . '/../../class/include.php';
header('Content-Type: application/json; charset=UTF-8');

$db = Database::getInstance();

// 1. Get all bulk sub_equipment records (no_sub_items = 1)
$query = "SELECT se.id, se.equipment_id, se.department_id, se.rented_qty as current_rented_qty, e.item_name
          FROM sub_equipment se
          JOIN equipment e ON se.equipment_id = e.id
          WHERE e.no_sub_items = 1";

$result = $db->readQuery($query);
$updates = [];
$total_updated = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $se_id = $row['id'];
    $equip_id = $row['equipment_id'];
    $dept_id = $row['department_id'];
    
    // 2. Calculate actual rented quantity from rent items
    $rentQuery = "SELECT COALESCE(SUM(eri.quantity - (SELECT COALESCE(SUM(return_qty),0) FROM equipment_rent_returns WHERE rent_item_id = eri.id)), 0) as dynamic_rented_qty
                  FROM equipment_rent_items eri 
                  WHERE eri.equipment_id = $equip_id 
                  AND eri.department_id = $dept_id 
                  AND eri.status = 'rented'
                  AND (eri.sub_equipment_id IS NULL OR eri.sub_equipment_id = 0)";
    
    $rentResult = mysqli_fetch_assoc($db->readQuery($rentQuery));
    $dynamic_rented_qty = (float)$rentResult['dynamic_rented_qty'];
    
    if ($dynamic_rented_qty != (float)$row['current_rented_qty']) {
        // 3. Update if mismatch found
        $updateQuery = "UPDATE sub_equipment SET rented_qty = $dynamic_rented_qty WHERE id = $se_id";
        if ($db->readQuery($updateQuery)) {
            $updates[] = [
                'item' => $row['item_name'],
                'dept_id' => $dept_id,
                'old' => $row['current_rented_qty'],
                'new' => $dynamic_rented_qty
            ];
            $total_updated++;
        }
    }
}

echo json_encode([
    'status' => 'success',
    'total_checked' => mysqli_num_rows($result),
    'total_updated' => $total_updated,
    'updates' => $updates
]);
exit;
