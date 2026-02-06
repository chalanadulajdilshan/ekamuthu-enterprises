<?php
$sync_id = $_GET['id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Capture | Ekamuthu Enterprises</title>
    <link href="assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; font-family: 'Inter', sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .btn-capture { height: 60px; font-size: 1.2rem; border-radius: 30px; }
        .preview-container { margin: 20px 0; border-radius: 10px; overflow: hidden; background: #eee; min-height: 200px; display: flex; align-items: center; justify-content: center; }
        #preview { width: 100%; display: none; }
        .uploading-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 1000; flex-direction: column; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <div class="card p-4 text-center">
        <h3 class="mb-4">Capture Photo</h3>
        
        <?php if (!$sync_id): ?>
            <div class="alert alert-danger">Invalid sync link. Please scan the QR code again.</div>
        <?php else: ?>
            <p class="text-muted mb-4">Take a photo of the document or customer. Once uploaded, it will appear on your PC instantly.</p>
            
            <div class="preview-container">
                <i class="uil uil-image-v text-muted" style="font-size: 4rem;" id="placeholder-icon"></i>
                <img id="preview" alt="Preview">
            </div>

            <input type="file" id="cameraInput" accept="image/*" capture="camera" style="display: none;">
            
            <button class="btn btn-primary btn-block btn-capture mb-3 w-100" id="takePhotoBtn">
                <i class="uil uil-camera me-2"></i> Take Photo
            </button>

            <button class="btn btn-success btn-block btn-capture w-100 mb-2" id="uploadBtn" disabled style="display: none;">
                <i class="uil uil-upload me-2"></i> Upload to PC
            </button>

            <button class="btn btn-outline-secondary btn-sm w-100" id="finishBtn" style="display: none;" onclick="finishCapture()">
                <i class="uil uil-check-circle me-1"></i> Finish Capture
            </button>

            <div class="mt-3">
                <small class="text-muted">Sync ID: <?php echo htmlspecialchars($sync_id); ?></small>
            </div>
        <?php endif; ?>
    </div>

    <div class="uploading-overlay" id="loader">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <h5 id="loader-text">Uploading...</h5>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script>
        const syncId = '<?php echo $sync_id; ?>';
        const cameraInput = document.getElementById('cameraInput');
        const preview = document.getElementById('preview');
        const placeholder = document.getElementById('placeholder-icon');
        const takePhotoBtn = document.getElementById('takePhotoBtn');
        const uploadBtn = document.getElementById('uploadBtn');
        const finishBtn = document.getElementById('finishBtn');
        const loader = document.getElementById('loader');
        let base64Image = '';

        takePhotoBtn.addEventListener('click', () => cameraInput.click());

        cameraInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    base64Image = event.target.result;
                    preview.src = base64Image;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                    uploadBtn.style.display = 'block';
                    uploadBtn.disabled = false;
                    takePhotoBtn.innerHTML = '<i class="uil uil-redo me-2"></i> Retake';
                    takePhotoBtn.className = 'btn btn-outline-primary btn-block btn-capture mb-3 w-100';
                };
                reader.readAsDataURL(file);
            }
        });

        uploadBtn.addEventListener('click', function() {
            if (!base64Image) return;

            loader.style.display = 'flex';
            uploadBtn.disabled = true;

            $.ajax({
                url: 'ajax/php/mobile-sync.php',
                type: 'POST',
                data: {
                    action: 'UPLOAD',
                    sync_id: syncId,
                    image: base64Image
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        document.getElementById('loader-text').innerText = 'Sent!';
                        loader.querySelector('.spinner-border').className = 'uil uil-check-circle text-success mb-3';
                        loader.querySelector('.spinner-border').style.fontSize = '4rem';
                        
                        setTimeout(() => {
                            loader.style.display = 'none';
                            // Reset UI for next capture
                            base64Image = '';
                            preview.src = '';
                            preview.style.display = 'none';
                            placeholder.style.display = 'block';
                            uploadBtn.style.display = 'none';
                            uploadBtn.disabled = true;
                            takePhotoBtn.innerHTML = '<i class="uil uil-camera me-2"></i> Take Photo';
                            takePhotoBtn.className = 'btn btn-primary btn-block btn-capture mb-3 w-100';
                            finishBtn.style.display = 'block'; // Show finish button after successful upload
                        }, 1500); // Show success briefly
                    } else {
                        alert('Upload failed: ' + response.message);
                        loader.style.display = 'none';
                        uploadBtn.disabled = false;
                    }
                },
                error: function() {
                    alert('System error during upload.');
                    loader.style.display = 'none';
                    uploadBtn.disabled = false;
                }
            });
        });

        function finishCapture() {
            document.querySelector('.card').innerHTML = `
                <div class="text-center py-5 bounce-in">
                    <i class="uil uil-check-circle text-success" style="font-size: 80px;"></i>
                    <h2 class="mt-4">All Set!</h2>
                    <p class="text-muted">You can close this tab now and check your PC screen.</p>
                    <button class="btn btn-outline-primary mt-3" onclick="location.reload()">Take More?</button>
                </div>
            `;
        }
    </script>
</body>
</html>
