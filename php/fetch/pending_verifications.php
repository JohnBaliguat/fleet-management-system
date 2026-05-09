<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['Dispatcher', 'Admin'], true)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authorised']); exit;
}

$sql = "SELECT d.d_id, d.booking_no, d.costumer, d.d_truck, d.d_driverName, d.driver_id,
               d.workflow_stage, d.driver_accepted_at, d.trip_started_at,
               p.pod_id, p.photo1_path, p.photo2_path, p.photo3_path, p.signature_path,
               p.signed_by, p.lat, p.lng, p.captured_at, p.verified_by, p.verified_at,
               TIMESTAMPDIFF(MINUTE, COALESCE(d.trip_started_at, d.driver_accepted_at), p.captured_at) AS trip_minutes
        FROM dispatch d
        LEFT JOIN pod_capture p ON p.d_id = d.d_id
        WHERE d.workflow_stage = 'pending_verification'
        ORDER BY d.d_id DESC LIMIT 100";
$res = $conn->query($sql);
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
echo json_encode(['status' => 'success', 'rows' => $rows]);
$conn->close();
