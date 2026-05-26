<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['Dispatcher', 'Admin', 'HR-Admin'], true)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authorised']); exit;
}

$bn = trim($_GET['booking_no'] ?? '');
$dId = (int)($_GET['d_id'] ?? 0);
if ($bn === '' && $dId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'booking_no or d_id required']); exit;
}

if ($dId > 0) {
    $stmt = $conn->prepare("SELECT booking_no FROM dispatch WHERE d_id = ? LIMIT 1");
    $stmt->bind_param("i", $dId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r) $bn = $r['booking_no'];
}

// Booking summary.
$booking = null;
if ($bn !== '') {
    $stmt = $conn->prepare(
        "SELECT booking_no, booking_type, costumer, container, container_status,
                trip_from, trip_to, status, vessel_name, customs_cleared, client_notified_at
         FROM booking WHERE booking_no = ? LIMIT 1"
    );
    $stmt->bind_param("s", $bn);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Dispatches under this booking.
$dispatches = [];
if ($bn !== '') {
    $stmt = $conn->prepare(
        "SELECT d_id, d_truck, d_driverName, workflow_stage, workflow_updated_at,
                driver_accepted_at, gate_cleared_at, billing_closed_at, client_notified_at,
                billing_amount, billing_currency
         FROM dispatch WHERE booking_no = ? ORDER BY d_id ASC"
    );
    $stmt->bind_param("s", $bn);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $dispatches[] = $r; }
    $stmt->close();
}

// Append-only event log.
$events = [];
if ($bn !== '') {
    $stmt = $conn->prepare(
        "SELECT we_id, d_id, stage, actor_role, actor_id, notes, event_at
         FROM workflow_event WHERE booking_no = ? ORDER BY event_at ASC, we_id ASC"
    );
    $stmt->bind_param("s", $bn);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $events[] = $r; }
    $stmt->close();
}

echo json_encode([
    'status'     => 'success',
    'booking'    => $booking,
    'dispatches' => $dispatches,
    'events'     => $events,
]);
$conn->close();
