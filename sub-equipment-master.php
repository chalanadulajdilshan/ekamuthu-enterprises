<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';

$SUB_EQUIPMENT = new SubEquipment(NULL);

// Get parent equipment ID from URL
$parent_equipment_id = isset($_GET['equipment_id']) ? (int) $_GET['equipment_id'] : 0;

$parent_equipment = null;
$parent_equipment_display = '';

if ($parent_equipment_id) {
    $parent_equipment = new Equipment($parent_equipment_id);
    $parent_equipment_display = $parent_equipment->code . ' - ' . $parent_equipment->item_name;
}
?>

<head>

    <meta charset="utf-8" />
    <title>Sub Equipment Master |
        <?php echo $COMPANY_PROFILE_DETAILS->name ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>

</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Page Preloader -->
    <div id="page-preloader" class="preloader full-preloader">
        <div class="preloader-container">
            <div class="preloader-animation"></div>
        </div>
    </div>

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

                            <a href="#" class="btn btn-primary" id="create">
                                <i class="uil uil-save me-1"></i> Save
                            </a>

                            <a href="#" class="btn btn-warning" id="update" style="display: none;">
                                <i class="uil uil-edit me-1"></i> Update
                            </a>

                            <a href="#" class="btn btn-danger delete-sub-equipment">
                                <i class="uil uil-trash-alt me-1"></i> Delete
                            </a>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="equipment-master.php">Equipment Master</a></li>
                                <li class="breadcrumb-item active">Sub Equipment</li>
                            </ol>
                        </div>
                    </div>

                    <!-- end page title -->

                    <div class="row">
                        <div class="col-lg-12">
                            <div id="addproduct-accordion" class="custom-accordion">
                                <div class="card">
                                    <a href="#" class="text-dark" data-bs-toggle="collapse" aria-expanded="true"
                                        aria-controls="addproduct-billinginfo-collapse">
                                        <div class="p-4">

                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar-xs">
                                                        <div
                                                            class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                            01
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <h5 class="font-size-16 mb-1">Sub Equipment Master</h5>
                                                    <p class="text-muted text-truncate mb-0">
                                                        Add sub equipment for: <strong>
                                                            <?php echo $parent_equipment_display ?: 'Select Equipment'; ?>
                                                        </strong>
                                                    </p>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                                </div>

                                            </div>

                                        </div>
                                    </a>

                                    <div class="p-4">
                                        <form id="form-data" autocomplete="off">
                                            <div class="row">
                                                <!-- Parent Equipment (readonly) -->
                                                <div class="col-md-4">
                                                    <label for="parent_equipment_display" class="form-label">Parent
                                                        Equipment</label>
                                                    <div class="input-group mb-3">
                                                        <input id="parent_equipment_display"
                                                            name="parent_equipment_display" type="text"
                                                            class="form-control"
                                                            value="<?php echo $parent_equipment_display ?>" readonly>
                                                        <input type="hidden" id="parent_equipment_id"
                                                            name="parent_equipment_id"
                                                            value="<?php echo $parent_equipment_id ?>">
                                                        <input type="hidden" id="equipment_id" name="equipment_id"
                                                            value="<?php echo $parent_equipment_id ?>">
                                                    </div>
                                                </div>

                                                <!-- Sub Equipment Code -->
                                                <div class="col-md-3">
                                                    <label for="code" class="form-label">Sub Equipment Code</label>
                                                    <div class="input-group mb-3">
                                                        <input id="code" name="code" type="text"
                                                            class="form-control" placeholder="Enter sub equipment code">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal" data-bs-target="#SubEquipmentModal">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <input type="hidden" id="sub_equipment_id" name="sub_equipment_id" />

                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div>


            <?php include 'footer.php' ?>

        </div>
    </div>

    <!-- Sub Equipment Modal -->
    <div id="SubEquipmentModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
        aria-labelledby="ModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ModalLabel">Manage Sub Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="subEquipmentTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Code</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- /////////////////////////// -->
    <script src="ajax/js/sub-equipment-master.js"></script>

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <!-- Page Preloader Script -->
    <script>
        $(window).on('load', function () {
            $('#page-preloader').fadeOut('slow', function () {
                $(this).remove();
            });
        });
    </script>

</body>

</html>