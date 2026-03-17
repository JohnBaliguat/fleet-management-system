<?php
session_start();
include '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId   = $_POST['booking_id'];
    $bookingDate = $_POST['booking_date1'];
    $booking_required = $_POST['booking_required1'];
    $costumer    = $_POST['costumer1'];
    $container_seal   = $_POST['container_seal1'];
    $container   = $_POST['container1'];
    $status      = $_POST['container_status1'];
    $segment     = $_POST['hauling_segment1'];
    $tripFrom    = $_POST['trip_from1'];
    $tripTo      = $_POST['trip_to1'];
    $quantity    = $_POST['quantity1'];
    $booking_activity = $_POST['booking_activity1'];

    // ✅ Get hauling_type based on hauling_segment
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

    // ✅ Get current quantity_use
    $stmtCheck = $conn->prepare("SELECT quantity_use FROM booking WHERE booking_id = ? LIMIT 1");
    $stmtCheck->bind_param("i", $bookingId);
    $stmtCheck->execute();
    $stmtCheck->bind_result($quantityUse);
    $stmtCheck->fetch();
    $stmtCheck->close();

    // ✅ Validation: Do not allow quantity < quantity_use
    if ($quantity < $quantityUse) {
        echo json_encode([
            'status' => 'error',
            'message' => "Quantity cannot be less than already used quantity ($quantityUse)."
        ]);
        exit;
    }

    // ✅ Update bookings
    $query = "UPDATE booking 
              SET booking_date = ?, booking_dateRequired = ?, costumer = ?, container_seal = ?, container = ?, booking_activity = ?, container_status = ?, 
                  hauling_segment = ?, hauling_type = ?, trip_from = ?, trip_to = ?, quantity = ? 
              WHERE booking_id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param(
        "sssssssssssii",
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
        $bookingId
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Booking updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update booking.']);
    }

    $stmt->close();
    $conn->close();
}
?>
