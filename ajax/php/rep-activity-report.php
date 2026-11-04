<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$db = Database::getInstance();
$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

if ($userId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in again']);
    exit;
}

if ($action === 'get_activity') {
    $data = RepFollowup::getActivity($_POST['from_date'] ?? date('Y-m-d'), $_POST['to_date'] ?? date('Y-m-d'), (int)($_POST['rep_id'] ?? 0) ?: null);
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// Office check of a rep collection: verified (proof accepted) or rejected
if ($action === 'set_collection_status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $remark = $db->escapeString(mb_substr(trim($_POST['admin_remark'] ?? ''), 0, 255));

    if (!in_array($status, ['verified', 'rejected'], true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit;
    }

    $row = mysqli_fetch_assoc($db->readQuery("SELECT id, status, proof_image FROM rep_collections WHERE id = $id"));
    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Collection not found']);
        exit;
    }
    if ($status === 'verified' && empty($row['proof_image'])) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot verify a payment without proof']);
        exit;
    }

    $db->readQuery("UPDATE rep_collections SET status = '$status', admin_remark = '$remark', verified_by = $userId, verified_at = NOW() WHERE id = $id");
    echo json_encode(['status' => 'success', 'message' => 'Payment marked as ' . $status]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
