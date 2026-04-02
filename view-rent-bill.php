<!doctype html>
<?php
include 'class/include.php';

if (!isset($_SESSION)) {
    session_start();
}

$rent_id_param = $_GET['rent_id'] ?? '';
$US = new User($_SESSION['id']);
$COMPANY_PROFILE = new CompanyProfile($US->company_id);

$EQUIPMENT_RENT = new EquipmentRent($rent_id_param);

if (!$EQUIPMENT_RENT->id) {
    die('Rent record not found');
}

$CUSTOMER_MASTER = new CustomerMaster($EQUIPMENT_RENT->customer_id);

// Get rent items
$rent_items = $EQUIPMENT_RENT->getItems();

// DB instance for queries
$db = Database::getInstance();

// Determine rent label
$rent_types = array_unique(array_map(function ($ri) {
    return $ri['rent_type'] ?? '';
}, $rent_items));
$has_month = in_array('month', $rent_types, true);
$has_daily = count($rent_types) === 0 ? false : ($has_month ? count($rent_types) > 1 : true);

$rent_label = 'Rental Amount:';
if ($has_month && !$has_daily) {
    $rent_label = 'Monthly Rental:';
}

// Collect return rows
$return_rows = [];
foreach ($rent_items as $ritem) {
    if (empty($ritem['id'])) continue;
    $itemReturns = EquipmentRentReturn::getByRentItemId($ritem['id']);
    $storedDamage = floatval($ritem['damage_amount'] ?? 0);
    
    foreach ($itemReturns as $ret) {
        $returnDamage = floatval($ret['damage_amount'] ?? 0);
        $totalDamage = $returnDamage + $storedDamage;
        
        $return_rows[] = array_merge($ret, [
            'equipment_name' => $ritem['equipment_name'] ?? '-',
            'equipment_code' => $ritem['equipment_code'] ?? '-',
            'sub_equipment_code' => $ritem['sub_equipment_code'] ?? '-',
            'total_damage_display' => $totalDamage,
        ]);
    }
}

// Calculate totals
$total_amount = 0;
foreach ($rent_items as $item) {
    $total_amount += floatval($item['amount']);
}

$transport_amount = floatval($EQUIPMENT_RENT->transport_cost);
$total_deposit = floatval($EQUIPMENT_RENT->deposit_total);

// Calculate charges for refund balance
$chargesQuery = "SELECT COALESCE(SUM(
                        CASE WHEN err.rental_override IS NOT NULL
                            THEN err.rental_override
                            ELSE CASE WHEN COALESCE(e.is_fixed_rate, 0) = 1
                                THEN ((COALESCE(eri.amount,0) / NULLIF(eri.quantity,0)) * err.return_qty)
                                ELSE (GREATEST(1, CEILING(TIMESTAMPDIFF(SECOND, eri.rental_date, err.return_date) / 86400))
                                    * (COALESCE(eri.amount,0) / NULLIF(eri.quantity,0))
                                    * err.return_qty)
                            END
                        END
                        + COALESCE(err.extra_day_amount, 0)
                        + COALESCE(err.damage_amount, 0)
                        + COALESCE(err.penalty_amount, 0)
                        + COALESCE(err.extra_charge_amount, 0)
                        - COALESCE(err.repair_cost, 0)
                    ), 0) as total_charges
                    FROM equipment_rent_returns err
                    INNER JOIN equipment_rent_items eri ON err.rent_item_id = eri.id
                    LEFT JOIN equipment e ON eri.equipment_id = e.id
                    WHERE eri.rent_id = " . (int) $EQUIPMENT_RENT->id;
$chargesResult = $db->readQuery($chargesQuery);
$chargesData = mysqli_fetch_assoc($chargesResult);
$total_charges = floatval($chargesData['total_charges'] ?? 0);
$refund_balance = $total_deposit - $total_charges;

if (empty($return_rows)) {
    $refund_balance = 0;
}

$total_customer_paid = $total_deposit;
$total_extra_charges = 0;
foreach ($return_rows as $rr) {
    $total_customer_paid += floatval($rr['customer_paid'] ?? 0);
    $total_extra_charges += floatval($rr['extra_charge_amount'] ?? 0);
}

