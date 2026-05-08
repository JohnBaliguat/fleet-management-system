<?php
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

$source = $_GET['source'] ?? 'all';   // gate | dispatcher | all
$limit  = (int)($_GET['limit'] ?? 25);
if ($limit > 100) $limit = 100;

$where = '';
if ($source === 'gate') {
    $where = " WHERE i.incident_type IN ('unauthorised', 'damaged_goods', 'access_violation', 'cargo', 'exception') ";
}

$sql = "SELECT i.inc_id, i.d_id, i.trip_id, i.driver_id, i.incident_type, i.severity, i.description,
               i.lat, i.lng, i.photo_path, i.assistance, i.reassigned_d_id, i.status,
               i.reported_at, i.resolved_at,
               d.d_truck AS truck_plate, d.booking_no
        FROM incident i
        LEFT JOIN dispatch d ON d.d_id = i.d_id
        $where
        ORDER BY i.inc_id DESC LIMIT $limit";
$res = $conn->query($sql);
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
echo json_encode(['status' => 'success', 'rows' => $rows]);
$conn->close();
