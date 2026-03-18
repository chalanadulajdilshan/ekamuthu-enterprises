<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$VEHICLE = new Vehicle(null);
$vehicles = $VEHICLE->all();

$VEHICLE_REPAIR = new VehicleRepair(null);
$lastRepairId = $VEHICLE_REPAIR->getLastID();
$nextRef = 'VR/' . $_SESSION['id'] . '/0' . ($lastRepairId + 1);
$repairs = $VEHICLE_REPAIR->all();
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Vehicle Repair | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">
    <div id="layout-wrapper">
        <?php include 'navigation.php' ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                            <a href="#" class="btn btn-success" id="new">
                                <i class="uil uil-plus me-1"></i> New
                            </a>
                            <a href="#" class="btn btn-primary" id="create">
                                <i class="uil uil-save me-1"></i> Save
                            </a>
                            <a href="#" class="btn btn-warning" id="update" style="display:none;">
                                <i class="uil uil-edit me-1"></i> Update
                            </a>
                            <a href="#" class="btn btn-danger" id="delete" style="display:none;">
                                <i class="uil uil-trash-alt me-1"></i> Delete
                            </a>
                            <a href="#" class="btn btn-secondary" id="openRepairModal">
                                <i class="uil uil-search me-1"></i> Repairs
                            </a>
                        </div>
                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Vehicle Repair</li>
                            </ol>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar-xs">
                                                <div class="avatar-title rounded-circle bg-soft-primary text-primary">01</div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h5 class="font-size-16 mb-1">Vehicle Repair</h5>
                                            <p class="text-muted text-truncate mb-0">Record vehicle repairing cost</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <form id="form-data" autocomplete="off">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="ref_no" class="form-label">Ref No</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" id="ref_no" name="ref_no" class="form-control" value="<?php echo $nextRef; ?>" readonly>
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#repairSelectModal" id="repairModalBtn">
                                                        <i class="uil uil-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Vehicle</label>
                                                <div class="input-group mb-3">
                                                    <input type="hidden" id="vehicle_id" name="vehicle_id" value="">
                                                    <input type="text" id="vehicle_label" class="form-control" placeholder="-- Select Vehicle --" readonly>
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#vehicleSelectModal" id="vehicleModalBtn">
                                                        <i class="uil uil-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="repair_date" class="form-label">Repair Date</label>
                                                <input id="repair_date" name="repair_date" type="date" class="form-control mb-3" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="amount" class="form-label">Amount</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text">Rs.</span>
                                                    <input id="amount" name="amount" type="number" class="form-control text-end" step="0.01" min="0" value="0.00">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <label for="remark" class="form-label">Remark</label>
                                                <textarea id="remark" name="remark" class="form-control" rows="2" placeholder="Enter remark"></textarea>
                                            </div>
                                        </div>
                                    </form>
                                    <input type="hidden" id="id" name="id" value="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php' ?>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <!-- Vehicle Select Modal -->
    <div class="modal fade bs-example-modal-xl" id="vehicleSelectModal" tabindex="-1" role="dialog" aria-labelledby="vehicleSelectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vehicleSelectModalLabel">Select Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="datatable table table-bordered dt-responsive nowrap" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Vehicle No</th>
                                    <th>Ref No</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Type</th>
                                    <th>Select</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rowNo = 1; foreach ($vehicles as $vehicle): ?>
                                    <tr>
                                        <td><?php echo $rowNo++; ?></td>
                                        <td><?php echo htmlspecialchars($vehicle['vehicle_no']); ?></td>
                                        <td><?php echo htmlspecialchars($vehicle['ref_no']); ?></td>
                                        <td><?php echo htmlspecialchars($vehicle['brand']); ?></td>
                                        <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                        <td><?php echo htmlspecialchars($vehicle['type']); ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-primary select-vehicle"
                                                data-id="<?php echo $vehicle['id']; ?>"
                                                data-label="<?php echo htmlspecialchars($vehicle['vehicle_no']); ?> (<?php echo htmlspecialchars($vehicle['ref_no']); ?>)">
                                                Choose
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Repair Select Modal -->
    <div class="modal fade bs-example-modal-xl" id="repairSelectModal" tabindex="-1" role="dialog" aria-labelledby="repairSelectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="repairSelectModalLabel">Select Repair</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="datatable table table-bordered dt-responsive nowrap" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Ref No</th>
                                    <th>Vehicle</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Select</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $r = 1; foreach ($repairs as $repair): ?>
                                    <tr>
                                        <td><?php echo $r++; ?></td>
                                        <td><?php echo htmlspecialchars($repair['ref_no']); ?></td>
                                        <td><?php echo htmlspecialchars($repair['vehicle_no']); ?> (<?php echo htmlspecialchars($repair['vehicle_ref_no']); ?>)</td>
                                        <td><?php echo htmlspecialchars($repair['repair_date']); ?></td>
                                        <td><?php echo number_format($repair['amount'], 2); ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-primary select-repair"
                                                data-id="<?php echo $repair['id']; ?>"
                                                data-ref_no="<?php echo htmlspecialchars($repair['ref_no']); ?>"
                                                data-vehicle_id="<?php echo $repair['vehicle_id']; ?>"
                                                data-vehicle_label="<?php echo htmlspecialchars($repair['vehicle_no']); ?> (<?php echo htmlspecialchars($repair['vehicle_ref_no']); ?>)"
                                                data-repair_date="<?php echo htmlspecialchars($repair['repair_date']); ?>"
                                                data-amount="<?php echo htmlspecialchars($repair['amount']); ?>"
                                                data-remark="<?php echo htmlspecialchars($repair['remark']); ?>">
                                                Choose
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="ajax/js/vehicle-repair.js"></script>
    <?php include 'main-js.php' ?>
</body>

</html>
