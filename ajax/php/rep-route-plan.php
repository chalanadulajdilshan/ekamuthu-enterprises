<?php
include '../../class/include.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

if ($userId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in again']);
    exit;
}

$repId = (int)($_POST['rep_id'] ?? 0);
$date = $_POST['route_date'] ?? '';
if ($repId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a rep and a date']);
    exit;
}

function parseRentIds($raw)
{
    $ids = is_array($raw) ? $raw : explode(',', (string)$raw);
    return array_values(array_unique(array_filter(array_map('intval', $ids))));
}

try {
    if ($action === 'get_route') {
        $stops = RepFollowup::getRoute($repId, $date);

        // Rep's assigned overdue bills that are not on this route yet
        $onRoute = array_column($stops, 'rent_id');
        $available = [];
        foreach (RepFollowup::getOverdueBills($date, RepFollowup::getAssignedRentIds($repId)) as $b) {
            if (in_array($b['rent_id'], $onRoute, true)) {
                continue;
            }
            $available[] = [
                'rent_id' => $b['rent_id'],
                'bill_number' => $b['bill_number'],
                'customer_name' => $b['customer_code'] . ' - ' . $b['customer_name'],
                'location' => $b['location'],
                'late_text' => $b['late_text'],
                'overdue_days' => $b['overdue_days'],
                'outstanding' => round($b['projected_outstanding'] + $b['recorded_outstanding'], 2),
                'last_visit' => $b['last_visit']
            ];
        }

        echo json_encode(['status' => 'success', 'data' => ['stops' => $stops, 'available' => $available]]);
        exit;
    }

    if ($action === 'add_stops') {
        $rentIds = parseRentIds($_POST['rent_ids'] ?? []);
        // Only bills assigned to this rep can go on the route
        $rentIds = array_values(array_intersect($rentIds, RepFollowup::getAssignedRentIds($repId)));
        if (!$rentIds) {
            echo json_encode(['status' => 'error', 'message' => 'Select bills assigned to this rep']);
            exit;
        }
        $added = RepFollowup::addToRoute($repId, $date, $rentIds, $userId);
        echo json_encode(['status' => 'success', 'message' => "$added stop(s) added"]);
        exit;
    }

    if ($action === 'reorder') {
        RepFollowup::reorderRoute($repId, $date, parseRentIds($_POST['rent_ids'] ?? []));
        echo json_encode(['status' => 'success', 'message' => 'Order saved']);
        exit;
    }

    if ($action === 'remove_stop') {
        RepFollowup::removeFromRoute($repId, $date, (int)($_POST['rent_id'] ?? 0));
        echo json_encode(['status' => 'success', 'message' => 'Stop removed']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
