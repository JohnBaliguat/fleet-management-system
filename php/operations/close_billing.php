<?php
header('Content-Type: application/json');
session_start();
include __DIR__ . '/../config/config.php';
require_once __DIR__ . '/_push_send.php';

$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['Dispatcher', 'Admin'], true)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authorised']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required']); exit;
}

$dId      = (int)($_POST['d_id'] ?? 0);
$amount   = (float)($_POST['billing_amount'] ?? 0);
$currency = trim($_POST['billing_currency'] ?? 'PHP');
$notes    = trim($_POST['billing_notes'] ?? '');
$notifyClient = !empty($_POST['notify_client']) ? 1 : 0;
$actor    = (int)($_SESSION['user_id'] ?? 0);

if ($dId <= 0) { echo json_encode(['status' => 'error', 'message' => 'd_id required']); exit; }

$stmt = $conn->prepare("SELECT booking_no, costumer, driver_id, workflow_stage FROM dispatch WHERE d_id = ? LIMIT 1");
$stmt->bind_param("i", $dId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) { echo json_encode(['status' => 'error', 'message' => 'Dispatch not found']); exit; }

// Spec: only close after delivery / POD captured. Allow billing close
// from any post-delivery stage so a manual close after gateless still works.
$closeable = ['delivered', 'pod_captured', 'billing_closed'];
if (!in_array($row['workflow_stage'], $closeable, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot close billing yet — dispatch is at stage ' . $row['workflow_stage']]); exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "UPDATE dispatch
         SET workflow_stage = 'billing_closed',
             billing_closed_at = NOW(),
             billing_amount = ?, billing_currency = ?, billing_notes = ?,
             workflow_updated_at = NOW()
         WHERE d_id = ?"
    );
    $stmt->bind_param("dssi", $amount, $currency, $notes, $dId);
    $stmt->execute();
    $stmt->close();

    $bn = $row['booking_no'];
    $eventNote = "Closed by $role #$actor — $currency " . number_format($amount, 2) . ($notes !== '' ? ' — ' . $notes : '');
    $stmt = $conn->prepare("INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes) VALUES (?, ?, 'billing_closed', ?, ?, ?)");
    $stmt->bind_param("issis", $dId, $bn, $role, $actor, $eventNote);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Close failed: ' . $e->getMessage()]);
    exit;
}

// Optional client notification: bumps to 'client_notified' and emits
// another workflow_event + email/sms attempts.
if ($notifyClient) {
    $stmt = $conn->prepare(
        "UPDATE dispatch
         SET workflow_stage = 'client_notified', client_notified_at = NOW(),
             workflow_updated_at = NOW()
         WHERE d_id = ?"
    );
    $stmt->bind_param("i", $dId);
    $stmt->execute();
    $stmt->close();

    // Mirror booking-level timestamp.
    $stmt = $conn->prepare("UPDATE booking SET client_notified_at = NOW() WHERE booking_no = ?");
    $stmt->bind_param("s", $row['booking_no']);
    $stmt->execute();
    $stmt->close();

    $bn = $row['booking_no'];
    $note = 'Client notified via configured channels (email/sms attempts logged in push_send_log)';
    $stmt = $conn->prepare("INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes) VALUES (?, ?, 'client_notified', ?, ?, ?)");
    $stmt->bind_param("issis", $dId, $bn, $role, $actor, $note);
    $stmt->execute();
    $stmt->close();

    $subject = "Pantrucks: $bn delivered & billed";
    $body    = "Booking $bn for {$row['costumer']} has been delivered and billing closed at $currency " . number_format($amount, 2) . ".";
    pt_notify_client($conn, $row['costumer'], $subject, $body, $dId);

    // Driver also gets a "Billing closed" push.
    if (!empty($row['driver_id'])) {
        pt_notify_driver($conn, (int)$row['driver_id'], "Billing closed: $bn", "Your dispatch $bn is now closed. Thanks!", $dId);
    }
}

echo json_encode([
    'status'  => 'success',
    'message' => $notifyClient ? 'Closed and client notified.' : 'Billing closed.',
    'workflow_stage' => $notifyClient ? 'client_notified' : 'billing_closed',
]);
$conn->close();
