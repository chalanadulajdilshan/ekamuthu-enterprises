<!doctype html>
<?php
include 'class/include.php';
include './auth.php';
?>

<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Sales Invoice | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
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
                                <a href="#" class="btn btn-primary" id="payment">
                                    <i class="uil uil-save me-1"></i> Payment
                                </a>
                            <?php endif; ?>

                            <?php if ($PERMISSIONS['add_page']): ?>
                                <a href="#" class="btn btn-primary" id="save">
                                    <i class="uil uil-save me-1"></i> Save
                                </a>
                            <?php endif; ?>

                            <a href="#" class="btn btn-info" id="print" style="display: none;">
                                <i class="uil uil-print me-1"></i> Print
                            </a>

                            <?php if ($PERMISSIONS['delete_page']): ?>
                                <a href="#" class="btn btn-danger cancel-invoice" style="display: none;">
                                    <i class="uil uil-ban me-1"></i> Cancel
                                </a>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Sales Invoice </li>
                            </ol>
                        </div>
                    </div>
                    <!--- Hidden Values -->
                    <input type="hidden" id="item_id" name="item_id">
                    <input type="hidden" id="availableQty">
                    <input type="hidden" id="customer_id">

                    <!-- end page title -->

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
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <div class="me-3">
                                                <h5 class="font-size-16 mb-1">Sales Invoice </h5>
                                                <p class="text-muted text-truncate mb-0">Fill all information below to add Invoice</p>
                                            </div>
                                            <span id="cancelled-badge" class="badge bg-danger" style="font-size: 1.2rem; display: none; padding: 0.75rem 1.2rem;">
                                                <i class="uil uil-ban me-2"></i> This Invoice Already Cancelled
                                            </span>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4">
                                    <form id="form-data">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class=" ">
                                                    <label class="form-label fw-bold">Payment Type:</label><br />
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="payment_type"
                                                            id="cash" value="1" checked>
                                                        <label class="form-check-label" for="cash">Cash</label>
                                                    </div>
                                                    <?php if (!$COMPANY->getIsCredit()): ?>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="payment_type"
                                                                id="credit" value="2">
                                                            <label class="form-check-label" for="credit">Credit</label>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="InvoiceCode" class="form-label">Invoice No</label>
                                                <div class="input-group mb-3">
                                                    <input id="invoice_no" name="invoice_no" type="text"
                                                        class="form-control" readonly>
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#invoiceModal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- INVOICE ID HIDDEN -->
                                            <input type="hidden" id="invoice_id" name="invoice_id" />

                                            <!-- company ID -->
                                            <?php
                                            $COMPANY = new CompanyProfile(NULL);
                                            $isOneCompany = false;
                                            $defaultCompany = null;
                                            $activeCompanies = $COMPANY->getActiveCompany();

                                            if (count($activeCompanies) > 0) {
                                                $defaultCompany = $activeCompanies[0];
                                                $COMPANY->id = $defaultCompany['id'];
                                                $isOneCompany = $COMPANY->getIsOneCompany();
                                            }
                                            ?>
                                            <div class="col-md-3" <?php if ($isOneCompany && $defaultCompany): ?>hidden<?php endif; ?>>
                                                <label for="bankId" class="form-label" <?php if ($isOneCompany && $defaultCompany): ?>hidden<?php endif; ?>>Company</label>
                                                <div class="input-group mb-3">
                                                    <?php if ($isOneCompany && $defaultCompany): ?>
                                                        <input type="hidden" id="company_id" name="company_id" value="<?php echo $defaultCompany['id'] ?>">
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($defaultCompany['name']); ?>" hidden>
                                                    <?php else: ?>
                                                        <select id="company_id" name="company_id" class="form-select">
                                                            <?php foreach ($activeCompanies as $company): ?>
                                                                <option value="<?php echo $company['id'] ?>">
                                                                    <?php echo htmlspecialchars($company['name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <?php
                                            $DEPARTMENT_MASTER = new DepartmentMaster(NULL);
                                            $isOneCompanyDept = $DEPARTMENT_MASTER->getIsOneCompany();
                                            $activeDepartments = $DEPARTMENT_MASTER->getActiveDepartment();
                                            ?>
                                            <div class="col-md-3" <?php if ($isOneCompanyDept && count($activeDepartments) > 0): ?>hidden<?php endif; ?>>
                                                <label for="department" class="form-label" <?php if ($isOneCompanyDept && count($activeDepartments) > 0): ?>hidden<?php endif; ?>>Department</label>
                                                <div class="input-group mb-3">
                                                    <?php
                                                    if ($isOneCompanyDept && count($activeDepartments) > 0):
                                                        $defaultDepartment = $activeDepartments[0];
                                                    ?>
                                                        <input type="hidden" id="department_id" name="department_id" value="<?php echo $defaultDepartment['id'] ?>">
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($defaultDepartment['name']); ?>" hidden >
                                                    <?php else: ?>
                                                        <select id="department_id" name="department_id" class="form-select">
                                                            <?php foreach ($activeDepartments as $departments): ?>
                                                                <option value="<?php echo $departments['id'] ?>">
                                                                    <?php echo htmlspecialchars($departments['name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="department" class="form-label">Invoice Date</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" id="invoice_date" name="invoice_date" class="form-control date-picker"
                                                        value="<?php echo date('Y-m-d'); ?>"
                                                        <?php echo ($US->type == 1) ? '' : 'readonly'; ?>>
                                                </div>
                                            </div>


                                            <div class="col-md-2">
                                                <label for="customerCode" class="form-label">Customer Code</label>
                                                <div class="input-group mb-3">
                                                    <input id="customer_code" name="customer_code" type="text"
                                                        class="form-control" readonly>
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#customerModal" title="Search Customer">
                                                        <i class="uil uil-search"></i>
                                                    </button>
                                                    <?php
                                                    $hasAddCustomerPermission = false;
                                                    if (isset($_SESSION['id'])) {
                                                        $specialPermission = new SpecialUserPermission();
                                                        $hasAddCustomerPermission = $specialPermission->hasAccess($_SESSION['id'], 'add_customer');
                                                    }
                                                    ?>
                                                    <button class="btn btn-danger" type="button" title="Add New Customer"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#customerAddModal"
                                                        style="display: <?php echo $hasAddCustomerPermission ? 'inline-block' : 'none'; ?>">
                                                        <i class="uil uil-plus"></i>
                                                    </button>
                                                </div>
                                            </div>



                                            <div class="col-md-2">
                                                <label for="customerName" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                                <div class="input-group mb-3">
                                                    <input id="customer_name" name="customer_name" type="text"
                                                        class="form-control" placeholder="Enter Customer Name">
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <label for="customerAddress" class="form-label">Customer
                                                    Address</label>
                                                <div class="input-group mb-3">
                                                    <input id="customer_address" name="customer_address" type="text"
                                                        class="form-control" placeholder="Enter customer address">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="mobileNumber" class="form-label">Mobile Number</label>
                                                <div class="input-group mb-3">
                                                    <input id="customer_mobile" name="customer_mobile" type="text"
                                                        class="form-control" placeholder="Enter Mobile Number">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="isVatInvoice" class="form-label">VAT Invoice</label>
                                                <div class="input-group mb-3">
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" id="is_vat_invoice" name="is_vat_invoice" value="1">
                                                        <label class="form-check-label" for="is_vat_invoice">
                                                            Apply VAT
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3" hidden>
                                                <label for="vatNo" class="form-label">VAT No</label>
                                                <div class="input-group mb-3">
                                                    <?php
                                                    $COMPANY_PROFILE = new CompanyProfile(null);
                                                    $active_company = $COMPANY_PROFILE->getActiveCompany();
                                                    $vat_number = !empty($active_company) ? $active_company[0]['vat_number'] : '';
                                                    $vat_percentage = !empty($active_company) ? $active_company[0]['vat_percentage'] : '';
                                                    ?>
                                                    <input id="vat_no" name="vat_no" type="text"
                                                        class="form-control" value="<?php echo htmlspecialchars($vat_number); ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="customerVatNumber" class="form-label">Customer Vat Number</label>
                                                <div class="input-group mb-3">
                                                    <?php
                                                    $customer_vat_no = '';
                                                    if (!empty($_SESSION['customer_id'])) {
                                                        $CUSTOMER_MASTER = new CustomerMaster($_SESSION['customer_id']);
                                                        $customer_vat_no = $CUSTOMER_MASTER->vat_no ?? '';
                                                    }
                                                    ?>
                                                    <input id="customer_vat_no" name="customer_vat_no" type="text"
                                                        class="form-control" value="<?php echo htmlspecialchars($customer_vat_no); ?>" readonly>
                                                </div>
                                            </div>

                                            <input type="hidden" id="vat_percentage" name="vat_percentage" value="<?php echo htmlspecialchars($vat_percentage); ?>" />

                                            <div class="col-md-2">
                                                <label for="recommendedPerson" class="form-label">Recommended Person</label>
                                                <div class="input-group mb-3">
                                                    <input id="recommended_person" name="recommended_person" type="text"
                                                        class="form-control" placeholder="Enter Recommended Person">
                                                </div>
                                            </div>



                                            <div class="col-md-3">
                                                <label for="customerCode" class="form-label">Dag Ref No</label>
                                                <div class="input-group mb-3">
                                                    <input id="ref_no" name="ref_no" type="text" class="form-control"
                                                        placeholder="Select Dag Ref No" readonly>
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#dagModel">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>

                                                <input type="hidden" id="dag_id" name="dag_id" />
                                            </div>

                                            <div class="col-md-3">
                                                <label for="quotation_ref_no" class="form-label">Select Quotation</label>
                                                <div class="input-group mb-3">
                                                    <input id="quotation_ref_no" name="quotation_ref_no" type="text" class="form-control"
                                                        placeholder="Select Quotation" readonly>
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#quotationModel">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>

                                                <input type="hidden" id="quotation_id" name="quotation_id" />
                                            </div>

                                            <hr class="my-4">
                                            <div class="row align-items-end" id="addItemTable">
                                                <div class="col-md-2">
                                                    <label for="itemCode" class="form-label">Item
                                                        Code</label>
                                                    <div class="input-group">
                                                        <input id="itemCode" type="text" class="form-control"
                                                            placeholder="Item Code" readonly>

                                                        <?php
                                                        $hasViewAllItemsPermission = false;
                                                        if (isset($_SESSION['id'])) {
                                                            $specialPermission = new SpecialUserPermission();
                                                            $hasViewAllItemsPermission = $specialPermission->hasAccess($_SESSION['id'], 'item-Search-Btn');
                                                        }
                                                        ?>
                                                        <button class="btn btn-info" type="button" id="item-Search-Btn"
                                                            data-bs-toggle="modal" data-bs-target="#item_master"
                                                            style="display: <?php echo $hasViewAllItemsPermission ? 'inline-block' : 'none'; ?>">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                        <?php
                                                        $hasViewAllItemsPermission = false;
                                                        if (isset($_SESSION['id'])) {
                                                            $specialPermission = new SpecialUserPermission();
                                                            $hasViewAllItemsPermission = $specialPermission->hasAccess($_SESSION['id'], 'view_all_items');
                                                        }
                                                        ?>
                                                        <button class="btn btn-danger view-all-items-btn" type="button"
                                                            data-bs-toggle="modal"
                                                            name="all-item-master"
                                                            data-bs-target="#all_item_master"
                                                            style="display: <?php echo $hasViewAllItemsPermission ? 'inline-block' : 'none'; ?>">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                        <button class="btn btn-warning" type="button"
                                                            data-bs-toggle="tooltip" id="serviceItemBtn"
                                                            title="Service Item"
                                                            style="color: #fff">
                                                            <i class="uil uil-wrench"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Name</label>
                                                    <input type="text" id="itemName" class="form-control"
                                                        placeholder="Name" readonly>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">List Price</label>
                                                    <input type="number" id="itemPrice" class="form-control"
                                                        placeholder="Price" oninput="calculatePayment()">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label">Cost</label>
                                                    <input type="text" id="item_cost_arn" class="form-control"
                                                        placeholder="Cost" disabled>
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label">Qty</label>
                                                    <input type="number" id="itemQty" class="form-control"
                                                        placeholder="Qty" oninput="calculatePayment()">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label">Dis Amount</label>
                                                    <input type="number" id="itemDiscount" class="form-control" min="0"
                                                        placeholder="Dis Amount" oninput="calculatePayment()">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Selling Price</label>
                                                    <input type="number" id="itemSalePrice" class="form-control" min="0"
                                                        placeholder="Sale Price" oninput="calculatePayment()">
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-success w-100"
                                                        id="addItemBtn">Add</button>
                                                </div>
                                            </div>
                                            <div class="row align-items-end" id="serviceItemTable" style="width: 100%;display: none;">
                                                <div class="col-md-2">
                                                    <br>
                                                    <select id="service" class="form-control">
                                                        <option value="0">-- Select a Service --</option>
                                                        <?php
                                                        $SERVICE = new Service(NULL);
                                                        foreach ($SERVICE->all() as $service) {
                                                        ?>
                                                            <option value="<?php echo $service['id'] ?>">
                                                                <?php echo $service['service_name'] ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>

                                                <!-- Service Extra Details (Vehicle No & Current KM) -->
                                                <div class="col-md-2 hidden" id="serviceExtraDetails" style="display: none;">

                                                    <input type="text" id="vehicleNo" class="form-control" placeholder="Enter Vehicle No">
                                                </div>
                                                <div class="col-md-2 hidden" id="serviceKmDetails" style="display: none;">

                                                    <input type="number" id="currentKm" class="form-control" placeholder="Enter Current KM">
                                                </div>
                                                <div class="col-md-2 hidden" id="serviceNextServiceDetails" style="display: none;">

                                                    <input type="number" id="nextServiceDays" class="form-control" placeholder="Enter Days for Next Service">
                                                </div>
                                                <div class="col-md-2">
                                                    <br>

                                                    <select id="service_items" class="form-control">
                                                        <option value="0">-- Select a Service Item --</option>
                                                        <?php
                                                        $SERVICE_MASTER = new ServiceItem(NULL);
                                                        foreach ($SERVICE_MASTER->all() as $service) {
                                                        ?>
                                                            <option value="<?php echo $service['id'] ?>">
                                                                <?php echo $service['item_name'] ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" id="serviceQty" class="form-control" value="1"
                                                        placeholder="Qty Service" oninput="calculatePayment()">
                                                </div>

                                                <div class="col-md-2">
                                                    <br>
                                                    <input type="number" id="serviceSellingPrice" class="form-control" placeholder="Selling Price" oninput="calculatePayment()">
                                                </div>
                                            </div>



                                            <!-- dag item Table -->
                                            <div class="table-responsive ">

                                                <table class="table table-bordered" id="dagTableHide"
                                                    style="display:none">

                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Vehicle No</th>
                                                            <th>Belt Design</th>
                                                            <th>Size</th>
                                                            <th>Serial No</th>
                                                            <th>Cost</th>
                                                            <th>Price</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="dagItemsBodyInvoice">
                                                        <tr id="noDagItemRow">
                                                            <td colspan="7" class="text-center text-muted">No items
                                                                added</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>




                                            <!-- Table -->
                                            <div class="table-responsive mt-4">
                                                <table class="table table-bordered" id="invoiceTable">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Code</th>
                                                            <th>Name</th>
                                                            <th>List Price</th>
                                                            <th>Qty</th>
                                                            <th>Discount</th>
                                                            <th>Selling Price</th>
                                                            <th>Total</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="invoiceItemsBody">
                                                        <tr id="noInvoiceItemRow">
                                                            <td colspan="8" class="text-center text-muted">
                                                                No items
                                                                added</td>
                                                        </tr>
                                                    </tbody>

                                                </table>

                                            </div>


                                            <hr>
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <div class="  p-2 border rounded bg-light"
                                                        style="max-width: 500px;">
                                                        <div class="row mb-2">
                                                            <div class="col-5">
                                                                <input type="text" class="form-control text_purchase3"
                                                                    value="Stock Level" disabled>
                                                            </div>
                                                            <div class="col-7">
                                                                <input type="text"
                                                                    class="form-control text-danger fw-bold"
                                                                    id="available_qty" disabled>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5">
                                                                <input type="text" class="form-control text_purchase3"
                                                                    value="Outstanding Amount" disabled>
                                                            </div>
                                                            <div class="col-7">
                                                                <input type="text" id="outstandingInvoiceAmount" class="form-control" value="0.00"
                                                                    disabled>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5">
                                                                <input type="text" class="form-control text_purchase3"
                                                                    value="Credit Period  " disabled>
                                                            </div>

                                                            <div class="col-7">
                                                                <select class="form-control  " name="credit_period" id="credit_period">
                                                                    <option value=""> -- Select Credit Period -- </option>
                                                                    <?php
                                                                    $CREDIT_PERIOD = new CreditPeriod(NULL);
                                                                    foreach ($CREDIT_PERIOD->getCreditPeriodByStatus(1) as $Credit_period) {
                                                                    ?>
                                                                        <option value="<?php echo $Credit_period['id'] ?>">
                                                                            <?php echo $Credit_period['days'] . ' ' . "Days" ?>
                                                                        </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5">
                                                                <input type="text" class="form-control text_purchase3"
                                                                    value="Invoice Remarks  " disabled>
                                                            </div>
                                                            <div class="col-7">
                                                                <select class="form-control" name="remark" id="remark">
                                                                    <option> -- Select Remark -- </option>
                                                                    <?php
                                                                    $INVOICE_REMARK = new InvoiceRemark(null);
                                                                    foreach ($INVOICE_REMARK->all() as $remark) {
                                                                    ?>
                                                                        <option value="<?php echo $remark['id'] ?>">
                                                                            <?php echo $remark['remark'] ?>
                                                                        </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>


                                                <div class="col-md-3">


                                                </div>

                                                <div class="col-md-4 mb-4">
                                                    <div class="  p-2 border rounded bg-light"
                                                        style="max-width: 600px;">
                                                        <div class="row mb-2">
                                                            <div class="col-7">
                                                                <input type="text" class="form-control text_purchase3"
                                                                    value="Sub Total" disabled>
                                                            </div>
                                                            <div class="col-5">
                                                                <input type="text" class="form-control" id="subTotal"
                                                                    value="0.00" disabled>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <div class="col-7">
                                                                <input type="text" class="form-control text_purchase3"
                                                                    value="Discount Total:" disabled>
                                                            </div>
                                                            <div class="col-5">
                                                                <input type="text" class="form-control" id="disTotal"
                                                                    value="0.00" disabled>
                                                            </div>
                                                        </div>

                                                        <div class="row mb-2">
                                                            <div class="col-7">
                                                                <input type="text" class="form-control text_purchase3"
                                                                    value="Tax Total:" disabled>
                                                            </div>
                                                            <div class="col-5">
                                                                <input type="text" class="form-control" id="tax"
                                                                    value="0.00" disabled>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-7">
                                                                <input type="text"
                                                                    class="form-control text_purchase3 fw-bold"
                                                                    value="Grand Total:" disabled>
                                                            </div>
                                                            <div class="col-5">
                                                                <input type="text" class="form-control  fw-bold"
                                                                    id="finalTotal" value="0.00" disabled>
                                                            </div>

                                                        </div>

                                                        <div id="paymentSection">
                                                            <div class="row mb-2">
                                                                <div class="col-7">
                                                                    <input type="text"
                                                                        class="form-control text_purchase3 fw-bold"
                                                                        value="Paid Amount:" disabled>
                                                                </div>
                                                                <div class="col-5">
                                                                    <input type="text" class="form-control  fw-bold"
                                                                        id="paidAmount" value="0.00">
                                                                </div>

                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-7">
                                                                    <input type="text"
                                                                        class="form-control text_purchase3 fw-bold"
                                                                        value="Balance Amount:" disabled>
                                                                </div>
                                                                <div class="col-5">
                                                                    <input type="text" class="form-control  fw-bold"
                                                                        id="balanceAmount" value="0.00" disabled>
                                                                </div>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                            <hr>


                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div>

            <?php include 'footer.php' ?>

        </div>
        <!-- end main content-->

    </div>



    <!-- model open here -->
    <div class="modal fade bs-example-modal-xl" id="quotationModel" tabindex="-1" role="dialog"
        aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myExtraLargeModalLabel">Select Quotation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Click on a quotation to load its items into the invoice.</p>
                    <div class="row">
                        <div class="col-12">

                            <table id="quotation_table" class="table table-bordered dt-responsive nowrap table-hover datatable"
                                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Quotation No</th>
                                        <th>Date</th>
                                        <th>Customer Name</th>
                                        <th>Company</th>
                                        <th>Department</th>
                                        <th>Final Total</th>

                                    </tr>
                                </thead>

                                <tbody id="quotationTableBody">
                                    <?php
                                    $QUOTATION = new Quotation(null);
                                    foreach ($QUOTATION->getNotInvoiced() as $key => $quotation) {

                                        $key++;
                                        $CUSTOMER = new CustomerMaster($quotation['customer_id']);
                                        $COMPANY = new CompanyProfile($quotation['company_id']);
                                        $DEPARTMENT_MASTER = new DepartmentMaster($quotation['department_id']);
                                    ?>
                                        <tr class="select-model" style="cursor: pointer;" data-id="<?php echo $quotation['id']; ?>"
                                            data-quotation_no="<?php echo htmlspecialchars($quotation['quotation_no']); ?>"
                                            data-date="<?php echo htmlspecialchars($quotation['date']); ?>"
                                            data-customer_name="<?php echo htmlspecialchars($quotation['customer_id']); ?>"
                                            data-company_id="<?php echo htmlspecialchars($quotation['company_id']); ?>"
                                            data-department_id="<?php echo htmlspecialchars($quotation['department_id']); ?>"
                                            data-sub_total="<?php echo htmlspecialchars($quotation['sub_total']); ?>"
                                            data-discount="<?php echo htmlspecialchars($quotation['discount']); ?>"
                                            data-grand_total="<?php echo htmlspecialchars($quotation['grand_total']); ?>">

                                            <td><?php echo $key; ?></td>
                                            <td><?php echo htmlspecialchars($quotation['quotation_no']); ?></td>
                                            <td><?php echo htmlspecialchars($quotation['date']); ?></td>
                                            <td><?php echo htmlspecialchars($CUSTOMER->name); ?></td>
                                            <td><?php echo htmlspecialchars($COMPANY->name); ?></td>
                                            <td><?php echo htmlspecialchars($DEPARTMENT_MASTER->name); ?></td>
                                            <td><?php echo htmlspecialchars($quotation['grand_total']); ?></td>
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


    <!-- DAG Modal -->
    <?php include 'dag-mode.php' ?>

    <!-- Payment MOdelk Loard -->
    <?php include 'payment-model.php' ?>

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- /////////////////////////// -->
    <script src="ajax/js/sales-invoice.js"></script>
    <script src="ajax/js/common.js"></script>
    <script src="ajax/js/customer-master.js"></script>

    <!-- include main js  -->
    <?php include 'main-js.php' ?>


</body>

</html>