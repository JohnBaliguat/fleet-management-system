<?php
header('Content-Type: application/json');
session_start();
include __DIR__ . '/../config/config.php';

// Dispatcher / Admin only.
$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['Dispatcher', 'Admin'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Not authorised']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required']);
    exit;
}

$gqId     = (int)($_POST['gq_id'] ?? 0);
$decision = $_POST['decision'] ?? '';
$decider  = (int)($_SESSION['user_id'] ?? 0);

if ($gqId <= 0 || !in_array($decision, ['approved', 'denied'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'gq_id and decision (approved|denied) required']);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE gate_queue SET decision = ?, decided_by = ?, decided_at = NOW() WHERE gq_id = ? AND decision = 'pending'"
);
$stmt->bind_param("sii", $decision, $decider, $gqId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Queue row not found or already decided']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => "Queue entry $decision."]);
$conn->close();
