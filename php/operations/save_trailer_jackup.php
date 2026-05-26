<?php
require __DIR__ . '/_driver_auth.php';
$driverId = require_driver_session();
require_post();
include __DIR__ . '/../config/config.php';

$dId         = (int)($_POST['d_id'] ?? 0);
$trailerCode = strtoupper(trim($_POST['trailer_code'] ?? ''));
$lat         = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
$lng         = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;

if ($trailerCode === '') {
    json_out(['status' => 'error', 'message' => 'trailer_code required'], 400);
}

// Photo upload (optional but expected).
$dir = __DIR__ . '/../assets/uploads/jackup';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
$photoPath = '';
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic'], true)) { $ext = 'jpg'; }
    $name = 'tj_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dir . '/' . $name)) {
        $photoPath = 'php/assets/uploads/jackup/' . $name;
    }
}

$stmt = $conn->prepare(
    "INSERT INTO trailer_jackup (d_id, trailer_code, driver_id, lat, lng, photo_path, billing_active)
     VALUES (?, ?, ?, ?, ?, ?, 1)"
);
$stmt->bind_param("isidds", $dId, $trailerCode, $driverId, $lat, $lng, $photoPath);
$stmt->execute();
$tjId = $stmt->insert_id;
$stmt->close();

// workflow_event audit.
$bn = '';
if ($dId > 0) {
    $r = $conn->query("SELECT booking_no FROM dispatch WHERE d_id = $dId LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) { $bn = $row['booking_no']; }
}
$notes = "Trailer $trailerCode jacked up at " . ($lat !== null ? "$lat,$lng" : 'unknown location');
$stmt = $conn->prepare("INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes) VALUES (?, ?, 'trailer_jackup', 'driver', ?, ?)");
$stmt->bind_param("isis", $dId, $bn, $driverId, $notes);
$stmt->execute();
$stmt->close();

json_out(['status' => 'success', 'message' => 'Trailer jack-up logged. Billing continues until compound return.', 'tj_id' => $tjId]);
