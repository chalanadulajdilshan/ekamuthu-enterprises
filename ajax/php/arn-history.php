<?php
include("../../class/include.php");
require_once("../../class/ArnMaster.php");
require_once("../../class/CustomerMaster.php");
require_once("../../class/DepartmentMaster.php");
require_once("../../class/PaymentType.php");

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['command'])) {
    $command = $_POST['command'];

    // Get returns for a specific ARN
    if ($command == 'get_arn_returns') {
        $arn_id = isset($_POST['arn_id']) ? (int)$_POST['arn_id'] : 0;
        
        if ($arn_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ARN ID']);
            exit;
        }
        
        // Get purchase returns for this ARN
        $query = "SELECT pr.*, dm.name as department_name 
                  FROM purchase_return pr 
                  LEFT JOIN department_master dm ON pr.department_id = dm.id 
                  WHERE pr.arn_id = $arn_id 
                  ORDER BY pr.return_date DESC, pr.id DESC";
        $result = $db->readQuery($query);
        
        $returns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Get return items for each return
            $items_query = "SELECT pri.*, im.code as item_code, im.name as item_name 
                            FROM purchase_return_items pri 
                            LEFT JOIN item_master im ON pri.item_id = im.id 
                            WHERE pri.return_id = " . (int)$row['id'];
            $items_result = $db->readQuery($items_query);
            
            $items = [];
            while ($item = mysqli_fetch_assoc($items_result)) {
                $items[] = $item;
            }
            
            $row['items'] = $items;
            $returns[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'returns' => $returns]);
        exit;
    }

    if ($command == 'filter_arn_history') {
        $date_from = isset($_POST['date_from']) ? $_POST['date_from'] : '';
        $date_to = isset($_POST['date_to']) ? $_POST['date_to'] : '';
        $supplier = isset($_POST['supplier']) ? (int)$_POST['supplier'] : 0;
        $payment_type = isset($_POST['payment_type']) ? $_POST['payment_type'] : '';
        $grn_id = isset($_POST['grn_id']) ? trim($_POST['grn_id']) : '';

        $query = "SELECT * FROM `arn_master` WHERE 1=1";

        if (!empty($date_from)) {
            $query .= " AND `invoice_date` >= '" . $db->escapeString($date_from) . "'";
        }
        if (!empty($date_to)) {
            $query .= " AND `invoice_date` <= '" . $db->escapeString($date_to) . "'";
        }
        if ($supplier > 0) {
            $query .= " AND `supplier_id` = " . $supplier;
        }
        if (!empty($payment_type)) {
            $query .= " AND `purchase_type` = '" . $db->escapeString($payment_type) . "'";
        }
        if (!empty($grn_id)) {
            $query .= " AND `arn_no` LIKE '%" . $db->escapeString($grn_id) . "%'";
        }

        $query .= " ORDER BY `id` DESC";

        $result = $db->readQuery($query);
        $output = '';

        if ($result && mysqli_num_rows($result) > 0) {
            $key = 0;
            while ($arn = mysqli_fetch_assoc($result)) {
                $key++;
                $CUSTOMER_MASTER = new CustomerMaster($arn['supplier_id']);
                $PAYMENT_TYPE = new PaymentType($arn['purchase_type']);
                $is_cancelled = isset($arn['is_cancelled']) && $arn['is_cancelled'] == 1;
                $rowClass = $is_cancelled ? 'table-danger' : '';

                $output .= '<tr class="' . $rowClass . '">';
                $output .= '<td>' . $key . '</td>';
                $output .= '<td>' . htmlspecialchars($arn['arn_no']) . '</td>';
                $output .= '<td>' . htmlspecialchars($arn['invoice_date']) . '</td>';
                $output .= '<td>' . htmlspecialchars($CUSTOMER_MASTER->code . ' - ' . $CUSTOMER_MASTER->name) . '</td>';
                $output .= '<td>' . htmlspecialchars($PAYMENT_TYPE->name ?? '') . '</td>';
                $output .= '<td class="text-end">' . number_format($arn['total_arn_value'], 2) . '</td>';
                $output .= '<td class="text-end">' . number_format($arn['paid_amount'], 2) . '</td>';
                $output .= '<td class="text-end">' . number_format($arn['total_arn_value'] - $arn['paid_amount'], 2) . '</td>';
                $output .= '<td>';
                if ($is_cancelled) {
                    $output .= '<span class="badge bg-danger">Cancelled</span>';
                } else {
                    $output .= '<span class="badge bg-success">Active</span>';
                }
                $output .= '</td>';
                
                // Check for returns
                $return_count_query = "SELECT COUNT(*) as count FROM purchase_return WHERE arn_id = " . (int)$arn['id'];
                $return_count_result = mysqli_fetch_assoc($db->readQuery($return_count_query));
                $has_returns = $return_count_result['count'] > 0;
                
                $output .= '<td class="text-center">';
                if ($has_returns) {
                    $output .= '<button class="btn btn-sm btn-outline-warning toggle-returns" data-arn-id="' . $arn['id'] . '" title="View Returns">';
                    $output .= '<i class="fas fa-undo-alt"></i> ';
                    $output .= '<span class="badge bg-warning text-dark">' . $return_count_result['count'] . '</span>';
                    $output .= '</button>';
                } else {
                    $output .= '<span class="text-muted">-</span>';
                }
                $output .= '</td>';
                $output .= '</tr>';
            }
        } else {
            $output = '<tr><td colspan="10" class="text-center">No records found</td></tr>';
        }

        echo $output;
    }
}
?>
