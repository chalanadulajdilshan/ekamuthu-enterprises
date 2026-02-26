<?php
session_start();
include 'class/include.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Equipment Quantities</title>
    <?php include 'main-css.php'; ?>
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-4">Sync Equipment Quantities with Department Stock</h4>
                            <p>This tool will update all equipment quantities to match the sum of their department stock quantities.</p>
                            
                            <div class="mb-3">
                                <button class="btn btn-primary" id="btnPreview">
                                    <i class="bx bx-search-alt"></i> Preview Changes
                                </button>
                                <button class="btn btn-success" id="btnSync" disabled>
                                    <i class="bx bx-sync"></i> Sync All Equipment
                                </button>
                            </div>

                            <div id="previewSection" style="display: none;">
                                <h5 class="mt-4">Equipment with Mismatched Quantities</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped" id="previewTable">
                                        <thead>
                                            <tr>
                                                <th>Equipment Code</th>
                                                <th>Item Name</th>
                                                <th>Current Qty</th>
                                                <th>Calculated Qty</th>
                                                <th>Difference</th>
                                            </tr>
                                        </thead>
                                        <tbody id="previewBody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="resultSection" style="display: none;" class="mt-4">
                                <div class="alert alert-success">
                                    <h5>Sync Complete!</h5>
                                    <p id="resultMessage"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'main-js.php'; ?>
    
    <script>
    $(document).ready(function() {
        $('#btnPreview').click(function() {
            $.ajax({
                url: 'ajax/php/sync-all-equipment-quantities.php',
                type: 'POST',
                data: { preview: true },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.count > 0) {
                            $('#previewBody').empty();
                            response.data.forEach(function(item) {
                                const diffClass = item.difference > 0 ? 'text-success' : 'text-danger';
                                $('#previewBody').append(`
                                    <tr>
                                        <td>${item.code}</td>
                                        <td>${item.item_name}</td>
                                        <td>${item.current_qty}</td>
                                        <td>${item.calculated_qty}</td>
                                        <td class="${diffClass}">${item.difference > 0 ? '+' : ''}${item.difference}</td>
                                    </tr>
                                `);
                            });
                            $('#previewSection').show();
                            $('#btnSync').prop('disabled', false);
                        } else {
                            alert('All equipment quantities are already in sync!');
                            $('#previewSection').hide();
                            $('#btnSync').prop('disabled', true);
                        }
                    }
                },
                error: function() {
                    alert('Error loading preview');
                }
            });
        });

        $('#btnSync').click(function() {
            if (confirm('Are you sure you want to sync all equipment quantities? This will update the equipment master records.')) {
                $.ajax({
                    url: 'ajax/php/sync-all-equipment-quantities.php',
                    type: 'POST',
                    data: { sync_all: true },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#resultMessage').text(response.message);
                            $('#resultSection').show();
                            $('#btnSync').prop('disabled', true);
                            $('#previewSection').hide();
                            
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            alert('Error syncing equipment quantities');
                        }
                    },
                    error: function() {
                        alert('Error syncing equipment quantities');
                    }
                });
            }
        });
    });
    </script>
</body>
</html>
