<?php
include "../../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['record_id'];

    // Dispatch table fields
    $driverName = $_POST['drivers_name1'];
    $truck = $_POST['truck1'];
    $trailer = $_POST['trailer1'];
    $genset = $_POST['genset1'];
    $tripReceipt = $_POST['tr1'];
    $ecs = $_POST['ecs1'];

    // Trip 1
    $trip1_container = $_POST['container_no1'];
    $trip1_status = $_POST['container_status1'];
    $trip1_segment = $_POST['hauling_segment1'];
    $trip1_from = $_POST['destination_from1'];
    $trip1_to = $_POST['destination_to1'];

    // Trip 2
    $trip2_container = $_POST['container_no2'];
    $trip2_status = $_POST['container_status2'];
    $trip2_segment = $_POST['hauling_segment2'];
    $trip2_from = $_POST['destination_from12'];
    $trip2_to = $_POST['destination_to12'];

    // === Fetch Hauling Types ===
    $trip1_type = '';
    $trip2_type = '';

    $stmt = $conn->prepare("SELECT hauling_type FROM hauling WHERE hauling_segment = ?");
    $stmt->bind_param("s", $trip1_segment);
    $stmt->execute();
    $stmt->bind_result($trip1_type);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT hauling_type FROM hauling WHERE hauling_segment = ?");
    $stmt->bind_param("s", $trip2_segment);
    $stmt->execute();
    $stmt->bind_result($trip2_type);
    $stmt->fetch();
    $stmt->close();

    // === Update dispatch table ===
    $sql_dispatch = "UPDATE `dispatch` SET 
                        d_driverName = ?, 
                        d_truck = ?, 
                        d_trailer = ?, 
                        d_genset = ?, 
                        d_tripReceipt = ?, 
                        d_ecs = ? 
                    WHERE d_id = ?";

    $stmt1 = $conn->prepare($sql_dispatch);
    $stmt1->bind_param("ssssssi", $driverName, $truck, $trailer, $genset, $tripReceipt, $ecs, $id);

    // === Update Trip 1 ===
    $sql_trip1 = "UPDATE `trips` SET 
                    trip_container = ?, 
                    trip_containerStat = ?, 
                    trip_haulingSegment = ?, 
                    trip_haulingType = ?, 
                    trip_from = ?, 
                    trip_to = ? 
                 WHERE d_id = ? AND trip_type = 'Trip 1'";

    $stmt2 = $conn->prepare($sql_trip1);
    $stmt2->bind_param("ssssssi", $trip1_container, $trip1_status, $trip1_segment, $trip1_type, $trip1_from, $trip1_to, $id);

    // === Update Trip 2 ===
    $sql_trip2 = "UPDATE `trips` SET 
                    trip_container = ?, 
                    trip_containerStat = ?, 
                    trip_haulingSegment = ?, 
                    trip_haulingType = ?, 
                    trip_from = ?, 
                    trip_to = ? 
                 WHERE d_id = ? AND trip_type = 'Trip 2'";

    $stmt3 = $conn->prepare($sql_trip2);
    $stmt3->bind_param("ssssssi", $trip2_container, $trip2_status, $trip2_segment, $trip2_type, $trip2_from, $trip2_to, $id);

    // === Execute All ===
    if ($stmt1->execute() && $stmt2->execute() && $stmt3->execute()) {
        echo "Dispatch and Trip records updated successfully!";
    } else {
        http_response_code(500);
        echo "Error updating records: " . $conn->error;
    }

    $stmt1->close();
    $stmt2->close();
    $stmt3->close();
    $conn->close();
}
?>
