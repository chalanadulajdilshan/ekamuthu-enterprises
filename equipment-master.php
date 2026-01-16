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
                                        <form id="form-data" autocomplete="off">
                                            <div class="row">
                                                <!-- Equipment Code -->
                                                <div class="col-md-2">
                                                    <label for="code" class="form-label">Equipment Code</label>
                                                    <div class="input-group mb-3">
                                                        <input id="code" name="code" type="text" class="form-control"
                                                            value="<?php echo $equipment_id ?>" readonly>
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
                                                <div class="col-md-1">
                                                    <label for="size" class="form-label">Size <span
                                                            class="text-danger">*</span></label>
                                                    <input id="size" name="size" type="text" class="form-control"
                                                        placeholder="Size" required>

                                                </div>

                                                <!-- One Day's Rent -->
                                                <div class="col-md-2">
                                                    <label for="rent_one_day" class="form-label">One Day's Rent <span
                                                            class="text-danger">*</span></label>
                                                    <input id="rent_one_day" name="rent_one_day" type="number" step="0.01"
                                                        class="form-control" placeholder="0.00" min="0" required>

                                                </div>

                                                <!-- One Day's Deposit -->
                                                <div class="col-md-2">
                                                    <label for="deposit_one_day" class="form-label">One Day's Deposit <span
                                                            class="text-danger">*</span></label>
                                                    <input id="deposit_one_day" name="deposit_one_day" type="number" step="0.01"
                                                        class="form-control" placeholder="0.00" min="0" required>

                                                </div>

                                                <!-- One Month's Rent -->
                                                <div class="col-md-2">
                                                    <label for="rent_one_month" class="form-label">One Month's Rent <span
                                                            class="text-danger">*</span></label>
                                                    <input id="rent_one_month" name="rent_one_month" type="number" step="0.01"
                                                        class="form-control" placeholder="0.00" min="0" required>

                                                </div>

                                                <!-- Value -->
                                                <div class="col-md-2">
                                                    <label for="value" class="form-label">Value <span
                                                            class="text-danger">*</span></label>
                                                    <input id="value" name="value" type="number" step="0.01" class="form-control"
                                                        placeholder="0.00" min="0" required>

                                                </div>

                                                <!-- Quantity -->
                                                <div class="col-md-1">
                                                    <label for="quantity" class="form-label">Quantity <span
                                                            class="text-danger">*</span></label>
                                                    <input id="quantity" name="quantity" type="number"
                                                        class="form-control" placeholder="0" value="0" min="0" required>

                                                </div>

                                                <input type="hidden" id="equipment_id" name="equipment_id" />
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
                                        <th>Code</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Serial Number</th>
                                        <th>Value</th>
                                        <th>Quantity</th>
                                        <th>Action</th>
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
    <script src="ajax/js/equipment-master.js"></script>

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