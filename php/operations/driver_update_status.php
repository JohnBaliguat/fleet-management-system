<?php
require __DIR__ . '/_driver_auth.php';
$driverId = require_driver_session();
require_post();
include __DIR__ . '/../config/config.php';

$dId    = (int)($_POST['d_id'] ?? 0);
$status = strtolower(trim($_POST['status'] ?? ''));   // picked_up | on_the_way | arrived | delivered | issue
$lat    = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
$lng    = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;
$note   = trim($_POST['note'] ?? '');

$valid = ['picked_up', 'on_the_way', 'arrived', 'delivered', 'issue'];
if ($dId <= 0 || !in_array($status, $valid, true)) {
    json_out(['status' => 'error', 'message' => 'd_id and a valid status required'], 400);
}

$stmt = $conn->prepare("SELECT booking_no, driver_id, workflow_stage FROM dispatch WHERE d_id = ? LIMIT 1");
$stmt->bind_param("i", $dId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row || (int)$row['driver_id'] !== $driverId) {
    json_out(['status' => 'error', 'message' => 'Dispatch not assigned to you'], 403);
}

// Map driver-tap statuses to workflow stages.
$stageMap = [
    'picked_up'  => 'en_route',     // first tap kicks off movement
    'on_the_way' => 'en_route',
    'arrived'    => 'en_route',     // still en route until delivered tap
    'delivered'  => 'delivered',
    'issue'      => null,            // 'issue' is a flag, doesn't change stage
];
$newStage = $stageMap[$status];

if ($newStage !== null) {
    $stmt = $conn->prepare("UPDATE dispatch SET workflow_stage = ?, workflow_updated_at = NOW() WHERE d_id = ?");
    $stmt->bind_param("si", $newStage, $dId);
    $stmt->execute();
    $stmt->close();
}

// Last-known driver location.
if ($lat !== null && $lng !== null) {
    $stmt = $conn->prepare("UPDATE drivers SET last_lat = ?, last_lng = ?, last_seen_at = NOW() WHERE driver_id = ?");
    $stmt->bind_param("ddi", $lat, $lng, $driverId);
    $stmt->execute();
    $stmt->close();
}

// Audit trail.
$bn = $row['booking_no'];
$notes = "Status: $status" . ($note !== '' ? " — $note" : '');
$stmt = $conn->prepare("INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes) VALUES (?, ?, ?, 'driver', ?, ?)");
$stmt->bind_param("issis", $dId, $bn, $status, $driverId, $notes);
$stmt->execute();
$stmt->close();

json_out(['status' => 'success', 'message' => 'Status saved.']);
