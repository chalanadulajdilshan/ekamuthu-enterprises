<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';

$EQUIPMENT_RENT = new EquipmentRent(NULL);

// Get the last inserted ID
$lastId = $EQUIPMENT_RENT->getLastID();
$rent_code = 'ER/' . $_SESSION['id'] . '/0' . ($lastId + 1);
?>

<head>

    <meta charset="utf-8" />
    <title>Equipment Rent Master |
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
                                <a href="#" class="btn btn-danger delete-equipment-rent">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Equipment Rent Master</li>
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
                                                    <h5 class="font-size-16 mb-1">Equipment Rent Master</h5>
                                                    <p class="text-muted text-truncate mb-0">Fill all information below
                                                        to add equipment rent
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
                                                <!-- Equipment Rent Code -->
                                                <div class="col-md-3">
                                                    <label for="code" class="form-label">Rent Code</label>
                                                    <div class="input-group mb-3">
                                                        <input id="code" name="code" type="text" class="form-control"
                                                            value="<?php echo $rent_code ?>" readonly>
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#EquipmentRentModal"><i
                                                                class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Customer Selection -->
                                                <div class="col-md-3">
                                                    <label for="customer_display" class="form-label">Customer <span
                                                            class="text-danger">*</span></label>
                                                    <div class="input-group mb-3">
                                                        <input id="customer_display" name="customer_display" type="text"
                                                            class="form-control" placeholder="Select customer" readonly>
                                                        <input type="hidden" id="customer_id" name="customer_id">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#CustomerSelectModal"><i
                                                                class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Equipment Selection -->
                                                <div class="col-md-3">
                                                    <label for="equipment_display" class="form-label">Equipment <span
                                                            class="text-danger">*</span></label>
                                                    <div class="input-group mb-3">
                                                        <input id="equipment_display" name="equipment_display"
                                                            type="text" class="form-control"
                                                            placeholder="Select equipment" readonly>
                                                        <input type="hidden" id="equipment_id" name="equipment_id">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#EquipmentSelectModal"><i
                                                                class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Available Quantity -->
                                                <div class="col-md-3">
                                                    <label for="available_quantity" class="form-label">Available
                                                        Qty</label>
                                                    <div class="mb-3">
                                                        <input id="available_quantity" name="available_quantity"
                                                            type="number" class="form-control" placeholder="0" value="0"
                                                            readonly>
                                                    </div>
                                                </div>

                                                <!-- Rental Date -->
                                                <div class="col-md-3">
                                                    <label for="rental_date" class="form-label">Rental Date <span
                                                            class="text-danger">*</span></label>
                                                    <div class="mb-3">
                                                        <input id="rental_date" name="rental_date" type="date"
                                                            class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                                    </div>
                                                </div>

                                                <!-- Received Date -->
                                                <div class="col-md-3">
                                                    <label for="received_date" class="form-label">Received Date</label>
                                                    <div class="mb-3">
                                                        <input id="received_date" name="received_date" type="date"
                                                            class="form-control">
                                                    </div>
                                                </div>



                                                <!-- Quantity -->
                                                <div class="col-md-3">
                                                    <label for="quantity" class="form-label">Quantity</label>
                                                    <div class="mb-3">
                                                        <input id="quantity" name="quantity" type="number"
                                                            class="form-control" placeholder="1" value="1" min="1">
                                                    </div>
                                                </div>
                                                <!-- Status -->
                                                <div class="col-md-3">
                                                    <label for="status" class="form-label">Status</label>
                                                    <div class="mb-3">
                                                        <select id="rent_status" name="rent_status" class="form-select">
                                                            <option value="rented">Rented</option>
                                                            <option value="available">Available</option>
                                                            <option value="returned">Returned</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <!-- Remark Note -->
                                                <div class="col-12 mt-3">
                                                    <label for="remark" class="form-label">Remark Note</label>
                                                    <textarea id="remark" name="remark" class="form-control" rows="4"
                                                        placeholder="Enter any remarks or notes about the rental..."></textarea>
                                                </div>
                                                <input type="hidden" id="rent_id" name="rent_id" />
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

    <!-- Equipment Rent Modal -->
    <div id="EquipmentRentModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
        aria-labelledby="ModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ModalLabel">Manage Equipment Rentals</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="equipmentRentTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Code</th>
                                        <th>Customer</th>
                                        <th>Equipment</th>
                                        <th>Rental Date</th>
                                        <th>Received Date</th>
                                        <th>Qty</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Select Modal -->
    <div id="CustomerSelectModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
        aria-labelledby="CustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="CustomerModalLabel">Select Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="customerSelectTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Mobile</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Select Modal -->
    <div id="EquipmentSelectModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
        aria-labelledby="EquipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="EquipmentModalLabel">Select Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="equipmentSelectTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Code</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Available Qty</th>
                                        <th>Condition</th>
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
    <script src="ajax/js/equipment-rent-master.js"></script>

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