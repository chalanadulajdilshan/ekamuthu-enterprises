<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

if (isset($_POST['action']) && $_POST['action'] === 'get_stock_by_date') {
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';

    if (empty($date)) {
        echo json_encode(['status' => 'error', 'message' => 'Date is required']);
        exit;
    }

    try {
        $db = Database::getInstance();

        // Get all items that have transactions up to the date
        $itemsQuery = "
            SELECT DISTINCT im.id, im.name
            FROM item_master im
            JOIN stock_transaction st ON st.item_id = im.id AND st.date <= '{$date} 23:59:59'
            ORDER BY im.name ASC
        ";
        $itemsResult = $db->readQuery($itemsQuery);

        $data = [];
        while ($itemRow = mysqli_fetch_assoc($itemsResult)) {
            $itemId = $itemRow['id'];
            $itemName = $itemRow['name'];

            // Get all transactions for this item up to the date, ordered by date
            $transactionsQuery = "
                SELECT qty_in, qty_out, date
                FROM stock_transaction
                WHERE item_id = {$itemId} AND date <= '{$date} 23:59:59'
                ORDER BY date ASC, id ASC
            ";
            $transactionsResult = $db->readQuery($transactionsQuery);

            $balance = 0;
            while ($tx = mysqli_fetch_assoc($transactionsResult)) {
                $balance += (float)$tx['qty_in'] - (float)$tx['qty_out'];
            }

            if ($balance > 0) {
                $data[] = [
                    'item_name' => $itemName,
                    'quantity' => $balance
                ];
            }
        }

        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
exit;
?>
