<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$TRIP_MANAGEMENT = new TripManagement(null);
$lastId = $TRIP_MANAGEMENT->getLastID();
$trip_number = TripManagement::formatTripNumber($lastId + 1);

// Get vehicles for dropdown
$VEHICLE = new Vehicle(null);
$vehicles = $VEHICLE->all();

// Get employees for dropdown
$EMPLOYEE = new EmployeeMaster(null);
$employees = $EMPLOYEE->all();

// Get customers for dropdown
$CUSTOMER = new CustomerMaster(null);
$customers = $CUSTOMER->all();
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Trip Management | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
    <style>
        .section-card {
            border-left: 4px solid #3b5de7;
            transition: all 0.3s ease;
        }
        .section-card.section-success {
            border-left-color: #34c38f;
        }
        .section-card.section-warning {
            border-left-color: #f1b44c;
        }
        .section-card.section-info {
            border-left-color: #50a5f1;
        }
        .radio-group .form-check {
            display: inline-block;
            margin-right: 20px;
        }
        .radio-group .form-check-input:checked + .form-check-label {
            color: #3b5de7;
            font-weight: 600;
        }
        .conditional-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .conditional-section.show {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .trip-step-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .section-submit-row {
            border-top: 1px solid #eff2f7;
            padding-top: 16px;
            margin-top: 16px;
            text-align: right;
        }
    </style>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                            <a href="#" class="btn btn-success" id="new">
                                <i class="uil uil-plus me-1"></i> New
                            </a>
                            <?php if ($PERMISSIONS['delete_page']): ?>
                            <a href="#" class="btn btn-danger" id="delete-trip">
                                <i class="uil uil-trash-alt me-1"></i> Delete
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">TRIP MANAGEMENT</li>
                            </ol>
                        </div>
                    </div>

                    <form id="form-data" autocomplete="off">
                        <input type="hidden" id="id" name="id" value="0">

                        <!-- Step 1: Trip Category -->
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card section-card">
                                    <div class="p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="trip-step-badge bg-soft-primary text-primary">01</div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="font-size-16 mb-1">Trip Category</h5>
                                                <p class="text-muted text-truncate mb-0">Select the type of trip</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 pt-0">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Trip Number</label>
                                                <input id="trip_number" name="trip_number" type="text"
                                                    value="<?php echo $trip_number ?>" class="form-control" readonly>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Trip Category <span class="text-danger">*</span></label>
                                                <div class="radio-group mt-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="trip_category"
                                                            id="cat_internal" value="internal" checked>
                                                        <label class="form-check-label" for="cat_internal">
                                                            <i class="uil uil-building me-1"></i> Internal Trip
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="trip_category"
                                                            id="cat_customer" value="customer">
                                                        <label class="form-check-label" for="cat_customer">
                                                            <i class="uil uil-users-alt me-1"></i> Customer Trip
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Invoice Type (Customer Trips only) -->
                        <div class="row conditional-section" id="section-invoice-type">
                            <div class="col-lg-12">
                                <div class="card section-card section-warning">
                                    <div class="p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="trip-step-badge bg-soft-warning text-warning">02</div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="font-size-16 mb-1">Invoice Type</h5>
                                                <p class="text-muted text-truncate mb-0">Select invoice or non-invoice</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 pt-0">
                                        <div class="radio-group">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="invoice_type"
                                                    id="inv_invoice" value="invoice">
                                                <label class="form-check-label" for="inv_invoice">
                                                    <i class="uil uil-invoice me-1"></i> Invoice
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="invoice_type"
                                                    id="inv_non_invoice" value="non_invoice">
                                                <label class="form-check-label" for="inv_non_invoice">
                                                    <i class="uil uil-file-alt me-1"></i> Non-Invoice
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2a: Invoice - Bill Selection -->
                        <div class="row conditional-section" id="section-bill">
                            <div class="col-lg-12">
                                <div class="card section-card section-info">
                                    <div class="p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="trip-step-badge bg-soft-info text-info">
                                                    <i class="uil uil-invoice"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="font-size-16 mb-1">Bill Selection</h5>
                                                <p class="text-muted text-truncate mb-0">Select the bill number for this trip</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 pt-0">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="bill_id" class="form-label">Bill Number <span class="text-danger">*</span></label>
                                                <select id="bill_id" name="bill_id" class="form-control select2">
                                                    <option value="">-- Select Bill --</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Customer</label>
                                                <input id="bill_customer_name" type="text" class="form-control" readonly>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Transport Info</label>
                                                <div id="bill_transport_info" class="text-muted small mt-2">
                                                    Select a bill to view transport details
                                                </div>
                                            </div>
                                        </div>
                                        <div class="section-submit-row">
                                            <a href="#" class="btn btn-primary btn-sm" id="submit-bill-section">
                                                <i class="uil uil-check me-1"></i> Confirm Bill Selection
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2b: Non-Invoice - Customer & Transport -->
                        <div class="row conditional-section" id="section-customer-transport">
                            <div class="col-lg-12">
                                <div class="card section-card section-info">
                                    <div class="p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="trip-step-badge bg-soft-info text-info">
                                                    <i class="uil uil-user"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="font-size-16 mb-1">Customer & Transport</h5>
                                                <p class="text-muted text-truncate mb-0">Select customer and add transport details</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 pt-0">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                                <select id="customer_id" name="customer_id" class="form-control select2">
                                                    <option value="">-- Select Customer --</option>
                                                    <?php foreach ($customers as $customer): ?>
                                                        <option value="<?php echo $customer['id']; ?>">
                                                            <?php echo htmlspecialchars($customer['code'] . ' - ' . $customer['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="transport_amount" class="form-label">Transport Amount</label>
                                                <input id="transport_amount" name="transport_amount" type="number" step="0.01"
                                                    placeholder="0.00" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="pay_amount" class="form-label">Transport Cost</label>
                                                <input id="pay_amount" name="pay_amount" type="number" step="0.01"
                                                    placeholder="0.00" class="form-control">
                                            </div>
                                        </div>
                                        <div class="section-submit-row">
                                            <a href="#" class="btn btn-primary btn-sm" id="submit-customer-section">
                                                <i class="uil uil-check me-1"></i> Confirm Customer & Transport
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Vehicle & Start Meter -->
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card section-card section-success">
                                    <div class="p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="trip-step-badge bg-soft-success text-success">03</div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="font-size-16 mb-1">Vehicle & Meter</h5>
                                                <p class="text-muted text-truncate mb-0">Select vehicle and enter start meter reading</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 pt-0">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="vehicle_id" class="form-label">Vehicle <span class="text-danger">*</span></label>
                                                <select id="vehicle_id" name="vehicle_id" class="form-control select2">
                                                    <option value="">-- Select Vehicle --</option>
                                                    <?php foreach ($vehicles as $vehicle): ?>
                                                        <option value="<?php echo $vehicle['id']; ?>"
                                                            data-start_meter="<?php echo $vehicle['start_meter']; ?>">
                                                            <?php echo htmlspecialchars($vehicle['vehicle_no'] . ' - ' . $vehicle['brand'] . ' ' . $vehicle['model']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="employee_id" class="form-label">Driver</label>
                                                <select id="employee_id" name="employee_id" class="form-control select2">
                                                    <option value="">-- Select Driver --</option>
                                                    <?php foreach ($employees as $employee): ?>
                                                        <option value="<?php echo $employee['id']; ?>">
                                                            <?php echo htmlspecialchars($employee['code'] . ' - ' . $employee['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="start_meter" class="form-label">Start Meter <span class="text-danger">*</span></label>
                                                <input id="start_meter" name="start_meter" type="number" step="0.01"
                                                    placeholder="0.00" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="remark" class="form-label">Remark</label>
                                                <input id="remark" name="remark" type="text"
                                                    placeholder="Enter Remark" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-3">
                                                <label for="start_location" class="form-label">Start Location</label>
                                                <input id="start_location" name="start_location" type="text"
                                                    placeholder="Enter Start Location" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="end_location" class="form-label">End Location</label>
                                                <input id="end_location" name="end_location" type="text"
                                                    placeholder="Enter End Location" class="form-control">
                                            </div>
                                        </div>
                                        <div class="section-submit-row">
                                            <a href="#" class="btn btn-primary" id="create">
                                                <i class="uil uil-save me-1"></i> Save Trip
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: End Meter & Trip Details (shown after initial save) -->
                        <div class="row conditional-section" id="section-trip-details">
                            <div class="col-lg-12">
                                <div class="card section-card" style="border-left-color: #556ee6;">
                                    <div class="p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="trip-step-badge bg-soft-primary text-primary">04</div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="font-size-16 mb-1">Trip Completion Details</h5>
                                                <p class="text-muted text-truncate mb-0">Enter end meter reading and trip costs</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 pt-0">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="end_meter" class="form-label">End Meter <span class="text-danger">*</span></label>
                                                <input id="end_meter" name="end_meter" type="number" step="0.01"
                                                    placeholder="0.00" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="trip_type" class="form-label">Trip Type <span class="text-danger">*</span></label>
                                                <select id="trip_type" name="trip_type" class="form-control">
                                                    <option value="">-- Select Trip Type --</option>
                                                    <option value="single">Single</option>
                                                    <option value="return">Return</option>
                                                    <option value="back_and_forth">Back & Forth</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="customer_fuel_cost" class="form-label">Customer Fuel Cost</label>
                                                <input id="customer_fuel_cost" name="customer_fuel_cost" type="number" step="0.01"
                                                    placeholder="0.00" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="toll" class="form-label">Toll</label>
                                                <input id="toll" name="toll" type="number" step="0.01"
                                                    placeholder="0.00" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-3">
                                                <label for="helper_payment" class="form-label">Helper Payment</label>
                                                <input id="helper_payment" name="helper_payment" type="number" step="0.01"
                                                    placeholder="0.00" class="form-control">
                                            </div>
                                            <div class="col-md-3 conditional-section" id="section-pay-amount">
                                                <label for="pay_amount_completion" class="form-label">Transport Cost</label>
                                                <input id="pay_amount_completion" name="pay_amount" type="number" step="0.01"
                                                    placeholder="0.00" class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="total_cost" class="form-label">Total Cost</label>
                                                <input id="total_cost" name="total_cost" type="number" step="0.01"
                                                    placeholder="0.00" class="form-control bg-light fw-bold" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-3">
                                                <label for="payment_method" class="form-label">Payment Method</label>
                                                <select id="payment_method" name="payment_method" class="form-control">
                                                    <option value="cash">Cash</option>
                                                    <option value="credit">Credit</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 conditional-section" id="section-settlement-btn">
                                                <label class="form-label">&nbsp;</label>
                                                <div>
                                                    <button type="button" class="btn btn-info w-100" id="open-settlement-modal">
                                                        <i class="uil uil-money-bill me-1"></i> Settlements
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="section-submit-row">
                                            <a href="#" class="btn btn-warning" id="update">
                                                <i class="uil uil-edit me-1"></i> Update & Complete Trip
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
            <?php include 'footer.php' ?>
        </div>
    </div>

    <!-- Trip List Modal -->
    <div class="modal fade bs-example-modal-xl" id="tripListModal" tabindex="-1" role="dialog"
        aria-labelledby="tripListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tripListModalLabel">Manage Trips</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="tripDataTable" class="datatable table table-bordered dt-responsive nowrap"
                                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Trip No</th>
                                        <th>Category</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Driver</th>
                                        <th>Start Meter</th>
                                        <th>End Meter</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="tripTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlement Modal -->
    <div class="modal fade" id="settlementModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="uil uil-money-bill me-2"></i> Trip Settlements</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border mb-0">
                                <div class="card-body p-3 text-center">
                                    <small class="text-muted">Total Amount</small>
                                    <h4 class="mb-0" id="settle-total-amount">0.00</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border border-success mb-0">
                                <div class="card-body p-3 text-center">
                                    <small class="text-muted">Total Paid</small>
                                    <h4 class="mb-0 text-success" id="settle-total-paid">0.00</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border border-danger mb-0">
                                <div class="card-body p-3 text-center">
                                    <small class="text-muted">Remaining</small>
                                    <h4 class="mb-0 text-danger" id="settle-remaining">0.00</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Settlement Form -->
                    <div class="card bg-light border mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-success mb-3"><i class="uil uil-plus-circle me-1"></i> Add Settlement Payment</h6>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" id="settle-amount" step="0.01" class="form-control" placeholder="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" id="settle-date" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Remark</label>
                                    <input type="text" id="settle-remark" class="form-control" placeholder="Optional remark">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-success w-100" id="add-settlement">
                                        <i class="uil uil-plus me-1"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settlements Table -->
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Remark</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="settlementTableBody">
                            <tr><td colspan="5" class="text-center text-muted">No settlements recorded yet</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="ajax/js/trip-management.js"></script>
    <?php include 'main-js.php' ?>

</body>

</html>
