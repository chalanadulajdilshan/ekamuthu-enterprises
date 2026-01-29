<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';

$REPAIR_JOB = new RepairJob(NULL);

// Get the job code from document tracking table
$DOCUMENT_TRACKING = new DocumentTracking($doc_id);
$lastId = $DOCUMENT_TRACKING->repair_job_id ?? 0;
$job_code = 'RJ/' . $_SESSION['id'] . '/0' . ($lastId + 1);
?>

<head>

    <meta charset="utf-8" />
    <title>Repair Job Management |
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

                            <a href="#" class="btn btn-warning" id="update" style="display: none;">
                                <i class="uil uil-edit me-1"></i> Update
                            </a>

                            <?php if ($PERMISSIONS['delete_page']): ?>
                                <a href="#" class="btn btn-danger delete-job" style="display: none;">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>

                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Repair Job</li>
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
                                                    <h5 class="font-size-16 mb-1">Repair Job Details - අලුත්වැඩියා රැකියා විස්තර</h5>
                                                    <p class="text-muted text-truncate mb-0">Create and manage repair service jobs</p>
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
                                                <!-- Job Code -->
                                                <div class="col-md-3">
                                                    <label for="job_code" class="form-label">Job Code - රැකියා කේතය</label>
                                                    <div class="input-group mb-3">
                                                        <input id="job_code" name="job_code" type="text" class="form-control"
                                                            value="<?php echo $job_code ?>">
                                                        <button class="btn btn-info" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#RepairJobModal"><i
                                                                class="uil uil-search me-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Item Type -->
                                                <div class="col-md-3">
                                                    <label class="form-label">Item Type - අයිතම වර්ගය<span class="text-danger">*</span></label>
                                                    <div class="mb-3">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="item_type" id="item_type_company" value="company">
                                                            <label class="form-check-label" for="item_type_company">Company Item</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="item_type" id="item_type_customer" value="customer" checked>
                                                            <label class="form-check-label" for="item_type_customer">Customer Item</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Breakdown Date -->
                                                <div class="col-md-3">
                                                    <label for="item_breakdown_date" class="form-label">Item Breakdown Date - බිඳවැටීම් දිනය</label>
                                                    <div class="mb-3">
                                                        <input id="item_breakdown_date" name="item_breakdown_date" type="text"
                                                            class="form-control date-picker" value="<?php echo date('Y-m-d'); ?>">
                                                    </div>
                                                </div>

                                                <!-- Job Status -->
                                                <div class="col-md-3">
                                                    <label for="job_status" class="form-label">Job Status - තත්ත්වය</label>
                                                    <select class="form-select mb-3" id="job_status" name="job_status">
                                                        <option value="pending">Pending - පොරොත්තු</option>
                                                        <option value="checking">Checking - පරීක්ෂා කරමින්</option>
                                                        <option value="in_progress">In Progress - ප්‍රගතියේ</option>
                                                        <option value="completed">Completed - සම්පූර්ණයි</option>
                                                        <option value="cannot_repair">Cannot Repair - අලුත්වැඩියා කළ නොහැක</option>
                                                    </select>
                                                </div>
                                            </div> 
 

                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label for="machine_name" class="form-label">Machine/Item Name - යන්ත්‍ර/අයිතම නම</label>
                                                    <input id="machine_name" name="machine_name" type="text" class="form-control mb-3" placeholder="Enter machine/item name for repair">
                                                </div>
                                                <div class="col-md-3"  id="machine_code_section" style="display: none;">
                                                    <label for="machine_code" class="form-label">Machine Code - යන්ත්‍ර කේතය</label>
                                                    <input id="machine_code" name="machine_code" type="text" class="form-control mb-3" placeholder="Enter machine code">
                                                </div>
                                                <!-- Customer Name -->
                                                <div class="col-md-3">
                                                    <label for="customer_name" class="form-label">Customer Name - පාරිභෝගික නම</label>
                                                    <input id="customer_name" name="customer_name" type="text" class="form-control mb-3" placeholder="Enter customer name">
                                                </div>

                                                <!-- Customer Phone -->
                                                <div class="col-md-3">
                                                    <label for="customer_phone" class="form-label">Phone - දුරකථන අංකය</label>
                                                    <input id="customer_phone" name="customer_phone" type="text" class="form-control mb-3" placeholder="Enter phone number">
                                                </div>




                                            </div>

                                            <div class="row">
                                                <!-- Customer Address -->
                                                <div class="col-md-6">
                                                    <label for="customer_address" class="form-label">Address - ලිපිනය</label>
                                                    <textarea id="customer_address" name="customer_address" class="form-control mb-3" rows="2" placeholder="Enter customer address"></textarea>
                                                </div>

                                                <!-- Technical Issue -->
                                                <div class="col-md-6">
                                                    <label for="technical_issue" class="form-label">Technical Issue Details - තාක්ෂණික ගැටලු විස්තර</label>
                                                    <textarea id="technical_issue" name="technical_issue" class="form-control mb-3" rows="2" placeholder="Describe the technical issue..."></textarea>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Remark -->
                                                <div class="col-md-12">
                                                    <label for="remark" class="form-label">Remark - සටහන</label>
                                                    <textarea id="remark" name="remark" class="form-control mb-3" rows="2" placeholder="Additional notes..."></textarea>
                                                </div>
                                            </div>



                                            <input type="hidden" id="job_id" name="job_id" />
                                        </form>
                                    </div>
                                </div>

                                <!-- Repair Items Card (visible when in_progress or later) -->
                                <div class="card" id="repair-items-card" style="display: none;">
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
                                                <h5 class="font-size-16 mb-0">Repair Items / Spare Parts - අලුත්වැඩියා අයිතම / සංරක්ෂිත කොටස්</h5>
                                            </div>
                                        </div>
                                        
                                        <div class="row align-items-end mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Item Name - අයිතම නම</label>
                                                <input type="text" class="form-control" id="repair_item_name" placeholder="Enter item/part name">
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Quantity - ප්‍රමාණය</label>
                                                <input type="number" class="form-control" id="repair_item_qty" min="1" value="1">
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Unit Price - ඒකක මිල</label>
                                                <input type="number" class="form-control" id="repair_item_price" min="0" step="0.01" value="0.00">
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Total - එකතුව</label>
                                                <input type="text" class="form-control" id="repair_item_total" readonly value="0.00">
                                            </div>

                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-success w-100" id="addRepairItemBtn">
                                                    <i class="uil uil-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Repair Items Table -->
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="repairItemsTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Item Name</th>
                                                        <th>Quantity</th>
                                                        <th>Unit Price</th>
                                                        <th>Total</th>
                                                        <th style="width: 80px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Items will be added here dynamically -->
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <td colspan="4" class="text-end fw-bold">Grand Total:</td>
                                                        <td class="fw-bold" id="grand_total">0.00</td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <div class="text-center text-muted py-3" id="noRepairItemsMessage">
                                            <i class="uil uil-cog font-size-24"></i>
                                            <p class="mb-0">No repair items added yet. Add spare parts or service items above.</p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            
                            <!-- Cost Summary Card (Always Visible) -->
                            <div class="card">
                                <div class="p-4">
                                    <h5 class="font-size-16 mb-3">Cost Summary - පිරිවැය සාරාංශය</h5>
                                    <div class="row">
                                        <!-- Repair Charge (Manual) -->
                                        <div class="col-md-3">
                                            <label class="form-label">Repair Charge - අලුත්වැඩියා ගාස්තුව</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" class="form-control fw-bold text-end" id="repair_charge" name="repair_charge" value="0.00" min="0" step="0.01">
                                            </div>
                                        </div>

                                        <!-- Commission Percentage -->
                                        <div class="col-md-3">
                                            <label class="form-label">Commission %</label>
                                            <div class="input-group mb-3">
                                                <input type="number" class="form-control fw-bold text-end" id="commission_percentage" name="commission_percentage" value="15" min="0" max="100" step="0.01">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>

                                        <!-- Commission Amount Display -->
                                        <div class="col-md-3">
                                            <label class="form-label">Commission Amount</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="text" class="form-control fw-bold text-end" id="commission_amount" readonly value="0.00">
                                            </div>
                                        </div>

                                        <!-- Total Cost Display (Calculated) -->
                                        <div class="col-md-3">
                                            <label class="form-label">Total Repair Cost - මුළු පිරිවැය</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="text" class="form-control fw-bold text-end" id="total_cost_display" readonly value="0.00">
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

    <!-- Repair Job Modal -->
    <div id="RepairJobModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
        aria-labelledby="ModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ModalLabel">Manage Repair Jobs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table id="repairJobTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Job Code</th>
                                        <th>Item Type</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Total Cost</th>
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

    <script src="ajax/js/repair-job.js?v=<?php echo time(); ?>"></script>

    <!-- Page Preloader Script -->
    <script>
        $(window).on('load', function () {
            $('#page-preloader').fadeOut('slow', function () {
                $(this).remove();
            });
        });
        
        $(document).ready(function() {
            // Check for job_id in URL
            const urlParams = new URLSearchParams(window.location.search);
            const jobId = urlParams.get('job_id');
            if (jobId) {
                // Using a small timeout to ensure repair-job.js has executed and exposed the function
                setTimeout(function() {
                     if (typeof window.loadJobDetails === 'function') {
                        window.loadJobDetails(jobId);
                     } else {
                        console.error("window.loadJobDetails function not found");
                     }
                }, 100);
            }
        });
    </script>
</body>
</html>
