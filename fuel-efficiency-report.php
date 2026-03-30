<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$VEHICLE = new Vehicle();
$vehicle_id = isset($_GET['vehicle_id']) ? $_GET['vehicle_id'] : '';
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

$db = Database::getInstance();
$report_data = [];
$total_trip_km = 0;
$total_fuel_liters = 0;

if ($vehicle_id) {
    // 1. Calculate Total Distance from trip_management
    $trip_query = "SELECT SUM(end_meter - start_meter) as total_distance 
                   FROM `trip_management` 
                   WHERE `vehicle_id` = $vehicle_id 
                   AND `created_at` BETWEEN '$from 00:00:00' AND '$to 23:59:59'";
    $trip_res = $db->readQuery($trip_query);
    $trip_data = mysqli_fetch_assoc($trip_res);
    $total_trip_km = (float)$trip_data['total_distance'];

    // 2. Fetch fuel records for detailed table
    $fuel_query = "SELECT * FROM `vehicle_fuel` 
                   WHERE `vehicle_id` = $vehicle_id 
                   AND `date` BETWEEN '$from' AND '$to' 
                   ORDER BY `date` ASC";
    $fuel_result = $db->readQuery($fuel_query);
    
    while ($row = mysqli_fetch_assoc($fuel_result)) {
        $total_fuel_liters += (float)$row['liters'];
        $report_data[] = $row;
    }
}

