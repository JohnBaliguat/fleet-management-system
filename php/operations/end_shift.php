<?php
require __DIR__ . '/_driver_auth.php';
$driverId = require_driver_session();
require_post();
include __DIR__ . '/../config/config.php';

// Refuse if the driver has an in-flight job (anything past
// driver_accepted that isn't fully finished). Lets dispatchers see a
// reason and avoids a driver clocking out mid-trip.
$stmt = $conn->prepare(
    "SELECT d_id, booking_no, workflow_stage FROM dispatch
     WHERE driver_id = ?
       AND workflow_stage IN ('driver_accepted', 'gate_cleared', 'en_route', 'pending_verification')
     ORDER BY d_id DESC LIMIT 1"
);
$stmt->bind_param("i", $driverId);
$stmt->execute();
$blocking = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($blocking) {
    json_out([
        'status'  => 'error',
        'message' => 'You still have an in-flight job (' . $blocking['booking_no'] . ' at stage ' . $blocking['workflow_stage'] . '). Finish or hand it off before ending your shift.',
    ], 409);
}

// Close the open driver_shift row (and capture machine hours).
$stmt = $conn->prepare(
    "UPDATE driver_shift
     SET ended_at = NOW(),
         machine_hours = ROUND(TIMESTAMPDIFF(MINUTE, started_at, NOW()) / 60.0, 2)
     WHERE driver_id = ? AND ended_at IS NULL"
);
$stmt->bind_param("i", $driverId);
$stmt->execute();
$rowsClosed = $stmt->affected_rows;
$stmt->close();

if ($rowsClosed === 0) {
    json_out(['status' => 'error', 'message' => 'No active shift to end.'], 409);
}

// Read back the just-closed shift so we can show the driver their hours.
$stmt = $conn->prepare(
    "SELECT ds_id, truck_code, started_at, ended_at, machine_hours
     FROM driver_shift WHERE driver_id = ? ORDER BY ds_id DESC LIMIT 1"
);
$stmt->bind_param("i", $driverId);
$stmt->execute();
$shift = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Mirror onto drivers row for fast filter checks.
$stmt = $conn->prepare("UPDATE drivers SET shift_ended_at = NOW(), shift_truck = '' WHERE driver_id = ?");
$stmt->bind_param("i", $driverId);
$stmt->execute();
$stmt->close();

json_out([
    'status'        => 'success',
    'message'       => 'Shift ended. Machine hours recorded.',
    'shift'         => $shift,
    'machine_hours' => $shift['machine_hours'] ?? 0,
]);
