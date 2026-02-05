<!-- Customer Modal -->
<div id="AllCustomerModal" class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog"
    aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Manage Customers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <input type="text" id="customerSearchInput" class="form-control" placeholder="Search by NIC, Name, or Mobile Number...">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table id="allCustomerTable" class="table table-bordered table-hover w-100">
                                <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th>#ID</th>
                                        <th>Code</th>
                                        <th>Type</th>
                                        <th>Name</th>
                                        <th>NIC</th>
                                        <th>Mobile</th>
                                        <th>Address</th>
                                        <th>Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted" id="customerTableStatus">Ready</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>