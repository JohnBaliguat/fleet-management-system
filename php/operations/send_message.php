<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

$role = $_SESSION['user_type'] ?? '';
$id   = (int)($_SESSION['user_id'] ?? 0);
if (!in_array($role, ['Driver', 'Dispatcher', 'Admin'], true) || $id <= 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Login required']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required']); exit;
}

$body  = trim($_POST['body'] ?? '');
$toRole= $_POST['to_role'] ?? null;
$toId  = isset($_POST['to_id']) && $_POST['to_id'] !== '' ? (int)$_POST['to_id'] : null;
$dId   = isset($_POST['d_id']) && $_POST['d_id'] !== '' ? (int)$_POST['d_id'] : null;
if ($body === '') { echo json_encode(['status' => 'error', 'message' => 'Empty message']); exit; }

// Driver default: send to dispatcher group (to_role=group, to_id=NULL).
$fromRole = strtolower($role);
if ($fromRole === 'admin') $fromRole = 'dispatcher';   // admins post as dispatcher in chat context
if (!$toRole) {
    $toRole = ($fromRole === 'driver') ? 'dispatcher' : 'driver';
}

$stmt = $conn->prepare(
    "INSERT INTO message (from_role, from_id, to_role, to_id, body, d_id) VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("sisisi", $fromRole, $id, $toRole, $toId, $body, $dId);
$stmt->execute();
$msgId = $stmt->insert_id;
$stmt->close();

echo json_encode(['status' => 'success', 'msg_id' => $msgId]);