$net_amount = $total_amount + $total_deposit + $transport_amount + $total_extra_charges;

// Fetch attachments
$attachments = [];

// Issue notes images
$issueNotesSql = "SELECT image_path, issue_note_code, issue_date FROM issue_notes WHERE rent_invoice_id = " . (int)$EQUIPMENT_RENT->id . " AND image_path IS NOT NULL AND image_path != ''";
$issueNotesRes = $db->readQuery($issueNotesSql);
while ($row = mysqli_fetch_assoc($issueNotesRes)) {
    $attachments[] = [
        'path' => $row['image_path'],
        'type' => 'Issue Note',
        'code' => $row['issue_note_code'],
        'date' => $row['issue_date']
    ];
}

// Issue returns images
$issueReturnsSql = "SELECT r.image_path, r.return_code, r.return_date 
                    FROM issue_returns r
                    INNER JOIN issue_notes n ON r.issue_note_id = n.id
                    WHERE n.rent_invoice_id = " . (int)$EQUIPMENT_RENT->id . " 
                    AND r.image_path IS NOT NULL AND r.image_path != ''";
$issueReturnsRes = $db->readQuery($issueReturnsSql);
while ($row = mysqli_fetch_assoc($issueReturnsRes)) {
    $attachments[] = [
        'path' => $row['image_path'],
        'type' => 'Return Note',
        'code' => $row['return_code'],
        'date' => $row['return_date']
    ];
}
?>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>View Rent Bill - <?php echo htmlspecialchars($EQUIPMENT_RENT->bill_number); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'main-css.php' ?>
    <link href="https://unicons.iconscout.com/release/v4.0.8/css/line.css" rel="stylesheet">
    <style>
        .invoice-card { max-width: 1000px; margin: 2rem auto; }
        .rent-header-img { max-height: 120px; display: block; margin: 0 auto 1rem auto; }
        .status-bar { width: 100%; text-align: center; padding: 5px 0; margin-bottom: 20px; font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: 5px; border: 2px solid #333; }
        @media print { .no-print { display: none !important; } .invoice-card { margin: 0; box-shadow: none; border: none; } }
    </style>
</head>
<body data-layout="horizontal" data-topbar="colored">
<div class="container">
    <div class="row no-print mt-3 mb-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Rental Bill Details</h4>
            <button onclick="window.print()" class="btn btn-primary"><i class="uil uil-print"></i> Print Bill</button>
        </div>
    </div>

    <div class="card invoice-card shadow-lg">
        <div class="card-body p-5">
            <div class="text-center">
                <img src="assets/images/rent-header.png" alt="Header" class="rent-header-img">
            </div>

            <div class="status-bar text-dark <?php echo ($EQUIPMENT_RENT->status === 'returned' ? 'bg-soft-success border-success' : 'bg-soft-warning border-warning'); ?>">
                <?php echo htmlspecialchars(ucfirst($EQUIPMENT_RENT->status)); ?>
            </div>

            <div class="row mb-4">
                <div class="col-sm-6">
                    <h5 class="font-size-15 mb-1">Customer Info:</h5>
                    <p class="mb-1 text-muted"><strong>Name:</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->name); ?></p>
                    <p class="mb-1 text-muted"><strong>Address:</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->address); ?></p>
                    <p class="mb-1 text-muted"><strong>Contact:</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->mobile_number); ?></p>
                    <p class="mb-1 text-muted"><strong>WP:</strong> <?php echo htmlspecialchars($CUSTOMER_MASTER->mobile_number_2); ?></p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <h5 class="font-size-15 mb-1">Bill Info:</h5>
                    <p class="mb-1 text-muted"><strong>Bill No:</strong> <span class="badge bg-soft-info font-size-14"><?php echo htmlspecialchars($EQUIPMENT_RENT->bill_number); ?></span></p>
                    <?php if (!empty($COMPANY_PROFILE->registration_number)): ?>
                        <p class="mb-1 text-muted"><strong>Reg No:</strong> <?php echo htmlspecialchars($COMPANY_PROFILE->registration_number); ?></p>
                    <?php endif; ?>
                    <p class="mb-1 text-muted"><strong>Issue Date:</strong> <?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->rental_date)); ?></p>
                    <?php if ($EQUIPMENT_RENT->rental_start_date): ?>
                        <p class="mb-1 text-muted"><strong>Rented From:</strong> <?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->rental_start_date)); ?></p>
                    <?php endif; ?>
                    <?php if ($EQUIPMENT_RENT->received_date): ?>
                        <p class="mb-1 text-muted"><strong>Received On:</strong> <?php echo date('d M, Y', strtotime($EQUIPMENT_RENT->received_date)); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Equipment / Code</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rent_items as $i => $item): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <h6 class="font-size-14 mb-0"><?php echo htmlspecialchars($item['equipment_name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['sub_equipment_code']); ?></small>
                                </td>
                                <td><?php echo ucfirst($item['rent_type']); ?></td>
                                <td><?php echo intval($item['duration']) . ' ' . ($item['rent_type'] == 'month' ? 'Mo' : 'Days'); ?></td>
                                <td class="text-center"><?php echo intval($item['quantity']); ?></td>
                                <td class="text-end"><?php echo number_format($item['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($return_rows)): ?>
            <div class="mb-4">
                <h5 class="font-size-15 mb-2">Return History:</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Equipment</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Rental</th>
                                <th class="text-end">Damage</th>
                                <th class="text-end">Penalty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($return_rows as $ret): ?>
                                <tr>
                                    <td><?php echo $ret['return_date']; ?></td>
                                    <td><?php echo htmlspecialchars($ret['equipment_name']); ?></td>
                                    <td class="text-center"><?php echo intval($ret['return_qty']); ?></td>
                                    <td class="text-end"><?php echo number_format($ret['rental_amount'], 2); ?></td>
                                    <td class="text-end"><?php echo number_format($ret['total_damage_display'], 2); ?></td>
                                    <td class="text-end"><?php echo number_format($ret['penalty_amount'], 2); ?></td>
                                    <td class="text-end font-weight-bold"><?php echo number_format($ret['rental_amount'] + $ret['total_damage_display'] + $ret['extra_day_amount'] + $ret['penalty_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="row justify-content-end">
                <div class="col-sm-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th class="text-end"><?php echo $rent_label; ?></th>
                            <td class="text-end" style="width: 120px;"><?php echo number_format($total_amount, 2); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end">Transport Cost:</th>
                            <td class="text-end"><?php echo number_format($transport_amount, 2); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end">Security Deposit:</th>
                            <td class="text-end"><?php echo number_format($total_deposit, 2); ?></td>
                        </tr>
                        <?php if ($refund_balance != 0): ?>
                        <tr class="border-top">
                            <th class="text-end"><?php echo $refund_balance > 0 ? 'Refundable to Customer:' : 'Outstanding Payable:'; ?></th>
                            <td class="text-end fw-bold <?php echo $refund_balance > 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format(abs($refund_balance), 2); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <?php if (!empty($attachments)): ?>
            <div class="mt-5 border-top pt-4">
                <h5 class="font-size-16 mb-3"><i class="uil uil-image-v me-1"></i> Attachments (Issue & Return Notes)</h5>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($attachments as $att): ?>
                        <div class="col">
                            <div class="card h-100 border shadow-sm">
                                <a href="<?php echo htmlspecialchars($att['path']); ?>" target="_blank">
                                    <img src="<?php echo htmlspecialchars($att['path']); ?>" class="card-img-top" alt="Attachment" style="height: 200px; object-fit: cover;">
                                </a>
                                <div class="card-body p-2">
                                    <h6 class="card-title font-size-13 mb-1"><?php echo htmlspecialchars($att['type']); ?></h6>
                                    <p class="card-text text-muted font-size-12 mb-0">
                                        <strong>Code:</strong> <?php echo htmlspecialchars($att['code']); ?><br>
                                        <strong>Date:</strong> <?php echo date('d M, Y', strtotime($att['date'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mt-4 pt-3 border-top no-print">
                <p class="text-muted"><i class="uil uil-info-circle me-1"></i> This is a read-only document for internal bill verification.</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
