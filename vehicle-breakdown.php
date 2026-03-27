<?php
include_once(dirname(__FILE__) . '/class/include.php');
include_once(dirname(__FILE__) . '/auth.php');

$page_id = 114; 
UserPermission::checkAccess($page_id); 

$VEHICLE = new Vehicle(NULL);
$BREAKDOWN = new VehicleBreakdown(NULL);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Vehicle Breakdown | Ekamuthu Enterprises</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <?php include 'main-css.php' ?>
    <link href="assets/css/custom-style.css" rel="stylesheet" type="text/css" />
</head>

<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <?php include 'navigation.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Vehicle Breakdown Tracking</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Vehicle Breakdown</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <button id="add-new" class="btn btn-primary"><i class="uil uil-plus me-1"></i> New Breakdown</button>
                                        </div>
                                    </div>

                                    <form id="form-data">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="vehicle_id" class="form-label">Vehicle</label>
                                                <select id="vehicle_id" name="vehicle_id" class="form-select mb-3">
                                                    <option value="">-- Select Vehicle --</option>
                                                    <?php foreach ($VEHICLE->all() as $v) { ?>
                                                        <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['vehicle_no']); ?> (<?php echo htmlspecialchars($v['ref_no']); ?>)</option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="breakdown_date" class="form-label">Breakdown Date & Time</label>
                                                <input id="breakdown_date" name="breakdown_date" type="datetime-local" class="form-control mb-3" value="<?php echo date('Y-m-d\TH:i'); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="resolved_date" class="form-label">Resolved Date & Time</label>
                                                <input id="resolved_date" name="resolved_date" type="datetime-local" class="form-control mb-3">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="breakdown_status" class="form-label">Status</label>
                                                <select id="breakdown_status" name="status" class="form-select mb-3">
                                                    <option value="Pending">Pending</option>
                                                    <option value="Resolved">Resolved</option>
                                                    <option value="In Progress">In Progress</option>
                                                </select>
                                            </div>
                                            <div class="col-md-12">
                                                <label for="description" class="form-label">Issue Description</label>
                                                <textarea id="description" name="description" class="form-control mb-3" rows="3" placeholder="Describe the breakdown issue"></textarea>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12 text-end">
                                                <input type="hidden" id="id" name="id">
                                                <button type="submit" id="save" name="create" class="btn btn-success p-2 px-4 shadow"><i class="uil uil-save me-1"></i> Save Breakdown</button>
                                                <button type="button" id="update" name="update" class="btn btn-warning p-2 px-4 shadow d-none"><i class="uil uil-edit me-1"></i> Update Breakdown</button>
                                            </div>
                                        </div>
                                    </form>

                                    <hr class="my-4">

                                    <div class="table-responsive mt-4">
                                        <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Vehicle</th>
                                                    <th>Breakdown Date</th>
                                                    <th>Resolved Date</th>
                                                    <th class="text-center">Downtime</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $r = 1;
                                                foreach ($BREAKDOWN->all() as $row) { ?>
                                                    <tr>
                                                        <td><?php echo $r++; ?></td>
                                                        <td><?php echo htmlspecialchars($row['vehicle_no']); ?></td>
                                                        <td><?php echo date('Y-m-d H:i', strtotime($row['breakdown_date'])); ?></td>
                                                        <td><?php echo $row['resolved_date'] ? date('Y-m-d H:i', strtotime($row['resolved_date'])) : '<span class="text-danger">Active</span>'; ?></td>
                                                        <td class="text-center">
                                                            <span class="badge bg-soft-info text-info font-size-12 px-2">
                                                                <?php echo $row['downtime_formatted']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $badgeClass = 'bg-warning';
                                                            if ($row['status'] == 'Resolved') $badgeClass = 'bg-success';
                                                            if ($row['status'] == 'In Progress') $badgeClass = 'bg-info';
                                                            ?>
                                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $row['status']; ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <button class="btn btn-sm btn-info edit-btn" 
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-vehicle_id="<?php echo $row['vehicle_id']; ?>"
                                                                data-breakdown_date="<?php echo date('Y-m-d\TH:i', strtotime($row['breakdown_date'])); ?>"
                                                                data-resolved_date="<?php echo $row['resolved_date'] ? date('Y-m-d\TH:i', strtotime($row['resolved_date'])) : ''; ?>"
                                                                data-description="<?php echo htmlspecialchars($row['issue_description'] ?? ''); ?>"
                                                                data-breakdown_status="<?php echo $row['status']; ?>">
                                                                <i class="uil uil-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>">
                                                                <i class="uil uil-trash"></i>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="ajax/js/vehicle-breakdown.js"></script>
    <?php include 'main-js.php' ?>
</body>

</html>
