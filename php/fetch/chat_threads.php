<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['Dispatcher', 'Admin'], true)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authorised']); exit;
}

// One row per driver who has ever sent or received a message, with:
//   - latest message body / time
//   - unread count of incoming messages from that driver
$sql = "SELECT
            d.driver_id,
            CONCAT(d.driver_lname, ', ', d.driver_fname) AS driver_name,
            d.shift_truck,
            (
                SELECT m.body FROM message m
                WHERE (m.from_role='driver' AND m.from_id = d.driver_id)
                   OR (m.to_role='driver'   AND m.to_id   = d.driver_id)
                ORDER BY m.msg_id DESC LIMIT 1
            ) AS last_body,
            (
                SELECT m.sent_at FROM message m
                WHERE (m.from_role='driver' AND m.from_id = d.driver_id)
                   OR (m.to_role='driver'   AND m.to_id   = d.driver_id)
                ORDER BY m.msg_id DESC LIMIT 1
            ) AS last_at,
            (
                SELECT COUNT(*) FROM message m
                WHERE m.from_role='driver' AND m.from_id = d.driver_id AND m.read_at IS NULL
            ) AS unread
        FROM drivers d
        WHERE EXISTS (
            SELECT 1 FROM message m
            WHERE (m.from_role='driver' AND m.from_id = d.driver_id)
               OR (m.to_role='driver'   AND m.to_id   = d.driver_id)
        )
        ORDER BY last_at DESC
        LIMIT 200";
$res = $conn->query($sql);
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }

echo json_encode(['status' => 'success', 'rows' => $rows]);
$conn->close();
