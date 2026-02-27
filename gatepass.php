<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$rent_id = $_GET['rent_id'] ?? null;

if (!$rent_id) {
    echo "Rent ID is required.";
    exit;
}

$EQUIPMENT_RENT = new EquipmentRent($rent_id);
if (!$EQUIPMENT_RENT->id) {
    echo "Rent bill not found.";
    exit;
}

$CUSTOMER = new CustomerMaster($EQUIPMENT_RENT->customer_id);

$items = $EQUIPMENT_RENT->getItems();

// Prepare equipment type string from items
$equipment_types = array();
foreach($items as $item) {
    $equipment_types[] = $item['equipment_name'] . ' (' . $item['quantity'] . ')';
}
$equipment_type_str = implode(", ", $equipment_types);

?>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Gate Pass | <?php echo $COMPANY_PROFILE_DETAILS->name ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
</head>

<body data-layout="horizontal" data-topbar="colored">
    <div id="page-preloader" class="preloader full-preloader">
        <div class="preloader-container">
            <div class="preloader-animation"></div>
        </div>
    </div>

    <div id="layout-wrapper">
        <?php include 'navigation.php' ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="row mb-4">
                        <div class="col-md-8 d-flex align-items-center flex-wrap gap-2">
                             <a href="equipment-rent-master.php" class="btn btn-secondary">
                                <i class="uil uil-arrow-left me-1"></i> Back to Rent Bill
                            </a>
                            <button class="btn btn-primary" id="save_gatepass">
                                <i class="uil uil-save me-1"></i> Save Gate Pass
                            </button>
                            <button class="btn btn-info" id="view_past_gatepasses" data-bs-toggle="modal" data-bs-target="#GatepassListModal">
                                <i class="uil uil-history me-1"></i> View Past Gatepasses
                            </button>
                        </div>
                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                             <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Gate Pass</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Main Form -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    <div class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                        <i class="uil uil-file-alt"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center flex-grow-1">
                                                <div class="me-3">
                                                    <h5 class="font-size-16 mb-1">Gate Pass Form</h5>
                                                    <p class="text-muted text-truncate mb-0">Create a gate pass for Rent Bill No: <?php echo $EQUIPMENT_RENT->bill_number; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="p-4">
                                            <!-- Gate Pass Number & Bill Number Search -->
                                            <div class="row mb-4">
                                                <div class="col-md-2">
                                                    <label class="form-label">Gate Pass No</label>
                                                    <input type="text" class="form-control" id="gatepass_code_display" value="Loading...">
                                                    <input type="hidden" id="gatepass_code" name="gatepass_code">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Bill Number (බිල්පත් අංකය)</label>
                                                    <input type="text" class="form-control" id="search_bill_no" placeholder="Enter Bill No" value="<?php echo $EQUIPMENT_RENT->bill_number; ?>" readonly>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Date (දිනය)</label>
                                                    <input type="date" class="form-control" id="gatepass_date" name="gatepass_date" value="<?php echo date('Y-m-d'); ?>">
                                                </div> 
                                            <!-- Note: Database column is still named invoice_id, so we use it to store rent_id -->
                                            <input type="hidden" id="invoice_id" name="invoice_id" value="<?php echo $rent_id; ?>"> 
                                                <div class="col-md-3">
                                                    <label class="form-label">Name (නම)</label>
                                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($CUSTOMER->name); ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">ID Number (හැඳුනුම්පත් අංකය)</label>
                                                    <input type="text" class="form-control" id="id_number" name="id_number" value="<?php echo htmlspecialchars($CUSTOMER->nic ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="row mb-4">
                                                <div class="col-md-12">
                                                    <label class="form-label">Address (ලිපිනය)</label>
                                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($CUSTOMER->address); ?>">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-12">
                                                    <h5 class="font-size-16 mb-3">Items to Include in Gate Pass (නිකුත් කරන උපකරණ)</h5>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-striped" id="gatepassItemsTable">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th style="width: 50px;">#</th>
                                                                    <th>Item Name (උපකරණයේ නම)</th>
                                                                    <th style="width: 100px;">Billed Qty</th>
                                                                    <th style="width: 100px;">Prev. Issued</th>
                                                                    <th style="width: 100px;">Remaining</th>
                                                                    <th style="width: 120px;">Include Qty</th>
                                                                    <th>Remarks (කරුණු)</th>
                                                                    <th style="width: 50px;">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="gatepassItemsTableBody">
                                                                <tr id="empty_row">
                                                                    <td colspan="8" class="text-center py-4 text-muted">
                                                                        <i class="uil uil-box font-size-24 d-block mb-2"></i>
                                                                        Loading items...
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-4 mt-4">
                                                <div class="col-md-12">
                                                    <label class="form-label">Issued By (නිකුත් කරන්නාගේ නම)</label>
                                                    <input type="text" class="form-control" id="issued_by" name="issued_by" value="<?php echo $_SESSION['name'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            

                        </div>
                    </div>

                </div>
            </div>
            <?php include 'footer.php' ?>
        </div>
    </div>

    <div id="GatepassListModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="GatepassListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="GatepassListModalLabel">View Past Gatepasses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12 text-end">
                            <div class="input-group" style="max-width: 300px; float: right;">
                                <span class="input-group-text"><i class="uil uil-search"></i></span>
                                <input type="text" id="gatepassSearchInput" class="form-control" placeholder="Search by Code / Name / Bill" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="gatepassListTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#ID</th>
                                    <th>Gatepass No</th>
                                    <th>Bill Number</th>
                                    <th>Customer Name</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="gatepassListTableBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <?php include 'main-js.php' ?>
    <script src="ajax/js/gatepass.js"></script>

    <script>
        $(window).on('load', function() {
            $('#page-preloader').fadeOut('slow', function() {
                $(this).remove();
            });
        });
    </script>
</body>
</html>
