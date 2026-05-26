<?php
require __DIR__ . '/_driver_auth.php';
$driverId = require_driver_session();
require_post();
include __DIR__ . '/../config/config.php';

$dId = (int)($_POST['d_id'] ?? 0);
if ($dId <= 0) json_out(['status' => 'error', 'message' => 'd_id required'], 400);

// Sanity — the dispatch must be assigned to this driver and not already accepted/declined.
$stmt = $conn->prepare("SELECT booking_no, driver_id, workflow_stage FROM dispatch WHERE d_id = ? LIMIT 1");
$stmt->bind_param("i", $dId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row || (int)$row['driver_id'] !== $driverId) {
    json_out(['status' => 'error', 'message' => 'Dispatch not assigned to you'], 403);
}

$stmt = $conn->prepare(
    "UPDATE dispatch
     SET workflow_stage = 'driver_accepted', driver_accepted_at = NOW(), workflow_updated_at = NOW()
     WHERE d_id = ? AND workflow_stage IN ('dispatcher_assigned', 'reassigned')"
);
$stmt->bind_param("i", $dId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected === 0) {
    json_out(['status' => 'error', 'message' => 'Dispatch not in an acceptable state']);
}

$bn = $row['booking_no'];
$stmt = $conn->prepare("INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes) VALUES (?, ?, 'driver_accepted', 'driver', ?, 'Driver accepted job')");
$stmt->bind_param("isi", $dId, $bn, $driverId);
$stmt->execute();
$stmt->close();

json_out(['status' => 'success', 'message' => 'Accepted.']);
