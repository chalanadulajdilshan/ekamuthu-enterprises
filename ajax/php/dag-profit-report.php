<?php
include '../../class/include.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_dag_profit_report':
            getDagProfitReport();
            break;
        case 'export_dag_profit_report':
            exportDagProfitReport();
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
}

function getDagProfitReport() {
    try {
        $from_date = $_POST['from_date'] ?? '';
        $to_date = $_POST['to_date'] ?? '';
        $customer_id = $_POST['customer_id'] ?? '';
        $company_id = $_POST['company_id'] ?? '';
        $serial_number = $_POST['serial_number'] ?? '';
        $job_number = $_POST['job_number'] ?? '';

        $db = Database::getInstance();
        
        // First check if there are any dag_item records
        $checkQuery = "SELECT COUNT(*) as total_records FROM dag_item WHERE serial_number IS NOT NULL AND serial_number != ''";
        $checkResult = mysqli_fetch_assoc($db->readQuery($checkQuery));
        
        if ($checkResult['total_records'] == 0) {
            // No dag_item records found
            $response = [
                'status' => 'success',
                'data' => [],
                'totals' => [
                    'total_casing_cost' => '0.00',
                    'total_amount' => '0.00',
                    'total_profit' => '0.00'
                ],
                'record_count' => 0,
                'debug_info' => 'No dag_item records found with valid serial numbers'
            ];
            echo json_encode($response);
            return;
        }
        
        // Build the query with joins to get all required data
        $query = "SELECT 
                    di.serial_number,
                    d.received_date as dag_received_date,
                    COALESCE(cm.name, 'N/A') as customer_name,
                    COALESCE(pcm.name, 'N/A') as previous_customer_name,
                    COALESCE(dc.name, 'N/A') as company_name,
                    di.company_issued_date,
                    di.company_delivery_date,
                    di.job_number,
                    COALESCE(sm.name, 'N/A') as size_name,
                    COALESCE(di.casing_cost, 0) as casing_cost,
                    COALESCE(di.total_amount, 0) as total_amount,
                    (COALESCE(di.total_amount, 0) - COALESCE(di.casing_cost, 0)) as profit,
                    d.ref_no as dag_ref_no,
                    COALESCE(bm.name, 'N/A') as belt_name,
                    COALESCE(br.name, 'N/A') as brand_name
                FROM dag_item di
                LEFT JOIN dag d ON di.dag_id = d.id
                LEFT JOIN customer_master cm ON d.customer_id = cm.id
                LEFT JOIN customer_master pcm ON di.customer_id = pcm.id
                LEFT JOIN dag_company dc ON di.dag_company_id = dc.id
                LEFT JOIN size_master sm ON di.size_id = sm.id
                LEFT JOIN belt_master bm ON di.belt_id = bm.id
                LEFT JOIN brands br ON di.brand_id = br.id
                WHERE di.serial_number IS NOT NULL AND di.serial_number != ''";

        // Add date filter
        if (!empty($from_date) && !empty($to_date)) {
            $query .= " AND d.received_date BETWEEN '$from_date' AND '$to_date'";
        }

        // Add customer filter
        if (!empty($customer_id)) {
            $query .= " AND d.customer_id = '$customer_id'";
        }

        // Add company filter
        if (!empty($company_id)) {
            $query .= " AND di.dag_company_id = '$company_id'";
        }

        // Add serial number filter
        if (!empty($serial_number)) {
            $query .= " AND di.serial_number LIKE '%$serial_number%'";
        }

        // Add job number filter
        if (!empty($job_number)) {
            $query .= " AND di.job_number LIKE '%$job_number%'";
        }

        $query .= " ORDER BY d.received_date DESC, di.serial_number ASC";

        $result = $db->readQuery($query);
        
        $data = [];
        $total_casing_cost = 0;
        $total_amount = 0;
        $total_profit = 0;

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Calculate profit
                $casing_cost = floatval($row['casing_cost'] ?? 0);
                $amount = floatval($row['total_amount'] ?? 0);
                $profit = $amount - $casing_cost;

                $data[] = [
                    'serial_number' => $row['serial_number'] ?? '',
                    'dag_received_date' => $row['dag_received_date'] ?? '',
                    'customer_name' => $row['customer_name'] ?? '',
                    'previous_customer_name' => $row['previous_customer_name'] ?? '',
                    'company_name' => $row['company_name'] ?? '',
                    'company_issued_date' => $row['company_issued_date'] ?? '',
                    'company_delivery_date' => $row['company_delivery_date'] ?? '',
                    'job_number' => $row['job_number'] ?? '',
                    'size_name' => $row['size_name'] ?? '',
                    'casing_cost' => number_format($casing_cost, 2),
                    'total_amount' => number_format($amount, 2),
                    'profit' => number_format($profit, 2),
                    'dag_ref_no' => $row['dag_ref_no'] ?? '',
                    'belt_name' => $row['belt_name'] ?? '',
                    'brand_name' => $row['brand_name'] ?? ''
                ];

                $total_casing_cost += $casing_cost;
                $total_amount += $amount;
                $total_profit += $profit;
            }
        }

        $response = [
            'status' => 'success',
            'data' => $data,
            'totals' => [
                'total_casing_cost' => number_format($total_casing_cost, 2),
                'total_amount' => number_format($total_amount, 2),
                'total_profit' => number_format($total_profit, 2)
            ],
            'record_count' => count($data)
        ];

        echo json_encode($response);

    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error generating report: ' . $e->getMessage()
        ]);
    }
}

