<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config/config.php';

$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['Maintenance', 'Admin'], true)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authorised']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required']); exit;
}

$kind            = strtolower(trim($_POST['unit_kind'] ?? ''));
$code            = strtoupper(trim($_POST['unit_code'] ?? ''));
$category        = trim($_POST['category']        ?? 'other');
$severity        = trim($_POST['severity']        ?? 'med');
$reason          = trim($_POST['reason']          ?? '');
$expectedReturn  = trim($_POST['expected_return'] ?? '');
$actor           = (int)($_SESSION['user_id'] ?? 0);

if (!in_array($kind, ['truck', 'genset', 'trailer'], true) || $code === '') {
    echo json_encode(['status' => 'error', 'message' => 'unit_kind (truck|genset|trailer) and unit_code required']); exit;
}
if ($reason === '') {
    echo json_encode(['status' => 'error', 'message' => 'Reason is required.']); exit;
}
$expectedReturnSql = $expectedReturn !== '' ? $expectedReturn : null;

// Optional photo upload.
$photoPath = '';
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $dir = __DIR__ . '/../assets/uploads/maintenance';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','heic'], true)) $ext = 'jpg';
    $name = 'um_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dir . '/' . $name)) {
        $photoPath = 'php/assets/uploads/maintenance/' . $name;
    }
}

// Confirm the unit exists.
if ($kind === 'trailer') {
    $stmt = $conn->prepare("SELECT trailer_name AS code, maintenance_blocked AS blocked FROM trailer WHERE trailer_name = ? LIMIT 1");
} else {
    $stmt = $conn->prepare("SELECT unit_name AS code, maintenance_blocked AS blocked FROM units WHERE unit_name = ? AND unit_type = ? LIMIT 1");
}
if ($kind === 'trailer') { $stmt->bind_param("s", $code); }
else                     { $stmt->bind_param("ss", $code, $kind); }
$stmt->execute();
$found = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$found)               { echo json_encode(['status' => 'error', 'message' => "$kind $code not found."]);   exit; }
if ((int)$found['blocked'] === 1) {
    echo json_encode(['status' => 'error', 'message' => "$code is already blocked."]); exit;
}

$conn->begin_transaction();
try {
    // Insert the history row.
    $stmt = $conn->prepare(
        "INSERT INTO unit_maintenance (unit_kind, unit_code, category, severity, reason, photo_path, expected_return, blocked_by, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')"
    );
    $stmt->bind_param("sssssssi", $kind, $code, $category, $severity, $reason, $photoPath, $expectedReturnSql, $actor);
    $stmt->execute();
    $umId = $stmt->insert_id;
    $stmt->close();

    // Update master row's denormalised mirror.
    if ($kind === 'trailer') {
        $stmt = $conn->prepare(
            "UPDATE trailer SET maintenance_blocked = 1, maintenance_reason = ?, maintenance_expected_return = ?,
                                maintenance_blocked_at = NOW(), trailer_status = 'Under Maintenance'
             WHERE trailer_name = ?"
        );
        $stmt->bind_param("sss", $reason, $expectedReturnSql, $code);
    } else {
        $stmt = $conn->prepare(
            "UPDATE units SET maintenance_blocked = 1, maintenance_reason = ?, maintenance_expected_return = ?,
                              maintenance_blocked_at = NOW(), unit_status = 'Under Maintenance'
             WHERE unit_name = ? AND unit_type = ?"
        );
        $stmt->bind_param("ssss", $reason, $expectedReturnSql, $code, $kind);
    }
    $stmt->execute();
    $stmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Block failed: ' . $e->getMessage()]); exit;
}

echo json_encode(['status' => 'success', 'message' => "$code blocked from dispatch.", 'um_id' => $umId]);
$conn->close();
