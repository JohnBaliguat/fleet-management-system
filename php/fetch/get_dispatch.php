<?php
include "../config/config.php";

if (isset($_POST['d_id'])) {
    $d_id = intval($_POST['d_id']);

    // Fetch dispatch
    $dispatchSql = "SELECT * FROM dispatch WHERE d_id = ?";
    $stmt = $conn->prepare($dispatchSql);
    $stmt->bind_param("i", $d_id);
    $stmt->execute();
    $dispatchResult = $stmt->get_result();
    $dispatch = $dispatchResult->fetch_assoc();

    // Fetch trips
    $tripSql = "SELECT * FROM trips WHERE d_id = ?";
    $stmt2 = $conn->prepare($tripSql);
    $stmt2->bind_param("i", $d_id);
    $stmt2->execute();
    $tripResult = $stmt2->get_result();

    $trip1 = null;
    $trip2 = null;

    while ($row = $tripResult->fetch_assoc()) {
        if ($row['trip_type'] == "Trip 1") {
            $trip1 = $row;
        } elseif ($row['trip_type'] == "Trip 2") {
            $trip2 = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'dispatch' => $dispatch,
        'trip1' => $trip1,
        'trip2' => $trip2
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Missing d_id']);
}
?>
