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
                            <div id="addproduct-accordion" class="custom-accordion">
                                <div class="card">
                                    <div class="p-4">
                                        <form id="form-data" autocomplete="off">
                                            
                                            <!-- Card 1: Customer Details -->
                                            <div class="card border shadow-sm mb-4">
                                                <div class="card-header" style="background-color: rgba(80, 141, 218, 0.15);">
                                                    <h5 class="card-title font-size-16 mb-1"><i class="mdi mdi-account-circle me-2"></i>Customer Details</h5>
                                                    <p class="text-muted text-truncate mb-0">Fill all information below to add customer details</p>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-12 col-md-4 col-lg-2">
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

                                                        <div class="col-12 col-md-8 col-lg-3">
                                                            <label for="fullName" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                            <input id="name" name="name" onkeyup="toUpperCaseInput(this)"
                                                                type="text" class="form-control" placeholder="Enter full name">
                                                        </div>

                                                        <div class="col-12 col-md-6 col-lg-3">
                                                            <label for="nic" class="form-label">NIC <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <input id="nic" name="nic" type="text" class="form-control"
                                                                    placeholder="Enter NIC number" maxlength="12"
                                                                    oninput="validateNIC(this)">
                                                                <span class="input-group-text" id="nic-status"></span>
                                                                <button class="btn btn-outline-secondary" type="button" onclick="openCameraModal('nic', 2)" title="Upload NIC Images">
                                                                    <i class="uil uil-camera"></i>
                                                                </button>
                                                            </div>
                                                            <small id="nic-error" class="text-danger" style="display: none;"></small>
                                                            <input type="hidden" id="nic_image_1" name="nic_image_1">
                                                            <input type="hidden" id="nic_image_2" name="nic_image_2">
                                                            <div id="nic_preview" class="mt-2 d-flex gap-2"></div>
                                                        </div>

                                                        <div class="col-12 col-md-3 col-lg-2">
                                                            <label for="mobile1" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                                            <input id="mobile_number" name="mobile_number" type="tel"
                                                                class="form-control" placeholder="Enter primary mobile"
                                                                pattern="[0-9]{10}" maxlength="10"
                                                                oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                        </div>
                                                        
                                                        <div class="col-12 col-md-3 col-lg-2">
                                                            <label for="mobile_number_2" class="form-label">Mobile Number 02</label>
                                                            <input id="mobile_number_2" name="mobile_number_2" type="tel"
                                                                class="form-control" placeholder="Enter secondary mobile"
                                                                pattern="[0-9]{10}" maxlength="10"
                                                                oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                        </div>

                                                        <div class="col-12 col-md-8 col-lg-4 mt-3">
                                                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                                            <input id="address" onkeyup="toUpperCaseInput(this)" name="address"
                                                                type="text" class="form-control" placeholder="Enter address">
                                                        </div>

                                                        <div class="col-12 col-md-8 col-lg-4 mt-3">
                                                            <label for="workplace_address" class="form-label">Workplace Address</label>
                                                            <input id="workplace_address" onkeyup="toUpperCaseInput(this)" name="workplace_address"
                                                                type="text" class="form-control" placeholder="Enter workplace address">
                                                        </div>

                                                        <div class="col-6 col-md-4 col-lg-2 d-flex align-items-center mt-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                                                <label class="form-check-label" for="is_active">Active</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-6 col-md-4 col-lg-2 d-flex align-items-center mt-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="is_company" name="is_company" onchange="toggleCompanyFields()">
                                                                <label class="form-check-label" for="is_company">Is Company</label>
                                                            </div>
                                                        </div>

                                                        <!-- Company Document Fields (shown when Is Company is checked) -->
                                                        <div id="company_fields" class="col-12 mt-3" style="display: none;">
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <label for="po_document" class="form-label">Purchase Order (PO)</label>
                                                                    <div class="input-group">
                                                                        <input id="po_document_name" type="text" class="form-control" placeholder="No file selected" readonly>
                                                                        <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('po_document')" title="Upload PO">
                                                                            <i class="uil uil-file-upload"></i> Upload
                                                                        </button>
                                                                    </div>
                                                                    <input type="hidden" id="po_document_image_1" name="po_document_image_1">
                                                                    <input type="file" id="po_document_file" name="po_document_file" accept=".pdf,image/*" style="display:none;" onchange="handleFileUpload('po_document', this)">
                                                                    <div id="po_document_preview" class="mt-2 d-flex gap-2 flex-wrap"></div>
                                                                </div>

                                                                <div class="col-md-4">
                                                                    <label for="letterhead_document" class="form-label">Company Letterhead</label>
                                                                    <div class="input-group">
                                                                        <input id="letterhead_document_name" type="text" class="form-control" placeholder="No file selected" readonly>
                                                                        <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('letterhead_document')" title="Upload Letterhead">
                                                                            <i class="uil uil-file-upload"></i> Upload
                                                                        </button>
                                                                    </div>
                                                                    <input type="hidden" id="letterhead_document_image_1" name="letterhead_document_image_1">
                                                                    <input type="file" id="letterhead_document_file" name="letterhead_document_file" accept=".pdf,image/*" style="display:none;" onchange="handleFileUpload('letterhead_document', this)">
                                                                    <div id="letterhead_document_preview" class="mt-2 d-flex gap-2 flex-wrap"></div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Hidden Fields -->
                                                        <div class="col-md-3 hidden">
                                                            <input id="name_2" name="name_2" type="text" class="form-control">
                                                        </div>
                                                        <div class="col-md-3 hidden">
                                                            <input id="email" name="email" type="email" class="form-control">
                                                        </div>
                                                        <div class="col-md-3 hidden">
                                                            <input id="contact_person" name="contact_person" type="text" class="form-control">
                                                        </div>
                                                        <div class="col-md-3 hidden">
                                                            <input id="contact_person_number" name="contact_person_number" type="tel" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Card 2: Billing Details -->
                                            <div class="card border shadow-sm mb-4">
                                                <div class="card-header" style="background-color: rgba(52, 195, 143, 0.15);">
                                                    <h5 class="card-title font-size-16 mb-1"><i class="mdi mdi-cash-multiple me-2"></i>Billing Details</h5>
                                                    <p class="text-muted text-truncate mb-0">Fill all information below to add billing details</p>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-12 col-md-6 col-lg-3">
                                                            <label for="water_bill_no" class="form-label">Water Bill Number <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <input id="water_bill_no" name="water_bill_no" type="text"
                                                                    class="form-control" placeholder="Enter water bill number">
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
                                                        </div>

                                                        <div class="col-12 col-md-6 col-lg-3">
                                                            <label for="electricity_bill_no" class="form-label">Electricity Bill Number <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <input id="electricity_bill_no" name="electricity_bill_no"
                                                                    type="text" class="form-control" placeholder="Enter electricity bill number">
                                                                <button class="btn btn-outline-secondary" type="button" onclick="openCameraModal('electricity_bill', 1)" title="Capture Image">
                                                                    <i class="uil uil-camera"></i>
                                                                </button>
                                                                <button class="btn btn-outline-primary" type="button" onclick="openFileUpload('electricity_bill')" title="Upload PDF">
                                                                    <i class="uil uil-file-upload"></i>
                                                                </button>
                                                            </div>
                                                            <input type="hidden" id="electricity_bill_image_1" name="electricity_bill_image_1">
                                                            <input type="file" id="electricity_bill_file" name="electricity_bill_file" accept=".pdf,image/*" style="display:none;" onchange="handleFileUpload('electricity_bill', this)">
                                                            <div id="electricity_bill_preview" class="mt-2 d-flex gap-2 flex-wrap"></div>
                                                        </div>

                                                        <div class="col-12 col-md-6 col-lg-3">
                                                            <label for="outstanding" class="form-label">Outstanding Balance</label>
                                                            <input id="outstanding" name="outstanding" type="text"
                                                                class="form-control" placeholder="Enter outstanding balance">
                                                        </div>

                                                        <div class="col-12 col-md-6 col-lg-3">
                                                            <label for="old_outstanding" class="form-label">Old Outstanding Balance</label>
                                                            <input id="old_outstanding" name="old_outstanding" type="text"
                                                                class="form-control" placeholder="Enter old outstanding balance">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Card 3: Guarantee Details -->
                                            <div class="card border shadow-sm mb-4">
                                                <div class="card-header" style="background-color: rgba(241, 180, 76, 0.15);">
                                                    <h5 class="card-title font-size-16 mb-1"><i class="mdi mdi-account-check me-2"></i>Guarantee Details</h5>
                                                    <p class="text-muted text-truncate mb-0">Fill all information below to add guarantee details</p>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-12 col-md-6 col-lg-3">
                                                            <label for="guarantor_name" class="form-label">Guarantor Name <span class="text-danger">*</span></label>
                                                            <input id="guarantor_name" name="guarantor_name" onkeyup="toUpperCaseInput(this)"
                                                                type="text" class="form-control" placeholder="Enter guarantor name">
                                                        </div>

                                                        <div class="col-12 col-md-6 col-lg-3">
                                                            <label for="guarantor_nic" class="form-label">Guarantor NIC <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <input id="guarantor_nic" name="guarantor_nic" type="text" class="form-control"
                                                                    placeholder="Enter guarantor NIC" maxlength="12"
                                                                    oninput="validateNIC(this)">
                                                                <button class="btn btn-outline-secondary" type="button" onclick="openCameraModal('guarantor_nic', 2)" title="Upload Guarantor NIC Images">
                                                                    <i class="uil uil-camera"></i>
                                                                </button>
                                                            </div>
                                                            <input type="hidden" id="guarantor_nic_image_1" name="guarantor_nic_image_1">
                                                            <input type="hidden" id="guarantor_nic_image_2" name="guarantor_nic_image_2">
                                                            <div id="guarantor_nic_preview" class="mt-2 d-flex gap-2"></div>
                                                        </div>

                                                        <div class="col-12 col-md-12 col-lg-6">
                                                            <label for="guarantor_address" class="form-label">Guarantor Address <span class="text-danger">*</span></label>
                                                            <input id="guarantor_address" name="guarantor_address" onkeyup="toUpperCaseInput(this)"
                                                                type="text" class="form-control" placeholder="Enter guarantor address">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Remark (Full Width) -->
                                            <div class="card border shadow-sm">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="remark" class="form-label">Remark Note</label>
                                                            <textarea id="remark" name="remark" class="form-control" rows="3"
                                                                placeholder="Enter any remarks or notes about the customer..."></textarea>
                                                        </div>
                                                    </div>
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
                        <video id="cameraStream" autoplay playsinline style="width: 100%; max-height: 400px; border-radius: 8px; background: #000;"></video>
                        <canvas id="captureCanvas" style="display: none;"></canvas>
                    </div>

                    <!-- Capture Controls -->
                    <div class="text-center mb-3">
                        <button type="button" class="btn btn-primary btn-lg" id="captureBtn" onclick="captureImage()">
                            <i class="uil uil-capture me-1"></i> Capture
                        </button>
                        <button type="button" class="btn btn-secondary" id="switchCameraBtn" onclick="switchCamera()">
                            <i class="uil uil-sync me-1"></i> Switch Camera
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

        async function startCamera() {
            // Check if browser supports mediaDevices
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                swal({
                    title: "Camera Error",
                    text: "Camera API is not supported in this browser. Please use a modern browser.",
                    type: "error"
                });
                return;
            }

            try {
                // Stop any existing stream first
                stopCamera();
                
                const constraints = {
                    video: {
                        facingMode: currentCameraFacing,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                };
                
                currentStream = await navigator.mediaDevices.getUserMedia(constraints);
                document.getElementById('cameraStream').srcObject = currentStream;
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
                    errorMessage = "Camera is already in use by another application.";
                }

                swal({
                    title: "Camera Error",
                    text: errorMessage,
                    type: "error"
                });
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
            
            // Update preview area in the form
            const previewContainer = $(`#${currentField}_preview`);
            previewContainer.empty();
            
            capturedImages.forEach((imageData, index) => {
                const previewHtml = `
                    <div class="position-relative" style="width: 60px;">
                        <img src="${imageData}" class="img-fluid rounded border" style="width: 60px; height: 40px; object-fit: cover;">
                        <span class="badge bg-primary position-absolute" style="top: -5px; right: -5px; font-size: 10px;">${index + 1}</span>
                    </div>
                `;
                previewContainer.append(previewHtml);
            });
            
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
        document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function () {
            stopCamera();
        });

        // File Upload Functions for PDF/Images
        function openFileUpload(fieldName) {
            document.getElementById(`${fieldName}_file`).click();
        }

        function handleFileUpload(fieldName, input) {
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
                $(`#${fieldName}_image_1`).val(base64Data);
                
                // Update document name field if exists (for PO/Letterhead)
                if ($(`#${fieldName}_name`).length) {
                    $(`#${fieldName}_name`).val(file.name);
                }
                
                // Update preview
                const previewContainer = $(`#${fieldName}_preview`);
                previewContainer.empty();

                const isPdf = file.type === 'application/pdf';
                
                if (isPdf) {
                    // PDF Preview
                    const previewHtml = `
                        <div class="position-relative border rounded p-2" style="background: #f8f9fa;">
                            <div class="d-flex align-items-center">
                                <i class="uil uil-file-alt text-danger" style="font-size: 24px;"></i>
                                <div class="ms-2">
                                    <small class="d-block text-truncate" style="max-width: 120px;">${file.name}</small>
                                    <small class="text-muted">${(file.size / 1024).toFixed(1)} KB</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger ms-2" onclick="removeFileUpload('${fieldName}')">
                                    <i class="uil uil-times"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    previewContainer.append(previewHtml);
                } else {
                    // Image Preview
                    const previewHtml = `
                        <div class="position-relative" style="width: 60px;">
                            <img src="${base64Data}" class="img-fluid rounded border" style="width: 60px; height: 40px; object-fit: cover;">
                            <button type="button" class="btn btn-sm btn-link text-danger position-absolute" style="top: -10px; right: -10px; padding: 0;" onclick="removeFileUpload('${fieldName}')">
                                <i class="uil uil-times-circle"></i>
                            </button>
                        </div>
                    `;
                    previewContainer.append(previewHtml);
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

        function removeFileUpload(fieldName) {
            $(`#${fieldName}_image_1`).val('');
            $(`#${fieldName}_file`).val('');
            $(`#${fieldName}_preview`).empty();
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
                $('#po_document_image_1').val('');
                $('#po_document_file').val('');
                $('#po_document_name').val('');
                $('#po_document_preview').empty();
                $('#letterhead_document_image_1').val('');
                $('#letterhead_document_file').val('');
                $('#letterhead_document_name').val('');
                $('#letterhead_document_preview').empty();
            }
        }
    </script>

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