<?php
require __DIR__ . '/_driver_auth.php';
$driverId = require_driver_session();
require_post();
include __DIR__ . '/../config/config.php';

$dId        = (int)($_POST['d_id'] ?? 0);
$tripId     = (int)($_POST['trip_id'] ?? 0);
$lat        = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
$lng        = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;
$accuracy   = (int)($_POST['accuracy_m'] ?? 0);
$capturedAt = trim($_POST['captured_at'] ?? '');
$offline    = !empty($_POST['offline']) ? 1 : 0;

if ($dId <= 0 || $lat === null || $lng === null) {
    json_out(['status' => 'error', 'message' => 'd_id and GPS required'], 400);
}

// Normalise captured_at (ISO -> MySQL DATETIME). Defaults to now.
$capturedAtMysql = null;
if ($capturedAt !== '') {
    try { $capturedAtMysql = (new DateTime($capturedAt))->format('Y-m-d H:i:s'); } catch (Exception $e) { /* fall through */ }
}
if (!$capturedAtMysql) { $capturedAtMysql = date('Y-m-d H:i:s'); }

// Confirm dispatch ownership.
$stmt = $conn->prepare("SELECT booking_no, driver_id FROM dispatch WHERE d_id = ? LIMIT 1");
$stmt->bind_param("i", $dId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row || (int)$row['driver_id'] !== $driverId) {
    json_out(['status' => 'error', 'message' => 'Dispatch not assigned to you'], 403);
}

// Photo uploads.
$dir = __DIR__ . '/../assets/uploads/gateless';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

function gl_save(string $field, string $dir): string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) { return ''; }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic'], true)) { $ext = 'jpg'; }
    $name = 'gl_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '_' . $field . '.' . $ext;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $name)) { return ''; }
    return 'php/assets/uploads/gateless/' . $name;
}
$p1 = gl_save('photo1', $dir);
$p2 = gl_save('photo2', $dir);
if ($p1 === '' || $p2 === '') {
    json_out(['status' => 'error', 'message' => 'Two photos required.']);
}

$stmt = $conn->prepare(
    "INSERT INTO gateless_completion
        (d_id, trip_id, driver_id, lat, lng, accuracy_m, photo1_path, photo2_path, captured_at, offline)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
// Types: d_id=i, trip_id=i, driver_id=i, lat=d, lng=d, accuracy_m=i, photo1=s, photo2=s, captured_at=s, offline=i
$stmt->bind_param("iiiddisssi", $dId, $tripId, $driverId, $lat, $lng, $accuracy, $p1, $p2, $capturedAtMysql, $offline);
$stmt->execute();
$stmt->close();

// Workflow advance — gateless completion is an end-state for the run.
$stmt = $conn->prepare(
    "UPDATE dispatch SET workflow_stage = 'delivered', workflow_updated_at = NOW()
     WHERE d_id = ? AND workflow_stage IN ('en_route', 'gate_cleared')"
);
$stmt->bind_param("i", $dId);
$stmt->execute();
$stmt->close();

$bn = $row['booking_no'];
$stmt = $conn->prepare(
    "INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes)
     VALUES (?, ?, 'delivered', 'driver', ?, 'Gateless completion (GPS + 2 photos)')"
);
$stmt->bind_param("isi", $dId, $bn, $driverId);
$stmt->execute();
$stmt->close();

json_out(['status' => 'success', 'message' => 'Gateless completion saved.']);
