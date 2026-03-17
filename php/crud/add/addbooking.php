<?php 
include "../../config/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_date = $_POST['booking_date'];
    $booking_required = $_POST['booking_required'];
    $costumer = $_POST['costumer'];
    $container_seal = $_POST['container_seal'];
    $container = $_POST['container'];
    $container_status = $_POST['container_status'];
    $hauling_segment = $_POST['hauling_segment'];
    $trip_from = $_POST['trip_from'];
    $trip_to = $_POST['trip_to'];
    $booking_activity = $_POST['booking_activity'];

    $quantity = $_POST['quantity'];

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

    // Insert into booking table
    $stmt2 = $conn->prepare("INSERT INTO booking (
        booking_no, booking_date, booking_dateRequired, costumer, container_seal, container, booking_activity, container_status,
        hauling_segment, hauling_type, trip_from, trip_to, quantity,
        quantity_use, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'Active')");

    $stmt2->bind_param(
        "ssssssssssssi",
        $booking_no, $booking_date, $booking_required, $costumer, $container_seal, $container, $booking_activity, $container_status,
        $hauling_segment, $hauling_type, $trip_from, $trip_to, $quantity
    );

    if ($stmt2->execute()) {
        echo json_encode(["status" => "success", "message" => "Booking successfully saved."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save booking."]);
    }

    $stmt2->close();
    $conn->close();
}
?>
