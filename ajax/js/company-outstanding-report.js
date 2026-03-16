$(document).ready(function () {
  const $tbody = $('#reportTableBody');
  const $totalOutstanding = $('#totalCompanyOutstanding');
  const $filterCompanyOnly = $('#companyOnly');

  // defaults: current month
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
  setDate($('#fromDate'), firstDay);
  setDate($('#toDate'), today);

  $('#searchBtn').on('click', function () {
    loadData();
  });

  $('#resetBtn').on('click', function () {
    $('#billNo').val('');
    $('#fromDate').val('');
    $('#toDate').val('');
    $filterCompanyOnly.prop('checked', true);
    $tbody.empty();
    $totalOutstanding.text('0.00');
  });

  $('#printBtn').on('click', function () {
    const fromDate = $('#fromDate').val();
    const toDate = $('#toDate').val();
    const billNo = $('#billNo').val();
    const companyOnly = $filterCompanyOnly.is(':checked') ? '1' : '0';

    const params = new URLSearchParams({
      from_date: fromDate || '',
      to_date: toDate || '',
      bill_no: billNo || '',
      company_only: companyOnly
    });

    window.open('company-outstanding-report-print.php?' + params.toString(), '_blank');
  });

  function setDate($el, date) {
    const d = new Date(date);
    let m = '' + (d.getMonth() + 1);
    let day = '' + d.getDate();
    const y = d.getFullYear();
    if (m.length < 2) m = '0' + m;
    if (day.length < 2) day = '0' + day;
    $el.val([y, m, day].join('-'));
  }

  function loadData() {
    const fromDate = $('#fromDate').val();
    const toDate = $('#toDate').val();
    const billNo = $('#billNo').val();
    const companyOnly = $filterCompanyOnly.is(':checked') ? '1' : '0';

    if ((fromDate && !toDate) || (!fromDate && toDate)) {
      alert('Please provide both From and To dates.');
      return;
    }

    const payload = {
      action: 'get_company_outstanding',
      from_date: fromDate,
      to_date: toDate,
      bill_no: billNo,
      company_only: companyOnly,
    };

    $tbody.html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');

    $.ajax({
      url: 'ajax/php/company-outstanding-report.php',
      type: 'POST',
      dataType: 'json',
      data: payload,
      success: function (res) {
        if (res && res.status === 'success') {
          render(res.data || [], res.totals || {});
        } else {
          const msg = (res && res.message) || 'Error loading data';
          alert(msg);
          $tbody.html('<tr><td colspan="8" class="text-center">No data found</td></tr>');
          $totalOutstanding.text('0.00');
        }
      },
      error: function (xhr) {
        console.error('Load error', xhr.responseText);
        alert('Error loading data');
        $tbody.html('<tr><td colspan="8" class="text-center">Error loading data</td></tr>');
        $totalOutstanding.text('0.00');
      },
    });
  }

  function render(rows, totals) {
    $tbody.empty();
    if (!rows.length) {
      $tbody.html('<tr><td colspan="8" class="text-center">No records found</td></tr>');
      $totalOutstanding.text('0.00');
      return;
    }

    rows.forEach(function (row) {
      const companyOutstanding = parseFloat(row.company_outstanding || 0);
      const badge = companyOutstanding > 0
        ? " <span class='badge bg-warning text-dark ms-1'>Company Outstanding</span>"
        : '';
      let statusLabel = '';
      if (parseInt(row.is_cancelled, 10) === 1 || row.status === 'cancelled') {
        statusLabel = "<span class='badge bg-danger'>Cancelled</span>";
      } else if (row.status === 'returned') {
        statusLabel = "<span class='badge bg-info text-dark'>Returned</span>";
      } else if (row.status === 'rented') {
        statusLabel = "<span class='badge bg-warning text-dark'>Rented</span>";
      } else {
        statusLabel = row.status || '';
      }
      const tr = `
        <tr>
          <td>${row.id || ''}</td>
          <td>${row.bill_number || ''}</td>
          <td>${row.customer || ''}</td>
          <td>${row.rental_date || ''}</td>
          <td>${row.received_date || ''}</td>
          <td class="text-end">${row.items || 0}</td>
          <td>${statusLabel}${badge}</td>
          <td class="text-end text-danger">${companyOutstanding.toFixed(2)}</td>
        </tr>`;
      $tbody.append(tr);
    });

    const total = totals.company_outstanding || 0;
    $totalOutstanding.text(parseFloat(total).toFixed(2));
  }

  // initial load
  loadData();
});
