<!doctype html>
<?php
include 'class/include.php';
include 'auth.php';

$id = $_GET['id'] ?? null;
$INVOICE = new SupplierInvoice($id);

if (!$INVOICE->id) {
    die("Supplier Invoice not found!");
}

$SUPPLIER = new SupplierMaster($INVOICE->supplier_id);
$items = SupplierInvoiceItem::getByInvoiceId($INVOICE->id);

// Items already have item_code and item_name stored directly
$enhancedItems = [];
foreach ($items as $item) {
    $enhancedItems[] = [
        'item_code' => $item['item_code'] ?? '',
        'description' => $item['item_name'] ?? '',
        'unit' => $item['unit'],
        'quantity' => $item['quantity'],
        'rate' => $item['rate'],
        'discount' => $item['discount_percentage'],
        'amount' => $item['amount']
    ];
}
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Supplier Invoice - <?php echo $INVOICE->grn_number; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'main-css.php' ?>

    <style>
        @media print {
            .no-print { display: none !important; }
            body, html { width: 100%; margin: 0; padding: 0; background: #fff; }
            .container { width: 100% !important; max-width: 100% !important; padding: 0 !important; }
            @page { margin: 8mm; size: A4; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }

        body { background: #fff; font-family: Arial, sans-serif; font-size: 13px; }

        .invoice-border {
            border: 2px solid #000;
            padding: 0;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 10px 15px;
            border-bottom: 2px solid #000;
        }

        .invoice-header .company-info h2 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .invoice-header .company-info p {
            margin: 2px 0;
            font-size: 11px;
            color: #333;
        }

        .invoice-label {
            border: 2px solid #e67300;
            padding: 6px 20px;
            font-size: 18px;
            font-weight: bold;
            color: #e67300;
            text-align: center;
            margin-bottom: 5px;
        }

        .copy-label {
            background: #e67300;
            color: #fff;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }

        /* Detail rows */
        .detail-grid {
            display: grid;
            border-bottom: 1px solid #000;
        }

        .detail-row {
            display: flex;
            border-bottom: 1px solid #ccc;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-cell {
            padding: 4px 8px;
            border-right: 1px solid #ccc;
            font-size: 12px;
        }

        .detail-cell:last-child {
            border-right: none;
        }

        .detail-cell label {
            font-weight: bold;
            font-size: 10px;
            color: #555;
            display: block;
            margin-bottom: 1px;
        }

        .detail-cell span {
            font-size: 12px;
            color: #000;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead th {
            background: #f5f5f5;
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        .items-table tbody td {
            border: 1px solid #ccc;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 5px 8px;
            font-size: 12px;
            vertical-align: top;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #000;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        /* Total row */
        .total-section {
            padding: 8px 15px;
            border-bottom: 2px solid #000;
            text-align: left;
        }

        .total-section strong {
            font-size: 13px;
        }

        /* Bottom section */
        .bottom-section {
            border-top: 2px solid #000;
        }

        .bottom-row {
            display: flex;
            border-bottom: 1px solid #000;
        }

        .bottom-left {
            flex: 1;
            padding: 8px 10px;
            border-right: 1px solid #000;
            font-size: 11px;
        }

        .payment-grid {
            display: flex;
            flex: 2;
        }

        .payment-cell {
            flex: 1;
            text-align: center;
            padding: 5px;
            border-right: 1px solid #000;
            font-size: 10px;
            font-weight: bold;
        }

        .payment-cell:last-child {
            border-right: none;
        }

        .payment-cell .value {
            font-size: 13px;
            margin-top: 5px;
            font-weight: normal;
        }

        .signature-row {
            display: flex;
            min-height: 80px;
        }

        .signature-cell {
            flex: 1;
            padding: 10px;
            border-right: 1px solid #000;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            font-size: 10px;
        }

        .signature-cell:last-child {
            border-right: none;
        }

        .signature-line {
            width: 80%;
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 3px;
            text-align: center;
            font-size: 10px;
        }

        .tagline {
            text-align: center;
            padding: 5px;
            font-size: 14px;
            font-weight: bold;
            font-style: italic;
            color: #e67300;
        }

        .credit-note {
            background: #fff3e0;
            padding: 5px 10px;
            font-size: 11px;
            border: 1px solid #e67300;
            margin: 5px;
            display: inline-block;
        }

        .receipt-note {
            background: #e67300;
            color: #fff;
            padding: 5px 10px;
            font-size: 10px;
            margin: 5px;
            display: inline-block;
        }

        .grand-total-value {
            font-size: 15px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container mt-3">
        <!-- Print / Close Buttons -->
        <div class="d-flex justify-content-end mb-3 no-print gap-2">
            <button onclick="window.print()" class="btn btn-success"><i class="mdi mdi-printer me-1"></i> Print</button>
            <button onclick="window.close()" class="btn btn-secondary"><i class="mdi mdi-close me-1"></i> Close</button>
        </div>

        <div class="invoice-border">

            <!-- Header -->
            <div class="invoice-header">
                <div class="company-info">
                    <h2><?php echo $COMPANY_PROFILE_DETAILS->name; ?></h2>
                    <p><?php echo $COMPANY_PROFILE_DETAILS->address; ?></p>
                    <p>Tel: <?php echo $COMPANY_PROFILE_DETAILS->mobile_number_1; ?>
                        <?php if (!empty($COMPANY_PROFILE_DETAILS->mobile_number_2)): ?>
                            , <?php echo $COMPANY_PROFILE_DETAILS->mobile_number_2; ?>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($COMPANY_PROFILE_DETAILS->email)): ?>
                        <p>E-mail: <?php echo $COMPANY_PROFILE_DETAILS->email; ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <div class="invoice-label">INVOICE</div>
                    <div class="copy-label">COMPANY COPY</div>
                </div>
            </div>

            <!-- Detail Rows -->
            <div class="detail-grid" style="border-bottom: 2px solid #000;">
                <!-- Row 1 -->
                <div class="detail-row">
                    <div class="detail-cell" style="width: 20%;">
                        <label>GRN No</label>
                        <span><?php echo $INVOICE->grn_number; ?></span>
                    </div>
                    <div class="detail-cell" style="width: 20%;">
                        <label>Order No.</label>
                        <span><?php echo $INVOICE->order_no ?: '-'; ?></span>
                    </div>
                    <div class="detail-cell" style="width: 20%;">
                        <label>Invoice No.</label>
                        <span><?php echo $INVOICE->invoice_no ?: '-'; ?></span>
                    </div>
                    <div class="detail-cell" style="width: 20%;">
                        <label>Invoice Date</label>
                        <span><?php echo $INVOICE->invoice_date ? date('d/m/Y', strtotime($INVOICE->invoice_date)) : '-'; ?></span>
                    </div>
                    <div class="detail-cell" style="width: 20%;">
                        <label>Delivered On</label>
                        <span><?php echo $INVOICE->delivery_date ? date('d/m/Y', strtotime($INVOICE->delivery_date)) : '-'; ?></span>
                    </div>
                </div>
                <!-- Row 2 -->
                <div class="detail-row">
                    <div class="detail-cell" style="width: 20%;">
                        <label>Supplier Code</label>
                        <span><?php echo $SUPPLIER->code ?? '-'; ?></span>
                    </div>
                    <div class="detail-cell" style="width: 40%;">
                        <label>Supplier Name</label>
                        <span><?php echo $SUPPLIER->name ?? '-'; ?></span>
                    </div>
                    <div class="detail-cell" style="width: 40%;">
                        <label>Supplier Address</label>
                        <span><?php echo $SUPPLIER->address ?? '-'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Item Code</th>
                        <th style="width: 35%;">Description</th>
                        <th style="width: 8%;">Unit</th>
                        <th style="width: 10%;">Quantity</th>
                        <th style="width: 12%;">Rate</th>
                        <th style="width: 8%;">Dis%</th>
                        <th style="width: 17%;">Amount<br><small>Rs.</small></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rowCount = 0;
                    foreach ($enhancedItems as $item): 
                        $rowCount++;
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $item['item_code']; ?></td>
                        <td><?php echo $item['description']; ?></td>
                        <td class="text-center"><?php echo $item['unit']; ?></td>
                        <td class="text-right"><?php echo number_format($item['quantity'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['rate'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['discount'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php 
                    // Fill empty rows to maintain consistent layout
                    $minRows = 8;
                    for ($i = $rowCount; $i < $minRows; $i++): 
                    ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <!-- Total -->
            <div class="total-section" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>Total Amount : <?php echo number_format($INVOICE->grand_total, 2); ?></strong>
                </div>
                <div class="grand-total-value" style="text-align: right;">
                    <?php echo number_format($INVOICE->grand_total, 2); ?>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="bottom-section">
                <!-- Payment Row -->
                <div class="bottom-row">
                    <div class="bottom-left">
                        <strong>Received the above items in good order & Condition</strong>
                    </div>
                    <div class="payment-grid">
                        <div class="payment-cell" style="background: <?php echo ($INVOICE->payment_type == 'cash') ? '#fff3e0' : 'transparent'; ?>;">
                            CASH
                            <div class="value">
                                <?php echo ($INVOICE->payment_type == 'cash') ? number_format($INVOICE->grand_total, 2) : '-'; ?>
                            </div>
                        </div>
                        <div class="payment-cell" style="background: <?php echo ($INVOICE->payment_type == 'cheque') ? '#fff3e0' : 'transparent'; ?>;">
                            CHEQUES
                            <div class="value">
                                <?php echo ($INVOICE->payment_type == 'cheque') ? number_format($INVOICE->grand_total, 2) : '-'; ?>
                            </div>
                        </div>
                        <div class="payment-cell">
                            CHEQUE NO
                            <div class="value">
                                <?php echo ($INVOICE->payment_type == 'cheque' && !empty($INVOICE->cheque_no)) ? $INVOICE->cheque_no : '-'; ?>
                            </div>
                        </div>
                        <div class="payment-cell">
                            CHEQUE DATE
                            <div class="value">
                                <?php echo ($INVOICE->payment_type == 'cheque' && !empty($INVOICE->cheque_date)) ? date('d/m/Y', strtotime($INVOICE->cheque_date)) : '-'; ?>
                            </div>
                        </div>
                        <div class="payment-cell" style="background: <?php echo ($INVOICE->payment_type == 'credit') ? '#fff3e0' : 'transparent'; ?>;">
                            CREDIT
                            <div class="value">
                                <?php echo ($INVOICE->payment_type == 'credit') ? number_format($INVOICE->grand_total, 2) : '-'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signature Row -->
                <div class="signature-row">
                    <div class="signature-cell" style="flex: 1;">
                        <?php if ($INVOICE->payment_type == 'credit' && $INVOICE->credit_period > 0): ?>
                            <div class="credit-note">
                                CREDIT PERIOD <?php echo $INVOICE->credit_period; ?> MONTHS
                            </div>
                        <?php endif; ?>
                        <div class="signature-line">
                            Signature & Rubber Stamp of Dealer
                        </div>
                    </div>
                    <div class="signature-cell" style="flex: 1;">
                        <?php if ($INVOICE->payment_type == 'cheque'): ?>
                            <div style="font-size: 11px; margin-bottom: 5px;">
                                <strong>Bank:</strong> <?php echo $INVOICE->bank_name; ?><br>
                                <strong>Branch:</strong> <?php echo $INVOICE->branch_name; ?>
                            </div>
                        <?php endif; ?>
                        <div class="signature-line">
                            Name of Delivery Officer / Signature
                        </div>
                    </div>
                    <div class="signature-cell" style="flex: 1; justify-content: center; align-items: center;">
                        <div class="tagline">We Care for Your Needs</div>
                        <div class="receipt-note">
                            Ensure that you get a receipt for every payment
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- invoice-border -->
    </div>
</body>
</html>
