<?php
include '../config/config.php';
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rfid'])) {
    $rfid = trim($_POST['rfid']);
    $response = ['status' => 'error', 'message' => 'Something went wrong.'];

    if ($rfid === "") {
        $response['message'] = "RFID is empty.";
        echo json_encode($response);
        exit;
    }

    // Get driver info
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE driver_rfid = ?");
    $stmt->bind_param("s", $rfid);
    $stmt->execute();
    $driverResult = $stmt->get_result();

    if ($driverResult->num_rows === 0) {
        $response['message'] = "RFID not recognized.";
        echo json_encode($response);
        exit;
    }

    $driver = $driverResult->fetch_assoc();
    $driverNameFormatted = strtoupper($driver['driver_lname']) . ", " . strtoupper(substr($driver['driver_fname'], 0, 1)) . ".";

    // Get dispatch info
    $stmt = $conn->prepare("SELECT * FROM dispatch WHERE d_driverName = ? ORDER BY d_datetime DESC LIMIT 1");
    $stmt->bind_param("s", $driverNameFormatted);
    $stmt->execute();
    $dispatchResult = $stmt->get_result();

    $dispatch = $dispatchResult->num_rows > 0 ? $dispatchResult->fetch_assoc() : null;

    // Get Trip 1 segment if there is a dispatch
    $tripSegment = "";
    if ($dispatch) {
        $stmt = $conn->prepare("SELECT * FROM trips WHERE d_id = ? AND trip_type = 'Trip 1' LIMIT 1");
        $stmt->bind_param("i", $dispatch['d_id']);
        $stmt->execute();
        $tripResult = $stmt->get_result();

        if ($tripResult->num_rows > 0) {
            $trip = $tripResult->fetch_assoc();
            $tripSegment = $trip['trip_haulingSegment'];

            if ($trip['trip_status'] !== "On Trip") {
                $conn->query("UPDATE trips SET trip_status = 'On Trip' WHERE trip_id = {$trip['trip_id']}");
            }
        }
    }

    // Check the last in/out record
    $stmt = $conn->prepare("SELECT * FROM outin WHERE drivers_name = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $driverNameFormatted);
    $stmt->execute();
    $outinResult = $stmt->get_result();

    $status = "Check In"; // default to Check In
    if ($outinResult->num_rows > 0) {
        $lastOutin = $outinResult->fetch_assoc();
        $status = ($lastOutin['status'] === "Check In") ? "Check Out" : "Check In";
    }

    // ✅ Restrict checkout
    if ($status === "Check Out" && $driver['driver_status'] !== "Dispatch") {
        $response['status'] = "error";
        $response['message'] = "Driver is not dispatched. Cannot Check Out.";
        echo json_encode($response);
        exit;
    }

    // Insert to outin
    $stmt = $conn->prepare("INSERT INTO outin (drivers_name, truck_name, trailer_name, genset_name, segment_trip, date, time, status) 
                            VALUES (?, ?, ?, ?, ?, CURDATE(), CURTIME(), ?)");
    $truck = $dispatch ? $dispatch['d_truck'] : "";
    $trailer = $dispatch ? $dispatch['d_trailer'] : "";
    $genset = $dispatch ? $dispatch['d_genset'] : "";
    $stmt->bind_param("ssssss", $driverNameFormatted, $truck, $trailer, $genset, $tripSegment, $status);

    if ($stmt->execute()) {
        // Update statuses
        $newStatus = ($status === "Check Out") ? "On Trip" : "Good";

        $conn->query("UPDATE drivers SET driver_status = '$newStatus' WHERE driver_id = {$driver['driver_id']}");

        if (!empty($truck)) {
            $stmt = $conn->prepare("UPDATE units SET unit_status = ? WHERE unit_name = ?");
            $stmt->bind_param("ss", $newStatus, $truck);
            $stmt->execute();
        }

        if (!empty($genset)) {
            $stmt = $conn->prepare("UPDATE units SET unit_status = ? WHERE unit_name = ?");
            $stmt->bind_param("ss", $newStatus, $genset);
            $stmt->execute();
        }

        if (!empty($trailer)) {
            $stmt = $conn->prepare("UPDATE trailer SET trailer_status = ? WHERE trailer_name = ?");
            $stmt->bind_param("ss", $newStatus, $trailer);
            $stmt->execute();
        }

        $response['status'] = "success";
        $response['message'] = "$status successful for $driverNameFormatted.";
        $response['driver_name'] = $driverNameFormatted;
        $response['truck'] = $truck;
        $response['trailer'] = $trailer;
        $response['genset'] = $genset;
        $response['segment'] = $tripSegment;
        $response['profile_image'] = !empty($driver['driver_image']) ? $driver['driver_image'] : 'assets/images/profile/user-7.jpg';
    } else {
        $response['message'] = "Failed to insert into outin table.";
    }

    echo json_encode($response);
}
?>
