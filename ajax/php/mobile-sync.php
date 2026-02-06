<?php
session_start();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$temp_dir = '../../uploads/temp/';

if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Clean up old temp files (older than 1 hour)
$files = glob($temp_dir . '*');
foreach ($files as $file) {
    if (is_file($file) && (time() - filemtime($file) > 3600)) {
        unlink($file);
    }
}

switch ($action) {
    case 'INIT':
        $sync_id = bin2hex(random_bytes(16));
        echo json_encode(['status' => 'success', 'sync_id' => $sync_id]);
        break;

    case 'UPLOAD':
        $sync_id = $_POST['sync_id'] ?? '';
        $image_data = $_POST['image'] ?? '';

        if (!$sync_id || !$image_data) {
            echo json_encode(['status' => 'error', 'message' => 'Missing data']);
            break;
        }

        // Handle base64 image
        if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
            $image_data = substr($image_data, strpos($image_data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid image type']);
                break;
            }

            $image_data = base64_decode($image_data);

            if ($image_data === false) {
                echo json_encode(['status' => 'error', 'message' => 'Base64 decode failed']);
                break;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Source data not valid base64']);
            break;
        }

        $file_path = $temp_dir . $sync_id . '.jpg';
        if (file_put_contents($file_path, $image_data)) {
            echo json_encode(['status' => 'success', 'message' => 'Uploaded successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
        }
        break;

    case 'POLL':
        $sync_id = $_GET['sync_id'] ?? '';
        if (!$sync_id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing sync_id']);
            break;
        }

        $file_path = $temp_dir . $sync_id . '.jpg';
        if (file_exists($file_path)) {
            $image_base64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($file_path));
            // Optional: delete file after polling
            // unlink($file_path); 
            echo json_encode(['status' => 'success', 'image' => $image_base64]);
        } else {
            echo json_encode(['status' => 'pending']);
        }
        break;

    case 'DELETE':
        $sync_id = $_POST['sync_id'] ?? '';
        if (!$sync_id) exit(json_encode(['status' => 'error', 'message' => 'Missing sync_id']));
        $file_path = $temp_dir . $sync_id . '.jpg';
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        exit(json_encode(['status' => 'success']));
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
