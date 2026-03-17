<?php
include "../../config/config.php"; // your DB connection

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_id    = $_POST['trip_id'] ?? null;
    $arrival_cy = $_POST['arrival_cy'] ?? null;
    $departure  = $_POST['departure'] ?? null;
    $arrival_ph = $_POST['arrival_ph'] ?? null;
    $action     = $_POST['action'] ?? null;

    if (!$trip_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing trip ID']);
        exit;
    }

    // --- 1. Update the trip record ---
    $sql = "UPDATE trips 
            SET trip_arrivalDateTime = ?, 
                trip_departureDateTime = ?, 
                trip_pharrivalDateTime = ?";

    $params = [$arrival_cy, $departure, $arrival_ph];

    if ($action === 'done') {
        $sql .= ", trip_status = 'Done'";
    }

    $sql .= " WHERE trip_id = ?";
    $params[] = $trip_id;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $types = str_repeat("s", count($params) - 1) . "i"; // trip_id is int
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update trip']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // --- 2. If trip is Done, also update drivers & units ---
    if ($action === 'done') {
        // get related driver_id and unit from dispatch table
        $getDispatch = $conn->prepare("SELECT d.driver_id, d.d_truck 
                                       FROM dispatch d 
                                       INNER JOIN trips t ON t.d_id = d.d_id 
                                       WHERE t.trip_id = ?");
        $getDispatch->bind_param("i", $trip_id);
        $getDispatch->execute();
        $result = $getDispatch->get_result();
        if ($row = $result->fetch_assoc()) {
            $driver_id = $row['driver_id'];
            $unit_name = $row['d_truck'];

            // update driver status
            $updateDriver = $conn->prepare("UPDATE drivers SET driver_status = 'Good' WHERE driver_id = ?");
            $updateDriver->bind_param("i", $driver_id);
            $updateDriver->execute();
            $updateDriver->close();

            // update unit status
            $updateUnit = $conn->prepare("UPDATE units 
                                          SET unit_assign = '', 
                                              driver_id = '', 
                                              unit_assignGenset = '', 
                                              unit_assignTrailer = '', 
                                              unit_status = 'Good' 
                                          WHERE unit_name = ?");
            $updateUnit->bind_param("s", $unit_name);
            $updateUnit->execute();
            $updateUnit->close();
        }
        $getDispatch->close();
    }

    $conn->close();
    echo json_encode(['status' => 'success', 'message' => 'Trip updated successfully']);
}
?>
