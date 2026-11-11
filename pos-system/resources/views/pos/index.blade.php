@extends('layouts.app')
@section('title', 'POS')

@section('content')
<div class="pos" id="pos"
     data-products-url="{{ route('pos.products') }}"
     data-lookup-url="{{ route('pos.lookup') }}"
     data-stock-url="{{ route('pos.stock') }}"
     data-checkout-url="{{ route('pos.checkout') }}"
     data-held-url="{{ route('pos.held') }}"
     data-last-receipt-url="{{ route('pos.receipt.last') }}"
     data-customers-url="{{ route('customers.search') }}"
     data-customer-store-url="{{ route('customers.store') }}"
     data-tax-rate="{{ (float) $settings['tax_rate'] }}"
     data-allow-negative="{{ $settings['allow_negative_stock'] ? 1 : 0 }}"
     data-branch="{{ $currentBranch->id }}"
     data-currency="{{ $settings['currency'] }}">

    {{-- ============ LEFT: products ============ --}}
    <section class="pos-left">
        <div class="pos-search">
            <button type="button" class="btn-scan" id="btnScan" title="Focus scanner input (F2)"><i class="fa-solid fa-barcode"></i> Scan Barcode</button>
            <span class="or">or</span>
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="search" type="text" placeholder="Enter Product Code / Barcode / Name..." autocomplete="off" autofocus>
                <button type="button" class="clear hidden" id="searchClear" aria-label="Clear search"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>

        <div class="cats" id="cats">
            <button type="button" class="cat on" data-cat=""><i class="fa-solid fa-table-cells-large"></i>All Items</button>
            @foreach ($categories as $c)
                <button type="button" class="cat" data-cat="{{ $c->id }}"><i class="fa-solid {{ $c->icon }}"></i>{{ $c->name }}</button>
            @endforeach
        </div>

        <div class="grid-wrap">
            <div class="pgrid" id="grid"></div>
        </div>
        <nav class="pager" id="pager"></nav>
    </section>

    {{-- ============ RIGHT: cart ============ --}}
    <section class="pos-right">
        <div class="cart-head">
            <h3><i class="fa-solid fa-cart-shopping"></i> Current Sale</h3>
            <span class="bill">Bill No: <span id="billNo">{{ $nextBill }}</span></span>
        </div>

        <div class="cart-table-wrap">
            <table class="cart-table">
                <thead>
                <tr><th class="idx">#</th><th>Item</th><th style="text-align:center">Qty</th><th class="num">Price ({{ $settings['currency'] }})</th><th class="num">Total ({{ $settings['currency'] }})</th><th></th></tr>
                </thead>
                <tbody id="cartBody"></tbody>
            </table>
            <div class="cart-empty" id="cartEmpty"><i class="fa-solid fa-basket-shopping"></i>Scan a barcode or tap a product to start a sale</div>
        </div>

        <div class="cart-mid">
            <div class="cust-box">
                <div class="t"><i class="fa-solid fa-user"></i> Customer <small>(Optional)</small></div>
                <div class="cust-row">
                    <div class="cust-pick">
                        <button type="button" class="sel" id="custBtn">Walk-in Customer</button>
                        <div class="cust-pop" id="custPop">
                            <input class="input" id="custSearch" placeholder="Search name or phone..." autocomplete="off">
                            <ul id="custList"></ul>
                        </div>
                    </div>
                    <button type="button" class="btn-add" data-open="mCustomer" title="New customer"><i class="fa-solid fa-plus"></i></button>
                </div>
                <div class="cust-due hidden" id="custDue"></div>
            </div>

            <div class="totals">
                <div class="row"><span>Subtotal</span><b id="tSub">{{ $settings['currency'] }} 0.00</b></div>
                <div class="row">
                    <span>Discount</span>
                    <div class="disc">
                        <button type="button" id="discPct" class="on" title="Percent">%</button>
                        <button type="button" id="discAmt" title="Fixed amount">–</button>
                        <input id="discVal" type="number" min="0" step="0.01" value="0.00" title="Discount (F6)">
                    </div>
                </div>
                <div class="row"><span>Tax ({{ $settings['tax_label'] }} {{ qty((float) $settings['tax_rate'], 2) }}%)</span><b id="tTax">{{ $settings['currency'] }} 0.00</b></div>
            </div>
        </div>

        <div class="payable"><span>Total Payable</span><b id="tTotal">{{ $settings['currency'] }} 0.00</b></div>

        <div class="actions">
            <button type="button" class="act clear" id="btnClear"><i class="fa-regular fa-trash-can"></i>Clear<br>(F4)</button>
            <button type="button" class="act disc-btn" id="btnDisc"><i class="fa-solid fa-tags"></i>Discount<br>(F6)</button>
            <button type="button" class="act hold" id="btnHold"><i class="fa-solid fa-circle-pause"></i><span>Hold<span id="heldCount" class="held-badge hidden"></span></span>(F7)</button>
            <div class="act-col">
                <button type="button" class="act-big pay" id="btnPay" disabled><i class="fa-regular fa-credit-card"></i> Pay <small>(F8)</small></button>
                <button type="button" class="act-big print" id="btnPrint"><i class="fa-solid fa-print"></i> Print Bill</button>
            </div>
        </div>
    </section>
