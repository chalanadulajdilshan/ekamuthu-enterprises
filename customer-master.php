<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';

$CUSTOMER_MASTER = new CustomerMaster(NULL);

// Get the last inserted package id
$lastId = $CUSTOMER_MASTER->getLastID();
$customer_id = 'CM/' . $_SESSION['id'] . '/0' . ($lastId + 1);
?>

<head>

    <meta charset="utf-8" />
    <title>Customer Master | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="#" name="description" />
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
                                <a href="#" class="btn btn-danger delete-customer">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Customer Master</li>
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
                                                    <h5 class="font-size-16 mb-1">Customer Master</h5>
                                                    <p class="text-muted text-truncate mb-0">Fill all information below
                                                        to add items
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
                                                <!-- Customer Code -->
                                                <div class="col-md-2">
                                                    <label for="customerCode" class="form-label">Customer Code</label>
                                                    <div class="input-group mb-3">
                                                        <input id="code" name="code" type="text" class="form-control"
                                                            value="<?php echo $customer_id ?>" readonly>
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal" data-bs-target="#AllCustomerModal"><i
                                                                class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Full Name -->
                                                <div class="col-md-3">
                                                    <label for="fullName" class="form-label">Full Name <span
                                                            class="text-danger">*</span></label>
                                                    <input id="name" name="name" onkeyup="toUpperCaseInput(this)"
                                                        type="text" class="form-control" placeholder="Enter full name">
                                                </div>


                                                <!-- Name 2 -->
                                                <div class="col-md-3 hidden">
                                                    <label for="name2" class="form-label">Customer Number</label>
                                                    <input id="name_2" name="name_2" onkeyup="toUpperCaseInput(this)"
                                                        type="text" class="form-control"
                                                        placeholder="Enter customer number">
                                                </div>

                                                <!-- Address -->
                                                <div class="col-md-4 hidden">
                                                    <label for="address" class="form-label">Customer Number <span
                                                            class="text-danger">*</span></label>
                                                    <input id="address" onkeyup="toUpperCaseInput(this)" name="address"
                                                        type="text" class="form-control" placeholder="Enter address">
                                                </div>

                                                <!-- Mobile 1 -->
                                                <div class="col-md-3">
                                                    <label for="mobile1" class="form-label">Mobile Number </label>
                                                    <input id="mobile_number" name="mobile_number" type="tel"
                                                        class="form-control" placeholder="Enter primary mobile number"
                                                        pattern="[0-9]{10}" maxlength="10"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                </div>

                                                <!-- Mobile 2 -->
                                                <div class="col-md-3 hidden">
                                                    <label for="mobile_number_2" class="form-label">Mobile Number
                                                        02</label>
                                                    <input id="mobile_number_2" name="mobile_number_2" type="tel"
                                                        class="form-control" placeholder="Enter secondary mobile number"
                                                        pattern="[0-9]{10}" maxlength="10"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                </div>

                                                <!-- Email -->
                                                <div class="col-md-3 hidden">
                                                    <label for="email" class="form-label">Email</label>
                                                    <input id="email" name="email" type="email" class="form-control"
                                                        placeholder="Enter email">
                                                </div>

                                                <!-- Contact Person -->
                                                <div class="col-md-3 hidden">
                                                    <label for="contactPerson" class="form-label">Contact Person</label>
                                                    <input id="contact_person" name="contact_person" type="text"
                                                        class="form-control" placeholder="Enter contact person name">
                                                </div>

                                                <!-- Contact Person No -->
                                                <div class="col-md-3 hidden">
                                                    <label for="contact_person_number" class="form-label">Contact Person
                                                        No</label>
                                                    <input id="contact_person_number" name="contact_person_number"
                                                        type="tel" class="form-control"
                                                        placeholder="Enter contact person number" pattern="[0-9]{10}"
                                                        maxlength="10"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                </div>
                                                <div class="col-md-3 ">
                                                    <label for="outstanding" class="form-label">Outstanding
                                                        Balance</label>
                                                    <input id="outstanding" name="outstanding" type="text"
                                                        class="form-control" placeholder="Enter outstanding balance">
                                                </div>
                                                <div class="col-md-2 ">
                                                    <label for="old_outstanding" class="form-label">Old Outstanding
                                                        Balance</label>
                                                    <input id="old_outstanding" name="old_outstanding" type="text"
                                                        class="form-control"
                                                        placeholder="Enter old outstanding balance">
                                                </div>

                                                <!-- VAT Details -->
                                                <div class="col-md-3 ">
                                                    <label for="vat_no" class="form-label">VAT No</label>
                                                    <input id="vat_no" name="vat_no" type="text" class="form-control"
                                                        placeholder="Enter VAT number">
                                                </div>
                                                <div class="col-md-4 hidden">
                                                    <label for="svat_no" class="form-label">SVAT No</label>
                                                    <input id="svat_no" name="svat_no" type="text" class="form-control"
                                                        placeholder="Enter SVAT number">
                                                </div>
                                                <div
                                                    class="col-md-1 mt-3 d-flex justify-content-center align-items-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="is_active"
                                                            name="is_active" checked>
                                                        <label class="form-check-label" for="is_active">
                                                            Active
                                                        </label>
                                                    </div>
                                                </div>

                                                <!-- Remark Note -->
                                                <div class="col-12 mt-3">
                                                    <label for="remark" class="form-label">Remark Note</label>
                                                    <textarea id="remark" name="remark" class="form-control" rows="4"
                                                        placeholder="Enter any remarks or notes about the customer..."></textarea>
                                                </div>
                                                <input type="hidden" id="customer_id" name="customer_id" />
                                                <input type="hidden" id="category" name="category" value="1" />
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

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- /////////////////////////// -->
    <script src="ajax/js/customer-master.js"></script>
    <script src="ajax/js/common.js"></script>

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