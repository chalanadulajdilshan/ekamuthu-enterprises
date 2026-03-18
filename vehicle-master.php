<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$VEHICLE_MASTER = new Vehicle();

// Get the last inserted package id
$lastId = $VEHICLE_MASTER->getLastID();
$ref_no = 'VM/' . $_SESSION['id'] . '/0' . ($lastId + 1);
?>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Vehicle Master | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>

</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                            <a href="#" class="btn btn-success" id="new">
                                <i class="uil uil-plus me-1"></i> New
                            </a>
                            <?php if ($PERMISSIONS['add_page']): ?>
                            <a href="#" class="btn btn-primary" id="create">
                                <i class="uil uil-save me-1"></i> Save
                            </a>
                            <?php endif; ?>
                            <?php if ($PERMISSIONS['edit_page']): ?>
                            <a href="#" class="btn btn-warning" id="update" style="display:none;">
                                <i class="uil uil-edit me-1"></i> Update
                            </a>
                            <?php endif; ?>
                            <?php if ($PERMISSIONS['delete_page']): ?>
                            <a href="#" class="btn btn-danger delete-vehicle-master">
                                <i class="uil uil-trash-alt me-1"></i> Delete
                            </a>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">VEHICLE MASTER</li>
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
                                                <div class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                    01
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h5 class="font-size-16 mb-1">Vehicle Master</h5>
                                            <p class="text-muted text-truncate mb-0">Fill all information below</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                        </div>
                                    </div>

                                </div>

                                <div class="p-4">

                                    <form id="form-data" autocomplete="off">
                                        <div class="row">

                                            <div class="col-md-3">
                                                <label for="ref_no" class="form-label">Reference No</label>
                                                <div class="input-group mb-3">
                                                    <input id="ref_no" name="ref_no" type="text"
                                                        value="<?php echo $ref_no ?>" class="form-control" readonly>
                                                    <button class="btn btn-info" type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#vehicleModel">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="vehicle_no" class="form-label">Vehicle No</label>
                                                <div class="input-group mb-3">
                                                    <input id="vehicle_no" name="vehicle_no" type="text"
                                                    placeholder="Enter Vehicle No" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="brand" class="form-label">Brand</label>
                                                <div class="input-group mb-3">
                                                    <input id="brand" name="brand" type="text"
                                                    placeholder="Enter Brand" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="model" class="form-label">Model</label>
                                                <div class="input-group mb-3">
                                                    <input id="model" name="model" type="text"
                                                    placeholder="Enter Model" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="type" class="form-label">Type</label>
                                                <div class="input-group mb-3">
                                                    <input id="type" name="type" type="text"
                                                    placeholder="Enter Type" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="start_meter" class="form-label">Start Meter</label>
                                                <div class="input-group mb-3">
                                                    <input id="start_meter" name="start_meter" type="number" step="0.01"
                                                    placeholder="Enter Start Meter" class="form-control">
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <input type="hidden" id="id" name="id" value="0">
                                        
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div>  
            <?php include 'footer.php' ?>

        </div> 
    </div>
    
  
<!-- model open here -->
<div class="modal fade bs-example-modal-xl" id="vehicleModel" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Manage Vehicles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
  

                        <table  class="datatable table table-bordered dt-responsive nowrap"
                                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Vehicle No</th>
                                    <th>Ref No</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Type</th>

                                </tr>
                            </thead>


                            <tbody>
                                <?php
                                $VEHICLE = new Vehicle(null);
                                foreach ($VEHICLE->all() as $key => $vehicle) {
                                    $key++;
                                    ?>
                                    <tr class="select-vehicle" 
                                            data-id="<?php echo $vehicle['id']; ?>"
                                            data-ref_no="<?php echo htmlspecialchars($vehicle['ref_no']); ?>"
                                            data-vehicle_no="<?php echo htmlspecialchars($vehicle['vehicle_no']); ?>"
                                            data-brand="<?php echo htmlspecialchars($vehicle['brand']); ?>"
                                            data-model="<?php echo htmlspecialchars($vehicle['model']); ?>"
                                            data-type="<?php echo htmlspecialchars($vehicle['type']); ?>"
                                            data-start_meter="<?php echo htmlspecialchars($vehicle['start_meter']); ?>"
                                    >

                                    <td><?php echo $key; ?></td>
                                            <td><?php echo htmlspecialchars($vehicle['vehicle_no']); ?></td>
                                            <td><?php echo htmlspecialchars($vehicle['ref_no']); ?></td>
                                            <td><?php echo htmlspecialchars($vehicle['brand']); ?></td>
                                            <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                            <td><?php echo htmlspecialchars($vehicle['type']); ?></td>
                                    </tr>

                                <?php } ?>
                            </tbody>
                        </table>
                    </div> <!-- end col -->
                </div> <!-- end row -->
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
<!-- model close here -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- /////////////////////////// -->
    <script src="ajax/js/vehicle-master.js"></script>


    <!-- include main js  -->
    <?php include 'main-js.php' ?>

</body>

</html>
