<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';

$RENT_TYPE = new RentType(NULL);
$EQUIPMENT = new Equipment(NULL);

// Get the last inserted ID
$lastId = $RENT_TYPE->getLastID();
$rent_type_code = 'RT/' . $_SESSION['id'] . '/0' . ($lastId + 1);

// Get all equipment for dropdown
$equipmentList = $EQUIPMENT->all();
?>

<head>

    <meta charset="utf-8" />
    <title>Rent Type Master |
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
                                <a href="#" class="btn btn-danger delete-rent-type">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Rent Type Master</li>
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
                                                    <h5 class="font-size-16 mb-1">Rent Type Master</h5>
                                                    <p class="text-muted text-truncate mb-0">Fill all information below
                                                        to add rent type
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
                                                <!-- Equipment -->
                                                <div class="col-md-3">
                                                    <label for="equipment_id" class="form-label">Equipment <span
                                                            class="text-danger">*</span></label>
                                                    <div class="input-group mb-3">
                                                        <select id="equipment_id" name="equipment_id"
                                                            class="form-select">
                                                            <option value="">-- Select Equipment --</option>
                                                            <?php foreach ($equipmentList as $equipment): ?>
                                                                <?php if ($equipment['availability_status'] == 1): ?>
                                                                    <option value="<?php echo $equipment['id']; ?>">
                                                                        <?php echo $equipment['item_name']; ?>
                                                                    </option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>

                                                    </div>
                                                </div>

                                                <!-- Rent Type Name -->
                                                <div class="col-md-3">
                                                    <label for="name" class="form-label">Rent Type Name <span
                                                            class="text-danger">*</span></label>
                                                    <div class="input-group mb-3">
                                                        <input id="name" name="name" type="text" class="form-control"
                                                            placeholder="Enter rent type name">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal" data-bs-target="#RentTypeModal">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Price -->
                                                <div class="col-md-2">
                                                    <label for="price" class="form-label">Price <span
                                                            class="text-danger">*</span></label>
                                                    <input id="price" name="price" type="number" step="0.01"
                                                        class="form-control" placeholder="0.00" value="0.00">
                                                </div>

                                                <!-- Deposit Amount -->
                                                <div class="col-md-2">
                                                    <label for="deposit_amount" class="form-label">Deposit
                                                        Amount</label>
                                                    <input id="deposit_amount" name="deposit_amount" type="number"
                                                        step="0.01" class="form-control" placeholder="0.00"
                                                        value="0.00">
                                                </div>

                                                <input type="hidden" id="rent_type_id" name="rent_type_id" />
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

    <!-- Rent Type Modal -->
    <div id="RentTypeModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
        aria-labelledby="ModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ModalLabel">Manage Rent Types</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="rentTypeTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Equipment</th>
                                        <th>Rent Type Name</th>
                                        <th>Price</th>
                                        <th>Deposit Amount</th>
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
    <script src="ajax/js/rent-type-master.js"></script>

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