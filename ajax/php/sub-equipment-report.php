<?php
include '../../class/include.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'get_report') {
        // Get filter parameters
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        $condition_filter = $_POST['condition_filter'] ?? '';
        $equipment_filter = $_POST['equipment_filter'] ?? '';
        $brand_filter = $_POST['brand_filter'] ?? '';
        $status_filter = $_POST['status_filter'] ?? '';

        try {
            $db = Database::getInstance();
            $conn = $db->DB_CON;

            // Build query with filters
            $sql = "SELECT 
                        se.id,
                        se.code,
                        se.purchase_date,
                        se.value,
                        se.brand,
                        se.company_customer_name,
                        se.condition_type,
                        se.image,
                        se.rental_status,
                        e.code AS equipment_code,
                        e.item_name AS equipment_name,
                        d.name AS department_name
                    FROM sub_equipment se
                    LEFT JOIN equipment e ON se.equipment_id = e.id
                    LEFT JOIN department_master d ON se.department_id = d.id
                    WHERE 1=1";

            $params = [];

            // Date range filter
            if (!empty($date_from)) {
                $sql .= " AND se.purchase_date >= ?";
                $params[] = $date_from;
            }
            if (!empty($date_to)) {
                $sql .= " AND se.purchase_date <= ?";
                $params[] = $date_to;
            }

            // Condition filter
            if (!empty($condition_filter)) {
                $sql .= " AND se.condition_type = ?";
                $params[] = $condition_filter;
            }

            // Equipment filter
            if (!empty($equipment_filter)) {
                $sql .= " AND se.equipment_id = ?";
                $params[] = $equipment_filter;
            }

            // Brand filter
            if (!empty($brand_filter)) {
                $sql .= " AND se.brand LIKE ?";
                $params[] = '%' . $brand_filter . '%';
            }

            // Status filter
            if (!empty($status_filter)) {
                $sql .= " AND se.rental_status = ?";
                $params[] = $status_filter;
            }

            $sql .= " ORDER BY se.purchase_date DESC, se.code ASC";

            $stmt = $conn->prepare($sql);

            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $refs = [];
                foreach ($params as $key => $value) {
                    $refs[$key] = &$params[$key];
                }
                $stmt->bind_param($types, ...$refs);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

            // Calculate statistics
            $total_value = 0;
            $new_count = 0;
            $used_count = 0;

            foreach ($results as $row) {
                $total_value += floatval($row['value']);
                if ($row['condition_type'] === 'new') {
                    $new_count++;
                } elseif ($row['condition_type'] === 'used') {
                    $used_count++;
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $results,
                'statistics' => [
                    'total_value' => number_format($total_value, 2),
                    'new_count' => $new_count,
                    'used_count' => $used_count,
                    'total_count' => count($results)
                ]
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching report data: ' . $e->getMessage()
            ]);
        }
    }

    if ($action === 'export_excel') {
        // Get filter parameters (same as above)
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        $condition_filter = $_POST['condition_filter'] ?? '';
        $equipment_filter = $_POST['equipment_filter'] ?? '';
        $brand_filter = $_POST['brand_filter'] ?? '';
        $status_filter = $_POST['status_filter'] ?? '';

        try {
            $db = Database::getInstance();
            $conn = $db->DB_CON;

            // Build same query as get_report
            $sql = "SELECT 
                        se.code,
                        e.code AS equipment_code,
                        e.item_name AS equipment_name,
                        d.name AS department_name,
                        se.purchase_date,
                        se.value,
                        se.brand,
                        se.company_customer_name,
                        se.condition_type,
                        se.rental_status
                    FROM sub_equipment se
                    LEFT JOIN equipment e ON se.equipment_id = e.id
                    LEFT JOIN department_master d ON se.department_id = d.id
                    WHERE 1=1";

            $params = [];

            if (!empty($date_from)) {
                $sql .= " AND se.purchase_date >= ?";
                $params[] = $date_from;
            }
            if (!empty($date_to)) {
                $sql .= " AND se.purchase_date <= ?";
                $params[] = $date_to;
            }
            if (!empty($condition_filter)) {
                $sql .= " AND se.condition_type = ?";
                $params[] = $condition_filter;
            }
            if (!empty($equipment_filter)) {
                $sql .= " AND se.equipment_id = ?";
                $params[] = $equipment_filter;
            }
            if (!empty($brand_filter)) {
                $sql .= " AND se.brand LIKE ?";
                $params[] = '%' . $brand_filter . '%';
            }
            if (!empty($status_filter)) {
                $sql .= " AND se.rental_status = ?";
                $params[] = $status_filter;
            }

            $sql .= " ORDER BY se.purchase_date DESC, se.code ASC";

            $stmt = $conn->prepare($sql);

            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $refs = [];
                foreach ($params as $key => $value) {
                    $refs[$key] = &$params[$key];
                }
                $stmt->bind_param($types, ...$refs);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sub_equipment_report_' . date('Y-m-d_H-i-s') . '.csv"');

            $output = fopen('php://output', 'w');

            // CSV headers
            fputcsv($output, [
                'Code',
                'Equipment Code',
                'Equipment Name',
                'Department',
                'Purchase Date',
                'Value',
                'Brand',
                'Company/Customer',
                'Condition',
                'Status'
            ]);

            // CSV data
            foreach ($results as $row) {
                fputcsv($output, [
                    $row['code'],
                    $row['equipment_code'],
                    $row['equipment_name'],
                    $row['department_name'],
                    $row['purchase_date'],
                    $row['value'],
                    $row['brand'],
                    $row['company_customer_name'],
                    strtoupper($row['condition_type']),
                    strtoupper($row['rental_status'])
                ]);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error exporting data: ' . $e->getMessage()
            ]);
        }
    }

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
