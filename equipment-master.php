<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';

$EQUIPMENT = new Equipment(NULL);

// Get the last inserted ID
$lastId = $EQUIPMENT->getLastID();
$equipment_id = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
?>

<head>

    <meta charset="utf-8" />
    <title>Equipment Master |
        <?php echo $COMPANY_PROFILE_DETAILS->name ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
    <link rel="stylesheet" href="assets/libs/jquery-ui-dist/jquery-ui.min.css">

    <!-- Cropper CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    <style>
        .img-container {
            max-width: 100%;
            max-height: 400px;
        }
    </style>

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

                            <?php if ($PERMISSIONS['add_page']): ?>
                                <a href="#" class="btn btn-primary" id="create">
                                    <i class="uil uil-save me-1"></i> Save
                                </a>
                            <?php endif; ?>

                            <?php if ($PERMISSIONS['edit_page']): ?>
                                <a href="#" class="btn btn-warning" id="update" style="display: none;">
                                    <i class="uil uil-edit me-1"></i> Update
                                </a>
                            <?php endif; ?>

                            <?php if ($PERMISSIONS['delete_page']): ?>
                                <a href="#" class="btn btn-danger delete-equipment">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>

                            <a href="#" class="btn btn-outline-success d-none" id="add-sub-equipment"
                                title="Add sub equipment for selected equipment">
                                <i class="uil uil-plus me-1"></i> Add Sub Equipment
                            </a>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Equipment Master</li>
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
                                                    <h5 class="font-size-16 mb-1">Equipment Master</h5>
                                                    <p class="text-muted text-truncate mb-0">Fill all information below
                                                        to add equipment
                                                    </p>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                                </div>

                                            </div>

                                        </div>
                                    </a>

                                    <div class="p-4">
                                        <form id="form-data" autocomplete="off" enctype="multipart/form-data">
                                            <div class="row">
                                                <!-- Equipment Code -->
                                                <div class="col-md-2">
                                                    <label for="code" class="form-label">Equipment Code</label>
                                                    <div class="input-group mb-3">
                                                        <input id="code" name="code" type="text" class="form-control"
                                                            value="<?php echo $equipment_id ?>">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal" data-bs-target="#EquipmentModal">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Item Name -->
                                                <div class="col-md-3">
                                                    <label for="item_name" class="form-label">Item Name <span
                                                            class="text-danger">*</span></label>
                                                    <input id="item_name" name="item_name" type="text"
                                                        class="form-control" placeholder="Enter item name" required>

                                                </div>

                                                <!-- Category -->
                                                <div class="col-md-2">
                                                    <label for="category" class="form-label">Category <span
                                                            class="text-danger">*</span></label>
                                                    <select id="category" name="category" class="form-select" required>

                                                        <option value="">- Select Category -</option>
                                                        <?php
                                                        $EQUIPMENT_CATEGORY = new EquipmentCategory(NULL);
                                                        foreach ($EQUIPMENT_CATEGORY->getActiveCategories() as $cat) {
                                                            echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['name']) . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <!-- Department -->
                                                <div class="col-md-2">
                                                    <label for="department" class="form-label">Department <span
                                                            class="text-danger">*</span></label>
                                                    <select id="department" name="department" class="form-select" required>
                                                        <option value="">- Select Department -</option>
                                                        <?php
                                                        $DEPARTMENT = new DepartmentMaster(NULL);
                                                        foreach ($DEPARTMENT->all() as $dept) {
                                                            echo '<option value="' . $dept['id'] . '">' . htmlspecialchars($dept['name']) . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <!-- One Day's Rent -->
                                                <div class="col-md-1">
                                                    <label for="rent_one_day" class="form-label">Day Rent <span
                                                            class="text-danger">*</span></label>
                                                    <input id="rent_one_day" name="rent_one_day" type="number"
                                                        step="0.01" class="form-control" placeholder="0.00" min="0"
                                                        required>

                                                </div>

                                                <!-- Serial Number -->
                                                <div class="col-md-2">
                                                    <label for="serial_number" class="form-label">Serial Number <span
                                                            class="text-danger">*</span></label>
                                                    <input id="serial_number" name="serial_number" type="text"
                                                        class="form-control" placeholder="Enter serial number" required>

                                                </div>

                                                <!-- Damage -->
                                                <div class="col-md-2">
                                                    <label for="damage" class="form-label">Damage <span
                                                            class="text-danger">*</span></label>
                                                    <input id="damage" name="damage" type="text" class="form-control"
                                                        placeholder="Enter damage status/notes" required>

                                                </div>

                                                <!-- Size -->
                                                <div class="col-md-2">
                                                    <label for="size" class="form-label">Size <span
                                                            class="text-danger">*</span></label>
                                                    <input id="size" name="size" type="text" class="form-control"
                                                        placeholder="Size" required>

                                                </div>

                                                <!-- One Day's Deposit -->
                                                <div class="col-md-2">
                                                    <label for="deposit_one_day" class="form-label">One Day's Deposit
                                                        <span class="text-danger">*</span></label>
                                                    <input id="deposit_one_day" name="deposit_one_day" type="number"
                                                        step="0.01" class="form-control" placeholder="0.00" min="0"
                                                        required>

                                                </div>

                                                <!-- One Month's Rent -->
                                                <div class="col-md-2">
                                                    <label for="rent_one_month" class="form-label">One Month's Rent
                                                        <span class="text-danger">*</span></label>
                                                    <input id="rent_one_month" name="rent_one_month" type="number"
                                                        step="0.01" class="form-control" placeholder="0.00" min="0"
                                                        required>

                                                </div>

                                                <!-- Value -->
                                                <div class="col-md-2">
                                                    <label for="value" class="form-label">Value <span
                                                            class="text-danger">*</span></label>
                                                    <input id="value" name="value" type="number" step="0.01"
                                                        class="form-control" placeholder="0.00" min="0" required>

                                                </div>

                                                <!-- Quantity -->
                                                <div class="col-md-2">
                                                    <label for="quantity" class="form-label">Quantity <span
                                                            class="text-danger">*</span></label>
                                                    <input id="quantity" name="quantity" type="number"
                                                        class="form-control" placeholder="0" value="0" min="0" required>
                                                </div>
                                                <div class="col-md-4 mt-3">
                                                    <label for="equipment_image" class="form-label">Equipment Image
                                                        (600x600)</label>
                                                    <input type="file" id="equipment_image" name="equipment_image"
                                                        class="form-control" accept="image/*">
                                                    <div id="image_preview_container"
                                                        class="mt-2 text-center border rounded p-2"
                                                        style="min-height: 150px; background: #f8f9fa;">
                                                        <img id="image_preview" src="assets/images/no-image.png"
                                                            alt="Preview"
                                                            style="max-width: 100%; max-height: 200px; display: block; margin: 0 auto;">
                                                        <p id="preview_text" class="text-muted mt-2 small">600 x 600
                                                            Recommended</p>
                                                    </div>
                                                </div>
                                                <!-- No Sub-Items / Change Value / Fixed Rate -->
                                                <div class="col-md-4 mt-4 pt-2">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="form-check form-check-inline mb-0">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="no_sub_items" name="no_sub_items" value="1">
                                                            <label class="form-check-label" for="no_sub_items">No
                                                                Sub-Items</label>
                                                        </div>
                                                        <div class="form-check form-check-inline mb-0">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="change_value" name="change_value" value="1">
                                                            <label class="form-check-label" for="change_value">Change
                                                                Value</label>
                                                        </div>
                                                        <div class="form-check form-check-inline mb-0">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="is_fixed_rate" name="is_fixed_rate" value="1">
                                                            <label class="form-check-label" for="is_fixed_rate">Fixed
                                                                Rate</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Image Upload -->


                                                <!-- Remark -->
                                                <div class="col-md-4 mt-3">
                                                    <label for="remark" class="form-label">Remark</label>
                                                    <textarea id="remark" name="remark" class="form-control" rows="8"
                                                        placeholder="Enter any additional remarks or notes..."></textarea>
                                                </div>

                                                <input type="hidden" id="equipment_id" name="equipment_id" />
                                                <input type="hidden" id="old_image_name" name="old_image_name" />
                                                <input type="hidden" id="cropped_image_data"
                                                    name="cropped_image_data" />
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

    <!-- Equipment Modal -->
    <div id="EquipmentModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
        aria-labelledby="ModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ModalLabel">Manage Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="equipmentTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th style="width: 60px;">Image</th>
                                        <th>Code</th>
                                        <th>Item Name</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cropping Modal -->
    <div class="modal fade" id="cropModal" tabindex="-1" role="dialog" aria-labelledby="cropModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cropModalLabel">Crop Equipment Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="img-container">
                        <img id="image-to-crop" src="" alt="Image to crop">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="crop-button">Crop & Use</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/jquery-ui-dist/jquery-ui.min.js"></script>

    <!-- /////////////////////////// -->

    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <!-- Cropper JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>

    <script src="ajax/js/equipment-master.js"></script>

    <!-- Page Preloader Script -->
    <script>
        $(window).on('load', function() {
            $('#page-preloader').fadeOut('slow', function() {
                $(this).remove();
            });
        });
    </script>

</body>

</html>