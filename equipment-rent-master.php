<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';

$EQUIPMENT_RENT = new EquipmentRent(NULL);

$PAYMENT_TYPE = new PaymentType(null);
$PAYMENT_TYPES = $PAYMENT_TYPE->getActivePaymentType();

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

                            <a href="#" class="btn btn-outline-danger" id="cancel-return" style="display: none;">
                                <i class="uil uil-times-circle me-1"></i> Cancel Return
                            </a>

                            <a href="#" class="btn btn-outline-secondary" id="show-all-bills">
                                <i class="uil uil-list-ul me-1"></i> All Return Bills
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
                                                        <div
                                                            class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                            01
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <h5 class="font-size-16 mb-1">Equipment Rent</h5>
                                                    <p class="text-muted text-truncate mb-0">Rent multiple equipment
                                                        items to a customer</p>
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
                                                    <label for="code" class="form-label">Bill Number - බිල්පත්
                                                        අංකය</label>
                                                    <div class="input-group mb-3">
                                                        <input id="code" name="code" type="text" class="form-control"
                                                            value="<?php echo $bill_number ?>" placeholder="Enter Bill Number">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#EquipmentRentModal"><i
                                                                class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Customer Selection -->
                                                <div class="col-md-4">
                                                    <label for="customer_display" class="form-label">Customer -
                                                        පාරිභෝගිකයා<span class="text-danger">*</span></label>
                                                    <div class="input-group mb-3">
                                                        <input id="customer_display" name="customer_display" type="text"
                                                            class="form-control" placeholder="Select customer" readonly>
                                                        <input type="hidden" id="customer_id" name="customer_id">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#CustomerSelectModal"><i
                                                                class="uil uil-search me-1"></i>
                                                        </button>
                                                        <button class="btn btn-success" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#AddCustomerModal" title="Add New Customer"><i
                                                                class="uil uil-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Rental Date -->
                                                <div class="col-md-2">
                                                    <label for="rental_date" class="form-label">Rental Date - කුලියට ගත්
                                                        දිනය<span class="text-danger">*</span></label>
                                                    <div class="mb-3">
                                                        <input id="rental_date" name="rental_date" type="text"
                                                            class="form-control date-picker"
                                                            value="<?php echo date('Y-m-d'); ?>">
                                                    </div>
                                                </div>

                                                <!-- Payment Method -->
                                                <div class="col-md-3">
                                                    <label for="payment_type_id" class="form-label">Payment Method - ගෙවීමේ ක්‍රමය<span class="text-danger">*</span></label>
                                                    <div class="mb-3">
                                                        <select id="payment_type_id" name="payment_type_id" class="form-select" required>
                                                            <option value="">-- Select Payment Method --</option>
                                                            <?php foreach ($PAYMENT_TYPES as $pt) { ?>
                                                                <option value="<?php echo (int) $pt['id']; ?>"><?php echo htmlspecialchars($pt['name']); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Received Date -->
                                                <div class="col-md-3" id="received_date_container"
                                                    style="display: none;">
                                                    <label for="received_date" class="form-label">Received Date (All
                                                        Items) - ලැබුණු දිනය (සියලුම අයිතම)</label>
                                                    <div class="mb-3">
                                                        <input id="received_date" name="received_date" type="text"
                                                            class="form-control">
                                                    </div>
                                                </div>

                                                <!-- Remark Note -->
                                                <div class="col-md-6">
                                                    <label for="remark" class="form-label">Remark - සටහන</label>
                                                    <textarea id="remark" name="remark" class="form-control" rows="2"
                                                        placeholder="Enter any remarks or notes about the rental..."></textarea>
                                                </div>

                                                <!-- Return-after-9 checkbox (applies to Return All) -->
                                                <div class="col-md-3 align-self-end">
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input" type="checkbox" id="after_9am_extra_day_all" value="1">
                                                        <label class="form-check-label" for="after_9am_extra_day_all">
                                                            Return after 9:00 AM (count extra day)
                                                        </label>
                                                    </div>
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
                                                    <div
                                                        class="avatar-title rounded-circle bg-soft-success text-success">
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
                                                <label for="item_equipment_display" class="form-label">Equipment -
                                                    උපකරණ</label>
                                                <div class="input-group">
                                                    <input id="item_equipment_display" type="text" class="form-control"
                                                        placeholder="Select equipment" readonly>
                                                    <input type="hidden" id="item_equipment_id">
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#EquipmentSelectModal"><i
                                                            class="uil uil-search"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Sub Equipment Selection -->
                                            <div class="col-md-3">
                                                <label for="item_sub_equipment_display" class="form-label">Sub Equipment
                                                    (Code) - උප උපකරණ (කේතය)</label>
                                                <div class="input-group">
                                                    <input id="item_sub_equipment_display" type="text"
                                                        class="form-control" placeholder="Select sub equipment"
                                                        readonly>
                                                    <input type="hidden" id="item_sub_equipment_id">
                                                    <button class="btn btn-info" type="button"
                                                        id="btn-select-sub-equipment" data-bs-toggle="modal"
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
                                                    <input type="number" class="form-control" id="item_duration" min="1"
                                                        step="1" placeholder="0">
                                                    <span class="input-group-text" id="duration_label">Days</span>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <label class="form-label">Qty - ප්‍රමාණය</label>
                                                <input type="number" class="form-control" id="item_qty" min="1" step="1"
                                                    value="1" readonly>
                                            </div>


                                            <div class="col-md-2">
                                                <label class="form-label">Amount - අගය</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="item_amount" readonly
                                                        placeholder="0.00">
                                                    <?php if ($PERMISSIONS['edit_page']): ?>
                                                        <button class="btn btn-danger" type="button" id="btn-enable-amount-edit" 
                                                            title="Enable manual amount editing" style="display: none;">
                                                            <i class="uil uil-plus"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row align-items-end">

                                            <!-- Item Rental Date -->
                                            <div class="col-md-3">
                                                <label for="item_rental_date" class="form-label">Rental Date - කුලියට
                                                    ගත් දිනය</label>
                                                <input id="item_rental_date" type="text"
                                                    class="form-control date-picker"
                                                    value="<?php echo date('Y-m-d'); ?>">
                                            </div>

                                            <!-- Item Return Date -->
                                            <div class="col-md-2">
                                                <label for="item_return_date" class="form-label">Return Date - ආපසු දිය යුතු දිනය</label>
                                                <input id="item_return_date" type="text"
                                                    class="form-control date-picker-date">
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
                                                        <th>Sub Equipment</th>
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
                                            <p class="mb-0">No equipment items added yet. Select equipment and
                                                sub-equipment above to add items.</p>
                                        </div>

                                        <div class="row mt-4 align-items-start" id="totalsSummarySection">
                                            <div class="col-md-7">
                                                <div id="customerOutstandingAlert" class="alert alert-danger" role="alert"
                                                    style="display:none; font-size:18px; font-weight:700; padding:10px 14px; max-width:50%;">
                                                    Outstanding: <span id="customerOutstandingValue">0.00</span>
                                                </div>

                                            </div>
                                            <div class="col-md-5">
                                                <div class="card border shadow-sm">
                                                    <div class="card-body">
                                                        <table class="table table-borderless mb-0">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="text-start fw-medium">Sub Total:</td>
                                                                    <td class="text-end" id="summary_sub_total">0.00
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-start fw-medium">Transport Cost:
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <input type="text"
                                                                            class="form-control form-control-sm text-end"
                                                                            id="transport_cost" name="transport_cost"
                                                                            placeholder="0.00"
                                                                            style="max-width: 120px; display: inline-block;">
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-start fw-medium">Customer Deposit:
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <input type="text"
                                                                            class="form-control form-control-sm text-end"
                                                                            id="custom_deposit" name="custom_deposit"
                                                                            placeholder="0.00"
                                                                            style="max-width: 120px; display: inline-block;">
                                                                        <br><small class="text-muted">Calculated: <span
                                                                                id="calculated_deposit_display"
                                                                                class="fw-bold">0.00</span></small>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-start fw-medium">Customer Refund Balance:
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span id="customer_refund_balance" class="fw-bold text-success">0.00</span>
                                                                        <span id="customer_refund_badge" class="ms-2"></span>
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
                            <div class="d-flex justify-content-end mb-2">
                                <div class="input-group" style="max-width: 280px;">
                                    <span class="input-group-text"><i class="uil uil-search"></i></span>
                                    <input type="text" id="equipmentRentSearchInput" class="form-control" placeholder="Search by bill / customer" autocomplete="off">
                                </div>
                            </div>
                            <table id="equipmentRentTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Bill</th>
                                        <th>Customer</th>
                                        <th>Rental Date</th>
                                        <th>Received Date</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="equipmentRentTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Returned Bills Modal -->
    <div id="ReturnedBillsModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
        aria-labelledby="ReturnedBillsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ReturnedBillsModalLabel">Returned Bills</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                <div class="fw-semibold">Search and view returned bills</div>
                                <div class="input-group" style="max-width: 340px;">
                                    <span class="input-group-text"><i class="uil uil-search"></i></span>
                                    <input type="text" id="returnedBillsSearchInput" class="form-control" placeholder="Search by bill number" autocomplete="off">
                                </div>
                            </div>

                            <table id="returnedBillsTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Bill</th>
                                        <th>Customer</th>
                                        <th>Rental Date</th>
                                        <th>Received Date</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="returnedBillsTableBody"></tbody>
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
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                <div class="fw-semibold">Click a customer to select</div>
                                <div class="input-group" style="max-width: 340px;">
                                    <span class="input-group-text"><i class="uil uil-search"></i></span>
                                    <input type="text" id="customerSearchInput" class="form-control" placeholder="Search by name / NIC / mobile" autocomplete="off">
                                </div>
                            </div>

                            <table id="customerSelectTable" class="table table-bordered table-hover w-100 mb-0">
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
                                <tbody>
                                </tbody>
                            </table>
                            <div id="customerTableStatus" class="text-muted small mt-2"></div>
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
                                        <th style="width: 30px;"></th>
                                        <th>#</th>
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

    <!-- Equipment Return Modal -->
    <div id="returnModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="returnModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnModalLabel">Process Equipment Return</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="returnItemInfo"></div>

                    <form id="returnForm">
                        <input type="hidden" id="return_rent_item_id">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="return_date" class="form-label">Return Date <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control date-picker" id="return_date" placeholder="YYYY-MM-DD" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="return_time" class="form-label">Return Time <span
                                            class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="return_time" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="return_qty" class="form-label">Return Quantity <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="return_qty" min="1" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="damage_amount" class="form-label">Damage Amount (Rs.)</label>
                                    <input type="number" class="form-control" id="damage_amount" min="0" step="0.01"
                                        value="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="return_remark" class="form-label">Remark</label>
                                    <textarea class="form-control" id="return_remark" rows="2"
                                        placeholder="Enter any notes about this return..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="after_9am_extra_day" value="1">
                                        <label class="form-check-label" for="after_9am_extra_day">
                                            Return after 9:00 AM (count extra day)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="extra_day_amount" class="form-label">Extra Day Amount (Rs.)</label>
                                    <input type="number" class="form-control" id="extra_day_amount" min="0" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-4" id="penaltySection" style="display: none;">
                                <div class="mb-3">
                                    <label for="penalty_percentage" class="form-label text-danger">Penalty % (10-20%)</label>
                                    <input type="number" class="form-control" id="penalty_percentage" min="10" max="20" step="1" value="10" disabled>
                                    <small class="text-muted">Only applies when return is late</small>
                                </div>
                            </div>
                        </div>

                        <div id="settlementPreview" style="display: none;"></div>
                        <div id="previousReturns" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveReturnBtn">
                        <i class="uil uil-check me-1"></i> Process Return
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Return All Items Modal -->
    <div id="returnAllModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="returnAllModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnAllModalLabel">Return All Items</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="returnAllForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="return_all_date" class="form-label">Return Date <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control date-picker" id="return_all_date" placeholder="YYYY-MM-DD" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="return_all_time" class="form-label">Return Time <span
                                            class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="return_all_time" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="return_all_after_9am" value="1">
                                        <label class="form-check-label" for="return_all_after_9am">
                                            Return after 9:00 AM (count extra day)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="returnAllPreview" class="alert alert-info" style="display: none;">
                            <h6><strong>Return Summary:</strong></h6>
                            <div id="returnAllPreviewContent"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmReturnAllBtn">
                        <i class="uil uil-check me-1"></i> Confirm Return All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div id="AddCustomerModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="AddCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" style="max-width: 95%;">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="AddCustomerModalLabel"><i class="uil uil-user-plus me-2"></i>Add New Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <form id="modal-customer-form" autocomplete="off">
                        <!-- Customer Details Section -->
                        <div class="mb-4">
                            <div class="card-header mb-3" style="background-color: rgba(80, 141, 218, 0.15);">
                                <h5 class="card-title font-size-16 mb-1"><i class="mdi mdi-account-circle me-2"></i>Customer Details</h5>
                                <p class="text-muted text-truncate mb-0">Fill all information below to add customer details</p>
                            </div>
                            <div class="row">
                                <div class="col-12 col-md-8 col-lg-5">
                                    <label for="modal_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-group mb-3">
                                        <input id="modal_name" name="modal_name" type="text" class="form-control" placeholder="Enter full name" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="openModalCamera('modal_customer_photo', 1)" title="Capture Customer Photo">
                                            <i class="uil uil-camera"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" type="button" onclick="openModalFileUpload('modal_customer_photo', 1)" title="Upload Photo">
                                            <i class="uil uil-file-upload"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" id="modal_customer_photo_image_1" name="modal_customer_photo_image_1">
                                    <input type="file" id="modal_customer_photo_file_1" accept="image/*" style="display:none;" onchange="handleModalFileUpload('modal_customer_photo', this, 1)">
                                    <div id="modal_customer_photo_preview" class="mb-2 d-flex gap-2"></div>
                                </div>

                                <div class="col-12 col-md-6 col-lg-3">
                                    <label for="modal_nic" class="form-label">NIC</label>
                                    <div class="input-group mb-3">
                                        <input id="modal_nic" name="modal_nic" type="text" class="form-control" placeholder="Enter NIC number" maxlength="12">
                                        <span class="input-group-text" id="modal-nic-status"></span>
                                        <button class="btn btn-outline-secondary" type="button" onclick="openModalCamera('modal_nic', 2)" title="Capture NIC">
                                            <i class="uil uil-camera"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" type="button" onclick="openModalFileUpload('modal_nic', 1)" title="Upload Front">
                                            <i class="uil uil-file-upload"></i> F
                                        </button>
                                        <button class="btn btn-outline-primary" type="button" onclick="openModalFileUpload('modal_nic', 2)" title="Upload Back">
                                            <i class="uil uil-file-upload"></i> B
                                        </button>
                                    </div>
                                    <input type="hidden" id="modal_nic_image_1" name="modal_nic_image_1">
                                    <input type="hidden" id="modal_nic_image_2" name="modal_nic_image_2">
                                    <input type="file" id="modal_nic_file_1" accept="image/*" style="display:none;" onchange="handleModalFileUpload('modal_nic', this, 1)">
                                    <input type="file" id="modal_nic_file_2" accept="image/*" style="display:none;" onchange="handleModalFileUpload('modal_nic', this, 2)">
                                    <div id="modal_nic_preview" class="mb-2 d-flex gap-2"></div>
                                </div>

                                <div class="col-12 col-md-3 col-lg-2">
                                    <label for="modal_mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                    <input id="modal_mobile_number" name="modal_mobile_number" type="tel" class="form-control mb-3" placeholder="Enter mobile" pattern="[0-9]{10}" maxlength="10" required>
                                </div>

                                <div class="col-12 col-md-3 col-lg-2">
                                    <label for="modal_mobile_number_2" class="form-label">WP No</label>
                                    <input id="modal_mobile_number_2" name="modal_mobile_number_2" type="tel" class="form-control mb-3" placeholder="Enter WhatsApp" pattern="[0-9]{10}" maxlength="10">
                                </div>

                                <div class="col-12 col-md-8 col-lg-4">
                                    <label for="modal_address" class="form-label">NIC Address</label>
                                    <input id="modal_address" name="modal_address" type="text" class="form-control mb-3" placeholder="Enter NIC address">
                                </div>

                                <div class="col-12 col-md-8 col-lg-4">
                                    <label for="modal_workplace_address" class="form-label">Workplace Address</label>
                                    <input id="modal_workplace_address" name="modal_workplace_address" type="text" class="form-control mb-3" placeholder="Enter workplace address">
                                </div>

                                <div class="col-6 col-md-4 col-lg-2 d-flex align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="modal_is_company" name="modal_is_company" onchange="toggleModalCompanyFields()">
                                        <label class="form-check-label" for="modal_is_company">If Company</label>
                                    </div>
                                </div>

                                <div id="modal_company_fields" class="col-12" style="display: none;">
                                    <div class="row mt-2">
                                        <div class="col-12 col-md-6">
                                            <label for="modal_company_name" class="form-label">Company Name</label>
                                            <input id="modal_company_name" name="modal_company_name" type="text" class="form-control mb-3" placeholder="Enter company name">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="modal_po_document_name" class="form-label">Company Document (PO/Letterhead)</label>
                                            <div class="input-group mb-3">
                                                <input id="modal_po_document_name" type="text" class="form-control" placeholder="No file selected" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="openModalCamera('modal_po_document', 1)" title="Capture">
                                                    <i class="uil uil-camera"></i>
                                                </button>
                                                <button class="btn btn-outline-primary" type="button" onclick="openModalFileUpload('modal_po_document', 1)" title="Upload">
                                                    <i class="uil uil-file-upload"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" id="modal_po_document_image_1" name="modal_po_document_image_1">
                                            <input type="file" id="modal_po_document_file_1" accept=".pdf,image/*" style="display:none;" onchange="handleModalFileUpload('modal_po_document', this, 1)">
                                            <div id="modal_po_document_preview" class="d-flex gap-2 flex-wrap"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Details Section -->
                        <div class="mb-4">
                            <div class="card-header mb-3" style="background-color: rgba(52, 195, 143, 0.15);">
                                <h5 class="card-title font-size-16 mb-1"><i class="mdi mdi-cash-multiple me-2"></i>Billing Details</h5>
                                <p class="text-muted text-truncate mb-0">Fill all information below to add billing details</p>
                            </div>
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <label for="modal_water_bill_no" class="form-label">Utility Bill Number (Water/Electricity)</label>
                                    <div class="input-group mb-3">
                                        <input id="modal_water_bill_no" name="modal_water_bill_no" type="text" class="form-control" placeholder="Enter utility bill number">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openModalCamera('modal_water_bill', 1)" title="Capture">
                                            <i class="uil uil-camera"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" type="button" onclick="openModalFileUpload('modal_water_bill', 1)" title="Upload">
                                            <i class="uil uil-file-upload"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" id="modal_water_bill_image_1" name="modal_water_bill_image_1">
                                    <input type="file" id="modal_water_bill_file_1" accept=".pdf,image/*" style="display:none;" onchange="handleModalFileUpload('modal_water_bill', this, 1)">
                                    <div id="modal_water_bill_preview" class="d-flex gap-2 flex-wrap"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Guarantee Details Section -->
                        <div class="mb-4">
                            <div class="card-header mb-3" style="background-color: rgba(241, 180, 76, 0.15);">
                                <h5 class="card-title font-size-16 mb-1"><i class="mdi mdi-account-check me-2"></i>Guarantee Details</h5>
                                <p class="text-muted text-truncate mb-0">Fill all information below to add guarantee details</p>
                            </div>
                            <div class="row">
                                <div class="col-12 col-md-4">
                                    <label for="modal_guarantor_name" class="form-label">Guarantor Name</label>
                                    <div class="input-group mb-3">
                                        <input id="modal_guarantor_name" name="modal_guarantor_name" type="text" class="form-control" placeholder="Enter guarantor name">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openModalCamera('modal_guarantor_photo', 1)" title="Capture">
                                            <i class="uil uil-camera"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" type="button" onclick="openModalFileUpload('modal_guarantor_photo', 1)" title="Upload">
                                            <i class="uil uil-file-upload"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" id="modal_guarantor_photo_image_1" name="modal_guarantor_photo_image_1">
                                    <input type="file" id="modal_guarantor_photo_file_1" accept="image/*" style="display:none;" onchange="handleModalFileUpload('modal_guarantor_photo', this, 1)">
                                    <div id="modal_guarantor_photo_preview" class="mb-2 d-flex gap-2"></div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="modal_guarantor_nic" class="form-label">Guarantor NIC</label>
                                    <div class="input-group mb-3">
                                        <input id="modal_guarantor_nic" name="modal_guarantor_nic" type="text" class="form-control" placeholder="Enter guarantor NIC" maxlength="12">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openModalCamera('modal_guarantor_nic', 2)" title="Capture">
                                            <i class="uil uil-camera"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" type="button" onclick="openModalFileUpload('modal_guarantor_nic', 1)" title="Upload Front">
                                            <i class="uil uil-file-upload"></i> F
                                        </button>
                                        <button class="btn btn-outline-primary" type="button" onclick="openModalFileUpload('modal_guarantor_nic', 2)" title="Upload Back">
                                            <i class="uil uil-file-upload"></i> B
                                        </button>
                                    </div>
                                    <input type="hidden" id="modal_guarantor_nic_image_1" name="modal_guarantor_nic_image_1">
                                    <input type="hidden" id="modal_guarantor_nic_image_2" name="modal_guarantor_nic_image_2">
                                    <input type="file" id="modal_guarantor_nic_file_1" accept="image/*" style="display:none;" onchange="handleModalFileUpload('modal_guarantor_nic', this, 1)">
                                    <input type="file" id="modal_guarantor_nic_file_2" accept="image/*" style="display:none;" onchange="handleModalFileUpload('modal_guarantor_nic', this, 2)">
                                    <div id="modal_guarantor_nic_preview" class="mb-2 d-flex gap-2"></div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="modal_guarantor_address" class="form-label">Guarantor Address</label>
                                    <input id="modal_guarantor_address" name="modal_guarantor_address" type="text" class="form-control mb-3" placeholder="Enter guarantor address">
                                </div>
                            </div>
                        </div>

                        <!-- Remark -->
                        <div class="row">
                            <div class="col-12">
                                <label for="modal_remark" class="form-label">Remark Note</label>
                                <textarea id="modal_remark" name="modal_remark" class="form-control" rows="2" placeholder="Enter any remarks or notes about the customer..."></textarea>
                            </div>
                        </div>

                        <input type="hidden" id="modal_category" name="modal_category" value="1" />
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveCustomerBtn">
                        <i class="uil uil-save me-1"></i> Save Customer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Camera Capture Modal (Add Customer) -->
    <div class="modal fade" id="AddCustomerCameraModal" tabindex="-1" aria-labelledby="AddCustomerCameraLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="AddCustomerCameraLabel"><i class="uil uil-camera me-2"></i>Capture Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="stopModalCamera()"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <p class="text-muted mb-2" id="modalCaptureInstructions">Capture image <span id="modalCurrentImageNum">1</span> of <span id="modalTotalImageNum">1</span></p>
                    </div>

                    <div class="camera-container text-center mb-3">
                        <video id="modalCameraStream" autoplay playsinline style="width: 100%; max-height: 400px; border-radius: 8px; background: #000;"></video>
                        <canvas id="modalCaptureCanvas" style="display: none;"></canvas>
                    </div>

                    <div class="text-center mb-3">
                        <button type="button" class="btn btn-primary btn-lg" id="modalCaptureBtn" onclick="captureModalImage()">
                            <i class="uil uil-capture me-1"></i> Capture
                        </button>
                        <button type="button" class="btn btn-secondary" id="modalSwitchCameraBtn" onclick="switchModalCamera()">
                            <i class="uil uil-sync me-1"></i> Switch Camera
                        </button>
                    </div>

                    <div class="row" id="modalCapturedImagesContainer">
                        <div class="col-6 text-center" id="modalCapturedImage1Container" style="display: none;">
                            <h6>Image 1 (Front)</h6>
                            <img id="modalCapturedImage1" src="" class="img-fluid rounded border" style="max-height: 150px;">
                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeModalImage(1)">
                                <i class="uil uil-trash"></i> Remove
                            </button>
                        </div>
                        <div class="col-6 text-center" id="modalCapturedImage2Container" style="display: none;">
                            <h6>Image 2 (Back)</h6>
                            <img id="modalCapturedImage2" src="" class="img-fluid rounded border" style="max-height: 150px;">
                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeModalImage(2)">
                                <i class="uil uil-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="stopModalCamera()">Cancel</button>
                    <button type="button" class="btn btn-success" id="modalSaveImagesBtn" onclick="saveModalImages()" disabled>
                        <i class="uil uil-check me-1"></i> Save Images
                    </button>
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
    <script src="ajax/js/equipment-rent-return.js"></script>

    <!-- Page Preloader Script -->
    <script>
        $(window).on('load', function () {
            $('#page-preloader').fadeOut('slow', function () {
                $(this).remove();
            });
        });
    </script>

    <!-- Customer Modal JavaScript -->
    <script>
        // Camera globals
        let modalCurrentStream = null;
        let modalCurrentField = '';
        let modalMaxImages = 1;
        let modalCapturedImages = [];
        let modalCameraFacing = 'environment';

        // Toggle company fields in modal
        function toggleModalCompanyFields() {
            const isCompany = document.getElementById('modal_is_company').checked;
            const companyFields = document.getElementById('modal_company_fields');
            
            if (isCompany) {
                companyFields.style.display = 'block';
            } else {
                companyFields.style.display = 'none';
                $('#modal_company_name').val('');
                $('#modal_po_document_image_1').val('');
                $('#modal_po_document_name').val('');
                $('#modal_po_document_preview').empty();
            }
        }

        // File upload functions for modal
        function openModalFileUpload(fieldName, index = 1) {
            const fileInput = document.getElementById(`${fieldName}_file_${index}`);
            if (fileInput) {
                fileInput.click();
            }
        }

        function handleModalFileUpload(fieldName, input, index = 1) {
            const file = input.files[0];
            if (!file) return;

            const maxSize = 5 * 1024 * 1024; // 5MB limit
            if (file.size > maxSize) {
                swal({
                    title: "File Too Large",
                    text: "File size must be less than 5MB",
                    type: "error"
                });
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const base64Data = e.target.result;
                
                // Store in hidden input
                $(`#${fieldName}_image_${index}`).val(base64Data);
                
                // Update document name if exists
                if ($(`#${fieldName}_name`).length) {
                    $(`#${fieldName}_name`).val(file.name);
                }
                
                // Update preview
                refreshModalPreviews(fieldName);
                
                swal({
                    title: "Success!",
                    text: "File uploaded successfully!",
                    type: "success",
                    timer: 1500,
                    showConfirmButton: false
                });
            };
            
            reader.readAsDataURL(file);
        }

        function refreshModalPreviews(fieldName) {
            const previewContainer = $(`#${fieldName}_preview`);
            previewContainer.empty();
            
            [1, 2].forEach(index => {
                const hiddenInput = $(`#${fieldName}_image_${index}`);
                if (hiddenInput.length && hiddenInput.val()) {
                    const base64Data = hiddenInput.val();
                    const isPdf = base64Data.startsWith('data:application/pdf');
                    
                    let previewHtml = '';
                    if (isPdf) {
                        let filename = "Document " + index;
                        if ($(`#${fieldName}_name`).length) filename = $(`#${fieldName}_name`).val();
                        
                        previewHtml = `
                            <div class="position-relative border rounded p-2" style="background: #f8f9fa;">
                                <div class="d-flex align-items-center">
                                    <i class="uil uil-file-alt text-danger" style="font-size: 24px;"></i>
                                    <div class="ms-2">
                                        <small class="d-block text-truncate" style="max-width: 120px;">${filename}</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger ms-2" onclick="removeModalFileUpload('${fieldName}', ${index})">
                                        <i class="uil uil-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        previewHtml = `
                            <div class="position-relative" style="width: 60px;">
                                <img src="${base64Data}" class="img-fluid rounded border" style="width: 60px; height: 40px; object-fit: cover;">
                                <span class="badge bg-primary position-absolute" style="top: -5px; right: -5px; font-size: 10px;">${index}</span>
                                <button type="button" class="btn btn-sm btn-link text-danger position-absolute" style="top: -10px; right: -10px; padding: 0;" onclick="removeModalFileUpload('${fieldName}', ${index})">
                                    <i class="uil uil-times-circle"></i>
                                </button>
                            </div>
                        `;
                    }
                    previewContainer.append(previewHtml);
                }
            });
        }

        function removeModalFileUpload(fieldName, index = 1) {
            $(`#${fieldName}_image_${index}`).val('');
            $(`#${fieldName}_file_${index}`).val('');
            refreshModalPreviews(fieldName);
        }

        // Camera functions for modal
        function openModalCamera(fieldName, numImages) {
            modalCurrentField = fieldName;
            modalMaxImages = numImages;
            modalCapturedImages = [];

            $('#modalCurrentImageNum').text('1');
            $('#modalTotalImageNum').text(numImages);
            $('#modalCapturedImage1Container, #modalCapturedImage2Container').hide();
            $('#modalCapturedImage1, #modalCapturedImage2').attr('src', '');
            $('#modalSaveImagesBtn').prop('disabled', true);

            if (numImages === 1) {
                $('#modalCapturedImagesContainer .col-6').removeClass('col-6').addClass('col-12');
                $('#modalCapturedImage1Container h6').text('Captured Image');
            } else {
                $('#modalCapturedImagesContainer .col-12').removeClass('col-12').addClass('col-6');
                $('#modalCapturedImage1Container h6').text('Image 1 (Front)');
            }

            const modal = new bootstrap.Modal(document.getElementById('AddCustomerCameraModal'));
            modal.show();
            startModalCamera();
        }

        async function startModalCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                let msg = "Camera API is not supported in this browser.";
                if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                    msg = "Camera access requires HTTPS when accessing via network/IP. Please use HTTPS or access via localhost.";
                }
                swal({
                    title: "Camera Error",
                    text: msg,
                    type: "error"
                });
                return;
            }

            try {
                stopModalCamera();

                const constraints = {
                    video: {
                        facingMode: modalCameraFacing,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                };

                modalCurrentStream = await navigator.mediaDevices.getUserMedia(constraints);
                const videoElement = document.getElementById('modalCameraStream');
                videoElement.srcObject = modalCurrentStream;
                videoElement.play().catch(e => console.error("Error playing video:", e));
            } catch (err) {
                console.error('Error accessing camera:', err);

                // Fallback to basic constraints
                if (err.name === 'OverconstrainedError' || err.name === 'NotReadableError') {
                    startModalCameraWithBasicConstraints();
                    return;
                }

                let errorMessage = "Unable to access camera. Please ensure permissions are granted.";

                if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                    errorMessage = "Camera access requires a secure HTTPS connection when accessing via IP address. If you are using a mobile phone, please connect via HTTPS.";
                } else if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    errorMessage = "Camera permission denied. Please allow access.";
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    errorMessage = "No camera device found.";
                } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                    errorMessage = "Camera is already in use by another application.";
                }

                swal({
                    title: "Camera Error",
                    text: errorMessage,
                    type: "error"
                });
            }
        }

        async function startModalCameraWithBasicConstraints() {
            try {
                const constraints = {
                    video: { facingMode: modalCameraFacing }
                };
                modalCurrentStream = await navigator.mediaDevices.getUserMedia(constraints);
                const videoElement = document.getElementById('modalCameraStream');
                videoElement.srcObject = modalCurrentStream;
                videoElement.play().catch(e => console.error("Error playing video:", e));
            } catch (e) {
                console.error("Basic camera fallback failed", e);
                swal({
                    title: "Camera Error",
                    text: "Unable to start camera even with basic settings. Name: " + e.name,
                    type: "error"
                });
            }
        }

        function stopModalCamera() {
            if (modalCurrentStream) {
                modalCurrentStream.getTracks().forEach(track => track.stop());
                modalCurrentStream = null;
            }
        }

        function switchModalCamera() {
            modalCameraFacing = modalCameraFacing === 'environment' ? 'user' : 'environment';
            startModalCamera();
        }

        function captureModalImage() {
            const video = document.getElementById('modalCameraStream');
            const canvas = document.getElementById('modalCaptureCanvas');
            const ctx = canvas.getContext('2d');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            const imageNum = modalCapturedImages.length + 1;

            if (imageNum <= modalMaxImages) {
                modalCapturedImages.push(imageData);

                $(`#modalCapturedImage${imageNum}`).attr('src', imageData);
                $(`#modalCapturedImage${imageNum}Container`).show();

                if (modalCapturedImages.length < modalMaxImages) {
                    $('#modalCurrentImageNum').text(modalCapturedImages.length + 1);
                } else {
                    $('#modalCaptureInstructions').html('<span class="text-success"><i class="uil uil-check-circle"></i> All images captured!</span>');
                }

                if (modalCapturedImages.length >= modalMaxImages) {
                    $('#modalSaveImagesBtn').prop('disabled', false);
                }
            }
        }

        function removeModalImage(imageNum) {
            modalCapturedImages.splice(imageNum - 1, 1);

            if (imageNum === 1 && modalCapturedImages.length > 0) {
                $('#modalCapturedImage1').attr('src', modalCapturedImages[0]);
                $('#modalCapturedImage2').attr('src', '');
                $('#modalCapturedImage2Container').hide();
            } else {
                $(`#modalCapturedImage${imageNum}`).attr('src', '');
                $(`#modalCapturedImage${imageNum}Container`).hide();
            }

            $('#modalCurrentImageNum').text(modalCapturedImages.length + 1);
            $('#modalCaptureInstructions').html(`Capture image <span id="modalCurrentImageNum">${modalCapturedImages.length + 1}</span> of <span id="modalTotalImageNum">${modalMaxImages}</span>`);

            $('#modalSaveImagesBtn').prop('disabled', true);
        }

        function saveModalImages() {
            modalCapturedImages.forEach((imageData, index) => {
                $(`#${modalCurrentField}_image_${index + 1}`).val(imageData);
            });

            refreshModalPreviews(modalCurrentField);
            stopModalCamera();
            bootstrap.Modal.getInstance(document.getElementById('AddCustomerCameraModal')).hide();

            swal({
                title: "Success!",
                text: `${modalCapturedImages.length} image(s) captured successfully!`,
                type: "success",
                timer: 2000,
                showConfirmButton: false
            });
        }

        // Save customer from modal
        $('#saveCustomerBtn').on('click', function() {
            const form = $('#modal-customer-form');
            
            // Basic validation
            const name = $('#modal_name').val().trim();
            const mobile = $('#modal_mobile_number').val().trim();
            
            if (!name) {
                swal({
                    title: "Validation Error",
                    text: "Please enter customer name",
                    type: "error"
                });
                return;
            }
            
            if (!mobile) {
                swal({
                    title: "Validation Error",
                    text: "Please enter mobile number",
                    type: "error"
                });
                return;
            }
            
            if (mobile.length !== 10) {
                swal({
                    title: "Validation Error",
                    text: "Mobile number must be 10 digits",
                    type: "error"
                });
                return;
            }
            
            // Show loading while getting next code
            swal({
                title: "Preparing...",
                text: "Please wait",
                type: "info",
                showConfirmButton: false,
                allowOutsideClick: false
            });
            
            // Get next customer code from server
            $.ajax({
                url: 'ajax/php/customer-master.php',
                type: 'POST',
                data: { get_next_code: 1 },
                dataType: 'json',
                success: function(codeResponse) {
                    if (codeResponse.status === 'success' && codeResponse.code) {
                        saveCustomerWithCode(codeResponse.code);
                    } else {
                        swal({
                            title: "Error!",
                            text: "Failed to generate customer code",
                            type: "error"
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error status:', status, 'error:', error);
                    console.error('Response:', xhr.responseText);
                    swal({
                        title: "Error!",
                        text: "Failed to generate customer code",
                        type: "error"
                    });
                }
            });
        });

        function saveCustomerWithCode(generatedCode) {
            const formData = {
                code: generatedCode,
                name: $('#modal_name').val().toUpperCase(),
                nic: $('#modal_nic').val(),
                mobile_number: $('#modal_mobile_number').val(),
                mobile_number_2: $('#modal_mobile_number_2').val(),
                address: $('#modal_address').val().toUpperCase(),
                workplace_address: $('#modal_workplace_address').val().toUpperCase(),
                is_company: $('#modal_is_company').is(':checked') ? 1 : 0,
                company_name: $('#modal_company_name').val().toUpperCase(),
                water_bill_no: $('#modal_water_bill_no').val(),
                guarantor_name: $('#modal_guarantor_name').val().toUpperCase(),
                guarantor_nic: $('#modal_guarantor_nic').val(),
                guarantor_address: $('#modal_guarantor_address').val().toUpperCase(),
                remark: $('#modal_remark').val(),
                old_outstanding: 0,
                category: 1,
                // Images
                customer_photo_image_1: $('#modal_customer_photo_image_1').val(),
                nic_image_1: $('#modal_nic_image_1').val(),
                nic_image_2: $('#modal_nic_image_2').val(),
                po_document_image_1: $('#modal_po_document_image_1').val(),
                water_bill_image_1: $('#modal_water_bill_image_1').val(),
                guarantor_photo_image_1: $('#modal_guarantor_photo_image_1').val(),
                guarantor_nic_image_1: $('#modal_guarantor_nic_image_1').val(),
                guarantor_nic_image_2: $('#modal_guarantor_nic_image_2').val()
            };
            
            // Show loading
            swal({
                title: "Saving...",
                text: "Please wait while we save the customer",
                type: "info",
                showConfirmButton: false,
                allowOutsideClick: false
            });
            
            // Send AJAX request
            $.ajax({
                url: 'ajax/php/customer-master.php',
                type: 'POST',
                data: {
                    create: 1,
                    ...formData
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const returned = response.data || {};
                        swal({
                            title: "Success!",
                            text: "Customer added successfully",
                            type: "success",
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Set the customer in the main form with code and name
                        $('#customer_id').val(returned.id || '');
                        const displayCode = returned.code || formData.code || '';
                        const displayName = returned.name || formData.name || '';
                        $('#customer_display').val(displayCode + (displayName ? ' - ' + displayName : ''));

                        // Clear modal form
                        $('#modal-customer-form')[0].reset();
                        $('#modal_company_fields').hide();
                        $('[id^="modal_"][id$="_preview"]').empty();
                        $('[id^="modal_"][id$="_image_1"]').val('');
                        $('[id^="modal_"][id$="_image_2"]').val('');
                        
                        // Close modal
                        $('#AddCustomerModal').modal('hide');
                        
                        // Reload customer table if it exists
                        if (typeof loadCustomers === 'function') {
                            loadCustomers();
                        }
                    } else {
                        swal({
                            title: "Error!",
                            text: response.message || "Failed to save customer",
                            type: "error"
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error status:', status, 'error:', error);
                    console.error('Response:', xhr.responseText);
                    swal({
                        title: "Error!",
                        text: "An error occurred while saving the customer",
                        type: "error"
                    });
                }
            });
        }

        // Clear modal when it's closed
        $('#AddCustomerModal').on('hidden.bs.modal', function() {
            $('#modal-customer-form')[0].reset();
            $('#modal_company_fields').hide();
            $('[id^="modal_"][id$="_preview"]').empty();
            $('[id^="modal_"][id$="_image_1"]').val('');
            $('[id^="modal_"][id$="_image_2"]').val('');
        });

        // Stop camera when capture modal closes
        document.getElementById('AddCustomerCameraModal').addEventListener('hidden.bs.modal', function() {
            stopModalCamera();
        });
    </script>

</body>

</html>