<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$FUEL = new VehicleFuel();
$VEHICLE = new Vehicle();

$db = Database::getInstance();
$eff_page_res = $db->readQuery("SELECT id FROM `pages` WHERE `page_url` = 'fuel-efficiency-report.php' LIMIT 1");
$eff_page_id = 0;
if ($eff_page_res && mysqli_num_rows($eff_page_res) > 0) {
    $eff_page_id = mysqli_fetch_assoc($eff_page_res)['id'];
}
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Fuel Management | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <style>
        .fuel-card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .fuel-card:hover {
            transform: translateY(-5px);
        }
        .table-premium thead {
            background: linear-gradient(135deg, #6259ca 0%, #a4508b 100%);
            color: white;
        }
        .btn-gradient-primary {
            background: linear-gradient(135deg, #6259ca 0%, #a4508b 100%);
            border: none;
            color: white;
        }
        .btn-gradient-primary:hover {
            opacity: 0.9;
            color: white;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Page Title & Breadcrumb -->
                    <div class="row mb-4">
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                             <h4 class="mb-0 font-size-18">Fuel Management</h4>
                             <a href="fuel-efficiency-report.php?page_id=<?php echo $eff_page_id; ?>" class="btn btn-soft-info ms-2">
                                <i class="uil uil-chart-growth me-1"></i> Efficiency Report
                             </a>
                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Fuel Management</li>
                            </ol>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Entry form -->
                        <div class="col-lg-4">
                            <div class="card fuel-card">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="avatar-xs me-3">
                                            <div class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                <i class="uil uil-pump"></i>
                                            </div>
                                        </div>
                                        <h5 class="font-size-16 mb-0">Add Fuel Record</h5>
                                    </div>

                                    <form id="form-data" autocomplete="off">
                                        <input type="hidden" id="id" name="id" value="0">
                                        
                                        <div class="mb-3">
                                            <label for="vehicle_no_display" class="form-label">Select Vehicle</label>
                                            <div class="input-group">
                                                <input type="text" id="vehicle_no_display" class="form-control" placeholder="Select Vehicle" readonly>
                                                <input type="hidden" id="vehicle_id" name="vehicle_id">
                                                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#vehicleModel">
                                                    <i class="uil uil-search"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="fuel_amount" class="form-label">Fuel Amount (Total Cost)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs.</span>
                                                <input id="fuel_amount" name="fuel_amount" type="number" step="0.01" class="form-control" placeholder="0.00">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="liters" class="form-label">Liters (Volume)</label>
                                            <div class="input-group">
                                                <input id="liters" name="liters" type="number" step="0.01" class="form-control" placeholder="0.00">
                                                <span class="input-group-text">L</span>
                                            </div>
                                        </div>



                                        <div class="mb-4">
                                            <label for="date" class="form-label">Date</label>
                                            <input id="date" name="date" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-gradient-primary btn-lg" id="create">
                                                <i class="uil uil-plus me-1"></i> Add Fuel Record
                                            </button>
                                            <button type="button" class="btn btn-warning btn-lg" id="update" style="display:none;">
                                                <i class="uil uil-edit me-1"></i> Update Record
                                            </button>
                                            <button type="button" class="btn btn-light" id="new">
                                                <i class="uil uil-refresh me-1"></i> New / Clear
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="col-lg-8">
                            <div class="card fuel-card">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="avatar-xs me-3">
                                            <div class="avatar-title rounded-circle bg-soft-success text-success">
                                                <i class="uil uil-list-ul"></i>
                                            </div>
                                        </div>
                                        <h5 class="font-size-16 mb-0">Recent Fuel History</h5>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-premium table-hover align-middle mb-0 datatable">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Vehicle No</th>
                                                    <th>Amount (Rs.)</th>
                                                    <th>Liters (L)</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $records = $FUEL->all();
                                                foreach ($records as $row) {
                                                ?>
                                                    <tr>
                                                        <td><?php echo $row['date']; ?></td>
                                                        <td>
                                                            <span class="fw-bold text-primary"><?php echo $row['vehicle_no']; ?></span>
                                                        </td>
                                                        <td><?php echo number_format($row['fuel_amount'], 2); ?></td>
                                                        <td><?php echo number_format($row['liters'], 2); ?></td>
                                                        <td class="text-center">
                                                            <div class="btn-group">
                                                                <button class="btn btn-sm btn-soft-info edit-fuel" 
                                                                    data-id="<?php echo $row['id']; ?>"
                                                                    data-vehicle_id="<?php echo $row['vehicle_id']; ?>"
                                                                    data-vehicle_no="<?php echo $row['vehicle_no']; ?>"
                                                                    data-fuel_amount="<?php echo $row['fuel_amount']; ?>"
                                                                    data-liters="<?php echo $row['liters']; ?>"
                                                                    data-date="<?php echo $row['date']; ?>"
                                                                >
                                                                    <i class="uil uil-pen"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-soft-danger delete-fuel" data-id="<?php echo $row['id']; ?>">
                                                                    <i class="uil uil-trash-alt"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>  
            <?php include 'footer.php' ?>
        </div> 
    </div>

    <!-- Vehicle Selection Modal -->
    <div class="modal fade" id="vehicleModel" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered datatable w-100">
                        <thead>
                            <tr>
                                <th>Vehicle No</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($VEHICLE->all() as $vehicle) {
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vehicle['vehicle_no']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['brand']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary select-vehicle" 
                                            data-id="<?php echo $vehicle['id']; ?>"
                                            data-vehicle_no="<?php echo htmlspecialchars($vehicle['vehicle_no']); ?>">
                                            Select
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="ajax/js/vehicle-fuel.js"></script>
    <?php include 'main-js.php' ?>

</body>
</html>
