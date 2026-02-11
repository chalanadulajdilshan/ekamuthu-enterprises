<div id="department_stock" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="brandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="department_stockModalLabel">Department Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row mb-3">
                    <?php
                    // Check if we're on the stock transfer page
                    $isStockTransferPage = (basename($_SERVER['PHP_SELF']) === 'stock-transfer.php');

                    if (!$isStockTransferPage):
                    ?>
                        <div class="col-md-3">
                            <label for="filter_department_id" class="form-label">Department</label>
                            <div class="input-group">
                                <select id="filter_department_id" name="filter_department_id" class="form-select">
                                    <?php
                                    $DEPARTMENT_MASTER = new DepartmentMaster(NULL);
                                    foreach ($DEPARTMENT_MASTER->getActiveDepartment() as $department) {
                                        if ($US->type != 1) {
                                            if ($department['id'] == $US->department_id) {
                                    ?>
                                                <option value="<?php echo $department['id'] ?>" selected>
                                                    <?php echo $department['name'] ?>
                                                </option>
                                            <?php }
                                        } else {
                                            ?>
                                            <option value="<?php echo $department['id'] ?>">
                                                <?php echo $department['name'] ?>
                                            </option>
                                    <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                    <?php else: ?>

                    <?php endif; ?>
                </div>
                <div class="row">
                    <div class="col-12">
                        <table id="departmentStockTable" class="table table-bordered dt-responsive nowrap" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Department</th>
                                    <th>Qty</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                
                <hr>

                <!-- Add Stock Form -->
                <div class="row">
                    <div class="col-12">
                        <h5>Add New Stock</h5>
                        <form id="form-department-stock" autocomplete="off">
                            <input type="hidden" id="stock_equipment_id" name="equipment_id">
                            <input type="hidden" id="stock_id" name="id">
                            <div class="row align-items-end">
                                <div class="col-md-5">
                                    <label for="stock_department_id" class="form-label">Department <span class="text-danger">*</span></label>
                                    <select id="stock_department_id" name="department_id" class="form-select" required>
                                        <option value="">- Select Department -</option>
                                        <?php
                                        $DEPARTMENT_MASTER = new DepartmentMaster(NULL);
                                        foreach ($DEPARTMENT_MASTER->all() as $department) {
                                            echo '<option value="' . $department['id'] . '">' . htmlspecialchars($department['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="stock_qty" class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" id="stock_qty" name="qty" class="form-control" placeholder="0" min="0" required>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" id="save_stock" class="btn btn-primary w-100">
                                        <i class="uil uil-plus me-1"></i> Add Stock
                                    </button>
                                    <button type="button" id="update_stock" class="btn btn-warning w-100" style="display: none;">
                                        <i class="uil uil-edit me-1"></i> Update
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>