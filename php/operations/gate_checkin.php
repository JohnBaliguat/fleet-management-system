<?php
header('Content-Type: application/json');
session_start();
include __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required']);
    exit;
}

$dId         = (int)($_POST['d_id'] ?? 0);
$direction   = strtoupper(trim($_POST['direction'] ?? ''));
$truckPlate  = strtoupper(trim($_POST['truck_plate'] ?? ''));
$trailerCode = strtoupper(trim($_POST['trailer_code'] ?? ''));
$gensetCode  = strtoupper(trim($_POST['genset_code'] ?? ''));
$driverId    = (int)($_POST['driver_id'] ?? 0);
$qrPayload   = trim($_POST['qr_payload'] ?? '');
$guardUserId = (int)($_SESSION['user_id'] ?? 0);

if ($dId <= 0 || !in_array($direction, ['IN', 'OUT'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'd_id and IN/OUT direction required']);
    exit;
}

// Pull the canonical dispatch row to verify against.
$stmt = $conn->prepare("SELECT * FROM dispatch WHERE d_id = ? LIMIT 1");
$stmt->bind_param("i", $dId);
$stmt->execute();
$dispatch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dispatch) {
    echo json_encode(['status' => 'error', 'message' => 'Dispatch not found']);
    exit;
}

// Verification — every assigned component must match the dispatch
// record. Empty fields on the dispatch side don't fail the match
// (e.g. genset is sometimes optional).
$mismatches = [];
$expectedTruck   = strtoupper(trim($dispatch['d_truck']   ?? ''));
$expectedTrailer = strtoupper(trim($dispatch['d_trailer'] ?? ''));
$expectedGenset  = strtoupper(trim($dispatch['d_genset']  ?? ''));
if ($expectedTruck   !== '' && $truckPlate  !== $expectedTruck)   { $mismatches[] = "truck $truckPlate ≠ $expectedTruck"; }
if ($expectedTrailer !== '' && $trailerCode !== $expectedTrailer) { $mismatches[] = "trailer $trailerCode ≠ $expectedTrailer"; }
if ($expectedGenset  !== '' && $gensetCode  !== $expectedGenset)  { $mismatches[] = "genset $gensetCode ≠ $expectedGenset"; }

// Authorisation check — workflow_stage past dispatcher_assigned, OR
// gate_queue approval row exists.
$authorised = in_array($dispatch['workflow_stage'] ?? '', ['driver_accepted', 'gate_cleared', 'en_route', 'delivered', 'pod_captured', 'billing_closed', 'client_notified'], true) ? 1 : 0;
if (!$authorised) {
    $s = $conn->prepare("SELECT 1 FROM gate_queue WHERE d_id = ? AND decision = 'approved' LIMIT 1");
    $s->bind_param("i", $dId);
    $s->execute();
    if ($s->get_result()->fetch_assoc()) $authorised = 1;
    $s->close();
}

// Customs gate for Export bookings — block OUT if customs_cleared != 1.
$customsBlock = false;
if ($direction === 'OUT' && !empty($dispatch['booking_no'])) {
    $s = $conn->prepare("SELECT booking_type, customs_cleared FROM booking WHERE booking_no = ? LIMIT 1");
    $s->bind_param("s", $dispatch['booking_no']);
    $s->execute();
    $bk = $s->get_result()->fetch_assoc();
    $s->close();
    if ($bk && ($bk['booking_type'] ?? '') === 'Export' && (int)($bk['customs_cleared'] ?? 0) !== 1) {
        $customsBlock = true;
    }
}

if ($customsBlock) {
    // Still log the attempt — auditable refusal.
    $verified = 0;
    $mismatchReason = 'CUSTOMS NOT CLEARED — exit blocked';
    $stmt = $conn->prepare(
        "INSERT INTO gate_log (d_id, direction, truck_plate, trailer_code, genset_code, driver_id, qr_payload, verified, mismatch_reason, authorised, guard_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issssisisii",
        $dId, $direction, $truckPlate, $trailerCode, $gensetCode, $driverId, $qrPayload,
        $verified, $mismatchReason, $authorised, $guardUserId);
    $stmt->execute();
    $stmt->close();
    echo json_encode([
        'status'          => 'error',
        'verified'        => 0,
        'mismatch_reason' => $mismatchReason,
        'message'         => 'Exit refused: customs has not cleared this Export booking yet.'
    ]);
    exit;
}

