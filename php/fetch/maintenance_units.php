<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['Maintenance', 'Admin', 'Dispatcher'], true)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authorised']); exit;
}

$kind   = strtolower(trim($_GET['kind']   ?? 'all'));   // truck|genset|trailer|all
$status = strtolower(trim($_GET['status'] ?? 'all'));   // blocked|available|all
$q      = trim($_GET['q'] ?? '');

$where = [];
if ($status === 'blocked')   $where[] = 'maintenance_blocked = 1';
if ($status === 'available') $where[] = 'maintenance_blocked = 0';
if ($q !== '') {
    $like = '%' . $conn->real_escape_string($q) . '%';
    $where[] = "code LIKE '$like'";
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// Union trucks + gensets (from units) with trailers.
$kindFilter = '';
if (in_array($kind, ['truck', 'genset'], true)) $kindFilter = " AND unit_type = '" . $kind . "'";
$blockOnly  = $status === 'blocked'   ? ' AND maintenance_blocked = 1' : '';
$availOnly  = $status === 'available' ? ' AND maintenance_blocked = 0' : '';

$rows = [];

if ($kind === 'all' || $kind === 'truck' || $kind === 'genset') {
    $sql = "SELECT unit_name AS unit_code, unit_type AS unit_kind, unit_status,
                   maintenance_blocked, maintenance_reason, maintenance_expected_return, maintenance_blocked_at
            FROM units
            WHERE 1=1 $kindFilter $blockOnly $availOnly
              AND unit_type IN ('truck', 'genset')";
    if ($q !== '') { $like = $conn->real_escape_string($q); $sql .= " AND unit_name LIKE '%$like%'"; }
    $sql .= " ORDER BY unit_name ASC LIMIT 1000";
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}
if ($kind === 'all' || $kind === 'trailer') {
    $sql = "SELECT trailer_name AS unit_code, 'trailer' AS unit_kind, trailer_status AS unit_status,
                   maintenance_blocked, maintenance_reason, maintenance_expected_return, maintenance_blocked_at
            FROM trailer
            WHERE 1=1 $blockOnly $availOnly";
    if ($q !== '') { $like = $conn->real_escape_string($q); $sql .= " AND trailer_name LIKE '%$like%'"; }
    $sql .= " ORDER BY trailer_name ASC LIMIT 1000";
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

usort($rows, function ($a, $b) {
    return strcmp($a['unit_code'], $b['unit_code']);
});

echo json_encode(['status' => 'success', 'rows' => $rows]);
$conn->close();
