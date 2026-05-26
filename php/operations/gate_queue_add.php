<?php
header('Content-Type: application/json');
session_start();
include __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required']);
    exit;
}

$truckPlate = strtoupper(trim($_POST['truck_plate'] ?? ''));
$direction  = strtoupper(trim($_POST['direction'] ?? 'IN'));
$notes      = trim($_POST['notes'] ?? '');

if ($truckPlate === '') {
    echo json_encode(['status' => 'error', 'message' => 'truck_plate required']);
    exit;
}

// Try to attach the most recent dispatch for that truck.
$dId = null;
$driverId = null;
$stmt = $conn->prepare("SELECT d_id, driver_id FROM dispatch WHERE d_truck = ? ORDER BY d_id DESC LIMIT 1");
$stmt->bind_param("s", $truckPlate);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row) { $dId = (int)$row['d_id']; $driverId = (int)$row['driver_id']; }

$stmt = $conn->prepare(
    "INSERT INTO gate_queue (d_id, truck_plate, driver_id, direction, decision, notes)
     VALUES (?, ?, ?, ?, 'pending', ?)"
);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param("isiss", $dId, $truckPlate, $driverId, $direction, $notes);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['status' => 'error', 'message' => 'Insert failed']);
    exit;
}
echo json_encode(['status' => 'success', 'message' => 'Added to dispatcher queue.']);
$conn->close();
