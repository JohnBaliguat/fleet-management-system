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
$decision = $_POST['decision'] ?? '';   // 'verified' | 'rejected'
$notes    = trim($_POST['notes'] ?? '');
$actor    = (int)($_SESSION['user_id'] ?? 0);

if ($dId <= 0 || !in_array($decision, ['verified', 'rejected'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'd_id + decision required']); exit;
}

$stmt = $conn->prepare("SELECT booking_no, driver_id, workflow_stage FROM dispatch WHERE d_id = ? LIMIT 1");
$stmt->bind_param("i", $dId);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$d) { echo json_encode(['status' => 'error', 'message' => 'Dispatch not found']); exit; }
if ($d['workflow_stage'] !== 'pending_verification') {
    echo json_encode(['status' => 'error', 'message' => 'Not at pending_verification (currently: ' . $d['workflow_stage'] . ')']); exit;
}

$conn->begin_transaction();
try {
    if ($decision === 'verified') {
        // Trip flips to pod_captured + completion stamp + verification audit on dispatch row.
        $stmt = $conn->prepare(
            "UPDATE dispatch
             SET workflow_stage = 'pod_captured',
                 trip_completed_at = NOW(),
                 verified_by = ?, verified_at = NOW(), verification_notes = ?,
                 workflow_updated_at = NOW()
             WHERE d_id = ?"
        );
        $stmt->bind_param("isi", $actor, $notes, $dId);
        $stmt->execute();
        $stmt->close();

        // Mirror onto the pod_capture row.
        $stmt = $conn->prepare(
            "UPDATE pod_capture SET verified_by = ?, verified_at = NOW(), verification_notes = ? WHERE d_id = ?"
        );
        $stmt->bind_param("isi", $actor, $notes, $dId);
        $stmt->execute();
        $stmt->close();

        $stage = 'pod_captured';
        $logNote = 'POD verified by ' . $role . ' #' . $actor . ($notes !== '' ? ' — ' . $notes : '');
    } else {
        // Rejected: kick the dispatch back to en_route so the driver
        // can re-capture / fix.
        $stmt = $conn->prepare(
            "UPDATE dispatch
             SET workflow_stage = 'en_route',
                 verified_by = ?, verified_at = NOW(), verification_notes = ?,
                 workflow_updated_at = NOW()
             WHERE d_id = ?"
        );
        $stmt->bind_param("isi", $actor, $notes, $dId);
        $stmt->execute();
        $stmt->close();

        $stage = 'pod_rejected';
        $logNote = 'POD rejected — ' . ($notes !== '' ? $notes : 'no reason given');
    }

    // workflow_event row.
    $bn = $d['booking_no'];
    $stmt = $conn->prepare("INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $dId, $bn, $stage, $role, $actor, $logNote);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Verify failed: ' . $e->getMessage()]); exit;
}

// Push the driver — they're waiting to see whether to re-shoot or stand down.
if (!empty($d['driver_id'])) {
    $title = $decision === 'verified' ? 'Trip completed' : 'POD needs re-do';
    $body  = $decision === 'verified'
        ? ($d['booking_no'] . ' verified. You are clear.')
        : ($d['booking_no'] . ' POD rejected. ' . ($notes !== '' ? $notes : 'Please re-capture.'));
    pt_notify_driver($conn, (int)$d['driver_id'], $title, $body, $dId);
}

echo json_encode(['status' => 'success', 'message' => $decision === 'verified' ? 'Trip verified and marked complete.' : 'POD rejected — driver notified to re-capture.']);
$conn->close();