function exportDagProfitReport() {
    try {
        $from_date = $_POST['from_date'] ?? '';
        $to_date = $_POST['to_date'] ?? '';
        $customer_id = $_POST['customer_id'] ?? '';
        $company_id = $_POST['company_id'] ?? '';
        $serial_number = $_POST['serial_number'] ?? '';
        $job_number = $_POST['job_number'] ?? '';

        $db = Database::getInstance();
        
        // Same query as above
        $query = "SELECT 
                    di.serial_number,
                    d.received_date as dag_received_date,
                    cm.name as customer_name,
                    pcm.name as previous_customer_name,
                    dc.name as company_name,
                    di.company_issued_date,
                    di.company_delivery_date,
                    di.job_number,
                    sm.name as size_name,
                    di.casing_cost,
                    di.total_amount,
                    (di.total_amount - di.casing_cost) as profit,
                    d.ref_no as dag_ref_no,
                    bm.name as belt_name,
                    br.name as brand_name
                FROM dag_item di
                LEFT JOIN dag d ON di.dag_id = d.id
                LEFT JOIN customer_master cm ON d.customer_id = cm.id
                LEFT JOIN customer_master pcm ON di.customer_id = pcm.id
                LEFT JOIN dag_company dc ON di.dag_company_id = dc.id
                LEFT JOIN size_master sm ON di.size_id = sm.id
                LEFT JOIN belt_master bm ON di.belt_id = bm.id
                LEFT JOIN brands br ON di.brand_id = br.id
                WHERE 1=1";

        // Add filters (same as above)
        if (!empty($from_date) && !empty($to_date)) {
            $query .= " AND d.received_date BETWEEN '$from_date' AND '$to_date'";
        }
        if (!empty($customer_id)) {
            $query .= " AND d.customer_id = '$customer_id'";
        }
        if (!empty($company_id)) {
            $query .= " AND di.dag_company_id = '$company_id'";
        }
        if (!empty($serial_number)) {
            $query .= " AND di.serial_number LIKE '%$serial_number%'";
        }
        if (!empty($job_number)) {
            $query .= " AND di.job_number LIKE '%$job_number%'";
        }

        $query .= " ORDER BY d.received_date DESC, di.serial_number ASC";

        $result = $db->readQuery($query);

        // Set headers for CSV download
        $filename = "dag_profit_report_" . date('Y-m-d_H-i-s') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Create file pointer
        $output = fopen('php://output', 'w');

        // Add CSV headers
        fputcsv($output, [
            'Serial Number',
            'DAG Received Date',
            'Customer Name',
            'Previous Customer',
            'Company',
            'Company Issued Date',
            'Company Delivered Date',
            'Job No',
            'Size',
            'Casing Cost',
            'Total Amount',
            'Profit'
        ]);

        // Add data rows
        $total_casing_cost = 0;
        $total_amount = 0;
        $total_profit = 0;

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $casing_cost = floatval($row['casing_cost'] ?? 0);
                $amount = floatval($row['total_amount'] ?? 0);
                $profit = $amount - $casing_cost;

                fputcsv($output, [
                    $row['serial_number'] ?? '',
                    $row['dag_received_date'] ?? '',
                    $row['customer_name'] ?? '',
                    $row['previous_customer_name'] ?? '',
                    $row['company_name'] ?? '',
                    $row['company_issued_date'] ?? '',
                    $row['company_delivery_date'] ?? '',
                    $row['job_number'] ?? '',
                    $row['size_name'] ?? '',
                    number_format($casing_cost, 2),
                    number_format($amount, 2),
                    number_format($profit, 2)
                ]);

                $total_casing_cost += $casing_cost;
                $total_amount += $amount;
                $total_profit += $profit;
            }
        }

        // Add totals row
        fputcsv($output, [
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'TOTAL:',
            number_format($total_casing_cost, 2),
            number_format($total_amount, 2),
            number_format($total_profit, 2)
        ]);

        fclose($output);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error exporting report: ' . $e->getMessage()
        ]);
    }
}
?>
