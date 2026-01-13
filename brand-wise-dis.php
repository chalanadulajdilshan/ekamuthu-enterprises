<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Brandwise Discount | <?php echo $COMPANY_PROFILE_DETAILS->name ?> </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="<?php echo $COMPANY_PROFILE_DETAILS->name ?>" name="author" />
    <!-- include main CSS -->
    <?php include 'main-css.php' ?>
</head>

<body data-layout="horizontal" data-topbar="colored" class="someBlock">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include 'navigation.php' ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- page header -->
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
                                <a href="#" class="btn btn-danger delete-discount"  >
                                    <i class="uil uil-trash-alt me-1"></i> Delete
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <ol class="breadcrumb m-0 justify-content-md-end">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Brandwise Discount</li>
                            </ol>
                        </div>
                    </div>

                    <!-- main form -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div id="discount-accordion" class="custom-accordion">
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
                                                <h5 class="font-size-16 mb-1">Brandwise Discount</h5>
                                                <p class="text-muted text-truncate mb-0">Fill details for brand-wise discounts</p>
                                            </div>
                                            
                                        </div>
                                    </div>

                                    <div class="p-4">
                                        <form id="form-data" method="post" enctype="multipart/form-data" autocomplete="off">
                                            <div class="row">

                                                <!-- Category -->
                                                <div class="col-md-3">
                                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                                    <select id="category" name="category" class="form-select">
                                                        <option value="">-- All Category --</option>
                                                        <?php
                                                        $CATEGORY = new CategoryMaster(NULL);
                                                        foreach ($CATEGORY->getActiveCategory() as $category) {
                                                            echo "<option value='{$category['id']}'>{$category['name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <!-- Brand -->
                                                <div class="col-md-3">
                                                    <label for="brand_id" class="form-label">Brand <span class="text-danger">*</span></label>
                                                    <select id="brand_id" name="brand_id" class="form-select" required>
                                                        <?php
                                                        $BRAND = new Brand(NULL);
                                                        foreach ($BRAND->all() as $brand) {
                                                            echo "<option value='{$brand['id']}'>{$brand['name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <!-- Discount -->
                                                <div class="col-md-2">
                                                    <label for="discount_percent_01" class="form-label">Discount 01 %</label>
                                                    <div class="input-group mb-3">
                                                        <input id="discount_percent_01" name="discount_percent_01" type="text" class="form-control" placeholder="Enter Discount %">
                                                    </div>
                                                </div>

                                                <div class="col-md-2">
                                                    <label for="discount_percent_02" class="form-label">Discount 02 %</label>
                                                    <div class="input-group mb-3">
                                                        <input id="discount_percent_02" name="discount_percent_02" type="text" class="form-control" placeholder="Enter Discount %">
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="discount_percent_03" class="form-label">Discount 03 %</label>
                                                    <div class="input-group mb-3">
                                                        <input id="discount_percent_03" name="discount_percent_03" type="text" class="form-control" placeholder="Enter Discount %">
                                                    </div>
                                                </div>
                                                <input type="hidden" id="dis_id" name="dis_id">
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- discount list -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <table class="datatable table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>#ID</th>
                                                <th>Category</th>
                                                <th>Brand</th>
                                                <th>Discount 01 %</th>
                                                <th>Discount 02 %</th>
                                                <th>Discount 03 %</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $BRANDWISE_DIS = new BrandWiseDis(NULL);
                                            foreach ($BRANDWISE_DIS->all() as $key => $dis) {
                                                $key++;
                                                $CATEGORY = new CategoryMaster($dis['category_id']);
                                                $BRAND = new Brand($dis['brand_id']);
                                            ?>
                                                <tr class="select-dis" 
                                                    data-id="<?php echo $dis['id']; ?>"
                                                    data-category="<?php echo $dis['category_id']; ?>"
                                                    data-brand="<?php echo $dis['brand_id']; ?>"
                                                    data-discount_01="<?php echo htmlspecialchars($dis['discount_percent_01']); ?>"
                                                    data-discount_02="<?php echo htmlspecialchars($dis['discount_percent_02']); ?>"
                                                    data-discount_03="<?php echo htmlspecialchars($dis['discount_percent_03']); ?>">

                                                    <td><?php echo $key; ?></td>
                                                    <td><?php echo htmlspecialchars($CATEGORY->name); ?></td>
                                                    <td><?php echo htmlspecialchars($BRAND->name); ?></td>
                                                    <td><?php echo htmlspecialchars($dis['discount_percent_01']); ?>%</td>
                                                    <td><?php echo htmlspecialchars($dis['discount_percent_02']); ?>%</td>
                                                    <td><?php echo htmlspecialchars($dis['discount_percent_03']); ?>%</td>
                                                    <td>
                                                        <button type="button" class="btn btn-primary btn-sm edit-dis" data-id="<?php echo $dis['id']; ?>">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
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
    <script src="ajax/js/brand-wise-dis.js"></script>
    
    <?php include 'main-js.php' ?>

</body>
</html>
