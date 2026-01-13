<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$SERVICE = new Service(NULL);

// Get the last inserted service id
$lastId = $SERVICE->getLastID();
$service_id = 'S/0' . ($lastId + 1);

?>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Service Master | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
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
                                <a href="#" class="btn btn-primary" id="create-service">
                                    <i class="uil uil-save me-1"></i> Save
                                </a>
                            <?php endif; ?>

                            <?php if ($PERMISSIONS['edit_page']): ?>
                                <a href="#" class="btn btn-warning" id="update-service" style="display:none;">
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
                                <li class="breadcrumb-item active">Service Master</li>
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
                                            <h5 class="font-size-16 mb-1">Service Master</h5>
                                            <p class="text-muted text-truncate mb-0">Fill all information below to add services</p>
                                        </div>
                                        
                                    </div>
                                </div>

                                <div class="p-4">
                                    <form id="form-data" autocomplete="off">
                                        <div class="row">
                                            <!-- Service Code -->
                                            <div class="col-md-3">
                                                <input type="hidden" id="service_id" name="service_id">
                                                <label class="form-label" for="service_code">Service Code</label>
                                                <div class="input-group mb-3">
                                                    <input id="service_code" name="service_code" type="text" class="form-control"
                                                        placeholder="Enter Service Code" value="<?php echo $service_id ?>" readonly>
                                                <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                                    data-bs-target="#service_modal">
                                                    <i class="uil uil-search me-1"></i>
                                                </button>
                                                </div>
                                            </div>

                                            <!-- Service Name -->
                                            <div class="col-md-7">
                                                <label class="form-label" for="service_name">Service Name</label>
                                                <div class="input-group mb-3">
                                                    <input id="service_name" name="service_name" type="text" class="form-control"
                                                        placeholder="Enter Service Name">
                                                </div>
                                            </div>

                                            <!-- Price -->
                                            <div class="col-md-2">
                                                <label class="form-label" for="service_price">Price</label>
                                                <div class="input-group mb-3">
                                                    <input id="service_price" name="service_price" type="number" step="0.01"
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

    <!-- Modal for Services -->
    <div class="modal fade" id="service_modal" tabindex="-1" role="dialog" aria-labelledby="serviceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceModalLabel">Manage Services</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <table class="datatable table table-bordered dt-responsive nowrap" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Service Name</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $SERVICE = new Service(null);
                                    foreach ($SERVICE->all() as $key => $service) {
                                        $key++;
                                    ?>
                                        <tr class="select-service"
                                            data-id="<?php echo $service['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($service['service_name']); ?>"
                                            data-price="<?php echo $service['service_price']; ?>">
                                            <td><?php echo $key; ?></td>
                                            <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                            <td><?php echo number_format($service['service_price'], 2); ?></td>
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

    <div class="rightbar-overlay"></div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="ajax/js/service.js"></script>
    <?php include 'main-js.php' ?>

    <script>
        $('.datatable').DataTable();
    </script>

</body>

</html>