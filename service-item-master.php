<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$SERVICE_ITEM = new ServiceItem(NULL);

// Get the last inserted package id
$lastId = $SERVICE_ITEM->getLastID();
$service_id = 'SI/0' . ($lastId + 1);

?>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Service Item Master | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

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
                                <a href="#" class="btn btn-warning" id="update" style="display:none;">
                                    <i class="uil uil-edit me-1"></i> Update
                                </a>
                            <?php endif; ?>

                            <?php if ($PERMISSIONS['delete_page']): ?>
                                <a href="#" class="btn btn-danger delete-service">
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Service Item Master</li>
                            </ol>
                        </div>
                    </div>

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
                                            <h5 class="font-size-16 mb-1">Service Item Master</h5>
                                            <p class="text-muted text-truncate mb-0">Fill all information below to add service items</p>
                                        </div>
                                       
                                    </div>
                                </div>

                                <div class="p-4">
                                    <form id="form-data" autocomplete="off">
                                        <div class="row">
                                            <!-- Item Code -->
                                            <div class="col-md-3">
                                                <input type="hidden" id="item_id" name="item_id">
                                                <input type="hidden" id="current_qty" name="current_qty">
                                                <label class="form-label" for="item_code">Item Code</label>
                                                <div class="input-group mb-3">
                                                    <input id="item_code" name="item_code" type="text" class="form-control"
                                                        placeholder="Enter Item Code" value="<?php echo $service_id ?>" readonly>
                                                    <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#service_item_modal">
                                                        <i class="uil uil-search me-1"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Item Name -->
                                            <div class="col-md-3">
                                                <label class="form-label" for="item_name">Item Name</label>
                                                <div class="input-group mb-3">
                                                    <input id="item_name" name="item_name" type="text" class="form-control"
                                                        placeholder="Enter Item Name">
                                                </div>
                                            </div>

                                            <!-- Cost -->
                                            <div class="col-md-2">
                                                <label class="form-label" for="cost">Cost</label>
                                                <div class="input-group mb-3">
                                                    <input id="cost" name="cost" type="number" step="0.01"
                                                        class="form-control" placeholder="0.00">
                                                </div>
                                            </div>
                                            <!-- Selling Price -->
                                            <div class="col-md-2">
                                                <label class="form-label" for="selling_price">Selling Price</label>
                                                <div class="input-group mb-3">
                                                    <input id="selling_price" name="selling_price" type="number" step="0.01"
                                                        class="form-control" placeholder="0.00">
                                                </div>
                                            </div>
                                            <!-- QTY -->
                                            <div class="col-md-2" id="qty_col">
                                                <label class="form-label" for="qty">Qty</label>
                                                <div class="input-group mb-3">
                                                    <input id="qty" name="qty" type="number" step="0.01"
                                                        class="form-control" placeholder="0.00">
                                                </div>
                                            </div>
                                            <!-- Adjust Qty -->
                                            <div class="col-md-1 d-none" id="adjust_qty_col">
                                                <label class="form-label" for="adjust_qty">Adjust Qty</label>
                                                <div class="input-group mb-3">
                                                    <input id="adjust_qty" name="adjust_qty" type="number" step="0.01"
                                                        class="form-control" placeholder="0.00">
                                                </div>
                                            </div>

                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include 'footer.php' ?>

        </div>
    </div>

    <!-- Modal for Service Items -->
    <div class="modal fade" id="service_item_modal" tabindex="-1" role="dialog" aria-labelledby="serviceItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceItemModalLabel">Manage Service Items</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table class="datatable table table-bordered dt-responsive nowrap" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>Cost</th>
                                        <th>Selling Price</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $ITEM = new ServiceItem(null);
                                    foreach ($ITEM->all() as $key => $item) {
                                        ?>
                                        <tr class="select-item"
                                            data-id="<?php echo $item['id']; ?>"
                                            data-code="<?php echo htmlspecialchars($item['item_code']); ?>"
                                            data-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                            data-cost="<?php echo $item['cost']; ?>"
                                            data-selling="<?php echo $item['selling_price']; ?>"
                                            data-qty="<?php echo $item['qty']; ?>">
                                            <td><?php echo $key; ?></td>
                                            <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo number_format($item['cost'], 2); ?></td>
                                            <td><?php echo number_format($item['selling_price'], 2); ?></td>
                                            <td><?php echo number_format($item['qty'], 2); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="ajax/js/service-item.js"></script>
    <?php include 'main-js.php' ?>

    <script>
        $('.datatable').DataTable();
    </script>

</body>
</html>
