<?php

include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

// Create a new Trip
if (isset($_POST['create'])) {

    $TRIP = new TripManagement(NULL);

    // Generate trip number
    $lastId = $TRIP->getLastID();
    $tripNumber = TripManagement::formatTripNumber($lastId + 1);

    $TRIP->trip_number = $tripNumber;
    $TRIP->trip_category = isset($_POST['trip_category']) ? $_POST['trip_category'] : 'internal';
    $TRIP->invoice_type = (!empty($_POST['invoice_type']) && $_POST['invoice_type'] !== '') ? $_POST['invoice_type'] : null;
    $TRIP->bill_id = !empty($_POST['bill_id']) ? $_POST['bill_id'] : null;
    $TRIP->customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
    $TRIP->vehicle_id = !empty($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;
    $TRIP->employee_id = !empty($_POST['employee_id']) ? $_POST['employee_id'] : null;
    $TRIP->start_location = !empty($_POST['start_location']) ? $_POST['start_location'] : null;
    $TRIP->end_location = !empty($_POST['end_location']) ? $_POST['end_location'] : null;
    $TRIP->start_meter = isset($_POST['start_meter']) ? $_POST['start_meter'] : 0;
    $TRIP->end_meter = !empty($_POST['end_meter']) ? $_POST['end_meter'] : null;
    $TRIP->trip_type = !empty($_POST['trip_type']) ? $_POST['trip_type'] : null;
    $TRIP->transport_date = !empty($_POST['transport_date']) ? $_POST['transport_date'] : null;
    $TRIP->due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $TRIP->toll = isset($_POST['toll']) ? $_POST['toll'] : 0;
    $TRIP->helper_payment = isset($_POST['helper_payment']) ? $_POST['helper_payment'] : 0;
    $TRIP->transport_amount = isset($_POST['transport_amount']) ? $_POST['transport_amount'] : 0;
    $TRIP->pay_amount = isset($_POST['pay_amount']) ? $_POST['pay_amount'] : 0;
    $TRIP->payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
    $TRIP->remark = !empty($_POST['remark']) ? $_POST['remark'] : null;
    $TRIP->status = 'started';
    $TRIP->created_by = isset($_SESSION['id']) ? $_SESSION['id'] : null;

    $res = $TRIP->create();

    if ($res) {
        echo json_encode([
            'status' => 'success',
            'trip_id' => $res,
            'trip_number' => $tripNumber
        ]);
    } else {
        $db = Database::getInstance();
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to save trip. ' . mysqli_error($db->DB_CON)
        ]);
    }
    exit();
}

// Update Trip
if (isset($_POST['update'])) {

    $TRIP = new TripManagement($_POST['id']);

    if (!$TRIP->id) {
        echo json_encode(['status' => 'error', 'message' => 'Trip not found']);
        exit();
    }

    $TRIP->trip_category = isset($_POST['trip_category']) ? $_POST['trip_category'] : $TRIP->trip_category;
    $TRIP->invoice_type = (!empty($_POST['invoice_type']) && $_POST['invoice_type'] !== '') ? $_POST['invoice_type'] : $TRIP->invoice_type;
    $TRIP->bill_id = !empty($_POST['bill_id']) ? $_POST['bill_id'] : $TRIP->bill_id;
    $TRIP->customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : $TRIP->customer_id;
    $TRIP->vehicle_id = !empty($_POST['vehicle_id']) ? $_POST['vehicle_id'] : $TRIP->vehicle_id;
    $TRIP->employee_id = !empty($_POST['employee_id']) ? $_POST['employee_id'] : $TRIP->employee_id;
    $TRIP->start_location = isset($_POST['start_location']) ? $_POST['start_location'] : $TRIP->start_location;
    $TRIP->end_location = isset($_POST['end_location']) ? $_POST['end_location'] : $TRIP->end_location;
    $TRIP->start_meter = isset($_POST['start_meter']) ? $_POST['start_meter'] : $TRIP->start_meter;
    $TRIP->end_meter = (isset($_POST['end_meter']) && $_POST['end_meter'] !== '') ? $_POST['end_meter'] : $TRIP->end_meter;
    $TRIP->trip_type = !empty($_POST['trip_type']) ? $_POST['trip_type'] : $TRIP->trip_type;
    $TRIP->transport_date = !empty($_POST['transport_date']) ? $_POST['transport_date'] : $TRIP->transport_date;
    $TRIP->due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : $TRIP->due_date;
    $TRIP->toll = isset($_POST['toll']) ? $_POST['toll'] : $TRIP->toll;
    $TRIP->helper_payment = isset($_POST['helper_payment']) ? $_POST['helper_payment'] : $TRIP->helper_payment;
    $TRIP->transport_amount = isset($_POST['transport_amount']) ? $_POST['transport_amount'] : $TRIP->transport_amount;
    $TRIP->pay_amount = isset($_POST['pay_amount']) ? $_POST['pay_amount'] : $TRIP->pay_amount;
    $TRIP->payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : $TRIP->payment_method;
    $TRIP->remark = isset($_POST['remark']) ? $_POST['remark'] : $TRIP->remark;
    
    // If end_meter is provided, mark as completed
    if (!empty($_POST['end_meter']) && $_POST['end_meter'] !== '') {
        $TRIP->status = 'completed';
    }

    $result = $TRIP->update();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        $db = Database::getInstance();
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update trip. ' . mysqli_error($db->DB_CON)
        ]);
    }
    exit();
}

