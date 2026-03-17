<?php
include "../../config/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_date = $_POST['booking_date'];
    $booking_required = $_POST['booking_required'] ?? "";
    $booking_sn = $_POST['booking_sn'] ?? "";
    $booking_do = $_POST['booking_do'] ?? "";
    $haulingStart = $_POST['haulingStart'] ?? "";
    $lastDayStorage = $_POST['lastDayStorage'] ?? "";
    $lastDayDemurrage = $_POST['lastDayDemurrage'] ?? "";
    $lastDayDetention = $_POST['lastDayDetention'] ?? "";
    $costumer = $_POST['costumer'];
    $container_seal = $_POST['container_seal'];
    $container = $_POST['container'];
    $container_status = $_POST['container_status'];
    $hauling_segment = $_POST['hauling_segment'];
    $trip_from = $_POST['trip_from'];
    $trip_to = $_POST['trip_to'];
    $return_location = $_POST['return_location'];
    $booking_activity = $_POST['booking_activity'];
    $quantity = $_POST['quantity'];

    if(empty($booking_required)){
        $booking_required = $lastDayDetention;
    }

    // --- get hauling_type for each segment ---
    function getHaulingType($conn, $segment) {
        $hauling_type_result ="";
        $stmt = $conn->prepare("SELECT hauling_type FROM hauling WHERE hauling_segment = ? LIMIT 1");
        $stmt->bind_param("s", $segment);
        $stmt->execute();
        $stmt->bind_result($hauling_type_result);
        $type = '';
        if ($stmt->fetch()) {
            $type = $hauling_type_result;
        }
        $stmt->close();
        return $type;
    }

    // --- get last booking number ---
    $result = mysqli_query($conn, "SELECT booking_no FROM booking ORDER BY booking_id DESC LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        $last = mysqli_fetch_assoc($result);
        $lastNo = (int)substr($last['booking_no'], -5);
        $newNo = $lastNo + 1;
    } else {
        $newNo = 1;
    }

    $success = true;
    $messages = [];

    // --- loop through all containers ---
    for ($i = 0; $i < count($container); $i++) {
        $booking_no = 'PTSIBN-' . str_pad($newNo, 5, '0', STR_PAD_LEFT);
        $newNo++; // increment for next record

        $hauling_type = getHaulingType($conn, $hauling_segment[$i]);

         $stmt2 = $conn->prepare("
            INSERT INTO booking (
                booking_no, booking_sn, booking_do, booking_date, booking_dateRequired,
                booking_haulingStartDate, booking_LastDateStorage, booking_LastDateDemurrage, booking_LastDateDetention,
                costumer, container_seal, container, booking_activity, container_status,
                hauling_segment, hauling_type, trip_from, trip_to, return_location,
                quantity, quantity_use, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $quantity_use = 0;
        $status = 'Active';

        $stmt2->bind_param(
           "sssssssssssssssssssiss",
            $booking_no,
            $booking_sn,
            $booking_do,
            $booking_date,
            $booking_required,
            $haulingStart,
            $lastDayStorage,
            $lastDayDemurrage,
            $lastDayDetention,
            $costumer,
            $container_seal[$i],
            $container[$i],
            $booking_activity[$i],
            $container_status[$i],
            $hauling_segment[$i],
            $hauling_type,
            $trip_from[$i],
            $trip_to[$i],
            $return_location[$i],
            $quantity[$i],
            $quantity_use,
            $status
        );

        if ($stmt2->execute()) {
            $messages[] = "Booking $booking_no saved successfully.";
        } else {
            $success = false;
            $messages[] = "Failed to save booking $booking_no.";
        }
        $stmt2->close();
    }

    $conn->close();

    if ($success) {
        echo json_encode(["status" => "success", "message" => implode("<br>", $messages)]);
    } else {
        echo json_encode(["status" => "error", "message" => implode("<br>", $messages)]);
    }
}
?>
