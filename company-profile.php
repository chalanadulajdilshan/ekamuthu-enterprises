<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
?>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Company Profile | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>

    <!-- Cropper CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    <style>
        .img-container {
            max-width: 100%;
            margin-bottom: 1rem;
        }

        .img-preview {
            width: 250px;
            height: 60px;
            overflow: hidden;
            margin: 10px 0;
            border: 1px solid #ddd;
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
                            <a href="#" class="btn btn-success" id="new">
                                <i class="uil uil-plus me-1"></i> New
                            </a>

                            <?php if ($PERMISSIONS['add_page']): ?>
                                <a href="#" class="btn btn-primary" id="create">
                                    <i class="uil uil-save me-1"></i> Save
                                </a>
                            <?php endif; ?>

                            <?php if ($PERMISSIONS['edit_page']): ?>
                                <a href="#" class="btn btn-warning" id="update">
                                    <i class="uil uil-edit me-1"></i> Update
                                </a>
                            <?php endif; ?>

                            <?php if ($PERMISSIONS['delete_page']): ?>
                                <a href="#" class="btn btn-danger delete-brand">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Company Profile </li>
                            </ol>
                        </div>
                    </div>

                    <!-- end page title -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div id="addproduct-accordion" class="custom-accordion">
                                <div class="card">

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
                                                <h5 class="font-size-16 mb-1">Company Profile </h5>
                                                <p class="text-muted text-truncate mb-0">Fill all information below to
                                                    Company Profile </p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="p-4">
                                        <form id="form-data" method="post" enctype="multipart/form-data"
                                            autocomplete="off">
                                            <div class="row">
                                                <!-- Company Code -->
                                                <div class="col-md-2">
                                                    <label class="form-label" for="company_code">Company Code</label>
                                                    <div class="input-group mb-3">
                                                        <input id="company_code" name="company_code" type="text"
                                                            class="form-control" placeholder="Enter company code">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#companyProfileModel">
                                                            <i class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Customer ID -->
                                                <div class="col-md-2">
                                                    <label for="customer_id" class="form-label">Customer ID</label>
                                                    <input id="customer_id" name="customer_id" type="text" class="form-control"
                                                        placeholder="Enter customer id">
                                                </div>

                                                <!-- Company Name -->
                                                <div class="col-md-2">
                                                    <label for="name" class="form-label">Company Name</label>
                                                    <input id="name" name="name" type="text" class="form-control"
                                                        placeholder="Enter company name">
                                                </div>

                                                <!-- Address -->
                                                <div class="col-md-2">
                                                    <label for="address" class="form-label">Address</label>
                                                    <input id="address" name="address" type="text" class="form-control"
                                                        placeholder="Enter company address">
                                                </div>

                                                <!-- Mobile Number 01 -->
                                                <div class="col-md-2">
                                                    <label for="mobile_number_1" class="form-label">Mobile Number
                                                        01</label>
                                                    <input id="mobile_number_1" name="mobile_number_1" type="text"
                                                        class="form-control" placeholder="Enter mobile number 1">
                                                </div>

                                                <!-- Mobile Number 02 -->
                                                <div class="col-md-2">
                                                    <label for="mobile_number_2" class="form-label">Mobile Number
                                                        02</label>
                                                    <input id="mobile_number_2" name="mobile_number_2" type="text"
                                                        class="form-control" placeholder="Enter mobile number 2">
                                                </div>


                                                <!-- Email -->
                                                <div class="col-md-2">
                                                    <label for="email" class="form-label">Email</label>
                                                    <input id="email" name="email" type="email" class="form-control"
                                                        placeholder="Enter email address">
                                                </div>

                                                <!-- Color Theme -->
                                                <div class="col-md-2">
                                                    <label class="form-label" for="theme">Color Theme</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text"><i class="ri-palette-line"></i></span>
                                                        <input type="color" class="form-control form-control-color" id="theme" name="theme" value="<?php echo $COMPANY_PROFILE_DETAILS->theme ?: '#3b5de7'; ?>">
                                                        <button type="button" class="btn btn-light" id="theme-reset">Reset</button>
                                                    </div>
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label" for="home_view_mode">Home View</label>
                                                    <?php $homeViewMode = $COMPANY_PROFILE_DETAILS->home_view_mode ?? 'both'; ?>
                                                    <select id="home_view_mode" name="home_view_mode" class="form-select">
                                                        <option value="both" <?php echo $homeViewMode === 'both' ? 'selected' : ''; ?>>Header + Navigation Buttons</option>
                                                        <option value="header" <?php echo $homeViewMode === 'header' ? 'selected' : ''; ?>>Header Only</option>
                                                        <option value="nav_buttons" <?php echo $homeViewMode === 'nav_buttons' ? 'selected' : ''; ?>>Navigation Buttons Only</option>
                                                    </select>
                                                </div>

                                                <!-- VAT Registered -->
                                                <div class="col-md-1 d-flex align-items-center">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" id="is_vat"
                                                            name="is_vat">
                                                        <label class="form-check-label" for="is_vat">Is VAT?</label>
                                                    </div>
                                                </div>
                                                <?php
                                                $US = new User($_SESSION['id']);
                                                $user_name = $US->name;
                                                ?>
                                                <?php if ($user_name == 'dulaj'): ?>
                                                <!-- One Company -->
                                                <div class="col-md-1 d-flex align-items-center">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" id="is_one_company"
                                                            name="is_one_company">
                                                        <label class="form-check-label" for="is_one_company">Is One Company</label>
                                                    </div>
                                                </div>
                                                
                                                <!-- Credit -->
                                                <div class="col-md-1 d-flex align-items-center">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" id="is_credit"
                                                            name="is_credit">
                                                        <label class="form-check-label" for="is_credit">Is Credit</label>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                <!-- Active Status -->
                                                <div class="col-md-1 d-flex justify-content-center align-items-center">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" id="is_active"
                                                            name="is_active">
                                                        <label class="form-check-label" for="is_active">Active</label>
                                                    </div>
                                                </div>
                                                <!-- VAT Number -->
                                                <div class="col-md-2 mt-3" id="vat-number-group" style="display: none;">
                                                    <label for="vat_number" class="form-label">VAT Number</label>
                                                    <input id="vat_number" name="vat_number" type="text"
                                                        class="form-control" placeholder="Enter VAT number">
                                                </div>

                                                <!-- Logo Upload -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="logo" class="form-label">Company Logo</label>
                                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                                    <small class="text-muted">Recommended size: 250x60 pixels</small>
                                                    <div class="mt-2">
                                                        <img id="logo-preview" src="<?php echo !empty($COMPANY->image_name) ? 'uploads/company-logos/' . $COMPANY->image_name : 'assets/images/default-company.png'; ?>" alt="Logo Preview" style="max-height: 60px; max-width: 100%;">
                                                    </div>
                                                </div>

                                                <!-- Favicon Upload -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="favicon" class="form-label">Favicon</label>
                                                    <input type="file" class="form-control" id="favicon" name="favicon" accept="image/x-icon,image/vnd.microsoft.icon,.ico">
                                                    <small class="text-muted">Recommended format: .ico, size: 32x32 or 16x16 pixels</small>
                                                    <div class="mt-2">
                                                        <img id="favicon-preview" src="<?php echo !empty($COMPANY->favicon) ? 'uploads/company-logos/' . $COMPANY->favicon : 'assets/images/favicon.ico'; ?>" alt="Favicon Preview" style="width: 32px; height: 32px;">
                                                    </div>
                                                </div>

                                                <?php if (!empty($company_profile['image_name'])): ?>
                                                    <div id="logo-preview-existing" class="mt-2">
                                                        <img src="upload/company/<?php echo $company_profile['image_name']; ?>"
                                                            alt="Company Logo" class="img-thumbnail"
                                                            style="max-width: 250px; max-height: 60px;">
                                                    </div>
                                                <?php endif; ?>
                                            </div>



                                            <!-- Remark -->
                                            <div class="col-12 mt-3 hidden">
                                                <label for="remark" class="form-label">Remark</label>
                                                <textarea id="remark" name="remark" class="form-control" rows="4"
                                                    placeholder="Enter remarks about the company..."></textarea>
                                            </div>
                                            <input type="hidden" name="company_id" id="company_id">
                                            <input type="hidden" id="image_name" name="image_name" />

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
    <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <?php include 'company-profile-model.php' ?>


    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- /////////////////////////// -->
    <?php include 'main-js.php' ?>

    <!-- Cropper JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>

    <script src="ajax/js/company-profile.js"></script>

</body>

</html>