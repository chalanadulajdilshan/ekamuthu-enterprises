jQuery(document).ready(function ($) {
  // Configuration constants
  const CONFIG = {
    CHEQUE_NO_REGEX: /^\d{6,12}$/,
    MIN_AMOUNT: 0.01,
    SWAL_TIMEOUT: 3000,
  };

  // Centralized state management
  const state = {
    chequeInfo: [],
    totalUsed: 0,
    totalAvailable: 0,
    cashTotal: 0,
    cashBalance: 0,
    totalOutstanding: 0,
  };
  
  let paymentReceiptTableInitialized = false;

  // Utility functions
  function formatAmount(amount) {
    return parseFloat(amount || 0).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function parseAmount(value) {
    let num = parseFloat(value.toString().replace(/,/g, ""));
    return isNaN(num) ? 0 : num;
  }

  function debounce(func, wait) {
    let timeout;
    return function (...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }

  function isValidChequeNo(chequeNo) {
    return CONFIG.CHEQUE_NO_REGEX.test(chequeNo);
  }

  function isValidDate(dateStr) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const inputDate = new Date(dateStr);
    return !isNaN(inputDate.getTime()) && inputDate >= today;
  }

  // State and UI updates
  function updateState($excludeRow = null) {
    state.totalUsed = 0;
    state.totalAvailable = 0;
    let totalPaid = 0;
    let totalBalance = 0;
    let totalChequeAmount = 0;

    // Update cheque usage
    state.chequeInfo.forEach((cheque) => {
      let usedAmount = 0;
      $(".cheque-select").each(function () {
        const $select = $(this);
        const $row = $select.closest("tr");
        if (
          $select.val() === cheque.id &&
          (!$excludeRow || !$row.is($excludeRow))
        ) {
          usedAmount += parseAmount($row.find(".cheque-pay").val());
        }
      });

      cheque.usedAmount = usedAmount;
      cheque.remaining = Math.max(0, cheque.amount - usedAmount);
      cheque.used = cheque.remaining <= CONFIG.MIN_AMOUNT;
      state.totalUsed += usedAmount;
      state.totalAvailable += cheque.remaining;
      totalChequeAmount += cheque.amount;
    });

    // Update cash and totals
    state.cashTotal = parseAmount($("#cash_total").val());
    const totalCashPay = calculateTotalCashPay($excludeRow);
    state.cashBalance = state.cashTotal - totalCashPay;

    // Calculate total outstanding, paid amount, and balance amount
    state.totalOutstanding = 0;
    $("#invoiceBody tr")
      .not("#noItemRow")
      .each(function () {
        const overdue = parseAmount($(this).find(".invoice-overdue").text());
        const chequePay = parseAmount($(this).find(".cheque-pay").val());
        const cashPay = parseAmount($(this).find(".cash-pay").val());
        const paidAmount = chequePay + cashPay;
        const balance = overdue - paidAmount;

        state.totalOutstanding += overdue;
        totalPaid += paidAmount;
        totalBalance += balance;
      });

    $("#total_outstanding").val(formatAmount(state.totalOutstanding));
    $("#paid_amount").val(formatAmount(totalPaid));
    $("#balance_amount").val(formatAmount(totalBalance));
    $("#cheque_balance").val(formatAmount(state.totalAvailable));
    $("#cash_balance").val(formatAmount(state.cashBalance));
    $("#outstanding").val(formatAmount(state.totalOutstanding));

    $("#cheque_total").val(formatAmount(totalChequeAmount));
    updateChequeDropdowns();
    updateTotals();
    updateChequePayDisabledState();
  }

  function calculateTotalCashPay($excludeRow = null) {
    let total = 0;
    $(".cash-pay").each(function () {
      const $row = $(this).closest("tr");
      if (!$excludeRow || !$row.is($excludeRow)) {
        total += parseAmount($(this).val());
      }
    });
    return total;
  }

  function updateChequeDropdowns() {
    const $selects = $(".cheque-select");
    $selects.each(function () {
      const $select = $(this);
      const selectedValue = $select.val();
      const $row = $select.closest("tr");

      $select
        .find("option")
        .not(":first")
        .each(function () {
          const chequeId = $(this).val();
          const cheque = state.chequeInfo.find((c) => c.id === chequeId);
          if (cheque) {
            const isSelected = cheque.id === selectedValue;
            const displayAmount = isSelected
              ? formatAmount(cheque.amount)
              : `${formatAmount(cheque.remaining)} of ${formatAmount(
                  cheque.amount
                )}`;
            $(this).text(`${cheque.chequeNo} (${displayAmount})`);
            $(this).prop("disabled", cheque.used && !isSelected);
          } else {
            $(this).remove();
          }
        });

      state.chequeInfo.forEach((cheque) => {
        if (
          !$select.find(`option[value="${cheque.id}"]`).length &&
          cheque.remaining > CONFIG.MIN_AMOUNT
        ) {
          $select.append(
            $("<option>", {
              value: cheque.id,
              "data-amount": cheque.remaining,
              disabled: cheque.used,
            }).text(
              `${cheque.chequeNo} (${formatAmount(
                cheque.remaining
              )} of ${formatAmount(cheque.amount)})`
            )
          );
        }
      });

      if (
        $select.find(`option[value="${selectedValue}"]`).length &&
        selectedValue
      ) {
        $select.val(selectedValue);
      } else {
        $select.val("");
        $row.find(".cheque-pay").val("0.00");
      }
    });
  }

  function updateRowBalance($row) {
    const overdue = parseAmount($row.find(".invoice-overdue").text());
    const chequePay = parseAmount($row.find(".cheque-pay").val());
    const cashPay = parseAmount($row.find(".cash-pay").val());
    const paidAmount = chequePay + cashPay;
    const remaining = overdue - paidAmount;
    $row.find(".paid-amount").text(formatAmount(paidAmount));
    $row.find(".balance-amount").text(formatAmount(remaining));
  }

  function updateChequePayDisabledState() {
    $(".cheque-select").each(function () {
      const $select = $(this);
      const $row = $select.closest("tr");
      const $chequeInput = $row.find(".cheque-pay");
      const selectedChequeId = $select.val();

      if (selectedChequeId) {
        $chequeInput.prop("disabled", false);
      } else {
        $chequeInput.prop("disabled", true);
      }
    });
  }

  function validateChequePayment($input, $row) {
    let inputVal = parseAmount($input.val());
    const overdue = parseAmount($row.find(".invoice-overdue").text());
    const selectedChequeId = $row.find(".cheque-select").val();
    if (!selectedChequeId) {
      $input.val("0.00");
      updateRowBalance($row); // Update Paid Amount and Balance Amount
      updateState();
      return 0;
    }

    const selectedCheque = state.chequeInfo.find(
      (c) => c.id === selectedChequeId
    );
    if (!selectedCheque) {
      $input.val("0.00");
      updateRowBalance($row); // Update Paid Amount and Balance Amount
      updateState();
      return 0;
    }

    let usedAmount = 0;
    $(".cheque-select").each(function () {
      const $currentSelect = $(this);
      const $currentRow = $currentSelect.closest("tr");
      if ($currentSelect.val() === selectedChequeId && !$currentRow.is($row)) {
        usedAmount += parseAmount($currentRow.find(".cheque-pay").val());
      }
    });

    const remainingBalance = selectedCheque.amount - usedAmount;
    const maxAllowed = Math.min(overdue, remainingBalance);

    if (inputVal > maxAllowed) {
      inputVal = maxAllowed;
      $input.val(formatAmount(maxAllowed));
      swal({
        title: "Invalid Amount!",
        text: `You can't enter more than Overdue (Rs. ${formatAmount(
          overdue
        )}) or Remaining Cheque Balance (Rs. ${formatAmount(
          remainingBalance
        )})`,
        type: "error",
        timer: CONFIG.SWAL_TIMEOUT,
        showConfirmButton: false,
      });
    }
    return inputVal;
  }

  function validateCashPayment($input, $row) {
    let inputVal = parseAmount($input.val());
    const overdue = parseAmount($row.find(".invoice-overdue").text());

    let usedCash = 0;
    $(".cash-pay").each(function () {
      const $currentRow = $(this).closest("tr");
      if (!$currentRow.is($row)) {
        usedCash += parseAmount($(this).val());
      }
    });

    const remainingCashBalance = state.cashTotal - usedCash;
    const maxAllowed = Math.min(overdue, remainingCashBalance);

    if (inputVal > maxAllowed) {
      inputVal = maxAllowed;
      $input.val(formatAmount(maxAllowed));

      let errorMessage = "";
      if (inputVal > overdue && inputVal > remainingCashBalance) {
        errorMessage = `You can't enter more than Overdue (Rs. ${formatAmount(
          overdue
        )}) or Cash Available Balance (Rs. ${formatAmount(
          remainingCashBalance
        )})`;
      } else if (inputVal > overdue) {
        errorMessage = `You can't enter more than Overdue Amount (Rs. ${formatAmount(
          overdue
        )})`;
      } else {
        errorMessage = `You can't enter more than Cash Available Balance (Rs. ${formatAmount(
          remainingCashBalance
        )})`;
      }

      swal({
        title: "Invalid Amount!",
        text: errorMessage,
        type: "error",
        timer: CONFIG.SWAL_TIMEOUT,
        showConfirmButton: false,
      });
    }
    return inputVal;
  }

  function validateOutstandingLimit() {
    const chequeTotal = parseAmount($("#cheque_total").val());
    const cashTotal = parseAmount($("#cash_total").val());
    const totalAmount = chequeTotal + cashTotal;
    const outstanding = parseAmount($("#total_outstanding").val());

    return totalAmount <= outstanding;
  }

  function toggleCashPay() {
    const cashTotal = parseAmount($("#cash_total").val());
    if (cashTotal > 0) {
      $(".cash-pay").prop("disabled", false);
    } else {
      $(".cash-pay").prop("disabled", true).val("0.00");
      $("#invoiceBody tr")
        .not("#noItemRow")
        .each(function () {
          updateRowBalance($(this)); // Update Paid Amount and Balance Amount when cash is disabled
        });
      updateState();
    }
  }

  // Event handlers
  $(document).on("change", ".cheque-select", function () {
    const $select = $(this);
    const $row = $select.closest("tr");

    // Update hidden input fields with selected cheque details
    const selectedOption = $select.find("option:selected");
    if (selectedOption.val()) {
      const chequeId = selectedOption.val();
      const cheque = state.chequeInfo.find((c) => c.id === chequeId);
      if (cheque) {
        $row.find(".cheque-no").val(cheque.chequeNo);
        $row.find(".cheque-date").val(cheque.chequeDate);
        $row.find(".bank-branch").val(cheque.bankBranchId);
      }
    } else {
      // Clear the fields if no cheque is selected
      $row.find(".cheque-no, .cheque-date, .bank-branch").val("");
    }
    const selectedChequeId = $select.val();
    const $chequeInput = $row.find(".cheque-pay");
    const prevChequeId = $select.data("prev-cheque");

    if (prevChequeId && prevChequeId !== selectedChequeId) {
      $select.removeData("prev-cheque");
    }

    if (selectedChequeId) {
      const selectedCheque = state.chequeInfo.find(
        (c) => c.id === selectedChequeId
      );
      if (selectedCheque) {
        $select.data("prev-cheque", selectedChequeId);
        const maxAmount = parseAmount($row.find(".invoice-overdue").text());
        let usedAmount = 0;
        $(".cheque-select").each(function () {
          const $currentSelect = $(this);
          const $currentRow = $currentSelect.closest("tr");
          if (
            $currentSelect.val() === selectedChequeId &&
            !$currentRow.is($row)
          ) {
            usedAmount += parseAmount($currentRow.find(".cheque-pay").val());
          }
        });
        const remainingBalance = selectedCheque.amount - usedAmount;
        if (prevChequeId !== selectedChequeId) {
          const chequeAmount = Math.min(remainingBalance, maxAmount);
          $chequeInput.val(formatAmount(chequeAmount));
          // Focus on the cheque pay field immediately
          setTimeout(() => {
            $chequeInput.focus();
          }, 0);
        }
      }
      // Enable the cheque pay field when cheque is selected
      $chequeInput.prop("disabled", false);
    } else {
      $chequeInput.val("0.00");
      // Disable the cheque pay field when no cheque is selected
      $chequeInput.prop("disabled", true);
    }

    updateRowBalance($row); // Update Paid Amount and Balance Amount
    updateState();
  });

  $(document).on("focus", ".cheque-pay, .cash-pay", function () {
    const $input = $(this);
    let value = $input.val().replace(/[^0-9.]/g, "");
    $input.val(value === "0" || value === "0.00" ? "" : value);
    // Set cursor to the beginning of the input
    setTimeout(() => {
      $input[0].setSelectionRange(0, 0);
    }, 0);
  });

  $(document).on(
    "input",
    ".cheque-pay",
    debounce(function () {
      const $input = $(this);
      const $row = $input.closest("tr");
      let value = $input.val().replace(/[^0-9.]/g, "");
      if (value === "") {
        $input.val("0.00");
        // Set cursor to the beginning
        setTimeout(() => {
          $input[0].setSelectionRange(0, 0);
        }, 0);
        updateRowBalance($row); // Update Paid Amount and Balance Amount
        updateState();
        return;
      }
      validateChequePayment($input, $row);
      updateRowBalance($row); // Update Paid Amount and Balance Amount
      updateState();
    }, 300)
  );

  $(document).on(
    "input",
    ".cash-pay",
    debounce(function () {
      const $input = $(this);
      const $row = $input.closest("tr");
      let value = $input.val().replace(/[^0-9.]/g, "");
      if (value === "") {
        $input.val("0.00");
        // Set cursor to the beginning
        setTimeout(() => {
          $input[0].setSelectionRange(0, 0);
        }, 0);
        updateRowBalance($row); // Update Paid Amount and Balance Amount
        updateState();
        return;
      }
      validateCashPayment($input, $row);
      updateRowBalance($row); // Update Paid Amount and Balance Amount
      updateState();
    }, 300)
  );

  $(document).on("blur", ".cheque-pay, .cash-pay", function () {
    const $input = $(this);
    let amount = parseAmount($input.val()) || 0;
    $input.val(amount === 0 ? "0.00" : formatAmount(amount));
  });

  $("#add_cheque").on("click", function () {
    const chequeNo = $("#cheque_no").val().trim();
    const chequeDate = $("#cheque_date").val().trim();
    const bankBranch = $("#bank_branch_name").val().trim();
    const bankBranchId = $("#bank_branch").val().trim();
    const amount = parseAmount($("#amount").val());
    const chequeTotal = parseAmount($("#cheque_total").val());
    const cashTotal = parseAmount($("#cash_total").val());
    const outstanding = parseAmount($("#total_outstanding").val());

    if (!isValidChequeNo(chequeNo)) {
      return swal(
        "Invalid Cheque Number",
        "Cheque number should be 6–12 digits.",
        "error"
      );
    }
    if (!isValidDate(chequeDate)) {
      return swal(
        "Invalid Cheque Date",
        "Cheque date must be today or a future date.",
        "error"
      );
    }
    if (!bankBranch || !bankBranchId) {
      return swal(
        "Missing Bank",
        "Please select a valid Bank & Branch.",
        "error"
      );
    }
    if (amount <= 0) {
      return swal(
        "Invalid Amount",
        "Amount should be a number greater than 0.",
        "error"
      );
    }

    if (chequeTotal + amount + cashTotal > outstanding) {
      return swal({
        title: "Exceeded Outstanding Amount!",
        text: `Total amount (Cheque: Rs. ${formatAmount(
          chequeTotal + amount
        )} + Cash: Rs. ${formatAmount(
          cashTotal
        )}) cannot exceed Outstanding Amount (Rs. ${formatAmount(
          outstanding
        )}).`,
        type: "error",
        timer: CONFIG.SWAL_TIMEOUT,
        showConfirmButton: false,
      });
    }

    $("#noItemRow").remove();
    const chequeId = "cheque_" + Date.now();
    state.chequeInfo.push({
      id: chequeId,
      chequeNo,
      chequeDate,
      bankBranch,
      bankBranchId,
      amount,
      used: false,
      usedAmount: 0,
      remaining: amount,
    });

    const newRow = `
      <tr data-cheque-id="${chequeId}">
        <td>${chequeNo}<input type="hidden" name="cheque_no[]" value="${chequeNo}"></td>
        <td>${chequeDate}<input type="hidden" name="cheque_dates[]" value="${chequeDate}"></td>
        <td>${bankBranch}<input type="hidden" name="bank_branches[]" value="${bankBranchId}"></td>
        <td class="cheque-amount" data-amount="${amount}">${formatAmount(
      amount
    )}<input type="hidden" name="cheque_amounts[]" value="${amount}"></td>
        <td><button type="button" class="btn btn-sm btn-danger remove-row">Remove</button></td>
      </tr>`;
    $("#chequeBody").append(newRow);
    updateState();

    $("#cheque_no, #cheque_date, #bank_branch_name, #bank_branch, #amount").val(
      ""
    );
  });

  $("#cheque_no, #cheque_date, #bank_branch_name, #amount").on(
    "keypress",
    function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        $("#add_cheque").click();
      }
    }
  );

  $("#chequeBody").on("click", ".remove-row", function () {
    const $row = $(this).closest("tr");
    const chequeId = $row.data("cheque-id");
    $row.remove();
    if (chequeId) {
      const index = state.chequeInfo.findIndex(
        (cheque) => cheque.id === chequeId
      );
      if (index > -1) {
        state.chequeInfo.splice(index, 1);
      }
    }
    if ($("#chequeBody tr").length === 0) {
      $("#chequeBody").append(
        `<tr id="noItemRow"><td colspan="5" class="text-center text-muted">No items added</td></tr>`
      );
    }
    updateState();
  });

  $(document).on("click", ".select-branch", function () {
    const branchId = $(this).data("id");
    const bankBranchName = $(this).find("td:eq(2)").text();
    $("#bank_branch").val(branchId);
    $("#bank_branch_name").val(bankBranchName);
    $("#branch_master").modal("hide");
  });

  let customerTableInitialized = false;

  const loadCustomerTable = () => {
    if (!customerTableInitialized) {
      $("#customerTable").DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "ajax/php/customer-master.php",
          type: "POST",
          data: { filter: true, category: 1 },
          dataSrc: (json) => json.data,
          error: (xhr) =>
            console.error("Server Error Response:", xhr.responseText),
        },
        columns: [
          { data: "key", title: "#ID" },
          { data: "code", title: "Code" },
          { data: "name", title: "Name" },
          { data: "mobile_number", title: "Mobile Number" },
          { data: "email", title: "Email" },   
          { data: "outstanding", title: "Outstanding" },
        ],
        order: [[0, "desc"]],
        pageLength: 100,
        createdRow: (row, data) => {
          $(row).addClass("cursor-pointer");
        },
      });

      customerTableInitialized = true;
    } else {
      $("#customerTable").DataTable().ajax.reload();
    }

    $("#customerTable tbody")
      .off("click", "tr")
      .on("click", "tr", function () {
        const data = $("#customerTable").DataTable().row(this).data();

        if (data) {
          $("#customer_id").val(data.id);
          $("#customer_code").val(data.code);
          $("#customer_name").val(data.name);
          $("#customer_address").val(data.address);
          $("#outstanding").val(formatAmount(data.outstanding));
          $("#customerModal").modal("hide");
          loadCustomerCreditInvoices(data.id);
        }
      });
  };
  // Load customer table when modal is shown
  $("#customerModal").on("show.bs.modal", function () {
    loadCustomerTable();
  });

  const loadCustomerCreditInvoices = (customerId) => {
    if (!customerId) return;

    $.ajax({
      url: "ajax/php/payment-receipt.php",
      type: "POST",
      data: { action: "get_credit_invoices", customer_id: customerId },
      success: (response) => {
        $("#invoiceBody").empty();

        if (response.success && response.data?.length) {
          let totalOutstanding = 0;
          response.data.forEach((invoice) => {
            const invoiceValue = parseFloat(invoice.grand_total || 0);
            const paidAmount = parseFloat(
              invoice.outstanding_settle_amount || 0
            );
            const overdue = invoiceValue - paidAmount;
            totalOutstanding += overdue;

            $("#invoiceBody").append(`
              <tr>
                <td>${invoice.invoice_date}</td>
                <td class="hidden"><input type="hidden" name="invoice_id[]" value="${
                  invoice.id
                }">${invoice.id}</td>
                <td>${invoice.invoice_no}</td>
                <td>${formatAmount(invoiceValue)}</td>
                <td>${formatAmount(paidAmount)}</td>
                <td><span class="text-danger fw-bold invoice-overdue">${formatAmount(
                  overdue
                )}</span></td>
                <td>
                  <input type="text" name="cheque_pay[]" class="form-control form-control-sm cheque-pay" value="0.00">
                  <select name="cheque_select[]" class="form-select form-select-sm mt-1 cheque-select">
                    <option value="">Select Cheque</option>
                    ${state.chequeInfo
                      .map(
                        (cheque) =>
                          `<option value="${cheque.id}" data-amount="${
                            cheque.amount
                          }" ${cheque.used ? "disabled" : ""}>
                        ${cheque.chequeNo} (${formatAmount(cheque.amount)})
                      </option>`
                      )
                      .join("")}
                  </select>
                  <input type="hidden" name="cheque_no[]" class="form-control form-control-sm cheque-no" value="">
                  <input type="hidden" name="cheque_date[]" class="form-control form-control-sm cheque-date" value="">
                  <input type="hidden" name="bank_branch[]" class="form-control form-control-sm bank-branch" value="">
                </td>
                <td><input type="text" name="cash_pay[]" disabled class="form-control form-control-sm cash-pay" value="0.00"></td>
                <td class="paid-amount">${formatAmount(0)}</td>
                <td class="balance-amount">${formatAmount(overdue)}</td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="uil uil-trash"></i></button></td>
              </tr>
            `);
          });

          $("#total_outstanding, #balance_amount").val(
            formatAmount(totalOutstanding)
          );
        } else {
          $("#invoiceBody").html(
            `<tr><td colspan="11" class="text-center text-muted">No items found</td></tr>`
          );
          $("#total_outstanding, #balance_amount").val("0.00");
          swal("No Data", "No invoices found for this customer.", "info");
        }

        updateState();
        toggleCashPay();
        updateChequePayDisabledState();
      },
      error: (xhr) => {
        console.error("Failed to fetch invoices", xhr);
        swal("Error", "Failed to load invoices. Please try again.", "error");
      },
    });
  };

  $("#cash_total").on("input", function () {
    const cashTotal = parseAmount($(this).val());
    const chequeTotal = parseAmount($("#cheque_total").val());
    const outstanding = parseAmount($("#total_outstanding").val());

    if (cashTotal + chequeTotal > outstanding) {
      swal({
        title: "Exceeded Outstanding Amount!",
        text: `Total amount (Cash: Rs. ${formatAmount(
          cashTotal
        )} + Cheque: Rs. ${formatAmount(
          chequeTotal
        )}) cannot exceed Outstanding Amount (Rs. ${formatAmount(
          outstanding
        )}).`,
        type: "error",
        timer: CONFIG.SWAL_TIMEOUT,
        showConfirmButton: false,
      });

      const maxCashAmount = Math.max(0, outstanding - chequeTotal);
      $(this).val(formatAmount(maxCashAmount));
    }

    toggleCashPay();
    updateState();
  });

  $("#new").click(function (e) {
    e.preventDefault();
    location.reload();
  });

  $("#create").click(function (event) {
    event.preventDefault();

    if (!$("#code").val()) {
      return swal({
        title: "Error!",
        text: "Please enter receipt number",
        type: "error",
        timer: CONFIG.SWAL_TIMEOUT,
        showConfirmButton: false,
      });
    }
    if (!$("#customer_code").val()) {
      return swal({
        title: "Error!",
        text: "Please select a customer",
        type: "error",
        timer: CONFIG.SWAL_TIMEOUT,
        showConfirmButton: false,
      });
    }
    if (!$("#entry_date").val()) {
      return swal({
        title: "Error!",
        text: "Please select an entry date",
        type: "error",
        timer: CONFIG.SWAL_TIMEOUT,
        showConfirmButton: false,
      });
    }

    // Validate total paid amount > 0
    if (parseAmount($("#paid_amount").val()) <= 0) {
      return swal({
        title: "Error!",
        text: "Paid amount must be greater than 0",
        type: "error",
        timer: CONFIG.SWAL_TIMEOUT,
        showConfirmButton: false,
      });
    }

    if (!validateOutstandingLimit()) {
      return swal({
        title: "Error!",
        text: "Total cheque and cash amount cannot exceed the outstanding amount",
        type: "error",
        timer: CONFIG.SWAL_TIMEOUT,
        showConfirmButton: false,
      });
    }

    $(".someBlock").preloader();

    // Build payment methods per invoice row so that invoice_id, branch_id and amounts are saved correctly
    const paymentMethods = [];
    let totalAmount = 0;

    $("#invoiceBody tr")
      .not("#noItemRow")
      .each(function () {
        const $row = $(this);
        const invoiceId = $row.find('input[name="invoice_id[]"]').val();
        const chequePay = parseAmount($row.find(".cheque-pay").val());
        const cashPay = parseAmount($row.find(".cash-pay").val());
        const chequeNo = $row.find(".cheque-no").val() || null;
        const chequeDate = $row.find(".cheque-date").val() || null;
        const branchId = $row.find(".bank-branch").val() || null;

        if (cashPay > 0) {
          paymentMethods.push({
            invoice_id: invoiceId || null,
            payment_type_id: 1,
            amount: cashPay,
          });
          totalAmount += cashPay;
        }

        if (chequePay > 0) {
          paymentMethods.push({
            invoice_id: invoiceId || null,
            payment_type_id: 2,
            amount: chequePay,
            cheq_no: chequeNo,
            branch_id: branchId,
            cheq_date: chequeDate,
          });
          totalAmount += chequePay;
        }
      });

    const formData = new FormData($("#form-data")[0]);

    formData.append(
      "total_outstanding",
      parseAmount($("#total_outstanding").val())
    );
    formData.append("customer_id", $("#customer_id").val());
    formData.append("paid_amount", totalAmount);
    formData.append("methods", JSON.stringify(paymentMethods));

    formData.append("create", true);
    formData.append("action", "create");

    $.ajax({
      url: "ajax/php/payment-receipt.php",
      type: "POST",
      data: formData,
      async: false,
      cache: false,
      contentType: false,
      processData: false,
      success: function (result) {
        $(".someBlock").preloader("remove");
        if (result.status === "success") {
          swal({
            title: "Success!",
            text: "Payment receipt created successfully!",
            type: "success",
            timer: CONFIG.SWAL_TIMEOUT,
            showConfirmButton: false,
          });
          setTimeout(() => window.location.reload(), CONFIG.SWAL_TIMEOUT);
        } else {
          swal({
            title: "Error!",
            text: result.message || "Something went wrong.",
            type: "error",
            timer: CONFIG.SWAL_TIMEOUT,
            showConfirmButton: false,
          });
        }
      },
      error: function (xhr) {
        $(".someBlock").preloader("remove");
        swal(
          "Error",
          "Failed to create payment receipt. Please try again.",
          "error"
        );
      },
    });
  });

 

  // Initialize
  toggleCashPay();
  updateChequePayDisabledState();
});
