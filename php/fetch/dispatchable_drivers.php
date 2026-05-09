<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['Dispatcher', 'Admin'], true)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authorised']); exit;
}

$q = trim($_GET['q'] ?? '');

// A driver is dispatchable when:
//   • they currently have an open driver_shift (ended_at IS NULL), AND
//   • their drivers.shift_truck is non-empty.
// Today's attendance is also considered if drivers_attendance has a
// row stamped today with status 'Present'. We treat the open shift as
// the strongest evidence of attendance, so it's an OR.
$today = date('Y-m-d');
$where = "WHERE (
    EXISTS (SELECT 1 FROM driver_shift s WHERE s.driver_id = d.driver_id AND s.ended_at IS NULL)
    OR EXISTS (SELECT 1 FROM drivers_attendance a WHERE a.driver_id = d.driver_id AND a.da_date = '" . $conn->real_escape_string($today) . "' AND a.da_status = 'Present')
  )
  AND TRIM(d.shift_truck) <> ''";
if ($q !== '') {
    $like = '%' . $conn->real_escape_string($q) . '%';
    $where .= " AND (d.driver_lname LIKE '$like' OR d.driver_fname LIKE '$like' OR d.shift_truck LIKE '$like')";
}

$sql = "SELECT d.driver_id,
               CONCAT(d.driver_lname, ', ', SUBSTRING(d.driver_fname, 1, 1), '.') AS driver_name,
               d.shift_truck,
               d.driver_assignSegment AS segment,
               d.shift_started_at,
               (SELECT s.started_at FROM driver_shift s WHERE s.driver_id = d.driver_id AND s.ended_at IS NULL ORDER BY s.ds_id DESC LIMIT 1) AS shift_open_since
        FROM drivers d
        $where
        ORDER BY d.driver_lname ASC LIMIT 200";
$res = $conn->query($sql);
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
echo json_encode(['status' => 'success', 'rows' => $rows, 'count' => count($rows)]);
$conn->close();
