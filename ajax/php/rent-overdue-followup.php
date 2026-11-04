<?php
include '../../class/include.php';
header('Content-Type: application/json');

function parseRentIds($raw)
{
    $ids = is_array($raw) ? $raw : explode(',', (string)$raw);
    return array_values(array_unique(array_filter(array_map('intval', $ids))));
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance();
$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

if (!RepFollowup::ensureSchema()) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot create follow-up table']);
    exit;
}

if ($action === 'get_overdue_bills') {
    try {
        $data = RepFollowup::getOverdueBills($_POST['as_of_date'] ?? date('Y-m-d'));
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'assign_rep') {
    $rentIds = parseRentIds($_POST['rent_ids'] ?? []);
    $repId = (int)($_POST['rep_id'] ?? 0);

    if (empty($rentIds)) {
        echo json_encode(['status' => 'error', 'message' => 'No bills selected']);
        exit;
    }

    // rep_id 0 = remove assignment
    $repSql = 'NULL';
    $assignedAtSql = 'NULL';
    $assignedBySql = 'NULL';
    if ($repId > 0) {
        $REP = new MarketingExecutive($repId);
        if (!$REP->id) {
            echo json_encode(['status' => 'error', 'message' => 'Selected rep not found']);
            exit;
        }
        $repSql = $repId;
        $assignedAtSql = 'NOW()';
        $assignedBySql = $userId;
    }

    $values = [];
    foreach ($rentIds as $rentId) {
        $values[] = "($rentId, 1, $repSql, $assignedAtSql, $assignedBySql, $userId, NOW())";
    }

    // Assigning always puts the bill back into follow-up
    $sql = "INSERT INTO `rent_overdue_followup` (`rent_id`, `followup_required`, `rep_id`, `assigned_at`, `assigned_by`, `updated_by`, `updated_at`)
            VALUES " . implode(',', $values) . "
            ON DUPLICATE KEY UPDATE `followup_required` = 1, `rep_id` = VALUES(`rep_id`), `assigned_at` = VALUES(`assigned_at`),
                `assigned_by` = VALUES(`assigned_by`), `updated_by` = VALUES(`updated_by`), `updated_at` = VALUES(`updated_at`)";

    if ($db->readQuery($sql) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to assign: ' . mysqli_error($db->DB_CON)]);
        exit;
    }

    $msg = $repId > 0 ? count($rentIds) . ' bill(s) assigned to ' . $REP->full_name : 'Assignment removed from ' . count($rentIds) . ' bill(s)';

    // Optional: also put the bills on the rep's route for a date
    $routeDate = $_POST['route_date'] ?? '';
    if ($repId > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $routeDate)) {
        $added = RepFollowup::addToRoute($repId, $routeDate, $rentIds, $userId);
        $msg .= " and $added added to the $routeDate route";
    }
    echo json_encode(['status' => 'success', 'message' => $msg]);
    exit;
}

if ($action === 'set_followup') {
    $rentIds = parseRentIds($_POST['rent_ids'] ?? []);
    $required = (int)($_POST['followup_required'] ?? 1) === 1 ? 1 : 0;
    $note = $db->escapeString(mb_substr(trim($_POST['note'] ?? ''), 0, 255));

    if (empty($rentIds)) {
        echo json_encode(['status' => 'error', 'message' => 'No bills selected']);
        exit;
    }

    $values = [];
    foreach ($rentIds as $rentId) {
        $values[] = "($rentId, $required, '$note', $userId, NOW())";
    }

    // Marking "no follow-up" also clears any rep assignment
    $repReset = $required === 0 ? ", `rep_id` = NULL, `assigned_at` = NULL, `assigned_by` = NULL" : '';
    $sql = "INSERT INTO `rent_overdue_followup` (`rent_id`, `followup_required`, `note`, `updated_by`, `updated_at`)
            VALUES " . implode(',', $values) . "
            ON DUPLICATE KEY UPDATE `followup_required` = VALUES(`followup_required`), `note` = VALUES(`note`),
                `updated_by` = VALUES(`updated_by`), `updated_at` = VALUES(`updated_at`)$repReset";

    if ($db->readQuery($sql) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update: ' . mysqli_error($db->DB_CON)]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => count($rentIds) . ' bill(s) ' . ($required ? 'moved to follow-up' : 'marked as no follow-up')
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
