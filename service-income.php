<!doctype html>

<?php
include 'class/include.php';
include 'auth.php';

$SERVICE_INCOME = new ServiceIncome();

// Generate next Ref No
$lastId = $SERVICE_INCOME->getLastID();
$service_income_id = 'SI/0' . ($lastId + 1);
?>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Service Income | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <?php include 'main-css.php' ?>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">
    <div id="layout-wrapper">

```
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
                        <a href="#" class="btn btn-danger delete-service-income">
                            <i class="uil uil-trash-alt me-1"></i> Delete
                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                        <ol class="breadcrumb m-0 justify-content-md-end">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item active">SERVICE INCOME</li>
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
                                        <h5 class="font-size-16 mb-1">Service Income</h5>
                                        <p class="text-muted text-truncate mb-0">Fill all information below</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="mdi mdi-chevron-up accor-down-icon font-size-24"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4">
                                <form id="form-data" autocomplete="off">
                                    <div class="row">

                                        <div class="col-md-2">
                                            <label class="form-label" for="code">Ref No </label>
                                            <div class="input-group mb-3">
                                                <input id="code" name="code" type="text" value="<?php echo $service_income_id; ?>"
                                                    placeholder="Ref No" class="form-control" readonly>
                                                <button class="btn btn-info" type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#serviceIncomeModel">
                                                    <i class="uil uil-search me-1"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="name" class="form-label">Service Name</label>
                                            <div class="input-group mb-3">
                                                <input id="name" name="name" type="text" placeholder="Enter Service Name" class="form-control">
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="amount" class="form-label">Amount</label>
                                            <div class="input-group mb-3">
                                                <input id="amount" name="amount" type="number" step="0.01" placeholder="Enter Amount" class="form-control">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="remark" class="form-label">Remark</label>
                                            <div class="input-group mb-3">
                                                <textarea id="remark" name="remark" placeholder="Enter Remark" class="form-control" rows="1"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" id="id" name="id" value="0">
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

<!-- Modal -->
<div class="modal fade bs-example-modal-xl" id="serviceIncomeModel" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Manage Service Income</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <table class="datatable table table-bordered dt-responsive nowrap"
                               style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Ref No</th>
                                    <th>Service Name</th>
                                    <th>Amount</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $SERVICE_INCOME = new ServiceIncome(null);
                                foreach ($SERVICE_INCOME->all() as $key => $income) {
                                    $key++;
                                    ?>
                                    <tr class="select-service-income"
                                        data-id="<?php echo $income['id']; ?>"
                                        data-code="<?php echo htmlspecialchars('SI/0' . $income['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($income['name']); ?>"
                                        data-amount="<?php echo htmlspecialchars($income['amount']); ?>"
                                        data-remark="<?php echo htmlspecialchars($income['remark']); ?>">

                                        <td><?php echo $key; ?></td>
                                        <td><?php echo htmlspecialchars('SI/0' . $income['id']); ?></td>
                                        <td><?php echo htmlspecialchars($income['name']); ?></td>
                                        <td><?php echo htmlspecialchars($income['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($income['remark']); ?></td>
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
<script src="ajax/js/service-income.js"></script>
<?php include 'main-js.php' ?>

</body>
</html>
