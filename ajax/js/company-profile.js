jQuery(document).ready(function () {
    // Color Theme Handling
    const themeInput = document.getElementById('theme');
    const themeResetBtn = document.getElementById('theme-reset');
    const defaultTheme = '#3b5de7';

    // Apply theme on page load
    applyTheme(themeInput.value);

    // Handle theme color change
    if (themeInput) {
        themeInput.addEventListener('input', function () {
            applyTheme(this.value);
        });
    }

    // Handle theme reset
    if (themeResetBtn) {
        themeResetBtn.addEventListener('click', function () {
            themeInput.value = defaultTheme;
            applyTheme(defaultTheme);
        });
    }

    // Function to apply theme colors
    function applyTheme(color) {
        if (!color) return;

        // Update CSS variables for theme colors
        document.documentElement.style.setProperty('--bs-primary', color);

        // Calculate hover and active states
        const hoverColor = adjustColor(color, -10);
        const activeColor = adjustColor(color, -20);

        document.documentElement.style.setProperty('--bs-primary-hover', hoverColor);
        document.documentElement.style.setProperty('--bs-primary-active', activeColor);
    }

    // Helper function to adjust color brightness
    function adjustColor(color, amount) {
        return '#' + color.replace(/^#/, '').replace(/../g, color =>
            ('0' + Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).substr(-2)
        );
    }

    // Image Cropping Functionality
    let cropper;
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logo-preview');
    const croppedLogoInput = document.getElementById('cropped-logo');
    const preview = document.querySelector('.img-preview');

    // Initialize cropper when logo is selected
    if (logoInput) {
        logoInput.addEventListener('change', function (e) {
            const files = e.target.files;
            const file = files[0];

            if (!file) return;

            // Check file type
            if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
                swal("Error", "Only JPG and PNG files are allowed.", "error");
                $(this).val('');
                return;
            }

            // Check file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                swal("Error", "File size should not exceed 2MB.", "error");
                $(this).val('');
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                // Destroy previous cropper instance if exists
                if (cropper) {
                    cropper.destroy();
                }

                // Show preview image
                logoPreview.src = event.target.result;
                logoPreview.style.display = 'block';

                // Initialize cropper
                cropper = new Cropper(logoPreview, {
                    aspectRatio: 250 / 60,
                    viewMode: 1,
                    autoCropArea: 0.8,
                    responsive: true,
                    preview: '.img-preview',
                    crop: function (event) {
                        // Get cropped canvas
                        const canvas = cropper.getCroppedCanvas({
                            width: 250,
                            height: 60,
                            minWidth: 250,
                            minHeight: 60,
                            maxWidth: 1000,
                            maxHeight: 1000,
                            fillColor: '#fff',
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });

                        // Convert canvas to base64 and set to hidden input
                        croppedLogoInput.value = canvas.toDataURL('image/jpeg', 0.9);
                    }
                });
            };

            reader.readAsDataURL(file);
        });
    }

    // Update form submission to include cropped image
    $("#form-data").on('submit', function (e) {
        // If logo is selected but not cropped
        if (logoInput && logoInput.files.length > 0 && (!croppedLogoInput || !croppedLogoInput.value)) {
            e.preventDefault();
            swal("Error", "Please crop the logo before saving.", "error");
            return false;
        }
        return true;
    });

    // Create Company Profile
    $("#create").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$('#name').val() || $('#name').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter company name",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#email').val() || $('#email').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter company email",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#email').val() || $('#email').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter company email",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else {

            $('.someBlock').preloader();

            var formData = new FormData($("#form-data")[0]);
            formData.append('create', true);

            $.ajax({
                url: "ajax/php/company-profile.php",
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                success: function (result) {
                    $('.someBlock').preloader('remove');

                    if (result.status === 'success') {
                        swal({
                            title: "Success!",
                            text: "Company Profile added successfully!",
                            type: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        swal({
                            title: "Error!",
                            text: "Something went wrong.",
                            type: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            });
        }

        return false;
    });

    // Update Company Profile
    $("#update").click(function (event) {
        event.preventDefault();

        // Validation
        if (!$('#name').val() || $('#name').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter company name",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (!$('#email').val() || $('#email').val().length === 0) {
            swal({
                title: "Error!",
                text: "Please enter company email",
                type: 'error',
                timer: 2000,
                showConfirmButton: false
            });
        } else {

            $('.someBlock').preloader();

            var formData = new FormData($("#form-data")[0]);
            formData.append('update', true);

            $.ajax({
                url: "ajax/php/company-profile.php",
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "JSON",
                success: function (result) {
                    $('.someBlock').preloader('remove');

                    if (result.status == 'success') {
                        swal({
                            title: "Success!",
                            text: "Company Profile updated successfully!",
                            type: 'success',
                            timer: 2500,
                            showConfirmButton: false
                        });

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 2000);

                    } else {
                        swal({
                            title: "Error!",
                            text: "Something went wrong.",
                            type: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            });
        }

        return false;
    });

    // Reset form
    $("#new").click(function (e) {
        e.preventDefault();
        $('#form-data')[0].reset();
        $('#home_view_mode').val('both');
        $("#create").show();
    });

    // Open modal
    $('#open-company-profile-modal').click(function (e) {
        e.preventDefault();
        var myModal = new bootstrap.Modal(document.querySelector('.bs-example-modal-xl'));
        myModal.show();
    });

    // Populate form from modal click
    $(document).on('click', '.select-company', function () {
        $('#company_id').val($(this).data('id'));
        $('#name').val($(this).data('name'));
        $('#address').val($(this).data('address'));
        $('#mobile_number_1').val($(this).data('mobile1'));
        $('#mobile_number_2').val($(this).data('mobile2'));
        $('#mobile_number_3').val($(this).data('mobile3'));
        $('#email').val($(this).data('email'));
        $('#vat_number').val($(this).data('vatnumber'));
        $('#company_code').val($(this).data('companycode')).prop('readonly', false);
        $('#customer_id').val($(this).data('customerid'));
        $('#image_name').val($(this).data('image'));
        $('#home_view_mode').val($(this).data('homeviewmode') || 'both');
        $('#is_one_company').prop('checked', $(this).data('isonecompany') == 1);
        $('#is_credit').prop('checked', $(this).data('iscredit') == 1);

        // Update logo preview
        var imagePath = $(this).data('image');
        var logoPreview = $('#logo-preview');

        // Clear previous cropper instance if exists
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        if (imagePath) {
            var imageUrl = 'uploads/company-logos/' + imagePath;
            logoPreview.attr('src', imageUrl).css('display', 'block');

            // Initialize cropper for the preview
            logoPreview.on('load', function () {
                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(logoPreview[0], {
                    aspectRatio: 250 / 60,
                    viewMode: 1,
                    autoCropArea: 0.8,
                    responsive: true,
                    preview: '.img-preview',
                    crop: function (event) {
                        const canvas = cropper.getCroppedCanvas({
                            width: 250,
                            height: 60,
                            minWidth: 250,
                            minHeight: 60,
                            fillColor: '#fff',
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });
                        $('#cropped-logo').val(canvas.toDataURL('image/jpeg', 0.9));
                    }
                });
            });
        } else {
            logoPreview.hide();
            $('.img-preview').css('background-image', 'none');
        }

        $('#is_active').prop('checked', $(this).data('active') == 1);
        $('#is_vat').prop('checked', $(this).data('isvat') == 1);

        if ($('#is_vat').is(':checked')) {
            $('#vat-number-group').show();
        } else {
            $('#vat-number-group').hide();
        }

        $("#create").hide();
        $('.bs-example-modal-xl').modal('hide');
    });

    // Delete Company Profile
    $(document).on('click', '.delete-company-profile', function (e) {
        e.preventDefault();

        var companyId = $('#company_id').val();
        var companyName = $('#name').val();

        if (!companyId || companyId === "") {
            swal({
                title: "Error!",
                text: "Please select a company profile first.",
                type: "error",
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to delete the company profile '" + companyName + "'?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        }, function (isConfirm) {
            if (isConfirm) {
                $('.someBlock').preloader();

                $.ajax({
                    url: 'ajax/php/company-profile.php',
                    type: 'POST',
                    data: {
                        id: companyId,
                        delete: true
                    },
                    dataType: 'JSON',
                    success: function (response) {
                        $('.someBlock').preloader('remove');

                        if (response.status === 'success') {
                            swal({
                                title: "Deleted!",
                                text: "Company Profile has been deleted.",
                                type: "success",
                                timer: 2000,
                                showConfirmButton: false
                            });

                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);

                        } else {
                            swal({
                                title: "Error!",
                                text: "Something went wrong.",
                                type: "error",
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    }
                });
            }
        });
    });

    // Toggle VAT Number field
    $("#is_vat").change(function () {
        if ($(this).is(':checked')) {
            $("#vat-number-group").show();
            $("#vat_number").prop("required", true);
        } else {
            $("#vat-number-group").hide();
            $("#vat_number").val('').prop("required", false);
        }
    });

    // Optional: Trigger on load in case of edit mode
    if ($('#is_vat').is(':checked')) {
        $("#vat-number-group").show();
        $("#vat_number").prop("required", true);
    }


    const previewContainer = document.getElementById("logo-preview");

    // Logo preview
    $('#logo').on('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#logo-preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        }
    });

    // Favicon preview
    $('#favicon').on('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#favicon-preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        }
    });

    // Crop and Upload (if used separately)
    $('#form-data').submit(function (e) {
        e.preventDefault();

        if (cropper) {
            const canvas = cropper.getCroppedCanvas({ width: 600, height: 200 });

            canvas.toBlob(blob => {
                removeBackground(blob, function (bgRemovedBlob) {
                    const imageUrl = URL.createObjectURL(bgRemovedBlob);
                    const image = new Image();
                    image.src = imageUrl;

                    image.onload = () => {
                        const upscaler = new Upscaler();
                        upscaler.upscale(image).then(enhancedCanvas => {
                            enhancedCanvas.toBlob(enhancedBlob => {
                                const formData = new FormData($('#form-data')[0]);
                                formData.append('logo', enhancedBlob, 'logo.png');
                                formData.append('create', true);

                                $('.someBlock').preloader();

                                $.ajax({
                                    url: 'ajax/php/company-profile.php',
                                    type: 'POST',
                                    data: formData,
                                    cache: false,
                                    contentType: false,
                                    processData: false,
                                    success: function (response) {
                                        $('.someBlock').preloader('remove');
                                        console.log(response);
                                    }
                                });

                            }, 'image/png');
                        });
                    };
                });
            });
        } else {
            swal("Error", "Please upload and crop the logo before saving.", "error");
        }
    });

    // Background remover
    function removeBackground(fileBlob, callback) {
        const formData = new FormData();
        formData.append("image_file", fileBlob);
        formData.append("size", "auto");

        fetch("https://api.remove.bg/v1.0/removebg", {
            method: "POST",
            headers: {
                "X-Api-Key": "XKydp6uaquCcMK8WK7Agga4D" // Replace with your key
            },
            body: formData
        })
            .then(res => res.blob())
            .then(callback)
            .catch(err => {
                console.error("Background removal failed", err);
                swal("Error", "Background removal failed.", "error");
            });
    }

    // Alert helpers
    function showError(msg) {
        swal({ title: "Error!", text: msg, type: 'error', timer: 2000, showConfirmButton: false });
    }

    function showSuccess(msg) {
        swal({ title: "Success!", text: msg, type: 'success', timer: 2000, showConfirmButton: false });
    }

});
