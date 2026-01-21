<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';

$EQUIPMENT_RENT = new EquipmentRent(NULL);

// Get the bill number from document tracking table
$DOCUMENT_TRACKING = new DocumentTracking($doc_id);
$lastId = $DOCUMENT_TRACKING->equipment_rent_id;
$bill_number = $lastId + 1;
?>

<head>

    <meta charset="utf-8" />
    <title>Equipment Rent |
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

                            <a href="#" class="btn btn-info" id="return-all" style="display: none;">
                                <i class="uil uil-redo me-1"></i> Return All
                            </a>

                            <a href="#" class="btn btn-dark" id="print" style="display: none;">
                                <i class="uil uil-print me-1"></i> Print
                            </a>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Equipment Rent</li>
                            </ol>
                        </div>
                    </div>

                    <!-- end page title -->

                    <div class="row">
                        <div class="col-lg-12">
                            <div id="addproduct-accordion" class="custom-accordion">
                                <!-- Master Info Card -->
                                <div class="card">
                                    <a href="#" class="text-dark" data-bs-toggle="collapse" aria-expanded="true"
                                        aria-controls="addproduct-billinginfo-collapse">
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
                                                    <h5 class="font-size-16 mb-1">Equipment Rent</h5>
                                                    <p class="text-muted text-truncate mb-0">Rent multiple equipment items to a customer</p>
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
                                                <!-- Bill Number -->
                                                <div class="col-md-3">
                                                    <label for="code" class="form-label">Bill Number - බිල්පත් අංකය</label>
                                                    <div class="input-group mb-3">
                                                        <input id="code" name="code" type="text" class="form-control"
                                                            value="<?php echo $bill_number ?>" readonly>
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#EquipmentRentModal"><i
                                                                class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Customer Selection -->
                                                <div class="col-md-4">
                                                    <label for="customer_display" class="form-label">Customer - පාරිභෝගිකයා<span
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

                                                <!-- Rental Date -->
                                                <div class="col-md-2">
                                                    <label for="rental_date" class="form-label">Rental Date - කුලියට ගත් දිනය<span
                                                            class="text-danger">*</span></label>
                                                    <div class="mb-3">
                                                        <input id="rental_date" name="rental_date" type="text"
                                                            class="form-control date-picker" value="<?php echo date('Y-m-d'); ?>">
                                                    </div>
                                                </div>

                                                <!-- Received Date -->
                                                <div class="col-md-3" id="received_date_container" style="display: none;">
                                                    <label for="received_date" class="form-label">Received Date (All Items) - ලැබුණු දිනය (සියලුම අයිතම)</label>
                                                    <div class="mb-3">
                                                        <input id="received_date" name="received_date" type="text"
                                                            class="form-control date-picker-date">
                                                    </div>
                                                </div>

                                                <!-- Remark Note -->
                                                <div class="col-md-6">
                                                    <label for="remark" class="form-label">Remark - සටහන</label>
                                                    <textarea id="remark" name="remark" class="form-control" rows="2"
                                                        placeholder="Enter any remarks or notes about the rental..."></textarea>
                                                </div>
                                                
                                                <input type="hidden" id="rent_id" name="rent_id" />
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Add Item Card -->
                                <div class="card">
                                    <div class="p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    <div class="avatar-title rounded-circle bg-soft-success text-success">
                                                        02
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="font-size-16 mb-0">Add Equipment Items</h5>
                                            </div>
                                        </div>
                                        
                                        <div class="row align-items-end mb-3">
                                            <!-- Equipment Selection -->
                                            <div class="col-md-3">
                                                <label for="item_equipment_display" class="form-label">Equipment - උපකරණ</label>
                                                <div class="input-group">
                                                    <input id="item_equipment_display" type="text" class="form-control"
                                                        placeholder="Select equipment" readonly>
                                                    <input type="hidden" id="item_equipment_id">
                                                    <button class="btn btn-info" type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#EquipmentSelectModal"><i
                                                            class="uil uil-search"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Sub Equipment Selection -->
                                            <div class="col-md-3">
                                                <label for="item_sub_equipment_display" class="form-label">Sub Equipment (Code) - උප උපකරණ (කේතය)</label>
                                                <div class="input-group">
                                                    <input id="item_sub_equipment_display" type="text" class="form-control"
                                                        placeholder="Select sub equipment" readonly>
                                                    <input type="hidden" id="item_sub_equipment_id">
                                                    <button class="btn btn-info" type="button" id="btn-select-sub-equipment"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#SubEquipmentSelectModal"><i
                                                            class="uil uil-search"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <label class="form-label">Rent Type - කුලී වර්ගය</label>
                                                <select class="form-select" id="item_rent_type">
                                                    <option value="day">Day</option>
                                                    <option value="month">Month</option>
                                                </select>
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Duration - කාල සීමාව</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="item_duration" min="1" step="1" placeholder="0">
                                                    <span class="input-group-text" id="duration_label">Days</span>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <label class="form-label">Qty - ප්‍රමාණය</label>
                                                <input type="number" class="form-control" id="item_qty" min="1" step="1" value="1" readonly>
                                            </div>

                                            <div class="col-md-1" id="returned_qty_container" style="display: none;">
                                                <label class="form-label">Returned</label>
                                                <input type="number" class="form-control" id="item_returned_qty" min="0" step="1" value="0">
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Amount - අගය</label>
                                                <input type="text" class="form-control" id="item_amount" readonly placeholder="0.00">
                                            </div>

                                        </div>

                                        <div class="row align-items-end">
                                            
                                            <!-- Item Rental Date -->
                                            <div class="col-md-3">
                                                <label for="item_rental_date" class="form-label">Rental Date - කුලියට ගත් දිනය</label>
                                                <input id="item_rental_date" type="text" class="form-control date-picker" 
                                                    value="<?php echo date('Y-m-d'); ?>">
                                            </div>

                                            <!-- Item Return Date -->
                                            <div class="col-md-2">
                                                <label for="item_return_date" class="form-label">Return Date - ආපසු දුන් දිනය</label>
                                                <input id="item_return_date" type="text" class="form-control date-picker-date">
                                            </div>

                                            <!-- Add Button -->
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-success w-100" id="addItemBtn">
                                                    <i class="uil uil-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Items Table -->
                                        <div class="table-responsive mt-4">
                                            <table class="table table-bordered" id="rentItemsTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Equipment</th>
                                                        <th>Sub Equipment Code</th>
                                                        <th>Type</th>
                                                        <th>Duration</th>
                                                        <th>Qty</th>
                                                        <th>Returned</th>
                                                        <th>Amount</th>
                                                        <th>Deposit</th>
                                                        <th>Rental Date</th>
                                                        <th>Return Date</th>
                                                        <th>Status</th>
                                                        <th style="width: 120px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Items will be added here dynamically -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center text-muted py-3" id="noItemsMessage">
                                            <i class="uil uil-box font-size-24"></i>
                                            <p class="mb-0">No equipment items added yet. Select equipment and sub-equipment above to add items.</p>
                                        </div>

                                        <!-- Totals Summary Section -->
                                        <div class="row justify-content-end mt-4" id="totalsSummarySection">
                                            <div class="col-md-5">
                                                <div class="card border shadow-sm">
                                                    <div class="card-body">
                                                        <table class="table table-borderless mb-0">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="text-start fw-medium">Sub Total:</td>
                                                                    <td class="text-end" id="summary_sub_total">0.00</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-start fw-medium">Transport Cost:</td>
                                                                    <td class="text-end">
                                                                        <input type="text" class="form-control form-control-sm text-end" id="transport_cost" name="transport_cost" placeholder="0.00" style="max-width: 120px; display: inline-block;">
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-start fw-medium">Customer Deposit:</td>
                                                                    <td class="text-end">
                                                                        <input type="text" class="form-control form-control-sm text-end" id="custom_deposit" name="custom_deposit" placeholder="0.00" style="max-width: 120px; display: inline-block;">
                                                                        <br><small class="text-muted">Calculated: <span id="calculated_deposit_display" class="fw-bold">0.00</span></small>
                                                                    </td>
                                                                </tr>
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
                                        <th>Bill Number</th>
                                        <th>Customer</th>
                                        <th>Rental Date</th>
                                        <th>Received Date</th>
                                        <th>Items</th>
                                        <th>Outstanding</th>
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
                                        <th>#ID</th>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Mobile</th>
                                        <th>NIC</th>
                                        <th>Address</th>
                                        <th>Outstanding</th>
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
                                        <th>Availability</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sub Equipment Select Modal -->
    <div id="SubEquipmentSelectModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
        aria-labelledby="SubEquipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="SubEquipmentModalLabel">Select Sub Equipment (Available Units)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div id="noSubEquipmentMsg" class="alert alert-warning" style="display: none;">
                                <i class="uil uil-exclamation-triangle me-2"></i>
                                Please select an equipment first, or all units of this equipment are already rented.
                            </div>
                            <table id="subEquipmentSelectTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Code</th>
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

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- /////////////////////////// -->
    <!-- include main js  -->
    <?php include 'main-js.php' ?>

    <script src="ajax/js/equipment-rent-master.js"></script>

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