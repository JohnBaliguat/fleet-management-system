<?php
require __DIR__ . '/_driver_auth.php';
$driverId = require_driver_session();
require_post();
include __DIR__ . '/../config/config.php';

$dId       = (int)($_POST['d_id'] ?? 0);
$tripId    = (int)($_POST['trip_id'] ?? 0);
$signedBy  = trim($_POST['signed_by'] ?? '');
$lat       = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
$lng       = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;
$signatureDataUrl = $_POST['signature'] ?? '';

if ($dId <= 0) json_out(['status' => 'error', 'message' => 'd_id required'], 400);

// Confirm dispatch belongs to this driver.
$stmt = $conn->prepare("SELECT booking_no, driver_id FROM dispatch WHERE d_id = ? LIMIT 1");
$stmt->bind_param("i", $dId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row || (int)$row['driver_id'] !== $driverId) {
    json_out(['status' => 'error', 'message' => 'Dispatch not assigned to you'], 403);
}

$dir = __DIR__ . '/../assets/uploads/pod';
if (!is_dir($dir)) @mkdir($dir, 0775, true);

function save_upload(string $field, string $dir): string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','heic'], true)) $ext = 'jpg';
    $name = 'pod_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '_' . $field . '.' . $ext;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $name)) return '';
    return 'php/assets/uploads/pod/' . $name;
}

$p1 = save_upload('photo1', $dir);
$p2 = save_upload('photo2', $dir);
$p3 = save_upload('photo3', $dir);

if ($p1 === '' || $p2 === '') {
    json_out(['status' => 'error', 'message' => 'At least 2 photos required.']);
}

// Decode signature dataURL.
$sigPath = '';
if (preg_match('/^data:image\/(png|jpeg);base64,(.+)$/', $signatureDataUrl, $m)) {
    $bin = base64_decode($m[2]);
    if ($bin !== false) {
        $name = 'pod_sig_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.png';
        file_put_contents($dir . '/' . $name, $bin);
        $sigPath = 'php/assets/uploads/pod/' . $name;
    }
}

$stmt = $conn->prepare(
    "INSERT INTO pod_capture (d_id, trip_id, driver_id, photo1_path, photo2_path, photo3_path, signature_path, signed_by, lat, lng)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("iiissssssd",
    $dId, $tripId, $driverId, $p1, $p2, $p3, $sigPath, $signedBy, $lat, $lng);
$stmt->execute();
$stmt->close();

// Workflow advance: -> pod_captured.
$stmt = $conn->prepare("UPDATE dispatch SET workflow_stage = 'pod_captured', workflow_updated_at = NOW() WHERE d_id = ? AND workflow_stage IN ('en_route','delivered')");
$stmt->bind_param("i", $dId);
$stmt->execute();
$stmt->close();

$bn = $row['booking_no'];
$stmt = $conn->prepare("INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes) VALUES (?, ?, 'pod_captured', 'driver', ?, 'POD captured by driver')");
$stmt->bind_param("isi", $dId, $bn, $driverId);
$stmt->execute();
$stmt->close();

json_out(['status' => 'success', 'message' => 'POD saved.']);