</div>

{{-- ============ Payment modal ============ --}}
<div class="modal-bg" id="mPay">
    <div class="modal">
        <div class="modal-head"><h3><i class="fa-regular fa-credit-card"></i> Payment</h3><button class="x" data-close>&times;</button></div>
        <div class="modal-body">
            <div class="pay-total"><small>Total Payable</small><b id="payTotal">0.00</b></div>
            <div class="methods" id="methods">
                <button type="button" data-m="cash" class="on"><i class="fa-solid fa-money-bill-wave"></i>Cash</button>
                <button type="button" data-m="card"><i class="fa-regular fa-credit-card"></i>Card</button>
                <button type="button" data-m="bank"><i class="fa-solid fa-building-columns"></i>Bank</button>
                <button type="button" data-m="credit"><i class="fa-solid fa-file-invoice-dollar"></i>Credit</button>
            </div>
            <div id="tenderWrap">
                <label class="lbl" id="tenderLbl">Cash Received</label>
                <input class="input tender" id="tender" type="number" min="0" step="0.01">
                <div class="quick-cash" id="quickCash"></div>
                <div class="change" id="changeBox"><span>Change</span><span id="changeVal">0.00</span></div>
            </div>
            <div class="field" style="margin-top:14px">
                <label class="lbl">Note (optional)</label>
                <input class="input" id="payNote" maxlength="255">
            </div>
            <label class="check"><input type="checkbox" id="autoPrint" checked> Print receipt after payment</label>
        </div>
        <div class="modal-foot">
            <button class="btn" data-close>Cancel</button>
            <button class="btn btn-success" id="btnConfirmPay" style="min-width:180px;height:44px;font-size:16px"><i class="fa-solid fa-check"></i> Complete Sale (Enter)</button>
        </div>
    </div>
</div>

{{-- ============ Held bills modal ============ --}}
<div class="modal-bg" id="mHeld">
    <div class="modal">
        <div class="modal-head"><h3><i class="fa-solid fa-circle-pause"></i> Held Bills</h3><button class="x" data-close>&times;</button></div>
        <div class="modal-body"><ul class="held-list" id="heldList"></ul></div>
    </div>
</div>

{{-- ============ New customer modal ============ --}}
<div class="modal-bg" id="mCustomer">
    <div class="modal">
        <form id="custForm">
            <div class="modal-head"><h3><i class="fa-solid fa-user-plus"></i> New Customer</h3><button type="button" class="x" data-close>&times;</button></div>
            <div class="modal-body">
                <div class="field"><label class="lbl">Name *</label><input class="input" name="name" required maxlength="120" autofocus></div>
                <div class="grid-2">
                    <div class="field"><label class="lbl">Phone</label><input class="input" name="phone" maxlength="30"></div>
                    <div class="field"><label class="lbl">Credit limit</label><input class="input" name="credit_limit" type="number" min="0" step="0.01" value="0"></div>
                </div>
                <div class="field"><label class="lbl">Address</label><input class="input" name="address" maxlength="255"></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save & Select</button></div>
        </form>
    </div>
</div>

<iframe id="printFrame" title="print" style="position:absolute;width:0;height:0;border:0;visibility:hidden"></iframe>
@endsection

@section('statusbar')
    <b>Shortcuts:</b>
    <span><kbd>F8</kbd> Pay</span><span class="sep">|</span>
    <span><kbd>F4</kbd> Clear</span><span class="sep">|</span>
    <span><kbd>F6</kbd> Discount</span><span class="sep">|</span>
    <span><kbd>F7</kbd> Hold</span><span class="sep">|</span>
    <span><kbd>F12</kbd> Print Bill</span><span class="sep">|</span>
    <span><kbd>F2</kbd> Search</span>
    <span class="ready" id="netStatus"><i class="fa-solid fa-circle"></i> POS Ready</span>
@endsection

@push('scripts')
<script src="{{ asset('js/pos.js') }}?v={{ filemtime(public_path('js/pos.js')) }}"></script>
@endpush