// Delete Trip
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $TRIP = new TripManagement($_POST['id']);
    $result = $TRIP->delete();

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// Get vehicle start meter
if (isset($_POST['get_vehicle_meter'])) {
    $vehicle_id = (int) $_POST['vehicle_id'];
    $VEHICLE = new Vehicle($vehicle_id);
    
    echo json_encode([
        'status' => 'success',
        'start_meter' => $VEHICLE->start_meter ?? 0,
        'vehicle_no' => $VEHICLE->vehicle_no ?? ''
    ]);
    exit();
}

// Get bills for dropdown (equipment_rent)
if (isset($_POST['get_bills'])) {
    $db = Database::getInstance();
    $query = "SELECT er.id, er.bill_number, er.customer_id, cm.name AS customer_name, cm.code AS customer_code
              FROM `equipment_rent` er
              LEFT JOIN `customer_master` cm ON er.customer_id = cm.id
              ORDER BY er.id DESC";
    $result = $db->readQuery($query);
    $bills = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $bills[] = $row;
        }
    }
    echo json_encode(['status' => 'success', 'data' => $bills]);
    exit();
}

// Get transport details for a specific bill
if (isset($_POST['get_bill_transport'])) {
    $bill_id = (int) $_POST['bill_id'];
    
    // Get bill info
    $RENT = new EquipmentRent($bill_id);
    
    // Get transport details
    $transports = TransportDetail::getByRentId($bill_id);
    
    // Get customer info
    $customer = null;
    if ($RENT->customer_id) {
        $CUSTOMER = new CustomerMaster($RENT->customer_id);
        $customer = [
            'id' => $CUSTOMER->id,
            'name' => $CUSTOMER->name,
            'code' => $CUSTOMER->code
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'bill' => [
            'id' => $RENT->id,
            'bill_number' => $RENT->bill_number,
            'customer_id' => $RENT->customer_id,
            'transport_cost' => $RENT->transport_cost ?? 0
        ],
        'customer' => $customer,
        'transports' => $transports
    ]);
    exit();
}

