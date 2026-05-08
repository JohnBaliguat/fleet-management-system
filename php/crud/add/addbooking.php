<?php
include "../../config/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_date     = $_POST['booking_date'];
    $booking_required = $_POST['booking_required'];
    $costumer         = $_POST['costumer'];
    $container_seal   = $_POST['container_seal'];
    $container        = $_POST['container'];
    $container_status = $_POST['container_status'];
    $hauling_segment  = $_POST['hauling_segment'];
    $trip_from        = $_POST['trip_from'];
    $trip_to          = $_POST['trip_to'];
    $booking_activity = $_POST['booking_activity'];
    $quantity         = (int)$_POST['quantity'];

    // Phase 2 — booking type + port fields.
    $booking_type      = $_POST['booking_type']      ?? 'Local';
    $vessel_name       = $_POST['vessel_name']       ?? '';
    $voyage_no         = $_POST['voyage_no']         ?? '';
    $container_no_port = $_POST['container_no_port'] ?? '';
    $bill_of_lading    = $_POST['bill_of_lading']    ?? '';
    $port_location     = $_POST['port_location']     ?? '';
    $customs_cleared   = isset($_POST['customs_cleared']) ? 1 : 0;

    // Whitelist booking_type — defensive against tampered POSTs.
    if (!in_array($booking_type, ['Local', 'Import', 'Export'], true)) {
        $booking_type = 'Local';
    }
    // Local bookings have no port context; clear those fields rather
    // than store stale values from a previous selection.
    if ($booking_type === 'Local') {
        $vessel_name = $voyage_no = $container_no_port = $bill_of_lading = $port_location = '';
        $customs_cleared = 0;
    }

    // Get hauling_type based on hauling_segment
    $hauling_type = '';
    $stmt1 = $conn->prepare("SELECT hauling_type FROM hauling WHERE hauling_segment = ? LIMIT 1");
    $stmt1->bind_param("s", $hauling_segment);
    $stmt1->execute();
    $stmt1->bind_result($hauling_type_result);
    if ($stmt1->fetch()) {
        $hauling_type = $hauling_type_result;
    }
    $stmt1->close();

    // Generate Booking No
    $result = mysqli_query($conn, "SELECT booking_no FROM booking ORDER BY booking_id DESC LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        $last = mysqli_fetch_assoc($result);
        $lastNo = (int)substr($last['booking_no'], -5);
        $newNo = $lastNo + 1;
    } else {
        $newNo = 1;
    }
    $booking_no = 'PTSIBN-' . str_pad($newNo, 5, '0', STR_PAD_LEFT);

    $stmt2 = $conn->prepare("INSERT INTO booking (
        booking_no, booking_type, booking_date, booking_dateRequired, costumer,
        container_seal, container, booking_activity, container_status,
        hauling_segment, hauling_type, trip_from, trip_to, quantity, quantity_use, status,
        vessel_name, voyage_no, container_no_port, bill_of_lading, port_location, customs_cleared
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'Active', ?, ?, ?, ?, ?, ?)");

    $stmt2->bind_param(
        "sssssssssssssissssssi",
        $booking_no,
        $booking_type,
        $booking_date,
        $booking_required,
        $costumer,
        $container_seal,
        $container,
        $booking_activity,
        $container_status,
        $hauling_segment,
        $hauling_type,
        $trip_from,
        $trip_to,
        $quantity,
        $vessel_name,
        $voyage_no,
        $container_no_port,
        $bill_of_lading,
        $port_location,
        $customs_cleared
    );

    if ($stmt2->execute()) {
        // Phase 1 workflow audit — every new booking starts at order_created.
        $logged_no = $booking_no;
        $stage_log = $conn->prepare(
            "INSERT INTO workflow_event (booking_no, stage, actor_role, notes) VALUES (?, 'order_created', 'system', 'Booking created via addbooking form')"
        );
        if ($stage_log) {
            $stage_log->bind_param("s", $logged_no);
            $stage_log->execute();
            $stage_log->close();
        }
        echo json_encode(["status" => "success", "message" => "Booking successfully saved.", "booking_no" => $booking_no]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save booking: " . $stmt2->error]);
    }

    $stmt2->close();
    $conn->close();
}
?>
