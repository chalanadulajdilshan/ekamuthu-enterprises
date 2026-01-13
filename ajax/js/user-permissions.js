
// Handle user selection change
$('#selectUser').on('change', function () {
    const userId = $(this).val();
    const $specialPermissionBtn = $('#BtnSpecialPermissionModal');

    if (userId) {
        $specialPermissionBtn.show();
        fetchPermissions(userId);
        loadSpecialPermissions(userId);
    } else {
        $specialPermissionBtn.hide();
        $('#permissionsTable').hide();
        $('#permissionsTableBody').empty();
        $('#special_permissionTableBody').empty();
    }
});
// Initially hide the special permission button on page load
$(document).ready(function () {
    $('#BtnSpecialPermissionModal').hide();
});


function fetchPermissions(userId) {
    $('.someBlock').preloader();

    $.ajax({
        url: 'ajax/php/get-permissions.php',
        method: 'GET',
        data: { userId: userId },
        dataType: 'json',
        success: function (data) {
            $('.someBlock').preloader('remove');
            const tableBody = $('#permissionsTableBody');
            tableBody.empty();
            $('#permissionsTable').show();

            // Make the table sortable if not already done
            if (!tableBody.hasClass('ui-sortable')) {
                tableBody.sortable({
                    update: function(event, ui) {
                        updatePageOrder();
                    },
                    handle: 'td:first-child', // Only allow dragging by the first column (the number)
                    cursor: 'move',
                    placeholder: 'sortable-placeholder',
                    helper: 'clone',
                    axis: 'y',
                    opacity: 0.7
                });
                
                // Add CSS for the sortable placeholder
                $('<style>' +
                    '.sortable-placeholder { background: #f8f9fa; height: 40px; border: 2px dashed #dee2e6; }' +
                    '.ui-sortable-helper { display: table; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }' +
                    '.ui-sortable-helper td { background: #fff; }' +
                    'tr { cursor: move; }' +
                    '</style>').appendTo('head');
            }

            $.each(data.pages, function (index, page) {
                const row = `
                    <tr data-page-id="${page.pageId}">
                        <td><i class="fas fa-arrows-alt-v me-2"></i>${index + 1}</td>
                        <td>${page.pageCategory}</td>
                        <td>${page.pageName}</td>
                        <td><input type="checkbox" name="permissions[${page.pageId}][add]" ${page.add_page == 1 ? 'checked' : ''}></td>
                        <td><input type="checkbox" name="permissions[${page.pageId}][edit]" ${page.edit_page == 1 ? 'checked' : ''}></td>
                        <td><input type="checkbox" name="permissions[${page.pageId}][search]" ${page.search_page == 1 ? 'checked' : ''}></td>
                        <td><input type="checkbox" name="permissions[${page.pageId}][delete]" ${page.delete_page == 1 ? 'checked' : ''}></td>
                        <td><input type="checkbox" name="permissions[${page.pageId}][print]" ${page.print_page == 1 ? 'checked' : ''}></td>
                        <td><input type="checkbox" name="permissions[${page.pageId}][other]" ${page.other_page == 1 ? 'checked' : ''}></td>
                    </tr>
                `;
                tableBody.append(row);
            });
            
            // Update row numbers after reordering
            updateRowNumbers();
        },
        error: function (xhr, status, error) {
            $('.someBlock').preloader('remove');
            console.error('Error fetching permissions:', error);
            swal({
                title: "Error!",
                text: "Failed to load permissions.",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

// Add select all functionality for the top checkbox
$('#selectAllTop').on('change', function() {
    const isChecked = $(this).prop('checked');
    // Select all checkboxes in the table
    $('#permissionsTable input[type="checkbox"]').prop('checked', isChecked);
});

// Add select all functionality for column headers
$('#permissionsTable thead th').on('click', 'input[type="checkbox"]', function() {
    const columnIndex = $(this).closest('th').index();
    const isChecked = $(this).prop('checked');
    
    // Select all checkboxes in the same column
    $('#permissionsTable tbody tr').each(function() {
        $(this).find('td').eq(columnIndex - 2).find('input[type="checkbox"]').prop('checked', isChecked);
    });
});

// Update the select all top checkbox when individual checkboxes are clicked
$('#permissionsTable').on('change', 'tbody input[type="checkbox"]', function() {
    const totalCheckboxes = $('#permissionsTable tbody input[type="checkbox"]').length;
    const checkedCheckboxes = $('#permissionsTable tbody input[type="checkbox"]:checked').length;
    
    // Update the top checkbox
    $('#selectAllTop').prop('checked', totalCheckboxes === checkedCheckboxes);
});

// Function to update row numbers after reordering
function updateRowNumbers() {
    $('#permissionsTable tbody tr').each(function(index) {
        $(this).find('td:first').html(`<i class="fas fa-arrows-alt-v me-2"></i>${index + 1}`);
    });
}

// Function to update page order in the database
function updatePageOrder() {
    const pageOrder = [];
    $('#permissionsTable tbody tr').each(function() {
        pageOrder.push($(this).data('page-id'));
    });
    
    // Update row numbers after reordering
    updateRowNumbers();
    
    // Send AJAX request to update the order in the database
    $.ajax({
        url: 'ajax/php/update-page-order.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ pageOrder: pageOrder }),
        success: function(response) {
            if (response.status !== 'success') {
                console.error('Failed to update page order:', response.message);
                // You might want to show an error message to the user here
            }
        },
        error: function(xhr, status, error) {
            console.error('Error updating page order:', error);
            // You might want to show an error message to the user here
        }
    });
}

$('#create').on('click', function (e) {
    e.preventDefault();

    // Check if a user is selected
    const selectedUser = $('#selectUser').val();
    if (!selectedUser) {
        swal({
            title: "Error!",
            text: "Please select a user first.",
            type: 'error',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }

    $('.someBlock').preloader();

    $.ajax({
        url: 'ajax/php/user-permissions.php',
        type: 'POST',
        data: $('#permissionsForm').serialize(),
        dataType: 'json',
        success: function (response) {
            $('.someBlock').preloader('remove');

            if (response.status === 'success') {
                swal({
                    title: "Success!",
                    text: "User permissions updated successfully!",
                    type: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });

                setTimeout(() => window.location.reload(), 2000);

            } else {
                swal({
                    title: "Error!",
                    text: response.message || "Something went wrong.",
                    type: 'error',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        },
        error: function () {
            $('.someBlock').preloader('remove');
            swal("Error", "Something went wrong while saving permissions.", "error");
        }
    });
});

function loadSpecialPermissions(userId) {
    if (!userId) return;

    $.ajax({
        url: 'ajax/php/special-user-permission.php',
        method: 'GET',
        data: { userId: userId },
        dataType: 'json',
        success: function (response) {
            const tbody = $('#special_permissionTableBody');
            tbody.empty();

            if (response.status === 'success' && response.data && response.data.length > 0) {
                response.data.forEach(permission => {
                    const row = `
                        <tr data-permission-id="${permission.id}">
                            <td>${permission.id}</td>
                            <td>${permission.permission_name}</td>
                            <td class="text-center">
                                <div class="form-check form-switch form-switch-md">
                                    <input type="checkbox" class="form-check-input status-toggle" 
                                        data-permission-id="${permission.id}"
                                        ${permission.status === 'active' ? 'checked' : ''}>
                                    <label class="form-check-label"></label>
                                </div>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                tbody.append('<tr><td colspan="3" class="text-center">No special permissions found</td></tr>');
            }
        },
        error: function (xhr, status, error) {
            console.error('Error loading special permissions:', error);
            $('#special_permissionTableBody').html('<tr><td colspan="3" class="text-center text-danger">Error loading special permissions</td></tr>');
        }
    });
}

// Handle status toggle for special permissions
$(document).on('change', '.status-toggle', function () {
    const $toggle = $(this);
    const permissionId = $toggle.data('permission-id');
    const isActive = $toggle.is(':checked') ? 'active' : 'inactive';
    
    // Store the current state in case we need to revert
    const originalState = $toggle.is(':checked');
    
    // Disable the toggle during the request
    $toggle.prop('disabled', true);
    $('.someBlock').preloader();

    $.ajax({
        url: 'ajax/php/special-user-permission.php',
        method: 'POST',
        data: {
            action: 'update_status',
            permission_id: permissionId,
            status: isActive
        },
        dataType: 'json',
        success: function (response) {
            $('.someBlock').preloader('remove');
            $toggle.prop('disabled', false);
            
            if (response.status !== 'success') {
                // Revert the toggle on error
                $toggle.prop('checked', !originalState);
                swal('Error', response.message || 'Failed to update permission status', 'error');
                return;
            }
            
            // Show success message
            swal('Success', 'Permission status updated successfully', 'success');
            
            // Update any UI elements that might depend on this permission
            if (response.data && response.data.status === 'active') {
                $toggle.closest('tr').find('.status-badge')
                    .removeClass('badge-soft-danger')
                    .addClass('badge-soft-success')
                    .text('Active');
            } else {
                $toggle.closest('tr').find('.status-badge')
                    .removeClass('badge-soft-success')
                    .addClass('badge-soft-danger')
                    .text('Inactive');
            }
        },
        error: function (xhr, status, error) {
            $('.someBlock').preloader('remove');
            $toggle.prop('disabled', false);
            
            // Revert the toggle on error
            $toggle.prop('checked', originalState);
            
            console.error('Error updating permission status:', error);
            swal('Error', 'An error occurred while updating permission status. Please try again.', 'error');
        }
    });
});

// Handle add new special permission
$(document).on('click', '#add_special_permission', function () {
    const permissionName = $('#newPermissionName').val().trim();
    const status = $('#newPermissionStatus').val();
    const selectedUser = $('#selectUser').val();

    if (!permissionName) {
        swal('Error', 'Please enter a permission name', 'error');
        return;
    }

    if (!selectedUser) {
        swal({
            title: "Error!",
            text: "Please select a user first.",
            type: 'error',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }

    $('.someBlock').preloader();

    $.ajax({
        url: 'ajax/php/special-user-permission.php',
        method: 'POST',
        data: {
            user_id: selectedUser,
            permission_name: permissionName,
            status: status
        },
        dataType: 'json',
        success: function (response) {
            $('.someBlock').preloader('remove');
            if (response.status === 'success') {
                $('#newPermissionName').val('');
                loadSpecialPermissions(selectedUser);
                swal('Success', 'Special permission added successfully', 'success');
            } else {
                swal('Error', response.message || 'Failed to add permission', 'error');
            }
        },
        error: function () {
            $('.someBlock').preloader('remove');
            swal('Error', 'An error occurred while processing your request', 'error');
        }
    });
});