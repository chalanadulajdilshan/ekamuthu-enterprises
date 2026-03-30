<!doctype html>
<?php
include 'class/include.php';
include './auth.php';

//doc id get by session 
$DOCUMENT_TRACKING = new DocumentTracking($doc_id);

// Get the last inserted supplier invoice
$lastId = $DOCUMENT_TRACKING->supplier_invoice_id;
$grn_id = $COMPANY_PROFILE_DETAILS->company_code . '/GRN/00/0' . ($lastId + 1);

?>

<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Supplier Invoice (GRN) | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>

    <style>
        .payment-section {
            display: none;
        }

        .cheque-section {
            display: none;
        }

        .credit-section {
            display: none;
        }

        .btn-danger {
            color: #fff;
            background-color: #f46a6a !important;
            border-color: #f46a6a;
            padding: 6px !important;
            margin: 4px !important;
        }

        .cheque-preview {
            max-width: 200px;
            max-height: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 8px;
        }
    </style>

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

                            <?php if (in_array(1, $PERMISSIONS)): ?>
                                <a href="#" class="btn btn-success" id="new">
                                    <i class="uil uil-plus me-1"></i> New
                                </a>
                            <?php endif; ?>

                            <?php if (in_array(2, $PERMISSIONS)): ?>
                                <a href="#" class="btn btn-primary" id="create">
                                    <i class="uil uil-save me-1"></i> Save
                                </a>
                            <?php endif; ?>

                            <?php if (in_array(3, $PERMISSIONS)): ?>
                                <a href="#" class="btn btn-warning" id="update" style="display:none;">
                                    <i class="uil uil-edit me-1"></i> Update
                                </a>
                            <?php endif; ?>

                            <?php if (in_array(4, $PERMISSIONS)): ?>
                                <a href="#" class="btn btn-danger delete-supplier-invoice" style="display:none;">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>

                            <?php if (in_array(5, $PERMISSIONS)): ?>
                                <a href="#" class="btn btn-info" id="printBtn" style="display:none;">
                                    <i class="uil uil-print me-1"></i> Print
                                </a>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Supplier Invoice (GRN)</li>
                            </ol>
                        </div>
                    </div>

                    <!--- Hidden Values -->
                    <input type="hidden" id="item_id">
                    <input type="hidden" id="supplier_invoice_id">

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
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h5 class="font-size-16 mb-1">Supplier Invoice Details (GRN)</h5>
                                            <p class="text-muted text-truncate mb-0">Fill all information below</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                        </div>
                                    </div>

                                </div>

                                <div class="p-4">
                                    <form id="form-data" enctype="multipart/form-data">
                                        <div class="row">

                                            <div class="col-md-3">
                                                <label for="grn_no" class="form-label">GRN No</label>
                                                <div class="input-group mb-3">
                                                    <input id="grn_no" name="grn_no" type="text" placeholder="GRN No"
                                                        class="form-control" value="<?php echo $grn_id; ?>" readonly>
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#grn_number_modal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <input type="hidden" id="supplier_id">

                                            <div class="col-md-5">
                                                <label for="supplier" class="form-label">Supplier</label>
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend" style="flex: 0 0 auto;">
                                                        <input id="supplier_code" name="supplier_code" type="text"
                                                            class="form-control ms-10 me-2" style="width: 200px;"
                                                            placeholder="Supplier Code" readonly>
                                                    </div>
                                                    <input id="supplier_name" name="supplier_name" type="text"
                                                        class="form-control" placeholder="Supplier Name" readonly>

                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#supplierModal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <label for="order_no" class="form-label">Order No</label>
                                                <div class="input-group mb-3">
                                                    <input id="order_no" name="order_no" type="text"
                                                        placeholder="Enter Order No" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="invoice_no" class="form-label">Invoice No</label>
                                                <div class="input-group mb-3">
                                                    <input id="invoice_no" name="invoice_no" type="text"
                                                        placeholder="Enter Invoice No" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="invoice_date" class="form-label">Invoice Date</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control date-picker"
                                                        id="invoice_date" name="invoice_date">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="delivery_date" class="form-label">Delivery Date</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control date-picker"
                                                        id="delivery_date" name="delivery_date">
                                                </div>
                                            </div>

                                            <hr class="my-4">

                                        <!-- Payment Section -->
                                        <div class="p-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar-xs">
                                                        <div
                                                            class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                            02
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <h5 class="font-size-16 mb-1">Payment Details</h5>
                                                    <p class="text-muted text-truncate mb-0">Select payment method</p>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label for="payment_type" class="form-label">Payment Type</label>
                                                    <select id="payment_type" name="payment_type" class="form-select">
                                                        <option value="">-- Select Payment Type --</option>
                                                        <option value="cash">Cash</option>
                                                        <option value="cheque">Cheque</option>
                                                        <option value="credit">Credit (Loan)</option>
                                                    </select>
                                                </div>

                                                <!-- Cash Section -->
                                                <div class="col-md-3 payment-section" id="cash_section">
                                                    <label class="form-label">Cash Amount</label>
                                                    <input type="text" id="cash_amount" class="form-control"
                                                        placeholder="Cash Amount" readonly>
                                                </div>

                                                <!-- Cheque Section -->
                                                <div class="col-md-9 cheque-section" id="cheque_section">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Bank</label>
                                                            <select id="bank_name" class="form-select">
                                                                <option value="">Select Bank</option>
                                                                <?php
                                                                $BANK = new Bank(NULL);
                                                                foreach ($BANK->all() as $bank) {
                                                                    echo '<option value="' . $bank['id'] . '">' . $bank['name'] . ' (' . $bank['code'] . ')</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Branch</label>
                                                            <select id="branch_name" class="form-select" disabled>
                                                                <option value="">Select Bank First</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Cheque Date</label>
                                                            <input type="text" id="cheque_date"
                                                                class="form-control date-picker-date"
                                                                placeholder="Cheque Date">
                                                        </div>
                                                        <div class="col-md-4 mt-3">
                                                            <label class="form-label">Cheque No</label>
                                                            <input type="text" id="cheque_no" class="form-control"
                                                                placeholder="Cheque Number">
                                                        </div>
                                                        <div class="col-md-4 mt-3">
                                                            <label class="form-label">Cheque Image</label>
                                                            <input type="file" id="cheque_image"
                                                                class="form-control" accept="image/*">
                                                            <img id="cheque_preview" class="cheque-preview"
                                                                style="display:none;" alt="Cheque Preview">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Credit Section -->
                                                <div class="col-md-3 credit-section" id="credit_section">
                                                    <label class="form-label">Credit Period (Months)</label>
                                                    <input type="number" id="credit_period" class="form-control"
                                                        placeholder="e.g. 3" min="1">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                            <h5 class="mb-3">Item Details</h5>

                                            <div class="row align-items-end">
                                                <div class="col-md-2">
                                                    <label for="itemCode" class="form-label">Item Code</label>
                                                    <input id="itemCode" type="text" class="form-control"
                                                        placeholder="Item Code">
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Description</label>
                                                    <input type="text" id="itemName" class="form-control"
                                                        placeholder="Description">
                                                </div>

                                                <div class="col-md-1">
                                                    <label class="form-label">Unit</label>
                                                    <input type="text" id="itemUnit" class="form-control"
                                                        placeholder="Unit">
                                                </div>

                                                <div class="col-md-1">
                                                    <label class="form-label">Quantity</label>
                                                    <input type="number" id="qty" class="form-control"
                                                        placeholder="Qty" step="0.01"
                                                        oninput="calculateItemAmount()">
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Rate</label>
                                                    <input type="number" id="rate" class="form-control"
                                                        placeholder="Rate" step="0.01"
                                                        oninput="calculateItemAmount()">
                                                </div>

                                                <div class="col-md-1">
                                                    <label class="form-label">Dis%</label>
                                                    <input type="number" id="itemDiscount" class="form-control"
                                                        placeholder="Dis%" step="0.01" value="0"
                                                        oninput="calculateItemAmount()">
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Amount</label>
                                                    <input type="number" id="itemAmount" class="form-control"
                                                        placeholder="Amount" readonly>
                                                </div>

                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-success w-100"
                                                        id="addItemBtn">Add</button>
                                                </div>
                                            </div>

                                        </div>

                                        <!-- Item Table -->
                                        <div class="table-responsive mt-4">
                                            <table class="table table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item Code</th>
                                                        <th>Description</th>
                                                        <th>Unit</th>
                                                        <th>Qty</th>
                                                        <th>Rate</th>
                                                        <th>Dis%</th>
                                                        <th>Amount</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="invoiceItemsBody">
                                                    <tr id="noItemRow">
                                                        <td colspan="8" class="text-center text-muted">No items added
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Totals -->
                                        <div class="row">
                                            <div class="col-md-8"></div>
                                            <div class="col-md-4">
                                                <div class="p-2 border rounded bg-light" style="max-width: 600px;">
                                                    <div class="row mb-2">
                                                        <div class="col-7">
                                                            <input type="text" class="form-control fw-bold"
                                                                value="Grand Total:" disabled>
                                                        </div>
                                                        <div class="col-5">
                                                            <input type="text" class="form-control fw-bold"
                                                                id="grandTotal" value="0.00" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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

    <!-- GRN Search Modal -->
    <div class="modal fade bs-example-modal-xl" id="grn_number_modal" tabindex="-1" role="dialog"
        aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myExtraLargeModalLabel">Manage Supplier Invoices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="grn_table" class="table table-bordered dt-responsive nowrap"
                                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>GRN No</th>
                                        <th>Supplier</th>
                                        <th>Invoice No</th>
                                        <th>Invoice Date</th>
                                        <th>Payment Type</th>
                                        <th>Grand Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $SUPPLIER_INVOICE = new SupplierInvoice(null);
                                    foreach ($SUPPLIER_INVOICE->all() as $key => $inv) {
                                        $SUPP = new SupplierMaster($inv['supplier_id']);
                                        $key++;
                                    ?>
                                        <tr class="select-grn" data-id="<?= $inv['id']; ?>">
                                            <td><?= $key; ?></td>
                                            <td><?= htmlspecialchars($inv['grn_number']); ?></td>
                                            <td><?= htmlspecialchars($SUPP->code . ' - ' . $SUPP->name); ?></td>
                                            <td><?= htmlspecialchars($inv['invoice_no']); ?></td>
                                            <td><?= htmlspecialchars($inv['invoice_date']); ?></td>
                                            <td>
                                                <?php
                                                $badge = 'bg-soft-primary';
                                                if ($inv['payment_type'] == 'cash') $badge = 'bg-soft-success';
                                                if ($inv['payment_type'] == 'cheque') $badge = 'bg-soft-warning';
                                                if ($inv['payment_type'] == 'credit') $badge = 'bg-soft-info';
                                                ?>
                                                <span class="badge <?= $badge; ?> font-size-12">
                                                    <?= ucfirst($inv['payment_type']); ?>
                                                </span>
                                            </td>
                                            <td><?= number_format($inv['grand_total'], 2); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
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
    <script src="ajax/js/supplier-invoice.js"></script>

    <!-- include main js -->
    <?php include 'main-js.php' ?>

    <!-- App js -->
    <script src="assets/js/app.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
    <script>
        $('#grn_table').DataTable();
        $(function() {
            $(".date-picker").datepicker({
                dateFormat: 'yy-mm-dd'
            });
            var today = $.datepicker.formatDate('yy-mm-dd', new Date());
            $(".date-picker").val(today);

            $(".date-picker-date").datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });
    </script>

</body>

</html>
