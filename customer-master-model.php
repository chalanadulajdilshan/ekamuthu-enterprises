<!-- Customer Modal -->
<div id="customerModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Manage Customers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="customerSearchInput" class="form-label">Search by Code / Name / Mobile / Company</label>
                            <input type="text" id="customerSearchInput" class="form-control" placeholder="Type to search customers" autocomplete="off">
                        </div>
                        <div class="table-responsive" style="max-height: 60vh;">
                            <table id="customerTable" class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:80px;">Code</th>
                                        <th>Name</th>
                                        <th style="width:140px;">Mobile</th>
                                        <th>Company</th>
                                        <th style="width:120px;">Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody id="customerSearchResults">
                                    <tr><td colspan="5" class="text-center text-muted">Start typing to search customers...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>