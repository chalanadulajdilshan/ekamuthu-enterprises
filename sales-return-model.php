<div id="salesReturnModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="salesReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salesReturnModalLabel">Sales Return Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- Search Bar -->
                        <div class="input-group mb-2">
                            <input type="text" id="grnSearch" class="form-control" placeholder="Search return no / invoice no / customer">
                            <button class="btn btn-primary" id="grnSearchBtn">Search</button>
                        </div>

                        <!-- Sales Return Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="grnTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#ID</th>
                                        <th>Return No</th>
                                        <th>Return Date</th>
                                        <th>Invoice No</th>
                                        <th>Customer</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="grnTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div> <!-- end col -->
                </div> <!-- end row -->
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
