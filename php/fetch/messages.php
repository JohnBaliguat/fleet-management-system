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

// --- Resolve a human-friendly sender_label per row ------------------
// One bulk query per source table so we don't fan out N queries.
$driverIds = [];
$userIds   = [];
foreach ($rows as $r) {
    $fid = (int)$r['from_id'];
    if ($fid <= 0) continue;
    if ($r['from_role'] === 'driver') {
        $driverIds[$fid] = true;
    } elseif (in_array($r['from_role'], ['dispatcher', 'admin'], true)) {
        $userIds[$fid] = true;
    }
}
$driverNames = [];
$userNames   = [];
if (!empty($driverIds)) {
    $idList = implode(',', array_map('intval', array_keys($driverIds)));
    $q = $conn->query("SELECT driver_id, CONCAT(driver_lname, ', ', driver_fname) AS name FROM drivers WHERE driver_id IN ($idList)");
    while ($r = $q->fetch_assoc()) { $driverNames[(int)$r['driver_id']] = $r['name']; }
}
if (!empty($userIds)) {
    $idList = implode(',', array_map('intval', array_keys($userIds)));
    $q = $conn->query("SELECT user_id, TRIM(CONCAT(user_fname, ' ', user_lname)) AS name, user_type FROM user WHERE user_id IN ($idList)");
    while ($r = $q->fetch_assoc()) { $userNames[(int)$r['user_id']] = $r['name'] !== '' ? $r['name'] : ($r['user_type'] . ' #' . $r['user_id']); }
}
foreach ($rows as &$r) {
    $fid = (int)$r['from_id'];
    if ($r['from_role'] === 'driver') {
        $r['sender_label'] = $driverNames[$fid] ?? ('Driver #' . $fid);
    } elseif ($r['from_role'] === 'dispatcher' || $r['from_role'] === 'admin') {
        // Use a clear "Dispatcher: Name" so the driver knows which one.
        $name = $userNames[$fid] ?? ('Dispatcher #' . $fid);
        $r['sender_label'] = 'Dispatcher: ' . $name;
    } elseif ($r['from_role'] === 'system') {
        $r['sender_label'] = 'System';
    } else {
        $r['sender_label'] = ucfirst($r['from_role']);
    }
}
unset($r);

// Mark unread messages as read for the current viewer (best-effort).
if ($role === 'Driver') {
    $conn->query("UPDATE message SET read_at = NOW() WHERE read_at IS NULL AND to_role = 'driver' AND (to_id = $id OR to_id IS NULL)");
} elseif (isset($driverIdFilter) && $driverIdFilter > 0) {
    // Dispatcher / Admin viewing a specific driver's thread —
    // mark that driver's incoming messages as read.
    $conn->query("UPDATE message SET read_at = NOW() WHERE read_at IS NULL AND from_role = 'driver' AND from_id = $driverIdFilter");
}

echo json_encode(['status' => 'success', 'rows' => $rows]);
