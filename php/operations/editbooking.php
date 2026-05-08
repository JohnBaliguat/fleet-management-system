<?php
session_start();
include '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId        = (int)$_POST['booking_id'];
    $bookingDate      = $_POST['booking_date1'];
    $booking_required = $_POST['booking_required1'];
    $costumer         = $_POST['costumer1'];
    $container_seal   = $_POST['container_seal1'];
    $container        = $_POST['container1'];
    $status           = $_POST['container_status1'];
    $segment          = $_POST['hauling_segment1'];
    $tripFrom         = $_POST['trip_from1'];
    $tripTo           = $_POST['trip_to1'];
    $quantity         = (int)$_POST['quantity1'];
    $booking_activity = $_POST['booking_activity1'];

    // Phase 2 — booking type + port fields.
    $booking_type      = $_POST['booking_type1']      ?? 'Local';
    $vessel_name       = $_POST['vessel_name1']       ?? '';
    $voyage_no         = $_POST['voyage_no1']         ?? '';
    $container_no_port = $_POST['container_no_port1'] ?? '';
    $bill_of_lading    = $_POST['bill_of_lading1']    ?? '';
    $port_location     = $_POST['port_location1']     ?? '';
    $customs_cleared   = isset($_POST['customs_cleared1']) ? 1 : 0;

    if (!in_array($booking_type, ['Local', 'Import', 'Export'], true)) {
        $booking_type = 'Local';
    }
    if ($booking_type === 'Local') {
        $vessel_name = $voyage_no = $container_no_port = $bill_of_lading = $port_location = '';
        $customs_cleared = 0;
    }

    $haulingType = "";
    $stmtType = $conn->prepare("SELECT hauling_type FROM hauling WHERE hauling_segment = ? LIMIT 1");
    $stmtType->bind_param("s", $segment);
    $stmtType->execute();
    $stmtType->bind_result($haulingType);
    $stmtType->fetch();
    $stmtType->close();

    if (empty($haulingType)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid hauling segment.']);
        exit;
    }

    $stmtCheck = $conn->prepare("SELECT quantity_use FROM booking WHERE booking_id = ? LIMIT 1");
    $stmtCheck->bind_param("i", $bookingId);
    $stmtCheck->execute();
    $stmtCheck->bind_result($quantityUse);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($quantity < $quantityUse) {
        echo json_encode([
            'status' => 'error',
            'message' => "Quantity cannot be less than already used quantity ($quantityUse)."
        ]);
        exit;
    }

    // Stamp customs_cleared_at when the flag flips on (Export only).
    $customsClearedAtSql = "";
    if ($customs_cleared === 1) {
        $customsClearedAtSql = ", customs_cleared_at = COALESCE(customs_cleared_at, NOW())";
    } else {
        $customsClearedAtSql = ", customs_cleared_at = NULL";
    }

    $query = "UPDATE booking
              SET booking_type = ?, booking_date = ?, booking_dateRequired = ?, costumer = ?, container_seal = ?,
                  container = ?, booking_activity = ?, container_status = ?,
                  hauling_segment = ?, hauling_type = ?, trip_from = ?, trip_to = ?, quantity = ?,
                  vessel_name = ?, voyage_no = ?, container_no_port = ?, bill_of_lading = ?,
                  port_location = ?, customs_cleared = ?
                  $customsClearedAtSql
              WHERE booking_id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param(
        "ssssssssssssissssssii",
        $booking_type,
        $bookingDate,
        $booking_required,
        $costumer,
        $container_seal,
        $container,
        $booking_activity,
        $status,
        $segment,
        $haulingType,
        $tripFrom,
        $tripTo,
        $quantity,
        $vessel_name,
        $voyage_no,
        $container_no_port,
        $bill_of_lading,
        $port_location,
        $customs_cleared,
        $bookingId
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Booking updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update booking: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
