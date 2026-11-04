<?php
// JSON API for the rep mobile app (Flutter). Usage: api/rep/?action=<name>
// Auth: "X-Auth-Token: <token>" header (Authorization: Bearer also accepted; some hosts strip it).
include __DIR__ . '/../../class/include.php';
header('Content-Type: application/json; charset=utf-8');

function respond($data = null, $message = '', $code = 200)
{
    http_response_code($code);
    echo json_encode(['status' => $code < 400 ? 'success' : 'error', 'message' => $message, 'data' => $data]);
    exit;
}

function fail($message, $code = 400)
{
    respond(null, $message, $code);
}

function requestToken()
{
    if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        return trim($_SERVER['HTTP_X_AUTH_TOKEN']);
    }
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    return preg_match('/^Bearer\s+(\S+)$/i', $auth, $m) ? $m[1] : '';
}

// Logged-in rep (still active with app access) or 401
function requireRep()
{
    $token = requestToken();
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        fail('Please log in', 401);
    }
    $db = Database::getInstance();
    $hash = hash('sha256', $token);
    $rep = mysqli_fetch_assoc($db->readQuery("SELECT me.*, t.id AS token_id FROM `rep_app_tokens` t
                                              INNER JOIN `marketing_executive` me ON me.id = t.rep_id
                                              WHERE t.token_hash = '$hash' AND me.is_active = 1 AND me.app_enabled = 1"));
    if (!$rep) {
        fail('Session expired. Please log in again', 401);
    }
    $db->readQuery("UPDATE `rep_app_tokens` SET `last_used_at` = NOW() WHERE `id` = " . (int)$rep['token_id']);
    return $rep;
}

function repPayload($rep)
{
    return [
        'id' => (int)$rep['id'],
        'code' => $rep['code'],
        'name' => $rep['full_name'],
        'mobile' => $rep['mobile_number'] ?? ''
    ];
}

function optionList($map)
{
    $list = [];
    foreach ($map as $value => $label) {
        $list[] = ['value' => $value, 'label' => $label];
    }
    return $list;
}

function meta()
{
    return [
        'visit_outcomes' => optionList(RepFollowup::VISIT_OUTCOMES),
        'payment_methods' => optionList(RepFollowup::PAYMENT_METHODS)
    ];
}

function collectionPayload($c)
{
    return [
        'id' => (int)$c['id'],
        'rent_id' => (int)$c['rent_id'],
        'bill_number' => $c['bill_number'] ?? '',
        'customer_name' => $c['customer_name'] ?? '',
        'amount' => (float)$c['amount'],
        'payment_method' => $c['payment_method'],
        'method_label' => $c['method_label'],
        'reference' => $c['reference'] ?? '',
        'remark' => $c['remark'] ?? '',
        'status' => $c['status'],
        'status_label' => $c['status_label'],
        'proof_image' => $c['proof_image'] ?? null,
        'admin_remark' => $c['admin_remark'] ?? '',
        'collected_at' => $c['collected_at']
    ];
}

function visitPayload($v)
{
    return [
        'id' => (int)$v['id'],
        'rent_id' => (int)$v['rent_id'],
        'bill_number' => $v['bill_number'] ?? '',
        'customer_name' => $v['customer_name'] ?? '',
        'rep_name' => $v['rep_name'] ?? '',
        'visit_at' => $v['visit_at'],
        'outcome' => $v['outcome'],
        'outcome_label' => $v['outcome_label'],
        'promised_date' => $v['promised_date'],
        'remark' => $v['remark'] ?? ''
    ];
}

function dayStats($repId, $date)
{
    $day = RepFollowup::getActivity($date, $date, $repId);
    $visitedBills = [];
    foreach ($day['visits'] as $v) {
        $visitedBills[(int)$v['rent_id']] = true;
    }
    return [$day, $visitedBills];
}

$action = $_GET['action'] ?? '';
$db = Database::getInstance();
RepFollowup::ensureSchema();

try {
    if ($action === 'login') {
        $rep = MarketingExecutive::appLogin($_POST['username'] ?? '', $_POST['password'] ?? '');
        if (!$rep) {
            fail('Invalid username or password, or app access is disabled', 401);
        }
        $token = bin2hex(random_bytes(32));
        $device = $db->escapeString(mb_substr(trim($_POST['device'] ?? ''), 0, 100));
        $db->readQuery("INSERT INTO `rep_app_tokens` (`rep_id`, `token_hash`, `device`, `last_used_at`)
                        VALUES (" . (int)$rep['id'] . ", '" . hash('sha256', $token) . "', '$device', NOW())");
        respond(['token' => $token, 'rep' => repPayload($rep), 'meta' => meta()]);
    }

    $rep = requireRep();
    $repId = (int)$rep['id'];

    if ($action === 'logout') {
        $db->readQuery("DELETE FROM `rep_app_tokens` WHERE `id` = " . (int)$rep['token_id']);
        respond(null, 'Logged out');
    }

    if ($action === 'me') {
        respond(['rep' => repPayload($rep), 'meta' => meta()]);
    }

    // Assigned bills + today's numbers for the home screen
    if ($action === 'bills') {
        $today = date('Y-m-d');
        $bills = RepFollowup::getOverdueBills($today, RepFollowup::getAssignedRentIds($repId));
        list($day, $visitedBills) = dayStats($repId, $today);

        $list = array_map(function ($b) use ($visitedBills) {
            return [
                'rent_id' => $b['rent_id'],
                'bill_number' => $b['bill_number'],
                'customer_name' => $b['customer_name'],
                'customer_code' => $b['customer_code'],
                'mobile_number' => $b['mobile_number'],
                'location' => $b['location'],
                'due_date' => $b['due_date'],
                'overdue_days' => $b['overdue_days'],
                'late_text' => $b['late_text'],
                'item_count' => count($b['items']),
                'pending_qty' => (float)$b['pending_qty'],
                'outstanding' => round($b['projected_outstanding'] + $b['recorded_outstanding'], 2),
                'visited_today' => !empty($visitedBills[$b['rent_id']]),
                'last_visit' => $b['last_visit']
            ];
        }, $bills);

        respond([
            'bills' => $list,
            'today' => [
                'assigned' => count($list),
                'visited' => count($visitedBills),
                'collected' => $day['totals']['collected'],
                'no_proof_count' => $day['totals']['no_proof_count'],
                'no_proof_amount' => $day['totals']['no_proof_amount']
            ]
        ]);
    }

    if ($action === 'bill') {
        $rentId = (int)($_GET['id'] ?? 0);
        if (!RepFollowup::isAssigned($repId, $rentId)) {
            fail('This bill is not assigned to you', 403);
        }
        $RENT = new EquipmentRent($rentId);
        $bills = RepFollowup::getOverdueBills(date('Y-m-d'), [$rentId]);
        $bill = $bills[0] ?? null;
        $history = RepFollowup::getActivity('2000-01-01', date('Y-m-d'), null, $rentId);

        $myCollections = [];
        foreach ($history['collections'] as $c) {
            if ((int)$c['rep_id'] === $repId) {
                $myCollections[] = collectionPayload($c);
            }
        }

        respond([
            'rent_id' => $rentId,
            'bill_number' => $RENT->bill_number,
            // null when every item has been returned
            'bill' => $bill ? [
                'customer_name' => $bill['customer_name'],
                'customer_code' => $bill['customer_code'],
                'phones' => array_values(array_filter(explode(' / ', $bill['mobile_number']))),
                'location' => $bill['location'],
                'bill_date' => $bill['bill_date'],
                'due_date' => $bill['due_date'],
                'overdue_days' => $bill['overdue_days'],
                'late_text' => $bill['late_text'],
                'paid_total' => $bill['paid_total'],
                'projected_outstanding' => $bill['projected_outstanding'],
                'recorded_outstanding' => $bill['recorded_outstanding'],
                'items' => $bill['items']
            ] : null,
            'my_collections' => $myCollections,
            'visits' => array_map('visitPayload', $history['visits'])
        ]);
    }

    if ($action === 'visit') {
        $rentId = (int)($_POST['rent_id'] ?? 0);
        if (!RepFollowup::isAssigned($repId, $rentId)) {
            fail('This bill is not assigned to you', 403);
        }
        $outcome = $_POST['outcome'] ?? '';
        $remark = trim($_POST['remark'] ?? '');
        if (!isset(RepFollowup::VISIT_OUTCOMES[$outcome])) {
            fail('Please select what happened');
        }
        if ($remark === '') {
            fail('Please add a remark about the visit');
        }
        $promised = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['promised_date'] ?? '') ? "'" . $_POST['promised_date'] . "'" : 'NULL';
        $lat = is_numeric($_POST['latitude'] ?? '') ? (float)$_POST['latitude'] : 'NULL';
        $lng = is_numeric($_POST['longitude'] ?? '') ? (float)$_POST['longitude'] : 'NULL';
        $RENT = new EquipmentRent($rentId);

        $db->readQuery("INSERT INTO `rep_visits` (`rep_id`, `rent_id`, `customer_id`, `visit_at`, `outcome`, `promised_date`, `remark`, `latitude`, `longitude`)
                        VALUES ($repId, $rentId, " . (int)$RENT->customer_id . ", NOW(), '" . $db->escapeString($outcome) . "', $promised,
                                '" . $db->escapeString($remark) . "', $lat, $lng)");
        respond(['id' => mysqli_insert_id($db->DB_CON)], 'Visit saved');
    }

    if ($action === 'payment') {
        $rentId = (int)($_POST['rent_id'] ?? 0);
        if (!RepFollowup::isAssigned($repId, $rentId)) {
            fail('This bill is not assigned to you', 403);
        }
        $amount = round((float)($_POST['amount'] ?? 0), 2);
        $method = $_POST['payment_method'] ?? '';
        if ($amount <= 0) {
            fail('Please enter the amount');
        }
        if (!isset(RepFollowup::PAYMENT_METHODS[$method])) {
            fail('Please select the payment method');
        }
        $proof = RepFollowup::saveProofImage($_FILES['proof'] ?? null);
        $status = $proof ? 'proof_added' : 'no_proof';
        $RENT = new EquipmentRent($rentId);

        $db->readQuery("INSERT INTO `rep_collections` (`rep_id`, `rent_id`, `customer_id`, `amount`, `payment_method`, `reference`, `remark`, `proof_image`, `status`, `collected_at`, `proof_added_at`)
                        VALUES ($repId, $rentId, " . (int)$RENT->customer_id . ", $amount, '" . $db->escapeString($method) . "',
                                '" . $db->escapeString(mb_substr(trim($_POST['reference'] ?? ''), 0, 100)) . "',
                                '" . $db->escapeString(mb_substr(trim($_POST['remark'] ?? ''), 0, 255)) . "',
                                " . ($proof ? "'" . $db->escapeString($proof) . "'" : 'NULL') . ", '$status', NOW(), " . ($proof ? 'NOW()' : 'NULL') . ")");
        respond(['id' => mysqli_insert_id($db->DB_CON), 'status' => $status], $proof
            ? 'Payment of Rs. ' . number_format($amount, 2) . ' saved with proof'
            : 'Payment of Rs. ' . number_format($amount, 2) . ' saved WITHOUT proof. It is not confirmed until you add proof');
    }

    // Proof for one of the rep's own "no proof" payments (works even if the bill is no longer assigned)
    if ($action === 'proof') {
        $collectionId = (int)($_POST['collection_id'] ?? 0);
        $row = mysqli_fetch_assoc($db->readQuery("SELECT id FROM `rep_collections` WHERE `id` = $collectionId AND `rep_id` = $repId AND `status` = 'no_proof'"));
        if (!$row) {
            fail('Payment not found or proof already added');
        }
        $proof = RepFollowup::saveProofImage($_FILES['proof'] ?? null);
        if (!$proof) {
            fail('Please choose a proof photo');
        }
        $db->readQuery("UPDATE `rep_collections` SET `proof_image` = '" . $db->escapeString($proof) . "', `status` = 'proof_added', `proof_added_at` = NOW() WHERE `id` = $collectionId");
        respond(null, 'Proof added. Payment is now confirmed');
    }

    // Ordered stops planned by the office for a date
    if ($action === 'route') {
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
        respond(['date' => $date, 'stops' => RepFollowup::getRoute($repId, $date)]);
    }

    if ($action === 'summary') {
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
        list($day) = dayStats($repId, $date);
        respond([
            'date' => $date,
            'totals' => $day['totals'],
            'collections' => array_map('collectionPayload', $day['collections']),
            'visits' => array_map('visitPayload', $day['visits'])
        ]);
    }

    fail('Invalid action', 404);
} catch (Exception $e) {
    fail($e->getMessage());
}