// Fetch all trips for DataTable
if (isset($_POST['fetch_trips'])) {
    $TRIP = new TripManagement(null);
    $trips = $TRIP->all();
    
    $data = [];
    foreach ($trips as $key => $trip) {
        $statusLabel = $trip['status'] === 'completed' 
            ? '<span class="badge bg-soft-success font-size-12">Completed</span>'
            : '<span class="badge bg-soft-warning font-size-12">Started</span>';
        
        $categoryLabel = $trip['trip_category'] === 'internal'
            ? '<span class="badge bg-soft-info font-size-12">Internal</span>'
            : '<span class="badge bg-soft-primary font-size-12">Customer</span>';

        $settlementLabel = '';
        if ($trip['payment_method'] === 'credit') {
            $settlementLabel = $trip['is_settled'] 
                ? '<span class="badge bg-soft-success font-size-12">Settled</span>'
                : '<span class="badge bg-soft-danger font-size-12">Credit</span>';
        } else {
            $settlementLabel = '<span class="badge bg-soft-info font-size-12">Cash</span>';
        }

        $data[] = [
            'key' => $key + 1,
            'id' => $trip['id'],
            'trip_number' => $trip['trip_number'],
            'trip_category' => $trip['trip_category'],
            'category_label' => $categoryLabel,
            'invoice_type' => $trip['invoice_type'],
            'bill_id' => $trip['bill_id'],
            'bill_number' => $trip['bill_number'] ?? '-',
            'customer_id' => $trip['customer_id'],
            'customer_name' => $trip['customer_name'] ? ($trip['customer_code'] . ' - ' . $trip['customer_name']) : '-',
            'vehicle_id' => $trip['vehicle_id'],
            'vehicle_no' => $trip['vehicle_no'] ?? '-',
            'employee_id' => $trip['employee_id'],
            'employee_name' => $trip['employee_name'] ?? '-',
            'start_location' => $trip['start_location'] ?? '',
            'end_location' => $trip['end_location'] ?? '',
            'start_meter' => $trip['start_meter'],
            'end_meter' => $trip['end_meter'] ?? '-',
            'trip_type' => $trip['trip_type'] ?? '-',
            'transport_date' => $trip['transport_date'] ?? '',
            'due_date' => $trip['due_date'] ?? '',
            'toll' => $trip['toll'] ?? 0,
            'helper_payment' => $trip['helper_payment'] ?? 0,
            'transport_amount' => $trip['transport_amount'] ?? 0,
            'total_cost' => $trip['total_cost'] ?? 0,
            'pay_amount' => $trip['pay_amount'] ?? 0,
            'payment_method' => $trip['payment_method'] ?? 'cash',
            'is_settled' => $trip['is_settled'] ?? 0,
            'settlement_amount' => $trip['settlement_amount'] ?? 0,
            'settlement_label' => $settlementLabel,
            'remark' => $trip['remark'] ?? '',
            'status' => $trip['status'],
            'status_label' => $statusLabel,
            'created_at' => $trip['created_at']
        ];
    }
    
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit();
}

// ========================
// Trip Settlements
// ========================

// Fetch settlement status for a trip
if (isset($_POST['get_settlement_status'])) {
    $trip_id = (int) $_POST['trip_id'];
    $status = TripSettlement::getSettlementStatus($trip_id);
    $settlements = TripSettlement::getByTripId($trip_id);
    
    echo json_encode([
        'status' => 'success',
        'total_amount' => floatval($status['total_cost'] ?? 0),
        'total_paid' => floatval($status['total_settled'] ?? 0),
        'remaining' => floatval($status['remaining_amount'] ?? 0),
        'is_settled' => (int)($status['is_settled'] ?? 0),
        'settlements' => $settlements
    ]);
    exit();
}

// Add settlement payment
if (isset($_POST['add_settlement'])) {
    $settlement = new TripSettlement(null);
    $settlement->trip_id = (int) $_POST['trip_id'];
    $settlement->settlement_date = $_POST['settlement_date'];
    $settlement->amount = floatval($_POST['amount']);
    $settlement->remark = !empty($_POST['remark']) ? $_POST['remark'] : null;
    
    $result = $settlement->create();
    
    if ($result) {
        echo json_encode(['status' => 'success', 'id' => $result]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add settlement']);
    }
    exit();
}

// Delete settlement payment
if (isset($_POST['delete_settlement'])) {
    $settlement = new TripSettlement((int) $_POST['settlement_id']);
    $result = $settlement->delete();
    
    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// Get trips by bill ID (for equipment-rent-master modal)
if (isset($_POST['get_trips_by_bill'])) {
    $bill_id = (int) $_POST['bill_id'];
    $trips = TripManagement::getByBillId($bill_id);
    $totalCost = TripManagement::getTotalCostByBillId($bill_id);
    
    echo json_encode([
        'status' => 'success',
        'trips' => $trips,
        'total_cost' => $totalCost
    ]);
    exit();
}

?>
