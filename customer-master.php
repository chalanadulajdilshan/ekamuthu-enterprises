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
    <style>
        .camera-container {
            position: relative;
            overflow: hidden;
            background: #1a1a1a;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        #cameraStream {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: cover;
            transform: scaleX(1); /* Default, will flip for front camera */
        }
        .camera-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 12px;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .camera-overlay::after {
            content: '';
            width: 200px;
            height: 200px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        #capturedImagesContainer .img-fluid {
            transition: transform 0.2s;
            cursor: pointer;
        }
        #capturedImagesContainer .img-fluid:hover {
            transform: scale(1.05);
        }
    </style>

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
                                <button type="button" class="btn btn-dark" id="blacklist-btn" style="display: none;">
                                    <i class="uil uil-ban me-1"></i> Blacklist
                                </button>
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
                                <div id="addproduct-accordion" class="custom-accordion">
                                    <div class="">
                                        <div class=" card border p-4">
                                            <form id="form-data" autocomplete="off">

                                                <!-- Card 1: Customer Details -->
                                                <div class=" mb-4">
                                                    <div class="card-header" style="background-color: rgba(80, 141, 218, 0.15);">
                                                        <h5 class="card-title font-size-16 mb-1"><i class="mdi mdi-account-circle me-2"></i>Customer Details</h5>
                                                        <p class="text-muted text-truncate mb-0">Fill all information below to add customer details</p>
                                                    </div>
                                                    <div class=" mt-3  ">
                                                        <div class="row">


                                                            <div class="col-12 mb-3">
                                                                <div id="blacklist-alert" class="alert alert-danger d-flex align-items-center" role="alert" style="display: none !important;">
                                                                    <i class="uil uil-exclamation-triangle me-2" style="font-size: 24px;"></i>
                                                                    <div>
                                                                        <strong>This customer is blacklisted!</strong> <br>
                                                                        Reason: <span id="blacklist-reason-display"></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-md-4 col-lg-2">
                                                                <label for="customerCode" class="form-label">Customer Code</label>
                                                                <div class="input-group mb-3">
                                                                    <input id="code" name="code" type="text" class="form-control"
                                                                        value="<?php echo $customer_id ?>" readonly>
                                                                    <button class="btn btn-info" type="button"
                                                                        data-bs-toggle="modal" data-bs-target="#AllCustomerModal"><i
                                                                            class="uil uil-search me-1"></i>
                                                                    </button>
                                                                    <input type="hidden" id="id" name="id">
                                                                </div>
                                                            </div>

                                                            <div class="col-12 col-md-8 col-lg-5">
                                                                <label for="fullName" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                                <div class="input-group">
                                                                    <input id="name" name="name" onkeyup="toUpperCaseInput(this)"
                                                                        type="text" class="form-control" placeholder="Enter full name">
                                                                    <button class="btn btn-outline-secondary" type="button" onclick="openCameraModal('customer_photo', 1)" title="Capture Customer Photo">
                                                                        <i class="uil uil-camera"></i>
                                                                    </button>
                                                                    <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('customer_photo', 1)" title="Upload Photo">
                                                                        <i class="uil uil-file-upload"></i>
                                                                    </button>
                                                                </div>
                                                                <input type="hidden" id="customer_photo_image_1" name="customer_photo_image_1">
                                                                <input type="file" id="customer_photo_file_1" name="customer_photo_file_1" accept="image/*" style="display:none;" onchange="handleFileUpload('customer_photo', this, 1)">
                                                                <div id="customer_photo_preview" class="mt-2 d-flex gap-2"></div>
                                                            </div>

                                                            <div class="col-12 col-md-6 col-lg-3">
                                                                <label for="nic" class="form-label">NIC</label>
                                                                <div class="input-group">
                                                                    <input id="nic" name="nic" type="text" class="form-control"
                                                                        placeholder="Enter NIC number" maxlength="12"
                                                                        oninput="validateNIC(this)">
                                                                    <span class="input-group-text" id="nic-status"></span>
                                                                    <button class="btn btn-outline-secondary" type="button" onclick="openCameraModal('nic', 2)" title="Upload NIC Images">
                                                                        <i class="uil uil-camera"></i>
                                                                    </button>
                                                                    <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('nic', 1)" title="Upload Front">
                                                                        <i class="uil uil-file-upload"></i> F
                                                                    </button>
                                                                    <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('nic', 2)" title="Upload Back">
                                                                        <i class="uil uil-file-upload"></i> B
                                                                    </button>
                                                                </div>
                                                                <small id="nic-error" class="text-danger" style="display: none;"></small>
                                                                <input type="hidden" id="nic_image_1" name="nic_image_1">
                                                                <input type="hidden" id="nic_image_2" name="nic_image_2">
                                                                <!-- File Inputs -->
                                                                <input type="file" id="nic_file_1" name="nic_file_1" accept="image/*" style="display:none;" onchange="handleFileUpload('nic', this, 1)">
                                                                <input type="file" id="nic_file_2" name="nic_file_2" accept="image/*" style="display:none;" onchange="handleFileUpload('nic', this, 2)">

                                                                <div id="nic_preview" class="mt-2 d-flex gap-2"></div>
                                                            </div>

                                                            <div class="col-12 col-md-3 col-lg-2">
                                                                <label for="mobile1" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                                                <input id="mobile_number" name="mobile_number" type="tel"
                                                                    class="form-control" placeholder="Enter primary mobile"
                                                                    pattern="[0-9]{10}" maxlength="10"
                                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                            </div>

                                                            <div class="col-12 col-md-3 col-lg-2 ">
                                                                <label for="mobile_number_2" class="form-label">WP No</label>
                                                                <input id="mobile_number_2" name="mobile_number_2" type="tel"
                                                                    class="form-control" placeholder="Enter WhatsApp number"
                                                                    pattern="[0-9]{10}" maxlength="10"
                                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                            </div>

                                                            <div class="col-12 col-md-8 col-lg-4 ">
                                                                <label for="address" class="form-label">NIC Address</label>
                                                                <input id="address" onkeyup="toUpperCaseInput(this)" name="address"
                                                                    type="text" class="form-control" placeholder="Enter NIC address">
                                                            </div>

                                                            <div class="col-12 col-md-8 col-lg-4  ">
                                                                <label for="workplace_address" class="form-label">Workplace Address</label>
                                                                <input id="workplace_address" onkeyup="toUpperCaseInput(this)" name="workplace_address"
                                                                    type="text" class="form-control" placeholder="Enter workplace address">
                                                            </div>



                                                            <div class="col-6 col-md-4 col-lg-1 d-flex align-items-center mt-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="is_company" name="is_company" onchange="toggleCompanyFields()">
                                                                    <label class="form-check-label" for="is_company">If Company</label>
                                                                </div>
                                                            </div>



                                                            <div id="company_fields" style="display: none;">
                                                                <div class="row">

                                                                    <div class="col-12 col-md-8 col-lg-6 mt-3">
                                                                        <label for="company_name" class="form-label">Company Name</label>
                                                                        <input id="company_name" name="company_name" type="text" class="form-control" placeholder="Enter company name" onkeyup="toUpperCaseInput(this)">
                                                                    </div>
                                                                    <div class="col-12 col-md-8 col-lg-6 mt-3">
                                                                        <label for="po_document" class="form-label mt-2">Company Document (PO/Letterhead)</label>
                                                                        <div class="input-group">
                                                                            <input id="po_document_name" type="text" class="form-control" placeholder="No file selected" readonly>
                                                                            <button class="btn btn-outline-secondary" type="button" onclick="openCameraModal('po_document', 1)" title="Capture Image">
                                                                                <i class="uil uil-camera"></i>
                                                                            </button>
                                                                            <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('po_document')" title="Upload File">
                                                                                <i class="uil uil-file-upload"></i>
                                                                            </button>
                                                                        </div>
                                                                        <input type="hidden" id="po_document_image_1" name="po_document_image_1">
                                                                        <input type="file" id="po_document_file" name="po_document_file" accept=".pdf,image/*" style="display:none;" onchange="handleFileUpload('po_document', this)">
                                                                        <div id="po_document_preview" class="mt-2 d-flex gap-2 flex-wrap"></div>
                                                                        <input type="hidden" id="company_document_image_1" name="company_document_image_1">
                                                                    </div>


                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Card 2: Billing Details -->
                                                <div class="   mb-4 mt-4  ">
                                                    <div class="card-header" style="background-color: rgba(52, 195, 143, 0.15);">
                                                        <h5 class="card-title font-size-16 mb-1"><i class="mdi mdi-cash-multiple me-2"></i>Billing Details</h5>
                                                        <p class="text-muted text-truncate mb-0">Fill all information below to add billing details</p>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-12 col-md-6 col-lg-6">
                                                                <label for="water_bill_no" class="form-label">Utility Bill Number (Water/Electricity) <span class="text-danger"></span></label>
                                                                <div class="input-group">
                                                                    <input id="water_bill_no" name="water_bill_no" type="text"
                                                                        class="form-control" placeholder="Enter utility bill number">
                                                                    <button class="btn btn-outline-secondary" type="button" onclick="openCameraModal('water_bill', 1)" title="Capture Image">
                                                                        <i class="uil uil-camera"></i>
                                                                    </button>
                                                                    <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('water_bill')" title="Upload PDF">
                                                                        <i class="uil uil-file-upload"></i>
                                                                    </button>
                                                                </div>
                                                                <input type="hidden" id="water_bill_image_1" name="water_bill_image_1">
                                                                <input type="file" id="water_bill_file" name="water_bill_file" accept=".pdf,image/*" style="display:none;" onchange="handleFileUpload('water_bill', this)">
                                                                <div id="water_bill_preview" class="mt-2 d-flex gap-2 flex-wrap"></div>
                                                                <input id="utility_bill_no" name="utility_bill_no" type="hidden">
                                                            </div>




                                                            <div class="col-12 col-md-6 col-lg-3">
                                                                <label for="old_outstanding" class="form-label">Outstanding Balance</label>
                                                                <div class="input-group">
                                                                    <input id="old_outstanding" name="old_outstanding" type="text"
                                                                        class="form-control" placeholder="Enter outstanding balance" readonly>
                                                                    <button class="btn btn-warning" type="button" id="btnAddDescription" style="display:none;" title="Add Description">
                                                                        <i class="mdi mdi-playlist-plus"></i> Add
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Card 3: Guarantee Details -->
                                                <div class="   mb-4">
                                                    <div class="card-header" style="background-color: rgba(241, 180, 76, 0.15);">
                                                        <h5 class="card-title font-size-16 mb-1"><i class="mdi mdi-account-check me-2"></i>Guarantee Details</h5>
                                                        <p class="text-muted text-truncate mb-0">Fill all information below to add guarantee details</p>
                                                    </div>
                                                    <div class=" mt-3">
                                                        <div class="row">
                                                            <div class="col-12 col-md-6 col-lg-3">
                                                                <label for="guarantor_name" class="form-label">Guarantor Name</label>
                                                                <div class="input-group">
                                                                    <input id="guarantor_name" name="guarantor_name" onkeyup="toUpperCaseInput(this)"
                                                                        type="text" class="form-control" placeholder="Enter guarantor name">
                                                                    <button class="btn btn-outline-secondary" type="button" onclick="openCameraModal('guarantor_photo', 1)" title="Capture Guarantor Photo">
                                                                        <i class="uil uil-camera"></i>
                                                                    </button>
                                                                    <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('guarantor_photo', 1)" title="Upload Photo">
                                                                        <i class="uil uil-file-upload"></i>
                                                                    </button>
                                                                </div>
                                                                <input type="hidden" id="guarantor_photo_image_1" name="guarantor_photo_image_1">
                                                                <input type="file" id="guarantor_photo_file_1" name="guarantor_photo_file_1" accept="image/*" style="display:none;" onchange="handleFileUpload('guarantor_photo', this, 1)">
                                                                <div id="guarantor_photo_preview" class="mt-2 d-flex gap-2"></div>
                                                            </div>

                                                            <div class="col-12 col-md-6 col-lg-3">
                                                                <label for="guarantor_nic" class="form-label">Guarantor NIC</label>
                                                                <div class="input-group">
                                                                    <input id="guarantor_nic" name="guarantor_nic" type="text" class="form-control"
                                                                        placeholder="Enter guarantor NIC" maxlength="12"
                                                                        oninput="validateNIC(this)">
                                                                    <button class="btn btn-outline-secondary" type="button" onclick="openCameraModal('guarantor_nic', 2)" title="Upload Guarantor NIC Images">
                                                                        <i class="uil uil-camera"></i>
                                                                    </button>
                                                                    <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('guarantor_nic', 1)" title="Upload Front">
                                                                        <i class="uil uil-file-upload"></i> F
                                                                    </button>
                                                                    <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('guarantor_nic', 2)" title="Upload Back">
                                                                        <i class="uil uil-file-upload"></i> B
                                                                    </button>
                                                                </div>
                                                                <input type="hidden" id="guarantor_nic_image_1" name="guarantor_nic_image_1">
                                                                <input type="hidden" id="guarantor_nic_image_2" name="guarantor_nic_image_2">

                                                                <input type="file" id="guarantor_nic_file_1" name="guarantor_nic_file_1" accept="image/*" style="display:none;" onchange="handleFileUpload('guarantor_nic', this, 1)">
                                                                <input type="file" id="guarantor_nic_file_2" name="guarantor_nic_file_2" accept="image/*" style="display:none;" onchange="handleFileUpload('guarantor_nic', this, 2)">

                                                                <div id="guarantor_nic_preview" class="mt-2 d-flex gap-2"></div>
                                                            </div>

                                                            <div class="col-12 col-md-12 col-lg-6">
                                                                <label for="guarantor_address" class="form-label">Guarantor Address</label>
                                                                <input id="guarantor_address" name="guarantor_address" onkeyup="toUpperCaseInput(this)"
                                                                    type="text" class="form-control" placeholder="Enter guarantor address">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Remark (Full Width) -->

                                                <div class="row">
                                                    <div class="col-12">
                                                        <label for="remark" class="form-label">Remark Note</label>
                                                        <textarea id="remark" name="remark" class="form-control" rows="3"
                                                            placeholder="Enter any remarks or notes about the customer..."></textarea>
                                                    </div>
                                                </div>


                                                <input type="hidden" id="customer_id" name="customer_id" />
                                                <input type="hidden" id="category" name="category" value="1" />
                                            </form>
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

            <!-- Camera Capture Modal -->
            <div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cameraModalLabel"><i class="uil uil-camera me-2"></i>Capture Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="stopCamera()"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <p class="text-muted mb-2" id="captureInstructions">Capture image <span id="currentImageNum">1</span> of <span id="totalImageNum">1</span></p>
                            </div>

                            <!-- Camera View -->
                            <div class="camera-container text-center mb-3">
                                <video id="cameraStream" autoplay playsinline></video>
                                <div class="camera-overlay"></div>
                                <canvas id="captureCanvas" style="display: none;"></canvas>
                            </div>

                            <!-- Capture Controls -->
                            <div class="row mb-3">
                                <div class="col-md-6 offset-md-3">
                                    <label for="cameraSelect" class="form-label">Select Camera</label>
                                    <select id="cameraSelect" class="form-select mb-2" onchange="changeCamera(this.value)">
                                        <option value="">Default Camera</option>
                                    </select>
                                </div>
                            </div>

                            <div class="text-center mb-3">
                                <button type="button" class="btn btn-primary btn-lg" id="captureBtn" onclick="captureImage()">
                                    <i class="uil uil-capture me-1"></i> Capture
                                </button>
                                <button type="button" class="btn btn-info" id="switchCameraBtn" onclick="switchCamera()">
                                    <i class="uil uil-sync me-1"></i> Flip Camera
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="updateCameraList()">
                                    <i class="uil uil-refresh me-1"></i> Refresh List
                                </button>
                            </div>

                            <!-- Captured Images Preview -->
                            <div class="row" id="capturedImagesContainer">
                                <div class="col-6 text-center" id="capturedImage1Container" style="display: none;">
                                    <h6>Image 1 (Front)</h6>
                                    <img id="capturedImage1" src="" class="img-fluid rounded border" style="max-height: 150px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeImage(1)">
                                        <i class="uil uil-trash"></i> Remove
                                    </button>
                                </div>
                                <div class="col-6 text-center" id="capturedImage2Container" style="display: none;">
                                    <h6>Image 2 (Back)</h6>
                                    <img id="capturedImage2" src="" class="img-fluid rounded border" style="max-height: 150px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeImage(2)">
                                        <i class="uil uil-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="stopCamera()">Cancel</button>
                            <button type="button" class="btn btn-success" id="saveImagesBtn" onclick="saveImages()" disabled>
                                <i class="uil uil-check me-1"></i> Save Images
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Camera Capture JavaScript -->
            <script>
                let currentStream = null;
                let currentField = '';
                let maxImages = 1;
                let capturedImages = [];
                let currentCameraFacing = 'environment'; // 'environment' for back, 'user' for front

                function openCameraModal(fieldName, numImages) {
                    currentField = fieldName;
                    maxImages = numImages;
                    capturedImages = [];

                    // Update UI
                    $('#currentImageNum').text('1');
                    $('#totalImageNum').text(numImages);
                    $('#capturedImage1Container, #capturedImage2Container').hide();
                    $('#capturedImage1, #capturedImage2').attr('src', '');
                    $('#saveImagesBtn').prop('disabled', true);

                    // Show/hide second image container based on numImages
                    if (numImages === 1) {
                        $('#capturedImagesContainer .col-6').removeClass('col-6').addClass('col-12');
                        $('#capturedImage1Container h6').text('Captured Image');
                    } else {
                        $('#capturedImagesContainer .col-12').removeClass('col-12').addClass('col-6');
                        $('#capturedImage1Container h6').text('Image 1 (Front)');
                    }

                    // Open modal
                    var modal = new bootstrap.Modal(document.getElementById('cameraModal'));
                    modal.show();

                    // Start camera
                    startCamera();
                }

                async function startCamera(deviceId = null) {
                    // Check if browser supports mediaDevices
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        swal({
                            title: "Camera Error",
                            text: "Camera API is not supported in this browser. Please use a modern browser (Chrome/Edge/Firefox) over HTTPS.",
                            type: "error"
                        });
                        return;
                    }

                    try {
                        // Stop any existing stream first
                        stopCamera();

                        const constraints = {
                            video: {
                                width: { ideal: 1920 },
                                height: { ideal: 1080 },
                                facingMode: deviceId ? undefined : currentCameraFacing
                            }
                        };

                        if (deviceId) {
                            constraints.video.deviceId = { exact: deviceId };
                        }

                        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
                        const videoElement = document.getElementById('cameraStream');
                        videoElement.srcObject = currentStream;

                        // Once camera is started, update device list (to get labels)
                        updateCameraList();
                        
                    } catch (err) {
                        console.error('Error accessing camera:', err);

                        let errorMessage = "Unable to access camera. Please ensure camera permissions are granted.";

                        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                            errorMessage = "Camera access requires a secure HTTPS connection on live servers. Please switch to HTTPS.";
                        } else if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                            errorMessage = "Camera permission denied. Please allow camera access in your browser settings.";
                        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                            errorMessage = "No camera device found.";
                        } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                            errorMessage = "Camera is already in use by another application or device.";
                        } else if (err.name === 'OverconstrainedError') {
                            // If resolution is too high for this camera, try with lower
                            startCameraWithBasicConstraints(deviceId);
                            return;
                        }

                        swal({
                            title: "Camera Error",
                            text: errorMessage,
                            type: "error"
                        });
                    }
                }

                async function startCameraWithBasicConstraints(deviceId = null) {
                    try {
                        const constraints = {
                            video: deviceId ? { deviceId: { exact: deviceId } } : { facingMode: currentCameraFacing }
                        };
                        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
                        document.getElementById('cameraStream').srcObject = currentStream;
                        updateCameraList();
                    } catch (e) {
                        console.error("Basic camera fallback failed", e);
                    }
                }

                async function updateCameraList() {
                    if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;

                    try {
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        const videoDevices = devices.filter(device => device.kind === 'videoinput');
                        const select = document.getElementById('cameraSelect');
                        
                        // Keep track of selected value
                        const currentValue = select.value;
                        
                        // Clear list except first
                        select.innerHTML = '<option value="">Default Camera</option>';
                        
                        videoDevices.forEach((device, index) => {
                            const option = document.createElement('option');
                            option.value = device.deviceId;
                            option.text = device.label || `Camera ${index + 1}`;
                            if (device.deviceId === currentValue) option.selected = true;
                            select.appendChild(option);
                        });

                        // If label is "Camera 1", it means user hasn't granted permission yet
                        // to see device labels, which usually happens AFTER first getUserMedia call
                    } catch (err) {
                        console.error("Error enumerating devices:", err);
                    }
                }

                function changeCamera(deviceId) {
                    if (deviceId) {
                        startCamera(deviceId);
                    } else {
                        startCamera(); // Use default facingMode logic
                    }
                }

                function stopCamera() {
                    if (currentStream) {
                        currentStream.getTracks().forEach(track => track.stop());
                        currentStream = null;
                    }
                }

                function switchCamera() {
                    currentCameraFacing = currentCameraFacing === 'environment' ? 'user' : 'environment';
                    
                    // Update video transform for mirror effect if using front camera
                    const video = document.getElementById('cameraStream');
                    if (currentCameraFacing === 'user') {
                        video.style.transform = 'scaleX(-1)';
                    } else {
                        video.style.transform = 'scaleX(1)';
                    }
                    
                    startCamera();
                }

                function captureImage() {
                    const video = document.getElementById('cameraStream');
                    const canvas = document.getElementById('captureCanvas');
                    const ctx = canvas.getContext('2d');

                    // Set canvas size to video dimensions
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;

                    // Draw video frame to canvas
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    // Get image data as base64
                    const imageData = canvas.toDataURL('image/jpeg', 0.8);

                    // Determine which image slot to use
                    const imageNum = capturedImages.length + 1;

                    if (imageNum <= maxImages) {
                        capturedImages.push(imageData);

                        // Show preview
                        $(`#capturedImage${imageNum}`).attr('src', imageData);
                        $(`#capturedImage${imageNum}Container`).show();

                        // Update instructions
                        if (capturedImages.length < maxImages) {
                            $('#currentImageNum').text(capturedImages.length + 1);
                        } else {
                            $('#captureInstructions').html('<span class="text-success"><i class="uil uil-check-circle"></i> All images captured!</span>');
                        }

                        // Enable save button if all images captured
                        if (capturedImages.length >= maxImages) {
                            $('#saveImagesBtn').prop('disabled', false);
                        }
                    }
                }

                function removeImage(imageNum) {
                    // Remove from array
                    capturedImages.splice(imageNum - 1, 1);

                    // Update UI - shift images if needed
                    if (imageNum === 1 && capturedImages.length > 0) {
                        $('#capturedImage1').attr('src', capturedImages[0]);
                        $('#capturedImage2').attr('src', '');
                        $('#capturedImage2Container').hide();
                    } else {
                        $(`#capturedImage${imageNum}`).attr('src', '');
                        $(`#capturedImage${imageNum}Container`).hide();
                    }

                    // Update instructions
                    $('#currentImageNum').text(capturedImages.length + 1);
                    $('#captureInstructions').html(`Capture image <span id="currentImageNum">${capturedImages.length + 1}</span> of <span id="totalImageNum">${maxImages}</span>`);

                    // Disable save button
                    $('#saveImagesBtn').prop('disabled', true);
                }

                function saveImages() {
                    // Save images to hidden inputs
                    capturedImages.forEach((imageData, index) => {
                        $(`#${currentField}_image_${index + 1}`).val(imageData);
                    });

                    // Update preview area logic
                    refreshPreviews(currentField);

                    /*
                    capturedImages.forEach((imageData, index) => {
                        const previewHtml = `
                            <div class="position-relative" style="width: 60px;">
                                <img src="${imageData}" class="img-fluid rounded border" style="width: 60px; height: 40px; object-fit: cover;">
                                <span class="badge bg-primary position-absolute" style="top: -5px; right: -5px; font-size: 10px;">${index + 1}</span>
                            </div>
                        `;
                        previewContainer.append(previewHtml);
                    });
                    */

                    // Stop camera and close modal
                    stopCamera();
                    bootstrap.Modal.getInstance(document.getElementById('cameraModal')).hide();

                    swal({
                        title: "Success!",
                        text: `${capturedImages.length} image(s) captured successfully!`,
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                }

                // Stop camera when modal is closed
                document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function() {
                    stopCamera();
                });

                // File Upload Functions for PDF/Images
                function openFileUpload(fieldName, index = 1) {
                    // Check for indexed input first, then fallback to non-indexed
                    let fileInput = document.getElementById(`${fieldName}_file_${index}`);
                    if (!fileInput) {
                        fileInput = document.getElementById(`${fieldName}_file`);
                    }
                    if (fileInput) {
                        fileInput.click();
                    }
                }

                function handleFileUpload(fieldName, input, index = 1) {
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
                        const hiddenInput = $(`#${fieldName}_image_${index}`);
                        if (hiddenInput.length) {
                            hiddenInput.val(base64Data);
                        } else {
                            // Fallback for fields that might just use _image_1 implicitly or non-indexed
                            $(`#${fieldName}_image_1`).val(base64Data);
                        }

                        // Update document name field if exists (for PO/Letterhead)
                        if ($(`#${fieldName}_name`).length) {
                            $(`#${fieldName}_name`).val(file.name);
                        }

                        // Update document name field indexed if exists
                        if ($(`#${fieldName}_name_${index}`).length) {
                            $(`#${fieldName}_name_${index}`).val(file.name);
                        }

                        // Update preview
                        const previewContainer = $(`#${fieldName}_preview`);


                        if (fieldName === 'nic' || fieldName === 'guarantor_nic') {

                        } else {
                            previewContainer.empty();
                        }

                        const isPdf = file.type === 'application/pdf';

                        if (isPdf) {
                            // Update preview by refreshing all
                            refreshPreviews(fieldName);
                        } else {
                            // Update preview by refreshing all
                            refreshPreviews(fieldName);
                            /*
                            const previewHtml = `
                                <div class="position-relative" style="width: 60px;">
                                    <img src="${base64Data}" class="img-fluid rounded border" style="width: 60px; height: 40px; object-fit: cover;">
                                    <span class="badge bg-primary position-absolute" style="top: -5px; right: -5px; font-size: 10px;">${index}</span>
                                    <button type="button" class="btn btn-sm btn-link text-danger position-absolute" style="top: -10px; right: -10px; padding: 0;" onclick="removeFileUpload('${fieldName}', ${index})">
                                        <i class="uil uil-times-circle"></i>
                                    </button>
                                </div>
                            `;
                            previewContainer.append(previewHtml);
                            */
                        }

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

                function removeFileUpload(fieldName, index = 1) {
                    // Clear specific inputs
                    $(`#${fieldName}_image_${index}`).val('');
                    if (document.getElementById(`${fieldName}_file_${index}`)) {
                        $(`#${fieldName}_file_${index}`).val('');
                    } else if (index === 1) {
                        $(`#${fieldName}_file`).val('');
                    }

                    // Refresh previews to show remaining images
                    refreshPreviews(fieldName);
                }

                function refreshPreviews(fieldName) {
                    const previewContainer = $(`#${fieldName}_preview`);
                    previewContainer.empty();

                    // Check for index 1 and 2
                    [1, 2].forEach(index => {
                        const hiddenInput = $(`#${fieldName}_image_${index}`);
                        if (hiddenInput.length && hiddenInput.val()) {
                            const base64Data = hiddenInput.val();
                            const isPdf = base64Data.startsWith('data:application/pdf');

                            let previewHtml = '';
                            if (isPdf) {
                                // Try to get filename if possible, otherwise generic
                                let filename = "Document " + index;
                                if ($(`#${fieldName}_name_${index}`).length) filename = $(`#${fieldName}_name_${index}`).val();
                                else if (index === 1 && $(`#${fieldName}_name`).length) filename = $(`#${fieldName}_name`).val();

                                previewHtml = `
                            <div class="position-relative border rounded p-2" style="background: #f8f9fa;">
                                <div class="d-flex align-items-center">
                                    <i class="uil uil-file-alt text-danger" style="font-size: 24px;"></i>
                                    <div class="ms-2">
                                        <small class="d-block text-truncate" style="max-width: 120px;">${filename}</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger ms-2" onclick="removeFileUpload('${fieldName}', ${index})">
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
                                <button type="button" class="btn btn-sm btn-link text-danger position-absolute" style="top: -10px; right: -10px; padding: 0;" onclick="removeFileUpload('${fieldName}', ${index})">
                                    <i class="uil uil-times-circle"></i>
                                </button>
                            </div>
                        `;
                            }
                            previewContainer.append(previewHtml);
                        }
                    });
                }

                // Toggle company document fields visibility
                function toggleCompanyFields() {
                    const isCompany = document.getElementById('is_company').checked;
                    const companyFields = document.getElementById('company_fields');

                    if (isCompany) {
                        companyFields.style.display = 'block';
                    } else {
                        companyFields.style.display = 'none';
                        // Clear the fields when unchecked
                        $('#company_name').val('');
                        $('#po_document_image_1').val('');
                        $('#po_document_file').val('');
                        $('#po_document_name').val('');
                        $('#po_document_preview').empty();
                    }
                }
            </script>

            <!-- Page Preloader Script -->
            <script>
                $(window).on('load', function() {
                    $('#page-preloader').fadeOut('slow', function() {
                        $(this).remove();
                    });
                });
            </script>


            </script>

</body>

</html>