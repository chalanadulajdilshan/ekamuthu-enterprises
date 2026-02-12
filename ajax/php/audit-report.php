<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

if (isset($_POST['action']) && $_POST['action'] == 'get_audit_report') {
    $from_date = $_POST['from_date'] ?? null;
    $to_date = $_POST['to_date'] ?? null;
    $user_id = $_POST['user_id'] ?? null;

    $db = Database::getInstance();
    
    $whereRent = "1=1";
    $whereReturn = "1=1";
    
    if ($from_date && $to_date) {
        $whereRent .= " AND er.rental_date BETWEEN '$from_date' AND '$to_date'";
        $whereReturn .= " AND err.return_date BETWEEN '$from_date' AND '$to_date'";
    }

    if ($user_id) {
        $whereRent .= " AND er.created_by = '" . (int)$user_id . "'";
        $whereReturn .= " AND err.created_by = '" . (int)$user_id . "'";
    }

    $query = "
        SELECT 
            'Rent' as type,
            er.bill_number as bill_no,
            er.rental_date as date,
            cm.name as customer_name,
            u.name as creator_name,
            er.created_at as created_at_time
        FROM `equipment_rent` er
        LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
        LEFT JOIN `user` u ON er.created_by = u.id
        WHERE $whereRent

        UNION ALL

        SELECT 
            'Return' as type,
            er.bill_number as bill_no,
            err.return_date as date,
            cm.name as customer_name,
            u.name as creator_name,
            err.created_at as created_at_time
        FROM `equipment_rent_returns` err
        INNER JOIN `equipment_rent_items` eri ON err.rent_item_id = eri.id
        INNER JOIN `equipment_rent` er ON eri.rent_id = er.id
        LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
        LEFT JOIN `user` u ON err.created_by = u.id
        WHERE $whereReturn
        
        ORDER BY created_at_time DESC
    ";

    $result = $db->readQuery($query);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
    exit();
}