$verified = empty($mismatches) ? 1 : 0;
$mismatchReason = $verified ? '' : implode('; ', $mismatches);

$stmt = $conn->prepare(
    "INSERT INTO gate_log (d_id, direction, truck_plate, trailer_code, genset_code, driver_id, qr_payload, verified, mismatch_reason, authorised, guard_user_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("issssisisii",
    $dId, $direction, $truckPlate, $trailerCode, $gensetCode, $driverId, $qrPayload,
    $verified, $mismatchReason, $authorised, $guardUserId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['status' => 'error', 'message' => 'Insert failed']);
    exit;
}

// Workflow tick: an IN that passes verification can move the dispatch
// from dispatcher_assigned → gate_cleared. An OUT marks en_route.
if ($verified && $authorised) {
    $newStage = null;
    if ($direction === 'IN' && ($dispatch['workflow_stage'] === 'dispatcher_assigned' || $dispatch['workflow_stage'] === 'driver_accepted')) {
        $newStage = 'gate_cleared';
    }
    if ($direction === 'OUT' && $dispatch['workflow_stage'] === 'gate_cleared') {
        $newStage = 'en_route';
    }
    if ($newStage) {
        $colSql = ($direction === 'IN') ? 'gate_cleared_at = NOW()' : 'workflow_updated_at = NOW()';
        $upd = $conn->prepare("UPDATE dispatch SET workflow_stage = ?, workflow_updated_at = NOW(), $colSql WHERE d_id = ?");
        $upd->bind_param("si", $newStage, $dId);
        $upd->execute();
        $upd->close();

        $bn   = $dispatch['booking_no'] ?? '';
        $note = "Gate $direction by guard #$guardUserId";
        $log = $conn->prepare("INSERT INTO workflow_event (d_id, booking_no, stage, actor_role, actor_id, notes) VALUES (?, ?, ?, 'gate_guard', ?, ?)");
        if ($log) {
            $log->bind_param("issis", $dId, $bn, $newStage, $guardUserId, $note);
            $log->execute();
            $log->close();
        }
    }
}

// If a queue row exists for this dispatch, mark it consumed.
$conn->query("UPDATE gate_queue SET decision='consumed', decided_at=NOW(), decided_by=$guardUserId WHERE d_id=$dId AND decision IN ('approved','pending')");

// Phase 7 — keep equipment current_location in sync with scans.
// Guard's session is expected to have user_assignLocation = the base
// they're working at (PTSI Base / Consol Base / etc.). On IN, the
// equipment is "at this base"; on OUT, it's "In Transit".
$baseName = '';
if (!empty($guardUserId)) {
    $stmt = $conn->prepare("SELECT user_assignLocation FROM user WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $guardUserId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r && !empty($r['user_assignLocation'])) {
        $baseName = $r['user_assignLocation'];
    }
}
$base = ($baseName !== '') ? $baseName : 'PTSI Base';
$newLoc = $direction === 'IN' ? $base : 'In Transit';

if ($truckPlate !== '') {
    $stmt = $conn->prepare("UPDATE units SET current_location = ?, current_location_updated_at = NOW() WHERE unit_name = ? AND unit_type = 'truck'");
    $stmt->bind_param("ss", $newLoc, $truckPlate);
    $stmt->execute();
    $stmt->close();
}
if ($gensetCode !== '') {
    $stmt = $conn->prepare("UPDATE units SET current_location = ?, current_location_updated_at = NOW() WHERE unit_name = ? AND unit_type = 'genset'");
    $stmt->bind_param("ss", $newLoc, $gensetCode);
    $stmt->execute();
    $stmt->close();
}
if ($trailerCode !== '') {
    $stmt = $conn->prepare("UPDATE trailer SET current_base = ?, current_base_updated_at = NOW() WHERE trailer_name = ?");
    $stmt->bind_param("ss", $newLoc, $trailerCode);
    $stmt->execute();
    $stmt->close();
}

$msg = $verified
    ? ($authorised ? "Logged $direction successfully." : "Logged $direction (driver was NOT pre-authorised by dispatcher).")
    : "Logged $direction with MISMATCH — see reason.";

echo json_encode([
    'status'          => 'success',
    'verified'        => $verified,
    'authorised'      => $authorised,
    'mismatch_reason' => $mismatchReason,
    'message'         => $msg,
]);
$conn->close();
