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
$since = (int)($_GET['since'] ?? 0);
$limit = (int)($_GET['limit'] ?? 100);
if ($limit > 200) $limit = 200;

// Driver sees: messages they sent + messages targeted at them or at dispatcher group.
// Dispatcher sees: messages from any driver to dispatcher + their own replies.
if ($role === 'Driver') {
    $sql = "SELECT msg_id, from_role, from_id, to_role, to_id, body, d_id, sent_at, read_at
            FROM message
            WHERE msg_id > ?
              AND ((from_role = 'driver' AND from_id = ?)
                   OR (to_role = 'driver' AND to_id = ?)
                   OR (to_role = 'dispatcher' AND from_role = 'driver' AND from_id = ?)
                   OR (from_role = 'dispatcher' AND to_role = 'driver' AND (to_id IS NULL OR to_id = ?)))
            ORDER BY msg_id ASC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiii", $since, $id, $id, $id, $id, $limit);
} else {
    // Dispatcher / Admin
    $driverIdFilter = isset($_GET['driver_id']) && $_GET['driver_id'] !== '' ? (int)$_GET['driver_id'] : 0;
    if ($driverIdFilter > 0) {
        $sql = "SELECT msg_id, from_role, from_id, to_role, to_id, body, d_id, sent_at, read_at
                FROM message
                WHERE msg_id > ?
                  AND ((from_role = 'driver' AND from_id = ?)
                       OR (to_role = 'driver' AND to_id = ?))
                ORDER BY msg_id ASC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $since, $driverIdFilter, $driverIdFilter, $limit);
    } else {
        $sql = "SELECT msg_id, from_role, from_id, to_role, to_id, body, d_id, sent_at, read_at
                FROM message
                WHERE msg_id > ?
                ORDER BY msg_id ASC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $since, $limit);
    }
}
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$stmt->close();

// Mark unread messages as read for the current viewer (best-effort).
if ($role === 'Driver') {
    $conn->query("UPDATE message SET read_at = NOW() WHERE read_at IS NULL AND to_role = 'driver' AND (to_id = $id OR to_id IS NULL)");
}

echo json_encode(['status' => 'success', 'rows' => $rows]);
