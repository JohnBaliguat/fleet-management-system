<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

// Authed staff only — same audience as the monitoring page.
$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['Admin', 'Dispatcher', 'HR-Admin', 'Visual', 'Maintenance'], true)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authorised']); exit;
}

$dId = (int)($_GET['d_id'] ?? 0);
if ($dId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'd_id required']); exit;
}

// Dispatch header — driver, truck, booking, workflow stage etc.
$stmt = $conn->prepare(
    "SELECT d.d_id, d.booking_no, d.d_datetime, d.d_driverName, d.driver_id,
            d.d_truck, d.d_trailer, d.d_genset, d.d_tripReceipt, d.d_ecs,
            d.costumer AS d_costumer, d.d_dispatcher, d.d_dispatchHub,
            d.workflow_stage, d.workflow_updated_at,
            d.driver_accepted_at, d.gate_cleared_at, d.trip_completed_at,
            d.billing_amount, d.billing_currency, d.billing_closed_at,
            d.client_notified_at,
            b.booking_type, b.costumer AS booking_costumer
     FROM dispatch d
     LEFT JOIN booking b ON b.booking_no = d.booking_no
     WHERE d.d_id = ? LIMIT 1"
);
$stmt->bind_param("i", $dId);
$stmt->execute();
$dispatch = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$dispatch) { echo json_encode(['status' => 'error', 'message' => 'Dispatch not found']); exit; }

// All trips for this dispatch.
$stmt = $conn->prepare(
    "SELECT trip_id, trip_type, trip_container, container_activity, trip_containerStat,
            trip_haulingSegment, trip_haulingType, trip_from, trip_to,
            trip_status, segment_costumer, segment_status, foul_trip,
            scheduled_at, deliver_dateTime, withdraw_dateTime, required_date
     FROM trips
     WHERE d_id = ?
     ORDER BY trip_id ASC"
);
$stmt->bind_param("i", $dId);
$stmt->execute();
$res = $stmt->get_result();
$trips = [];
while ($r = $res->fetch_assoc()) { $trips[] = $r; }
$stmt->close();

echo json_encode([
    'status'   => 'success',
    'dispatch' => $dispatch,
    'trips'    => $trips,
]);
$conn->close();