// Get vehicle list for filter
$vehicles = $VEHICLE->all();
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Fuel Efficiency Report | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'main-css.php' ?>
    <style>
        .print-header { display: none; }
        @media print {
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
            .print-header h2 { margin: 0; font-size: 24px; color: #000; font-weight: bold; }
            .print-header p { margin: 5px 0; font-size: 16px; color: #333; }
            
            .d-print-none, .breadcrumb, header, footer, .alert { display: none !important; }
            
            body { background: #fff !important; margin: 0; padding: 0; font-size: 12px; }
            .page-content { padding: 0 !important; margin: 0 !important; }
            .container-fluid { padding: 0 !important; max-width: 100% !important; }
            
            .card { border: none !important; box-shadow: none !important; margin-bottom: 10px !important; }
            .card-body { padding: 0 !important; }
            
            .stat-box { 
                border: 1px solid #ddd !important; 
                padding: 15px !important; 
                background: #f8f9fa !important; 
                border-radius: 8px !important; 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .stat-box h6 { font-size: 12px !important; color: #555 !important; }
            .stat-box h3, .stat-box h2 { font-size: 18px !important; color: #000 !important; margin: 0 !important; }
            
            .row:not(.d-print-none) { display: flex !important; flex-wrap: wrap !important; }
            .col-print-4 { flex: 0 0 33.333333% !important; max-width: 33.333333% !important; padding: 0 5px !important; }
            .col-print-6 { flex: 0 0 50% !important; max-width: 50% !important; padding: 0 10px !important; }
            .col-print-12 { flex: 0 0 100% !important; max-width: 100% !important; }
            
            .table { width: 100% !important; border-collapse: collapse !important; border: 1px solid #ddd !important; }
            .table th, .table td { border: 1px solid #ddd !important; padding: 6px !important; font-size: 11px !important; }
            .table-light th { background-color: #f1f1f1 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            
            h5.card-title { font-size: 14px !important; margin-bottom: 10px !important; color: #000 !important; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        }
        
        .efficiency-card {
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .stat-box {
            padding: 20px;
            border-radius: 12px;
            background: #f8f9fa;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored">

    <div id="layout-wrapper">
        <?php include 'navigation.php' ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    
                    <!-- Filters -->
                    <div class="row mb-4 d-print-none">
                        <div class="col-12">
                            <div class="card efficiency-card">
                                <div class="card-body">
                                    <form method="GET" class="row align-items-end">
                                        <input type="hidden" name="page_id" value="<?php echo $_GET['page_id']; ?>">

                                        <div class="col-md-3">
                                            <label class="form-label">Vehicle</label>
                                            <select name="vehicle_id" class="form-select select2" required>
                                                <option value="">Select Vehicle</option>
                                                <?php foreach($vehicles as $v): ?>
                                                    <option value="<?php echo $v['id']; ?>" <?php echo ($vehicle_id == $v['id']) ? 'selected' : ''; ?>>
                                                        <?php echo $v['vehicle_no']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">From</label>
                                            <input type="date" name="from" class="form-control" value="<?php echo $from; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">To</label>
                                            <input type="date" name="to" class="form-control" value="<?php echo $to; ?>">
                                        </div>
                                        <div class="col-md-3 d-flex gap-2">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="uil uil-filter"></i> Generate
                                            </button>
                                            <button type="button" class="btn btn-success" onclick="window.print()">
                                                <i class="uil uil-print"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($vehicle_id): 
                        // Find vehicle no for header
                        $vehicle_no_display = '';
                        foreach($vehicles as $v) {
                            if ($v['id'] == $vehicle_id) {
                                $vehicle_no_display = $v['vehicle_no'];
                                break;
                            }
                        }
                    ?>
                    
                    <!-- Print Header -->
                    <div class="print-header">
                        <h2><?php echo $COMPANY_PROFILE_DETAILS->name; ?></h2>
                        <p><strong>Fuel Efficiency Report</strong></p>
                        <p>Vehicle: <strong><?php echo $vehicle_no_display; ?></strong> &nbsp;|&nbsp; Period: <strong><?php echo date('Y-m-d', strtotime($from)); ?></strong> to <strong><?php echo date('Y-m-d', strtotime($to)); ?></strong></p>
                    </div>

                    <!-- Summary Stats -->
                    <div class="row mb-4">
                        <?php
                        $avg_kmpl = ($total_fuel_liters > 0) ? ($total_trip_km / $total_fuel_liters) : 0;
                        ?>
                        <div class="col-md-4 col-print-4 mb-3">
                            <div class="stat-box text-center shadow-sm">
                                <h6 class="text-muted mb-2">Total Distance (from Trips)</h6>
                                <h3 class="mb-0 text-primary"><?php echo number_format($total_trip_km, 2); ?> KM</h3>
                            </div>
                        </div>
                        <div class="col-md-4 col-print-4 mb-3">
                            <div class="stat-box text-center shadow-sm">
                                <h6 class="text-muted mb-2">Total Fuel Added</h6>
                                <h3 class="mb-0 text-success"><?php echo number_format($total_fuel_liters, 2); ?> L</h3>
                            </div>
                        </div>
                        <div class="col-md-4 col-print-4 mb-3">
                            <div class="stat-box text-center bg-soft-info border-info border shadow-sm">
                                <h6 class="text-info mb-2 font-weight-bold">Combined Efficiency</h6>
                                <h2 class="text-info mb-0"><?php echo number_format($avg_kmpl, 2); ?> <small>KM/L</small></h2>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="alert alert-info border-0 shadow-sm" role="alert">
                                <i class="uil uil-info-circle me-2"></i>
                                This efficiency calculation is based on the <strong>total distance recorded in Trip Management</strong> divided by the <strong>total fuel entered</strong> during the selected period.
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Table -->
                    <div class="row">
                        <!-- Fuel Details -->
                        <div class="col-lg-6 col-print-6 mb-4">
                            <div class="card efficiency-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title mb-4"><i class="uil uil-pump text-primary me-2"></i>Fuel Logs</h5>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Fuel (L)</th>
                                                    <th>Cost (Rs.)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($report_data)): ?>
                                                    <tr><td colspan="3" class="text-center">No fuel records found</td></tr>
                                                <?php else:
                                                    foreach($report_data as $row): ?>
                                                    <tr>
                                                        <td><?php echo $row['date']; ?></td>
                                                        <td><?php echo number_format($row['liters'], 2); ?></td>
                                                        <td><?php echo number_format($row['fuel_amount'], 2); ?></td>
                                                    </tr>
                                                    <?php endforeach; 
                                                endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Trip Details Summary -->
                        <div class="col-lg-6 col-print-6 mb-4">
                            <div class="card efficiency-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title mb-4"><i class="uil uil-map-marker text-success me-2"></i>Trip Distance Summary</h5>
                                    <?php
                                    $trip_details_query = "SELECT trip_number, start_location, end_location, (end_meter - start_meter) as distance, created_at
                                                           FROM `trip_management` 
                                                           WHERE `vehicle_id` = $vehicle_id 
                                                           AND `created_at` BETWEEN '$from 00:00:00' AND '$to 23:59:59'
                                                           ORDER BY `created_at` ASC";
                                    $trip_details_res = $db->readQuery($trip_details_query);
                                    ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Trip No</th>
                                                    <th>From - To</th>
                                                    <th class="text-end">KM</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $trip_count = 0;
                                                while($trip = mysqli_fetch_assoc($trip_details_res)): 
                                                    $trip_count++;
                                                ?>
                                                <tr>
                                                    <td><small><?php echo $trip['trip_number']; ?></small></td>
                                                    <td><small><?php echo $trip['start_location'] . ' to ' . $trip['end_location']; ?></small></td>
                                                    <td class="text-end fw-bold"><?php echo number_format($trip['distance'], 2); ?></td>
                                                </tr>
                                                <?php endwhile; 
                                                if ($trip_count == 0): ?>
                                                    <tr><td colspan="3" class="text-center">No trip records found</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <img src="assets/images/coming-soon.png" alt="" height="120" class="mb-4">
                        <h4>Select a vehicle and date range to generate the report</h4>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php include 'footer.php' ?>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <?php include 'main-js.php' ?>

</body>

</html>
