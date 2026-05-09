<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

$role = $_SESSION['user_type'] ?? '';
$id   = (int)($_SESSION['user_id'] ?? 0);
if (!in_array($role, ['Driver', 'Dispatcher', 'Admin'], true) || $id <= 0) {
    echo json_encode(['status' => 'success', 'count' => 0]); exit;
}

$count = 0;
if ($role === 'Driver') {
    // Messages addressed to me OR to the driver group, not yet read.
    $stmt = $conn->prepare(
        "SELECT COUNT(*) c FROM message
         WHERE read_at IS NULL
           AND from_role <> 'driver'
           AND ((to_role = 'driver' AND (to_id = ? OR to_id IS NULL)))"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $count = (int)($r['c'] ?? 0);
} else {
    // Dispatcher / Admin: unread driver messages.
    $r = $conn->query("SELECT COUNT(*) c FROM message WHERE read_at IS NULL AND from_role = 'driver'");
    $row = $r->fetch_assoc();
    $count = (int)($row['c'] ?? 0);
}

echo json_encode(['status' => 'success', 'count' => $count]);
$conn->close();
