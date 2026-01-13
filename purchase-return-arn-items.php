<?php
require_once 'class/include.php';

if (!isset($_GET['arn_id'])) {
    echo "<div class='alert alert-danger'>Invalid ARN ID</div>";
    exit;
}

$arn_id = (int) $_GET['arn_id'];

$db = Database::getInstance();

// Get ARN details for department
$arn = mysqli_fetch_assoc($db->readQuery("SELECT department FROM arn_master WHERE id = $arn_id"));
$department_id = $arn ? (int) $arn['department'] : 0;

// Get ARN items with available stock from stock_item_tmp
// Fallback to received_qty if no stock_item_tmp data exists
$query = "SELECT ai.*, 
                 im.code AS item_code,
                 im.id AS item_id, im.name, im.brand, im.size, im.pattern, im.group, im.category,
                 im.invoice_price as cost, im.list_price,
                 IFNULL(sit.available_qty, ai.received_qty) AS available_qty
          FROM arn_items ai
          LEFT JOIN item_master im ON ai.item_code = im.id
          LEFT JOIN (
              SELECT item_id, arn_id, SUM(qty) AS available_qty 
              FROM stock_item_tmp 
              WHERE arn_id = $arn_id AND department_id = $department_id
              GROUP BY item_id, arn_id
          ) sit ON sit.item_id = im.id AND sit.arn_id = ai.arn_id
          WHERE ai.arn_id = $arn_id";
$result = $db->readQuery($query);
?>
<input type="hidden" id="arn_id_hidden" value="<?= $arn_id; ?>">
<input type="hidden" id="department_id_hidden" value="<?= $department_id; ?>">

<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Code</th>
            <th>Name</th>
            <th>Size</th>
            <th>Pattern</th>
            <th>Order Qty</th>
            <th>Received Qty</th>
            <th>Available Stock</th>
            <th>Final Cost</th>
            <th>Return Qty</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $index = 1;
        $hasItems = false;
        while ($item = mysqli_fetch_assoc($result)) {
            $hasItems = true;
            $available = (float) $item['available_qty'];
        ?>
            <tr>
                <td><?= $index++; ?></td>
                <td><?= htmlspecialchars($item['item_code']); ?></td>
                <td><?= htmlspecialchars($item['name']); ?></td>
                <td><?= htmlspecialchars($item['size']); ?></td>
                <td><?= htmlspecialchars($item['pattern']); ?></td>
                <td><?= htmlspecialchars($item['order_qty']); ?></td>
                <td><?= htmlspecialchars($item['received_qty']); ?></td>
                <td>
                    <span class="badge <?= $available > 0 ? 'bg-success' : 'bg-danger'; ?>">
                        <?= number_format($available, 0); ?>
                    </span>
                </td>
                <td><?= htmlspecialchars(number_format($item['final_cost'], 2)); ?></td>
                <td>
                    <input type="number"
                        class="form-control return-qty"
                        name="return_qty[<?= $item['item_id']; ?>]"
                        max="<?= $available; ?>"
                        min="0"
                        value="0"
                        data-available="<?= $available; ?>"
                        data-item-id="<?= $item['item_id']; ?>"
                        data-final-cost="<?= $item['final_cost']; ?>"
                        <?= $available <= 0 ? 'disabled' : ''; ?>
                        style="width: 100px;">
                </td>
            </tr>
        <?php
        }
        if (!$hasItems) {
            echo '<tr><td colspan="10" class="text-center text-muted">No items found for this ARN.</td></tr>';
        }
        ?>
    </tbody>
</table>

<!-- Return Total Summary -->
<div class="row mt-3">
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title">Return Summary</h6>
                <p class="mb-1">Total Return Qty: <strong id="totalReturnQty">0</strong></p>
                <p class="mb-0">Total Return Value: <strong id="totalReturnValue">0.00</strong></p>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate totals when return qty changes
$(document).on('input', '.return-qty', function() {
    let totalQty = 0;
    let totalValue = 0;
    
    $('.return-qty').each(function() {
        const qty = parseFloat($(this).val()) || 0;
        const cost = parseFloat($(this).data('final-cost')) || 0;
        const available = parseFloat($(this).data('available')) || 0;
        
        // Validate max
        if (qty > available) {
            $(this).val(available);
            return;
        }
        
        totalQty += qty;
        totalValue += (qty * cost);
    });
    
    $('#totalReturnQty').text(totalQty);
    $('#totalReturnValue').text(totalValue.toFixed(2));
});
</script>