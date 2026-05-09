<?php
require __DIR__ . '/_driver_auth.php';
$driverId = require_driver_session();
require_post();
include __DIR__ . '/../config/config.php';

$dId        = (int)($_POST['d_id'] ?? 0);
$type       = trim($_POST['incident_type'] ?? 'breakdown');
$severity   = trim($_POST['severity'] ?? 'med');
$assistance = trim($_POST['assistance'] ?? '');
$desc       = trim($_POST['description'] ?? '');
$lat        = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
$lng        = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;

if ($desc === '') json_out(['status' => 'error', 'message' => 'description required'], 400);

// Photo upload (optional).
$dir = __DIR__ . '/../assets/uploads/incidents';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
$photoPath = '';
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic'], true)) { $ext = 'jpg'; }
    $name = 'bd_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dir . '/' . $name)) {
        $photoPath = 'php/assets/uploads/incidents/' . $name;
    }
}

$stmt = $conn->prepare(
    "INSERT INTO incident (d_id, driver_id, incident_type, severity, description, lat, lng, photo_path, assistance, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')"
);
$stmt->bind_param("iisssddss", $dId, $driverId, $type, $severity, $desc, $lat, $lng, $photoPath, $assistance);
$stmt->execute();
$incId = $stmt->insert_id;
$stmt->close();

// Workflow audit on the dispatch timeline.
$bn = '';
if ($dId > 0) {
    $r = $conn->query("SELECT booking_no FROM dispatch WHERE d_id = " . (int)$dId . " LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) { $bn = $row['booking_no']; }
}
$notes = "Driver breakdown #$incId — $type" . ($assistance !== '' ? " (assistance: $assistance)" : '') . ($desc !== '' ? " — $desc" : '');
$stmt = $conn->prepare("INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes) VALUES (?, ?, 'incident_flagged', 'driver', ?, ?)");
$stmt->bind_param("isis", $dId, $bn, $driverId, $notes);
$stmt->execute();
$stmt->close();

json_out(['status' => 'success', 'message' => 'Reported. Dispatcher has been notified.', 'inc_id' => $incId]);
