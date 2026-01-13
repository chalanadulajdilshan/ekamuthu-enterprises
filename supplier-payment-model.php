<!-- Payment Modal -->
<div class="modal fade" id="supplierPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-cash-coin me-2"></i> Finalize Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body bg-light">
                <form id="paymentForm">


                    <!-- Final Total -->
                    <div class="mb-3">
                        <label class="fw-bold">Final Total</label>
                        <input type="text" id="modalFinalTotal" class="form-control form-control-lg text-end fw-bold border-primary" value="" readonly>
                    </div>



                    <!-- Dynamic Payment Rows -->
                    <div id="paymentRows" class="mb-3"></div>

                    <button type="button" class="btn btn-outline-primary w-100 mb-4" id="addPaymentRow">
                        <i class="bi bi-plus-circle me-2"></i> Add Payment Method
                    </button>

                    <!-- Totals -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-bold">Total Paid</label>
                            <input type="text" id="totalPaid" class="form-control text-end bg-white fw-bold" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Balance</label>
                            <input type="text" id="balanceAmount" class="form-control text-end bg-white fw-bold" readonly>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="mt-4">
                        <label class="fw-bold">Note</label>
                        <textarea id="note" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-success" id="savePayment">
                            <i class="bi bi-check2-circle me-1"></i> Save Payment
                        </button>
                    </div>
                </form>
            </div>


        </div>
    </div>
</div>


<!-- Dependencies -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="assets/libs/jquery/jquery.min.js"></script>

<!-- JS Logic -->
<script>
    (function() {
        const supplierModal = document.getElementById('supplierPaymentModal');
        const finalTotal = supplierModal.querySelector('#modalFinalTotal');
        const totalPaid = supplierModal.querySelector('#totalPaid');
        const balanceAmount = supplierModal.querySelector('#balanceAmount');
        const paymentRows = supplierModal.querySelector('#paymentRows');
        const addPaymentRowBtn = supplierModal.querySelector('#addPaymentRow');

        let rowId = 0;

        // Function to create new payment row
        function createPaymentRow() {
            rowId++;
            const row = document.createElement('div');
            row.classList.add('payment-row', 'card', 'shadow-sm', 'mb-3');
            row.dataset.id = rowId;

            row.innerHTML = `
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="fw-semibold">Payment Type</label>
                            <select name="paymentType[]" class="form-select paymentType" required>
                                <option value="">-- Select --</option>
                                <option value="1">Cash</option>
                                <option value="2">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold">Amount</label>
                            <input type="number" name="amount[]" class="form-control paymentAmount text-end fw-bold" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-4 chequeDetails d-none">
                            <label class="fw-semibold">Cheque No</label>
                            <input type="text" name="chequeNumber[]" class="form-control mb-2">
                            <label class="fw-semibold">Bank</label>
                            <input type="text" name="chequeBank[]" class="form-control mb-2">
                            <label class="fw-semibold">Date</label>
                            <input type="date" name="chequeDate[]" class="form-control">
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-outline-danger btn-sm removeRow">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            paymentRows.appendChild(row);

            // Add event listeners for the new row
            const paymentTypeSelect = row.querySelector('.paymentType');
            const chequeDetails = row.querySelector('.chequeDetails');
            const amountInput = row.querySelector('.paymentAmount');

            // Default to Cash
            paymentTypeSelect.value = "1";
            paymentTypeSelect.dispatchEvent(new Event('change'));

            // Handle payment type change
            paymentTypeSelect.addEventListener('change', function() {
                const isCheque = this.options[this.selectedIndex].text.toLowerCase() === 'cheque';
                chequeDetails.classList.toggle('d-none', !isCheque);
            });

            // Handle amount input
            amountInput.addEventListener('input', updateTotals);
        }

        // Update totals
        function updateTotals() {
            let total = 0;
            supplierModal.querySelectorAll('.paymentAmount').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            totalPaid.value = total.toFixed(2);
            balanceAmount.value = (parseFloat(finalTotal.value) - total).toFixed(2);
        }

        // Add click handler for the add payment row button
        addPaymentRowBtn.addEventListener('click', function(e) {
            e.preventDefault();
            createPaymentRow();
        });

        // Reset modal on open to ensure a clean state
        supplierModal.addEventListener('shown.bs.modal', function() {
            // Clear existing payment rows
            paymentRows.innerHTML = '';
            // Reset totals
            totalPaid.value = '0.00';
            const tot = parseFloat(finalTotal.value) || 0;
            balanceAmount.value = (tot - 0).toFixed(2);
            // Add one fresh row
            createPaymentRow();

            // Prefill the first payment row amount with the Final Total
            const firstAmountInput = paymentRows.querySelector('.paymentAmount');
            if (firstAmountInput && tot > 0) {
                firstAmountInput.value = tot.toFixed(2);
                // Recalculate Total Paid and Balance based on this default amount
                updateTotals();
            }
        });

        // Handle remove row button clicks (scoped to this modal)
        supplierModal.addEventListener('click', function(e) {
            if (e.target.closest('.removeRow')) {
                e.preventDefault();
                const row = e.target.closest('.payment-row');
                if (row) {
                    row.remove();
                    updateTotals();
                }
            }
        });

        // Handle payment type and amount changes using event delegation (scoped)
        supplierModal.addEventListener('change', function(e) {
            if (e.target.matches('.paymentType')) {
                const row = e.target.closest('.payment-row');
                if (row) {
                    const paymentTypeSelect = row.querySelector('.paymentType');
                    const chequeDetails = row.querySelector('.chequeDetails');
                    const isCheque = paymentTypeSelect.options[paymentTypeSelect.selectedIndex].text.toLowerCase() === 'cheque';
                    chequeDetails.classList.toggle('d-none', !isCheque);
                }
            } else if (e.target.matches('.paymentAmount')) {
                updateTotals();
            }
        });

    })();
</script>