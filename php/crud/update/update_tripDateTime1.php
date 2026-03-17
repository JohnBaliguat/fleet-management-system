<?php
include "../../config/config.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $trip_id      = $_POST['trip_id'] ?? null;
    $container    = $_POST['container_no'] ?? null;
    $arrival_cy   = $_POST['arrival_cy'] ?? null;
    $departure    = $_POST['departure'] ?? null;
    $arrival_ph   = $_POST['arrival_ph'] ?? null;
    $action       = $_POST['action'] ?? null;

    if (!$trip_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing trip ID']);
        exit;
    }

    /* ---------------------------------------
       1. BUILD UPDATE QUERY DYNAMICALLY
    --------------------------------------- */
    $sql = "UPDATE trips SET 
              trip_arrivalDateTime = ?, 
              trip_departureDateTime = ?, 
              trip_pharrivalDateTime = ?";

    $params = [$arrival_cy, $departure, $arrival_ph];
    $types  = "sss";

    // only update container if provided
    if (!empty($container)) {
        $sql .= ", trip_container = ?";
        $params[] = $container;
        $types .= "s";
    }

    if ($action === 'done') {
        $sql .= ", trip_status = 'Done'";
    }

    $sql .= " WHERE trip_id = ?";
    $params[] = $trip_id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update trip']);
        exit;
    }
    $stmt->close();

    /* ---------------------------------------
       2. IF DONE → UPDATE DRIVER & UNIT
    --------------------------------------- */
    if ($action === 'done') {

        $getDispatch = $conn->prepare("
            SELECT d.driver_id, d.d_truck 
            FROM dispatch d
            INNER JOIN trips t ON t.d_id = d.d_id
            WHERE t.trip_id = ?
        ");
        $getDispatch->bind_param("i", $trip_id);
        $getDispatch->execute();
        $result = $getDispatch->get_result();

        if ($row = $result->fetch_assoc()) {

            // update driver
            $updateDriver = $conn->prepare("
                UPDATE drivers 
                SET driver_status = 'Good' 
                WHERE driver_id = ?
            ");
            $updateDriver->bind_param("i", $row['driver_id']);
            $updateDriver->execute();
            $updateDriver->close();

            // update unit
            $updateUnit = $conn->prepare("
                UPDATE units 
                SET unit_assign = '', 
                    driver_id = NULL, 
                    unit_assignGenset = '', 
                    unit_assignTrailer = '', 
                    unit_status = 'Good'
                WHERE unit_name = ?
            ");
            $updateUnit->bind_param("s", $row['d_truck']);
            $updateUnit->execute();
            $updateUnit->close();
        }

        $getDispatch->close();
    }

    $conn->close();
    echo json_encode(['status' => 'success', 'message' => 'Trip updated successfully']);
}
?>
